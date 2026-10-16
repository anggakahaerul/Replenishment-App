<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportReplenishmentRequest;
use App\Models\Import;
use App\Models\ReconciliationRun;
use App\Services\Replenishment\ReplenishmentImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ReplenishmentController extends Controller
{
    public function __construct(
        private readonly ReplenishmentImportService $importService
    ) {}

    /**
     * Display the main Replenishment Matching / Import page.
     */
    public function index(): View
    {
        $latestRun = $this->importService->getLatestRun();
        $initialRunData = $latestRun ? $this->importService->formatRunResponse($latestRun) : null;

        return view('replenishment.index', [
            'initialRunData' => $initialRunData,
        ]);
    }

    /**
     * Process both uploaded files: import to history and run matching.
     */
    public function store(ImportReplenishmentRequest $request): JsonResponse
    {
        try {
            // Step 1: Import Replenishment Report
            $reportImport = $this->importService->importReport(
                $request->file('report_file')
            );

            // Step 2: Import Analisa Replenishment
            $analysisImport = $this->importService->importAnalysis(
                $request->file('analysis_file')
            );

            // Step 3: Run matching/reconciliation
            $matchingResult = $this->importService->runMatching($reportImport, $analysisImport);

            return response()->json($matchingResult);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the Replenishment Report History page.
     */
    public function reportHistory(): View
    {
        $reports = Import::where('import_type', 'replenishment_report')
            ->withCount('replenishmentDetails')
            ->latest('imported_at')
            ->latest('id')
            ->paginate(15);

        return view('replenishment.report-history', compact('reports'));
    }

    /**
     * Display the Analysis Replenishment History page.
     */
    public function analysisHistory(): View
    {
        $analyses = Import::where('import_type', 'replenishment_analysis')
            ->withCount('analysisDetails')
            ->latest('imported_at')
            ->latest('id')
            ->paginate(15);

        return view('replenishment.analysis-history', compact('analyses'));
    }

    /**
     * Display detail rows of a specific import.
     */
    public function showHistory(Import $import): View
    {
        if ($import->import_type === 'replenishment_report') {
            $details = $import->replenishmentDetails()->orderBy('row_number')->paginate(30);
            return view('replenishment.report-history-detail', compact('import', 'details'));
        }

        $details = $import->analysisDetails()->orderBy('row_number')->paginate(30);
        return view('replenishment.analysis-history-detail', compact('import', 'details'));
    }
}
