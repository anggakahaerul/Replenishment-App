<?php

namespace App\Services\Replenishment;

use App\Models\Import;
use App\Models\Location;
use App\Models\ReconciliationRun;
use App\Models\ReplenishmentAnalysisDetail;
use App\Models\ReplenishmentDetail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ReplenishmentImportService
{
    public function __construct(
        private readonly ExcelParserService $parserService,
        private readonly ReplenishmentReconciliationService $reconciliationService
    ) {}

    /**
     * Import Replenishment Report file.
     */
    public function importReport(UploadedFile $file): Import
    {
        $hash = md5_file($file->getRealPath());
        $parsed = $this->parserService->parseReplenishmentReport($file->getRealPath());

        return DB::transaction(function () use ($file, $hash, $parsed) {
            $import = Import::create([
                'import_type' => 'replenishment_report',
                'file_name' => $file->getClientOriginalName(),
                'report_date' => $parsed['report_date'],
                'file_hash' => $hash,
                'status' => 'completed',
                'row_count' => count($parsed['rows']),
                'imported_at' => now(),
            ]);

            $details = [];
            foreach ($parsed['rows'] as $row) {
                // Find or create location in unified locations master
                $destLocation = Location::findOrCreateByCode($row['destination_location'], 'Pick Face');
                $sourceLocation = !empty($row['source_location'])
                    ? Location::findOrCreateByCode($row['source_location'], 'Pallet High')
                    : null;

                // Also ensure all pool locations exist in locations master
                if (!empty($row['suggested_sources'])) {
                    foreach ($row['suggested_sources'] as $poolLoc) {
                        Location::findOrCreateByCode($poolLoc, 'Pallet High');
                    }
                }

                $details[] = [
                    'import_id' => $import->id,
                    'item_no' => $row['item_no'],
                    'item_description' => $row['description'],
                    'source_location_id' => $sourceLocation?->id,
                    'destination_location_id' => $destLocation->id,
                    'source_location_code' => $row['source_location'],
                    'destination_location_code' => $row['destination_location'],
                    'suggested_sources' => !empty($row['suggested_sources']) ? json_encode($row['suggested_sources']) : null,
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit'] ?? 'CTN',
                    'available_qty' => $row['available_qty'],
                    'in_location_qty' => $row['in_location_qty'],
                    'safety_level' => $row['safety_level'],
                    'max_level' => $row['max_level'],
                    'row_number' => $row['row_number'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($details)) {
                // Chunk insert
                foreach (array_chunk($details, 200) as $chunk) {
                    ReplenishmentDetail::insert($chunk);
                }
            }

            return $import;
        });
    }

    /**
     * Import Analisa Replenishment file.
     */
    public function importAnalysis(UploadedFile $file): Import
    {
        $hash = md5_file($file->getRealPath());
        $parsed = $this->parserService->parseAnalisaReplenishment($file->getRealPath());

        return DB::transaction(function () use ($file, $hash, $parsed) {
            $import = Import::create([
                'import_type' => 'replenishment_analysis',
                'file_name' => $file->getClientOriginalName(),
                'report_date' => $parsed['report_date'],
                'file_hash' => $hash,
                'status' => 'completed',
                'row_count' => count($parsed['rows']),
                'imported_at' => now(),
            ]);

            $details = [];
            foreach ($parsed['rows'] as $row) {
                $destLocation = !empty($row['destination_location'])
                    ? Location::findOrCreateByCode($row['destination_location'], 'Pick Face')
                    : null;
                $sourceLocation = !empty($row['source_location'])
                    ? Location::findOrCreateByCode($row['source_location'], 'Pallet High')
                    : null;

                $details[] = [
                    'import_id' => $import->id,
                    'item_no' => $row['item_no'],
                    'item_description' => $row['description'],
                    'source_location_id' => $sourceLocation?->id,
                    'destination_location_id' => $destLocation?->id,
                    'source_location_code' => $row['source_location'],
                    'destination_location_code' => $row['destination_location'],
                    'whole_qty' => $row['whole_qty'],
                    'whole_uom' => $row['whole_uom'] ?? 'CTN',
                    'loose_qty' => $row['loose_qty'],
                    'loose_uom' => $row['loose_uom'],
                    'ratio' => $row['ratio'],
                    'storage' => $row['storage'],
                    'tag_validation' => $row['tag_validation'],
                    'tran_date' => $row['tran_date'],
                    'row_number' => $row['row_number'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($details)) {
                foreach (array_chunk($details, 200) as $chunk) {
                    ReplenishmentAnalysisDetail::insert($chunk);
                }
            }

            return $import;
        });
    }

    /**
     * Run matching between report and analysis imports and return JSON payload.
     */
    public function runMatching(Import $reportImport, Import $analysisImport): array
    {
        $run = $this->reconciliationService->reconcile($reportImport, $analysisImport);
        return $this->formatRunResponse($run);
    }

    /**
     * Get the latest reconciliation run, if any.
     */
    public function getLatestRun(): ?ReconciliationRun
    {
        return ReconciliationRun::with(['reportImport', 'analysisImport'])
            ->where('status', 'completed')
            ->latest('id')
            ->first();
    }

    /**
     * Format reconciliation run for frontend consumption.
     */
    public function formatRunResponse(ReconciliationRun $run): array
    {
        $results = $run->results()->get()->map(function ($r, $idx) {
            return [
                'no' => $idx + 1,
                'sku' => $r->sku,
                'nama_produk' => $r->nama_produk,
                'from_location_report' => $r->from_location_report,
                'from_location_report_type' => $r->from_location_report_type,
                'from_location_analysis' => $r->from_location_analysis,
                'from_location_analysis_type' => $r->from_location_analysis_type,
                'to_location' => $r->to_location,
                'to_location_type' => $r->to_location_type,
                'qty_replenish' => (float)$r->qty_replenish,
                'qty_replenish_unit' => $r->qty_replenish_unit,
                'qty_analysis' => (float)$r->qty_analysis,
                'qty_analysis_unit' => $r->qty_analysis_unit,
                'difference' => (float)$r->difference,
                'status' => $r->status,
                'notes' => $r->notes,
            ];
        })->toArray();

        $summary = $run->summary ?? [];

        return [
            'success' => true,
            'run_id' => $run->id,
            'summary' => $summary,
            'results' => $results,
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 1,
                'per_page' => count($results),
                'total' => count($results),
            ],
        ];
    }
}
