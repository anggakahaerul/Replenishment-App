<?php

namespace App\Services\Replenishment;

use App\Models\Import;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Orchestrator for the Replenishment Matching pipeline.
 *
 * Flow per upload:
 *  1. Parse both files in-memory (no DB writes yet)
 *  2. Validate paired-upload rules
 *  3. Detect shifts & filter analysis rows
 *  4. Run MatchingEngine for DRY and FROZEN separately
 *  5. Snapshot: delete old match results for same (date, shift, DRY) and (date, shift, FROZEN)
 *  6. Bulk insert match results
 *  7. Recalculate daily summary for the affected date
 *  8. Persist import metadata records
 *
 * Everything runs inside a single DB::transaction().
 */
class ReplenishmentImportService
{
    public function __construct(
        private readonly ExcelParserService $parserService,
        private readonly ShiftService $shiftService,
        private readonly CategoryService $categoryService,
        private readonly MatchingEngineService $matchingEngine
    ) {}

    /**
     * Process both uploaded files and return the matching result summary.
     *
     * @throws \RuntimeException on validation failure
     */
    public function processUpload(UploadedFile $reportFile, UploadedFile $analysisFile, ?int $branchId = null): array
    {
        $startTime = microtime(true);

        if (!$reportFile->isValid()) {
            throw new \RuntimeException('Upload file Replenishment Report gagal: ' . $reportFile->getErrorMessage());
        }
        if (!$analysisFile->isValid()) {
            throw new \RuntimeException('Upload file Analysis Replenishment gagal: ' . $analysisFile->getErrorMessage());
        }

        $reportPath   = $reportFile->getRealPath() ?: $reportFile->getPathname();
        $analysisPath = $analysisFile->getRealPath() ?: $analysisFile->getPathname();

        // ── Parse in-memory ──────────────────────────────────────────────────
        $reportParsed   = $this->parserService->parseReportInMemory($reportPath);
        $analysisParsed = $this->parserService->parseAnalysisInMemory($analysisPath);

        $reportBinary   = $reportFile->getContent();
        $analysisBinary = $analysisFile->getContent();

        // ── Validate & detect metadata ────────────────────────────────────────
        $reportMeta   = $reportParsed['metadata'];
        $analysisMeta = $analysisParsed['metadata'];
        $reportTasks  = $reportParsed['tasks'];
        $reportItems  = $reportParsed['items'] ?? [];
        $analysisRows = $analysisParsed['rows'];

        [$printedAt, $site, $printedBy, $batchId] = [
            $reportMeta['printedAt'],
            $reportMeta['site'],
            $reportMeta['printedBy'],
            $reportMeta['batchId'],
        ];

        $tranDate = $analysisMeta['tran_date'];
        $warehouse = $analysisMeta['warehouse'];

        // 1. Validate: non-empty & correct file types (not swapped)
        if (empty($reportTasks) && empty($reportItems)) {
            throw new \RuntimeException(
                'File Replenishment Report kosong atau format kolom tidak dikenali. Pastikan Anda tidak salah menukar posisi file Report dan Analisa.'
            );
        }

        if (empty($analysisRows)) {
            throw new \RuntimeException(
                'File Analisa Replenishment kosong atau format kolom tidak dikenali. Pastikan Anda mengunggah file Analisa pada kolom kedua.'
            );
        }

        // 2. Validate: single tran_date in analysis
        $uniqueTranDates = array_unique(array_filter(array_column($analysisRows, 'tran_date')));
        if (count($uniqueTranDates) > 1) {
            throw new \RuntimeException(
                'File Analisa mengandung lebih dari 1 tanggal transaksi (' . implode(', ', $uniqueTranDates) . '). Filter file Excel Analisa untuk 1 tanggal saja.'
            );
        }

        // 3. Validate: printed_at date must match tran_date
        $reportDate = $printedAt ? $printedAt->format('Y-m-d') : null;
        if ($reportDate && $tranDate && $reportDate !== $tranDate) {
            throw new \RuntimeException(
                "Tanggal tidak cocok: File Report dicetak tanggal {$reportDate}, sedangkan File Analisa bertanggal {$tranDate}. Pastikan kedua file berasal dari tanggal operasional yang sama."
            );
        }

        // 4. Validate: site must match warehouse (normalized alphanumeric comparison)
        $normSite = $site ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $site)) : '';
        $normWh   = $warehouse ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $warehouse)) : '';
        if ($normSite !== '' && $normWh !== '' && $normSite !== $normWh) {
            throw new \RuntimeException(
                "Cabang/Site tidak cocok antara kedua file: Report tertulis '{$site}', sedangkan Analisa tertulis '{$warehouse}'."
            );
        }

        // 5. Auto-resolve or validate Branch against file Site/Warehouse
        $fileBranchCode = $site ?: $warehouse;
        $normFileBranch = $fileBranchCode ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fileBranchCode)) : '';
        if ($branchId) {
            $selectedBranch = \App\Models\Branch::find($branchId);
            if ($selectedBranch && $normFileBranch !== '') {
                $normSelectedCode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $selectedBranch->code));
                $normSelectedName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $selectedBranch->name));
                if ($normFileBranch !== $normSelectedCode && $normFileBranch !== $normSelectedName && !str_contains($normSelectedName, $normFileBranch)) {
                    throw new \RuntimeException(
                        "File yang diunggah adalah milik site '{$fileBranchCode}', tidak sesuai dengan cabang '{$selectedBranch->code}' yang sedang dipilih. Silakan ubah pilihan cabang di pojok kanan atas atau unggah file cabang '{$selectedBranch->code}'."
                    );
                }
            }
        } elseif ($normFileBranch !== '') {
            $matchedBranch = \App\Models\Branch::where('is_active', true)->get()->first(function ($b) use ($normFileBranch) {
                $c = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $b->code));
                $n = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $b->name));
                return $c === $normFileBranch || $n === $normFileBranch;
            });
            if ($matchedBranch) {
                $branchId = $matchedBranch->id;
            }
        }

        $resolvedDate = $reportDate ?? $tranDate ?? now('Asia/Jakarta')->format('Y-m-d');

        // ── Detect Report Shift ───────────────────────────────────────────────
        $reportShift = $printedAt
            ? $this->shiftService->detectReportShift($printedAt, $branchId)
            : 'SHIFT 1';

        // 6. Validate: Prevent duplicate upload for the same (Branch, Date, Shift) or exact same file hash
        $reportHashIncoming   = md5($reportBinary);
        $analysisHashIncoming = md5($analysisBinary);

        $existingReportForShift = Import::where('import_type', 'replenishment_report')
            ->where('report_date', $resolvedDate)
            ->where('report_shift', $reportShift)
            ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
            ->latest('id')
            ->first();

        if ($existingReportForShift) {
            $importedAtStr = $existingReportForShift->imported_at
                ? $existingReportForShift->imported_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB'
                : 'sebelumnya';
            $branchLabel = $existingReportForShift->site ?: ($branchId ? (\App\Models\Branch::find($branchId)?->code ?? '') : '');
            $branchInfo  = $branchLabel ? " cabang {$branchLabel}" : '';

            throw new \RuntimeException(
                "Data {$reportShift} tanggal {$resolvedDate}{$branchInfo} sudah pernah di-import pada {$importedAtStr} (File: {$existingReportForShift->file_name}). Sistem menolak upload ulang agar data tidak duplikat."
            );
        }

        $duplicateReportHash = Import::where('import_type', 'replenishment_report')
            ->where('file_hash', $reportHashIncoming)
            ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId))
            ->first();

        if ($duplicateReportHash) {
            throw new \RuntimeException(
                "File Replenishment Report '{$reportFile->getClientOriginalName()}' persis sama dengan file yang sudah pernah di-import sebelumnya ({$duplicateReportHash->report_date} • {$duplicateReportHash->report_shift})."
            );
        }

        // ── Filter analysis rows by shift & cut-off ───────────────────────────
        $filteredRows = array_filter($analysisRows, function (array $row) use ($reportShift, $branchId) {
            $createdAt = $row['created_at']; // Carbon|null
            if (!$createdAt) {
                return false;
            }
            return $this->shiftService->isAnalysisMatchingShift($createdAt, $reportShift, $branchId);
        });
        $filteredRows = array_values($filteredRows);

        // 6. Validate: if analysis has rows with timestamps, but 0 rows match the detected Report Shift
        $rowsWithTimestamp = array_filter($analysisRows, fn($r) => !empty($r['created_at']));
        if (count($rowsWithTimestamp) > 0 && count($filteredRows) === 0) {
            $printedTimeStr = $printedAt ? $printedAt->format('H:i') : '-';
            throw new \RuntimeException(
                "Tidak ada baris transaksi Analisa yang masuk ke jadwal {$reportShift} (Report dicetak pukul {$printedTimeStr}). Kemungkinan Anda mengunggah file Report {$reportShift} bersama file Analisa dari shift lain, atau jadwal Jam Kerja & Cut-Off Shift cabang ini perlu disesuaikan di menu Master Data Shift."
            );
        }

        // ── Separate by category ──────────────────────────────────────────────
        $dryAnalysis    = [];
        $frozenAnalysis = [];

        foreach ($filteredRows as $row) {
            $cat = $this->categoryService->detectCategory(
                $row['to_location'],
                $row['from_location'],
                $row['storage']
            );
            if ($cat === 'FROZEN') {
                $frozenAnalysis[] = $row;
            } else {
                $dryAnalysis[] = $row;
            }
        }

        $dryTasks    = $this->filterTasksByCategory($reportTasks, 'DRY');
        $frozenTasks = $this->filterTasksByCategory($reportTasks, 'FROZEN');

        // ── Run Matching Engine ───────────────────────────────────────────────
        $dryResults    = $this->matchingEngine->run(
            $dryTasks, $dryAnalysis, $resolvedDate, $reportShift, $batchId, 'DRY'
        );
        $frozenResults = $this->matchingEngine->run(
            $frozenTasks, $frozenAnalysis, $resolvedDate, $reportShift, $batchId, 'FROZEN'
        );

        $allResults = array_merge($dryResults, $frozenResults);
        foreach ($allResults as &$resRow) {
            $resRow['branch_id'] = $branchId;
        }
        unset($resRow);

        // ── Persist ───────────────────────────────────────────────────────────
        return DB::transaction(function () use (
            $reportFile,
            $analysisFile,
            $branchId,
            $reportMeta,
            $analysisMeta,
            $reportTasks,
            $reportItems,
            $analysisRows,
            $filteredRows,
            $allResults,
            $resolvedDate,
            $reportShift,
            $batchId,
            $site,
            $warehouse,
            $printedAt,
            $printedBy,
            $tranDate,
            $startTime
        ) {
            // Check if this (branch, date, shift) was already imported previously
            $wasReplaced = DB::table('replenishment_match_results')
                ->where('report_date', $resolvedDate)
                ->where('report_shift', $reportShift)
                ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
                ->exists();

            // Delete old snapshot for same (branch, date, shift)
            DB::table('replenishment_match_results')
                ->where('report_date', $resolvedDate)
                ->where('report_shift', $reportShift)
                ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
                ->delete();

            // Bulk insert match results
            foreach (array_chunk($allResults, 500) as $chunk) {
                DB::table('replenishment_match_results')->insert($chunk);
            }

            // Recalculate daily summary
            $this->recalculateDailySummary($resolvedDate, $branchId);

            // Store Excel binaries directly in database (no local directory storage)
            $reportExt = strtolower($reportFile->getClientOriginalExtension() ?: 'xlsx');
            $analysisExt = strtolower($analysisFile->getClientOriginalExtension() ?: 'xlsx');

            $standardizedReportName = "Replenish Report - {$resolvedDate} - {$reportShift}.{$reportExt}";
            $standardizedAnalysisName = "Analisa Report - {$resolvedDate} - {$reportShift}.{$analysisExt}";

            $reportHash = $reportHashIncoming;
            $analysisHash = $analysisHashIncoming;

            $defaultMime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

            // Persist report import metadata & binary file
            $reportRowCount = count($reportItems) > 0 ? count($reportItems) : count($reportTasks);
            $reportImport = Import::create([
                'branch_id'   => $branchId,
                'import_type' => 'replenishment_report',
                'batch_id'    => $batchId,
                'file_name'   => $standardizedReportName,
                'file_path'   => null,
                'file_hash'   => $reportHash,
                'report_date' => $resolvedDate,
                'report_shift'=> $reportShift,
                'site'        => $reportMeta['site'],
                'printed_by'  => $reportMeta['printedBy'],
                'printed_at'  => $reportMeta['printedAt'],
                'status'      => 'completed',
                'row_count'   => $reportRowCount,
                'imported_at' => now(),
            ]);

            $reportImport->storedFile()->create([
                'mime_type'    => $reportFile->getMimeType() ?: $defaultMime,
                'file_size'    => strlen($reportBinary),
                'file_content' => $reportBinary,
            ]);

            // Persist analysis import metadata & binary file
            $analysisImport = Import::create([
                'branch_id'   => $branchId,
                'import_type' => 'replenishment_analysis',
                'batch_id'    => $batchId,
                'file_name'   => $standardizedAnalysisName,
                'file_path'   => null,
                'file_hash'   => $analysisHash,
                'report_date' => $resolvedDate,
                'report_shift'=> $reportShift,
                'tran_date'   => $tranDate,
                'warehouse'   => $analysisMeta['warehouse'],
                'status'      => 'completed',
                'row_count'   => count($analysisRows),
                'imported_at' => now(),
            ]);

            $analysisImport->storedFile()->create([
                'mime_type'    => $analysisFile->getMimeType() ?: $defaultMime,
                'file_size'    => strlen($analysisBinary),
                'file_content' => $analysisBinary,
            ]);

            // Persist detailed rows for report history
            $now = now();
            if (!empty($reportItems)) {
                foreach (array_chunk($reportItems, 500) as $chunk) {
                    $insertData = array_map(function ($row) use ($reportImport, $now) {
                        return [
                            'import_id'                 => $reportImport->id,
                            'item_no'                   => $row['item_no'],
                            'item_description'          => $row['item_description'] ?? null,
                            'source_location_code'      => $row['source_location_code'] ?? null,
                            'destination_location_code' => $row['destination_location_code'] ?? null,
                            'suggested_sources'         => !empty($row['suggested_sources']) ? json_encode($row['suggested_sources']) : null,
                            'quantity'                  => $row['quantity'] ?? 0,
                            'unit'                      => $row['unit'] ?? 'CTN',
                            'available_qty'             => $row['available_qty'] ?? null,
                            'in_location_qty'           => $row['in_location_qty'] ?? null,
                            'safety_level'              => $row['safety_level'] ?? null,
                            'max_level'                 => $row['max_level'] ?? null,
                            'row_number'                => $row['row_number'] ?? 0,
                            'created_at'                => $now,
                            'updated_at'                => $now,
                        ];
                    }, $chunk);
                    DB::table('replenishment_details')->insert($insertData);
                }
            }

            // Persist detailed rows for analysis history
            if (!empty($analysisRows)) {
                foreach (array_chunk($analysisRows, 500) as $chunk) {
                    $insertData = array_map(function ($row) use ($analysisImport, $now) {
                        return [
                            'import_id'                 => $analysisImport->id,
                            'item_no'                   => $row['item_no'],
                            'item_description'          => $row['item_description'] ?? null,
                            'source_location_code'      => $row['from_location'] ?? null,
                            'destination_location_code' => $row['to_location'] ?? null,
                            'whole_qty'                 => $row['whole_qty'] ?? 0,
                            'whole_uom'                 => $row['whole_uom'] ?? 'CTN',
                            'loose_qty'                 => $row['loose_qty'] ?? 0,
                            'loose_uom'                 => $row['loose_uom'] ?? 'PCS',
                            'ratio'                     => $row['ratio'] ?? 0,
                            'storage'                   => $row['storage'] ?? null,
                            'tag_validation'            => $row['tag_validation'] ?? null,
                            'tran_date'                 => $row['tran_date'] ?? null,
                            'row_number'                => $row['row_number'] ?? 0,
                            'created_at'                => $now,
                            'updated_at'                => $now,
                        ];
                    }, $chunk);
                    DB::table('replenishment_analysis_details')->insert($insertData);
                }
            }

            // Compute summary to return to frontend
            $duration = round(microtime(true) - $startTime, 2);
            $summary = $this->buildSummary($allResults, $resolvedDate, $reportShift, $batchId, $duration);

            // Map rows for the results table in frontend
            $mappedResults = array_map(function ($row, $idx) {
                return [
                    'no' => $idx + 1,
                    'sku' => $row['item_no'],
                    'nama_produk' => $row['item_description'] ?? '-',
                    'from_location_report' => $row['from_location'] ?? '-',
                    'from_location_report_type' => $row['record_type'] === 'REPORT_TASK' ? 'Report' : '',
                    'from_location_analysis' => $row['record_type'] === 'ANALYSIS' ? ($row['from_location'] ?? '-') : '-',
                    'from_location_analysis_type' => $row['record_type'] === 'ANALYSIS' ? 'Analisa' : '',
                    'to_location' => $row['to_location'] ?? '-',
                    'to_location_type' => $row['category'] ?? '',
                    'qty_replenish' => $row['qty_report'] ?? '-',
                    'qty_replenish_unit' => $row['qty_report'] !== null ? 'CTN' : '',
                    'qty_analysis' => $row['qty_analysis'] ?? '-',
                    'qty_analysis_unit' => $row['qty_analysis'] !== null ? 'CTN' : '',
                    'status' => $row['status'],
                    'category' => $row['category'],
                    'record_type' => $row['record_type'],
                ];
            }, $allResults, array_keys($allResults));

            return [
                'success'            => true,
                'was_replaced'       => $wasReplaced,
                'batch_id'           => $batchId,
                'branch_id'          => $branchId,
                'report_date'        => $resolvedDate,
                'report_shift'       => $reportShift,
                'report_import_id'   => $reportImport->id,
                'analysis_import_id' => $analysisImport->id,
                'report_file_name'   => $standardizedReportName,
                'analysis_file_name' => $standardizedAnalysisName,
                'summary'            => $summary,
                'results'          => $mappedResults,
                'pagination'       => [
                    'total'        => count($allResults),
                    'per_page'     => count($allResults),
                    'current_page' => 1,
                    'total_pages'  => 1,
                ],
            ];
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DAILY SUMMARY RECALCULATION
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Recalculate and upsert the daily summary for a given report_date and branch_id.
     * Processes both DRY and FROZEN categories.
     */
    public function recalculateDailySummary(string $reportDate, ?int $branchId = null): void
    {
        $branchScope = fn($q) => $branchId !== null ? $q->where('branch_id', $branchId) : $q->whereNull('branch_id');

        $hasAnyRows = DB::table('replenishment_match_results')
            ->tap($branchScope)
            ->where('report_date', $reportDate)
            ->exists();

        if (!$hasAnyRows) {
            DB::table('replenishment_daily_summaries')
                ->tap($branchScope)
                ->where('report_date', $reportDate)
                ->delete();
            return;
        }

        foreach (['DRY', 'FROZEN'] as $category) {
            $this->recalculateCategorySummary($reportDate, $category, $branchId);
        }
    }

    private function recalculateCategorySummary(string $reportDate, string $category, ?int $branchId = null): void
    {
        $branchScope = fn($q) => $branchId !== null ? $q->where('branch_id', $branchId) : $q->whereNull('branch_id');

        // Fetch all task rows for this branch+date+category (both shifts)
        $rows = DB::table('replenishment_match_results')
            ->tap($branchScope)
            ->where('report_date', $reportDate)
            ->where('category', $category)
            ->where('counts_as_task', true)
            ->whereIn('report_shift', ['SHIFT 1', 'SHIFT 2'])
            ->select('report_shift', 'task_key', 'task_done')
            ->get();

        // Per-shift counts (counts_as_task = true)
        $shift1Total = 0;
        $shift1Done  = 0;
        $shift2Total = 0;
        $shift2Done  = 0;

        // For per-shift unique task_key tracking
        $shift1Keys = [];
        $shift2Keys = [];

        foreach ($rows as $row) {
            if ($row->report_shift === 'SHIFT 1') {
                $shift1Keys[$row->task_key ?? uniqid()] = $row->task_done;
            } else {
                $shift2Keys[$row->task_key ?? uniqid()] = $row->task_done;
            }
        }

        foreach ($shift1Keys as $isDone) {
            $shift1Total++;
            if ($isDone) {
                $shift1Done++;
            }
        }

        foreach ($shift2Keys as $isDone) {
            $shift2Total++;
            if ($isDone) {
                $shift2Done++;
            }
        }

        // Check which shifts actually have data
        $shift1HasData = DB::table('replenishment_match_results')
            ->tap($branchScope)
            ->where('report_date', $reportDate)
            ->where('category', $category)
            ->where('report_shift', 'SHIFT 1')
            ->exists();

        $shift2HasData = DB::table('replenishment_match_results')
            ->tap($branchScope)
            ->where('report_date', $reportDate)
            ->where('category', $category)
            ->where('report_shift', 'SHIFT 2')
            ->exists();

        $shift1Completion = $shift1HasData
            ? ($shift1Total > 0 ? round(($shift1Done / $shift1Total) * 100, 2) : 0.0)
            : null;

        $shift2Completion = $shift2HasData
            ? ($shift2Total > 0 ? round(($shift2Done / $shift2Total) * 100, 2) : 0.0)
            : null;

        // Cross-shift reconciliation: unique task_key states
        // A task_key done in any shift counts as done overall
        $taskStates = [];
        foreach ($rows as $row) {
            $tk = $row->task_key ?? ('__null_' . uniqid());
            if (!isset($taskStates[$tk])) {
                $taskStates[$tk] = false;
            }
            if ($row->task_done) {
                $taskStates[$tk] = true;
            }
        }

        $totalTasks  = count($taskStates);
        $matchCount  = count(array_filter($taskStates));
        $pendingCount = max(0, $totalTasks - $matchCount);
        $matchPct    = $totalTasks > 0 ? round(($matchCount / $totalTasks) * 100, 1) : 0.0;

        // Activity counters (from ANALYSIS rows only, not counts_as_task filtered)
        $activityCounts = DB::table('replenishment_match_results')
            ->tap($branchScope)
            ->where('report_date', $reportDate)
            ->where('category', $category)
            ->where('record_type', 'ANALYSIS')
            ->whereIn('report_shift', ['SHIFT 1', 'SHIFT 2'])
            ->selectRaw("
                SUM(CASE WHEN status = 'INITIATIVE'   THEN 1 ELSE 0 END) as initiative_count,
                SUM(CASE WHEN status = 'PUT AWAY'     THEN 1 ELSE 0 END) as put_away_count,
                SUM(CASE WHEN status = 'TRANSFER'     THEN 1 ELSE 0 END) as transfer_count,
                SUM(CASE WHEN status = 'DUPLICATE RAW'THEN 1 ELSE 0 END) as duplicate_raw_count
            ")
            ->first();

        $surplusPocCount = DB::table('replenishment_match_results')
            ->tap($branchScope)
            ->where('report_date', $reportDate)
            ->where('category', $category)
            ->where('record_type', 'REPORT_TASK')
            ->where('status', 'MELEBIHI POC')
            ->whereIn('report_shift', ['SHIFT 1', 'SHIFT 2'])
            ->count();

        $payload = [
            'branch_id'           => $branchId,
            'report_date'         => $reportDate,
            'category'            => $category,
            'shift_1_total'       => $shift1Total,
            'shift_1_done'        => $shift1Done,
            'shift_1_completion'  => $shift1Completion,
            'shift_2_total'       => $shift2Total,
            'shift_2_done'        => $shift2Done,
            'shift_2_completion'  => $shift2Completion,
            'total_tasks'         => $totalTasks,
            'match_count'         => $matchCount,
            'pending_count'       => $pendingCount,
            'match_percentage'    => $matchPct,
            'initiative_count'    => (int) ($activityCounts->initiative_count ?? 0),
            'put_away_count'      => (int) ($activityCounts->put_away_count ?? 0),
            'transfer_count'      => (int) ($activityCounts->transfer_count ?? 0),
            'duplicate_raw_count' => (int) ($activityCounts->duplicate_raw_count ?? 0),
            'surplus_poc_count'   => $surplusPocCount,
            'updated_at'          => now(),
        ];

        $existing = DB::table('replenishment_daily_summaries')
            ->tap($branchScope)
            ->where('report_date', $reportDate)
            ->where('category', $category)
            ->first();

        if ($existing) {
            DB::table('replenishment_daily_summaries')
                ->where('id', $existing->id)
                ->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('replenishment_daily_summaries')->insert($payload);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Filter report tasks by category (DRY/FROZEN) based on location prefixes.
     */
    private function filterTasksByCategory(array $reportTasks, string $category): array
    {
        return array_values(array_filter($reportTasks, function (array $task) use ($category) {
            $detected = $this->categoryService->detectCategory(
                $task['to_location'],
                $task['from_location'],
                null
            );
            return $detected === $category;
        }));
    }

    /**
     * Build a summary array for frontend consumption.
     */
    private function buildSummary(
        array $allResults,
        string $reportDate,
        string $reportShift,
        string $batchId,
        float $duration = 0.0
    ): array {
        $totalCount = count($allResults);
        $summary = [
            'total'            => $totalCount,
            'total_rows'       => $totalCount,
            'matched_rows'     => 0,
            'duration_seconds' => $duration,
            'MATCH'            => 0,
            'PENDING'          => 0,
            'MELEBIHI POC'     => 0,
            'INITIATIVE'       => 0,
            'DUPLICATE RAW'    => 0,
            'PUT AWAY'         => 0,
            'TRANSFER'         => 0,
        ];

        foreach ($allResults as $row) {
            $status = $row['status'];
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        $summary['matched_rows']  = $summary['MATCH'];
        $summary['match_count']   = $summary['MATCH'];
        $summary['pending_count'] = $summary['PENDING'];
        $summary['report_date']   = $reportDate;
        $summary['report_shift']  = $reportShift;
        $summary['batch_id']      = $batchId;
        $summary['match_rate']    = $summary['total'] > 0
            ? round(($summary['MATCH'] / $summary['total']) * 100, 1)
            : 0;
        $summary['match_percentage'] = $summary['match_rate'];

        return $summary;
    }

    /**
     * Retrieve the latest daily summary for the dashboard.
     */
    public function getLatestDailySummary(?int $branchId = null): ?array
    {
        $latestDate = DB::table('replenishment_daily_summaries')
            ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('report_date')
            ->value('report_date');

        if (!$latestDate) {
            return null;
        }

        $rows = DB::table('replenishment_daily_summaries')
            ->where('report_date', $latestDate)
            ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->keyBy('category')
            ->toArray();

        return [
            'report_date' => $latestDate,
            'DRY'         => isset($rows['DRY'])    ? (array) $rows['DRY']    : null,
            'FROZEN'      => isset($rows['FROZEN'])  ? (array) $rows['FROZEN'] : null,
        ];
    }
}
