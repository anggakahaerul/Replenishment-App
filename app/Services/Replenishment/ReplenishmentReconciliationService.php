<?php

namespace App\Services\Replenishment;

use App\Models\Import;
use App\Models\ReconciliationResult;
use App\Models\ReconciliationRun;
use App\Models\ReplenishmentAnalysisDetail;
use App\Models\ReplenishmentDetail;
use Illuminate\Support\Collection;

class ReplenishmentReconciliationService
{
    /**
     * Whether to aggregate multiple transactions for the same canonical key.
     */
    protected bool $allowAggregation = true;

    public function setAllowAggregation(bool $allow): self
    {
        $this->allowAggregation = $allow;
        return $this;
    }

    /**
     * Execute reconciliation between a Replenishment Report import and an Analisa import.
     *
     * @param Import $reportImport
     * @param Import $analysisImport
     * @return ReconciliationRun
     */
    public function reconcile(Import $reportImport, Import $analysisImport): ReconciliationRun
    {
        $startTime = microtime(true);

        // Create run record
        $run = ReconciliationRun::create([
            'report_import_id' => $reportImport->id,
            'analysis_import_id' => $analysisImport->id,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        // Fetch details
        $reportDetails = ReplenishmentDetail::where('import_id', $reportImport->id)
            ->orderBy('row_number')
            ->get();

        $analysisDetails = ReplenishmentAnalysisDetail::where('import_id', $analysisImport->id)
            ->orderBy('row_number')
            ->get();

        // Build canonical matching groups
        // Key: item_no + destination_location_code (normalized)
        $groupedReport = $this->groupReportRecords($reportDetails);
        $groupedAnalysis = $this->groupAnalysisRecords($analysisDetails);

        $results = [];
        $matchedCount = 0;
        $unmatchedCount = 0;
        $overPocCount = 0;
        $duplicateCount = 0;
        $rowOrder = 1;

        $processedAnalysisKeys = [];

        // 1. Process Report Records against Analisa
        foreach ($groupedReport as $key => $reportGroup) {
            $analysisGroup = $groupedAnalysis[$key] ?? null;

            if ($analysisGroup !== null) {
                $processedAnalysisKeys[$key] = true;

                // Handle duplicates if aggregation not allowed
                if (!$this->allowAggregation && ($reportGroup['count'] > 1 || $analysisGroup['count'] > 1)) {
                    $duplicateCount++;
                    $results[] = $this->buildResultItem(
                        $run->id,
                        $reportGroup['first_detail'],
                        $analysisGroup['first_detail'],
                        $reportGroup['total_qty'],
                        $analysisGroup['total_qty'],
                        'duplicate_or_ambiguous',
                        'Ditemukan duplikasi transaksi pada key yang sama.',
                        $rowOrder++
                    );
                    continue;
                }

                $repQty = (float)$reportGroup['total_qty'];
                $anaQty = (float)$analysisGroup['total_qty'];
                $diff = round($repQty - $anaQty, 4);

                // Classify status
                if (abs($diff) < 0.0001) {
                    $status = 'matched';
                    $matchedCount++;
                } elseif ($anaQty > $repQty) {
                    $status = 'melebihi_poc';
                    $overPocCount++;
                } else {
                    $status = 'unmatch';
                    $unmatchedCount++;
                }

                $results[] = $this->buildResultItem(
                    $run->id,
                    $reportGroup['first_detail'],
                    $analysisGroup['first_detail'],
                    $repQty,
                    $anaQty,
                    $status,
                    $diff !== 0.0 ? "Selisih quantity: $diff CTN" : null,
                    $rowOrder++
                );
            } else {
                // In Report only
                $repQty = (float)$reportGroup['total_qty'];
                $unmatchedCount++;
                $results[] = $this->buildResultItem(
                    $run->id,
                    $reportGroup['first_detail'],
                    null,
                    $repQty,
                    0,
                    'unmatch',
                    'Item hanya tercatat pada Replenishment Report.',
                    $rowOrder++
                );
            }
        }

        // 2. Process remaining Analisa Records (not in Report)
        foreach ($groupedAnalysis as $key => $analysisGroup) {
            if (isset($processedAnalysisKeys[$key])) {
                continue;
            }

            $anaQty = (float)$analysisGroup['total_qty'];
            $unmatchedCount++;
            $results[] = $this->buildResultItem(
                $run->id,
                null,
                $analysisGroup['first_detail'],
                0,
                $anaQty,
                'unmatch',
                'Item hanya tercatat pada Analisa Replenishment.',
                $rowOrder++
            );
        }

        // Bulk insert results
        ReconciliationResult::insert($results);

        $duration = round(microtime(true) - $startTime, 2);
        $totalRows = count($results);
        $matchRate = $totalRows > 0 ? round(($matchedCount / $totalRows) * 100, 1) : 0;

        $summary = [
            'total_rows' => $totalRows,
            'matched_rows' => $matchedCount,
            'unmatched_rows' => $unmatchedCount,
            'over_poc_rows' => $overPocCount,
            'duplicate_rows' => $duplicateCount,
            'match_rate' => $matchRate,
            'duration_seconds' => $duration,
            'report_file' => $reportImport->file_name,
            'analysis_file' => $analysisImport->file_name,
            'report_date' => $reportImport->report_date ?? now()->format('d/m/Y'),
        ];

        $run->update([
            'status' => 'completed',
            'summary' => $summary,
            'completed_at' => now(),
        ]);

        return $run;
    }

    private function groupReportRecords(Collection $details): array
    {
        $grouped = [];
        foreach ($details as $detail) {
            $key = $this->makeCanonicalKey($detail->item_no, $detail->destination_location_code);
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'first_detail' => $detail,
                    'total_qty' => 0,
                    'count' => 0,
                ];
            }
            $grouped[$key]['total_qty'] += (float)$detail->quantity;
            $grouped[$key]['count']++;
        }
        return $grouped;
    }

    private function groupAnalysisRecords(Collection $details): array
    {
        $grouped = [];
        foreach ($details as $detail) {
            $key = $this->makeCanonicalKey($detail->item_no, $detail->destination_location_code);
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'first_detail' => $detail,
                    'total_qty' => 0,
                    'count' => 0,
                ];
            }
            $grouped[$key]['total_qty'] += (float)$detail->whole_qty;
            $grouped[$key]['count']++;
        }
        return $grouped;
    }

    private function makeCanonicalKey(string $itemNo, ?string $destLocation): string
    {
        $normalizedItem = trim(strtoupper($itemNo));
        $normalizedDest = trim(strtoupper((string)$destLocation));
        return $normalizedItem . '||' . $normalizedDest;
    }

    private function buildResultItem(
        int $runId,
        ?ReplenishmentDetail $repDetail,
        ?ReplenishmentAnalysisDetail $anaDetail,
        float $repQty,
        float $anaQty,
        string $status,
        ?string $notes,
        int $rowOrder
    ): array {
        $sku = $repDetail?->item_no ?? $anaDetail?->item_no ?? '-';
        $desc = $repDetail?->item_description ?? $anaDetail?->item_description ?? '-';
        $toLoc = $repDetail?->destination_location_code ?? $anaDetail?->destination_location_code ?? '-';

        $fromRep = $repDetail?->source_location_code;
        $fromAna = $anaDetail?->source_location_code;
        $pool = $repDetail?->suggested_sources;
        if (is_string($pool)) {
            $pool = json_decode($pool, true) ?: [];
        } elseif (!is_array($pool)) {
            $pool = [];
        }

        // If Analisa has a source location, check if it matches the report or its pool
        if ($fromAna && !empty($pool)) {
            if (in_array($fromAna, $pool)) {
                // The actual transfer location was chosen from the valid pool of suggested sources!
                $fromRep = $fromAna;
            } elseif (!$fromRep) {
                $fromRep = $pool[0];
            }
        } elseif (!$fromRep && !empty($pool)) {
            $fromRep = $pool[0];
        }

        $fromRep = $fromRep ?: ($fromAna ?: '-');
        $fromAna = $fromAna ?: ($fromRep ?: '-');

        return [
            'run_id' => $runId,
            'replenishment_detail_id' => $repDetail?->id,
            'analysis_detail_id' => $anaDetail?->id,
            'sku' => $sku,
            'nama_produk' => $desc,
            'from_location_report' => $fromRep,
            'from_location_report_type' => $this->inferLocationType($fromRep),
            'from_location_analysis' => $fromAna,
            'from_location_analysis_type' => $this->inferLocationType($fromAna),
            'to_location' => $toLoc,
            'to_location_type' => $this->inferLocationType($toLoc),
            'qty_replenish' => $repQty,
            'qty_replenish_unit' => $repDetail?->unit ?? 'CTN',
            'qty_analysis' => $anaQty,
            'qty_analysis_unit' => $anaDetail?->whole_uom ?? 'CTN',
            'difference' => round($repQty - $anaQty, 4),
            'status' => $status,
            'notes' => $notes,
            'row_order' => $rowOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function inferLocationType(?string $code): string
    {
        if (!$code || $code === '-') return '-';
        $upper = strtoupper($code);
        if (str_starts_with($upper, 'RCV')) return 'Receiving Dock';
        if (str_starts_with($upper, 'PF') || str_starts_with($upper, 'PK')) return 'Pick Face';
        if (str_starts_with($upper, 'D-FL') || str_contains($upper, 'FL')) return 'Floor Flow';
        if (str_starts_with($upper, 'D-') || str_starts_with($upper, 'A-') || str_starts_with($upper, 'B-')) return 'Pallet High';
        return 'Standard Location';
    }
}
