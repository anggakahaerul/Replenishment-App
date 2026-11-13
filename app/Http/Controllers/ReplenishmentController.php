<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportReplenishmentRequest;
use App\Models\Branch;
use App\Models\Import;
use App\Services\Replenishment\ReplenishmentImportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReplenishmentController extends Controller
{
    public function __construct(
        private readonly ReplenishmentImportService $importService
    ) {}

    /**
     * Resolve accessible branches for the current authenticated user.
     */
    private function getAccessibleBranches()
    {
        $user = auth()->user();
        $query = Branch::with('activeShifts')->where('is_active', true)->orderBy('code');

        if ($user && !$user->hasGlobalBranchAccess()) {
            $query->where('id', $user->branch_id);
        }

        return $query->get();
    }

    /**
     * Display the main Replenishment Matching / Import page.
     */
    public function index(): View
    {
        $user = auth()->user();
        $branches = $this->getAccessibleBranches();
        $scopedBranchId = ($user && !$user->hasGlobalBranchAccess()) ? $user->branch_id : null;

        $latestSummary = $this->importService->getLatestDailySummary($scopedBranchId);

        // Fetch History Matching per Tanggal & Shift
        $branchMap = Branch::pluck('code', 'id')->all();

        $allImports = Import::query()
            ->when($scopedBranchId !== null, fn($q) => $q->where('branch_id', $scopedBranchId))
            ->orderByDesc('id')
            ->get();

        $importLookup = [];
        foreach ($allImports as $imp) {
            $dKey = $imp->report_date ? Carbon::parse($imp->report_date)->format('Y-m-d') : '';
            $bKey = $imp->branch_id !== null ? (string) $imp->branch_id : 'null';
            $sKey = (string) ($imp->report_shift ?? '');
            $tKey = $imp->import_type;
            $fullKey = "{$bKey}|{$dKey}|{$sKey}|{$tKey}";
            if (!isset($importLookup[$fullKey])) {
                $importLookup[$fullKey] = $imp;
            }
            $fallbackKey = "any|{$dKey}|{$sKey}|{$tKey}";
            if (!isset($importLookup[$fallbackKey])) {
                $importLookup[$fallbackKey] = $imp;
            }
        }

        $matchBatchesRaw = DB::table('replenishment_match_results')
            ->when($scopedBranchId !== null, fn($q) => $q->where('branch_id', $scopedBranchId))
            ->selectRaw("
                branch_id,
                report_date,
                report_shift,
                COUNT(*) as total_rows,
                SUM(CASE WHEN counts_as_task = 1 THEN 1 ELSE 0 END) as total_tasks,
                SUM(CASE WHEN status = 'MATCH' THEN 1 ELSE 0 END) as match_count,
                SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'MELEBIHI POC' THEN 1 ELSE 0 END) as surplus_poc_count,
                MAX(created_at) as imported_at
            ")
            ->groupBy('branch_id', 'report_date', 'report_shift')
            ->orderByDesc('report_date')
            ->orderBy('report_shift')
            ->get();

        $shiftBatches = $matchBatchesRaw->map(function ($batch) use ($branchMap, $importLookup) {
            $dateStr = (string) $batch->report_date;
            $bKey = $batch->branch_id !== null ? (string) $batch->branch_id : 'null';
            $sKey = (string) ($batch->report_shift ?? '');

            $repImp = $importLookup["{$bKey}|{$dateStr}|{$sKey}|replenishment_report"]
                ?? $importLookup["any|{$dateStr}|{$sKey}|replenishment_report"]
                ?? null;

            $anaImp = $importLookup["{$bKey}|{$dateStr}|{$sKey}|replenishment_analysis"]
                ?? $importLookup["any|{$dateStr}|{$sKey}|replenishment_analysis"]
                ?? null;

            $totalTasks = (int) $batch->total_tasks;
            $matchCount = (int) $batch->match_count;
            $pendingCount = (int) $batch->pending_count;
            $surplusCount = (int) $batch->surplus_poc_count;
            $matchPctNum = $totalTasks > 0 ? round(($matchCount / $totalTasks) * 100, 1) : 0.0;
            $matchPct = number_format($matchPctNum, 1) . '%';

            $importedAtFormatted = '-';
            if ($repImp?->imported_at) {
                $importedAtFormatted = $repImp->imported_at->format('d/m/Y H:i') . ' WIB';
            } elseif ($batch->imported_at) {
                try {
                    $importedAtFormatted = Carbon::parse($batch->imported_at)->format('d/m/Y H:i') . ' WIB';
                } catch (\Throwable) {
                    $importedAtFormatted = (string) $batch->imported_at;
                }
            }

            return (object) [
                'branch_id'              => $batch->branch_id !== null ? (int) $batch->branch_id : null,
                'branch_code'            => $batch->branch_id ? ($branchMap[$batch->branch_id] ?? '-') : '-',
                'report_date'            => $dateStr,
                'report_shift'           => $batch->report_shift ?? '-',
                'total_rows'             => (int) $batch->total_rows,
                'total_tasks'            => $totalTasks,
                'match_count'            => $matchCount,
                'pending_count'          => $pendingCount,
                'surplus_poc_count'      => $surplusCount,
                'match_pct_num'          => $matchPctNum,
                'match_pct'              => $matchPct,
                'imported_at'            => $importedAtFormatted,
                'report_import_id'       => $repImp?->id,
                'report_file_name'       => $repImp?->file_name ?? ("Replenish Report - {$dateStr} - {$batch->report_shift}.xlsx"),
                'report_row_count'       => (int) ($repImp?->row_count ?? 0),
                'report_url'             => $repImp ? route('replenishment.history.show', $repImp->id) : route('replenishment.report-history'),
                'report_download_url'    => ($repImp && $repImp->file_path) ? route('replenishment.history.download', $repImp->id) : null,
                'analysis_import_id'     => $anaImp?->id,
                'analysis_file_name'     => $anaImp?->file_name ?? ("Analisa Report - {$dateStr} - {$batch->report_shift}.xlsx"),
                'analysis_row_count'     => (int) ($anaImp?->row_count ?? 0),
                'analysis_url'           => $anaImp ? route('replenishment.history.show', $anaImp->id) : route('replenishment.analysis-history'),
                'analysis_download_url'  => ($anaImp && $anaImp->file_path) ? route('replenishment.history.download', $anaImp->id) : null,
            ];
        });

        // Group by (report_date + branch_id) so Shift 1 and Shift 2 appear together in 1 row per date
        $matchHistoryItems = $shiftBatches
            ->groupBy(fn($item) => $item->report_date . '|' . ($item->branch_id ?? 0))
            ->map(function ($group) {
                $first = $group->first();
                $shifts = $group->sortBy('report_shift')->values()->all();
                $totalTasks = $group->sum('total_tasks');
                $matchCount = $group->sum('match_count');
                $pendingCount = $group->sum('pending_count');
                $surplusCount = $group->sum('surplus_poc_count');
                $matchPctNum = $totalTasks > 0 ? round(($matchCount / $totalTasks) * 100, 1) : 0.0;

                return (object) [
                    'id'                => $first->report_date . '-' . ($first->branch_id ?? 0),
                    'branch_id'         => $first->branch_id,
                    'branch_code'       => $first->branch_code,
                    'report_date'       => $first->report_date,
                    'total_tasks'       => $totalTasks,
                    'match_count'       => $matchCount,
                    'pending_count'     => $pendingCount,
                    'surplus_poc_count' => $surplusCount,
                    'match_pct_num'     => $matchPctNum,
                    'match_pct'         => number_format($matchPctNum, 1) . '%',
                    'imported_at'       => collect($shifts)->last()?->imported_at ?? $first->imported_at,
                    'match_detail_url'  => route('replenishment.match-detail', array_filter([
                        'date'      => $first->report_date,
                        'branch_id' => $first->branch_id,
                    ], fn($v) => $v !== null && $v !== '')),
                    'shifts'            => $shifts,
                ];
            })
            ->values();

        return view('replenishment.index', [
            'latestSummary' => $latestSummary,
            'branches' => $branches,
            'matchHistoryItems' => $matchHistoryItems,
        ]);
    }

    /**
     * Process both uploaded files: validate, run matching, persist results.
     */
    public function store(ImportReplenishmentRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $reportFileName = $request->file('report_file')->getClientOriginalName();
            $analysisFileName = $request->file('analysis_file')->getClientOriginalName();
            $branchId = $request->input('branch_id') ? (int)$request->input('branch_id') : null;

            // Enforce user branch restriction if user is assigned to a specific branch
            if ($user && !$user->hasGlobalBranchAccess()) {
                $branchId = (int) $user->branch_id;
            }

            $result = $this->importService->processUpload(
                $request->file('report_file'),
                $request->file('analysis_file'),
                $branchId
            );

            $loggedReportName = $result['report_file_name'] ?? $reportFileName;
            $loggedAnalysisName = $result['analysis_file_name'] ?? $analysisFileName;
            $loggedDate = $result['report_date'] ?? '-';
            $loggedShift = $result['report_shift'] ?? '-';
            $loggedMatch = $result['summary']['match_count'] ?? 0;
            $loggedPending = $result['summary']['pending_count'] ?? 0;

            \App\Models\ActivityLog::record(
                \App\Models\ActivityLog::ACTION_IMPORT,
                "Matching {$loggedDate} ({$loggedShift}): {$loggedMatch} matched, {$loggedPending} pending.",
                [
                    'branch_id' => $branchId,
                    'report_filename' => $loggedReportName,
                    'analysis_filename' => $loggedAnalysisName,
                    'report_import_id' => $result['report_import_id'] ?? null,
                    'analysis_import_id' => $result['analysis_import_id'] ?? null,
                    'match_count' => $loggedMatch,
                    'pending_count' => $loggedPending,
                    'percentage' => $result['summary']['match_percentage'] ?? 0,
                ],
                \App\Models\ActivityLog::LEVEL_SUCCESS
            );

            return response()->json($result);
        } catch (\RuntimeException $e) {
            \App\Models\ActivityLog::record(
                \App\Models\ActivityLog::ACTION_IMPORT,
                "Gagal matching: " . \Illuminate\Support\Str::limit($e->getMessage(), 90),
                ['error' => $e->getMessage()],
                \App\Models\ActivityLog::LEVEL_WARNING
            );

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            \App\Models\ActivityLog::record(
                \App\Models\ActivityLog::ACTION_IMPORT,
                "Error sistem saat matching: " . \Illuminate\Support\Str::limit($e->getMessage(), 90),
                ['error' => $e->getMessage()],
                \App\Models\ActivityLog::LEVEL_ERROR
            );

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
    public function reportHistory(Request $request): View
    {
        $user = auth()->user();
        $branches = $this->getAccessibleBranches();
        $selectedBranchId = $request->get('branch_id', '');

        $query = Import::with('branch')
            ->where('import_type', 'replenishment_report')
            ->withCount('replenishmentDetails');

        if ($user && !$user->hasGlobalBranchAccess()) {
            $query->where('branch_id', $user->branch_id);
            $selectedBranchId = (string) $user->branch_id;
        } elseif ($selectedBranchId !== '' && is_numeric($selectedBranchId)) {
            $query->where('branch_id', (int) $selectedBranchId);
        }

        $reports = $query->latest('imported_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('replenishment.report-history', compact('reports', 'branches', 'selectedBranchId'));
    }

    /**
     * Display the Analysis Replenishment History page.
     */
    public function analysisHistory(Request $request): View
    {
        $user = auth()->user();
        $branches = $this->getAccessibleBranches();
        $selectedBranchId = $request->get('branch_id', '');

        $query = Import::with('branch')
            ->where('import_type', 'replenishment_analysis')
            ->withCount('analysisDetails');

        if ($user && !$user->hasGlobalBranchAccess()) {
            $query->where('branch_id', $user->branch_id);
            $selectedBranchId = (string) $user->branch_id;
        } elseif ($selectedBranchId !== '' && is_numeric($selectedBranchId)) {
            $query->where('branch_id', (int) $selectedBranchId);
        }

        $analyses = $query->latest('imported_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('replenishment.analysis-history', compact('analyses', 'branches', 'selectedBranchId'));
    }

    /**
     * Display detail rows of a specific import.
     */
    public function showHistory(Import $import): View
    {
        $user = auth()->user();
        if ($user && !$user->canAccessBranch($import->branch_id)) {
            abort(403, 'Anda tidak memiliki akses untuk melihat data cabang ini.');
        }

        $import->loadMissing('branch');

        if ($import->import_type === 'replenishment_report') {
            $details = $import->replenishmentDetails()->orderBy('row_number')->paginate(30);
            return view('replenishment.report-history-detail', compact('import', 'details'));
        }

        $details = $import->analysisDetails()->orderBy('row_number')->paginate(30);
        return view('replenishment.analysis-history-detail', compact('import', 'details'));
    }

    /**
     * Download the original uploaded Excel file from local storage.
     */
    public function download(Import $import): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = auth()->user();
        if ($user && !$user->canAccessBranch($import->branch_id)) {
            abort(403, 'Anda tidak memiliki akses untuk mengunduh file cabang ini.');
        }

        if (!$import->file_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($import->file_path)) {
            abort(404, 'File Excel tidak ditemukan di direktori lokal.');
        }

        \App\Models\ActivityLog::record(
            \App\Models\ActivityLog::ACTION_DOWNLOAD,
            "Unduh {$import->file_name}",
            ['import_id' => $import->id, 'file_name' => $import->file_name, 'import_type' => $import->import_type],
            \App\Models\ActivityLog::LEVEL_INFO
        );

        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $import->file_path,
            $import->file_name
        );
    }

    /**
     * Display combined Shift 1 & Shift 2 matching result details for a specific date & branch.
     */
    public function matchDetail(Request $request): View
    {
        $reportDate = trim((string) $request->query('date', ''));
        if ($reportDate === '') {
            abort(404, 'Tanggal report tidak ditemukan.');
        }

        $user = auth()->user();
        $branchIdParam = $request->query('branch_id');
        $branchId = ($branchIdParam !== null && $branchIdParam !== '' && is_numeric($branchIdParam))
            ? (int) $branchIdParam
            : null;

        if ($user && !$user->hasGlobalBranchAccess()) {
            $branchId = $user->branch_id;
        }

        $branch = $branchId ? Branch::find($branchId) : null;

        $rowsQuery = DB::table('replenishment_match_results')
            ->where('report_date', $reportDate)
            ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
            ->orderBy('report_shift')
            ->orderBy('category')
            ->orderBy('id');

        $rows = $rowsQuery->get()->map(function ($row) {
            return [
                'id'                     => $row->id,
                'report_shift'           => $row->report_shift ?? '-',
                'category'               => $row->category ?? '-',
                'item_no'                => $row->item_no ?? '-',
                'item_name'              => $row->item_description ?? '-',
                'from_location_report'   => $row->record_type === 'REPORT' ? ($row->from_location ?? '-') : '-',
                'from_location_analysis' => $row->record_type === 'ANALYSIS' ? ($row->from_location ?? '-') : '-',
                'to_location'            => $row->to_location ?? '-',
                'qty_report'             => $row->qty_report,
                'qty_analysis'           => $row->qty_analysis,
                'status'                 => $row->status ?? '-',
                'record_type'            => $row->record_type ?? '-',
                'counts_as_task'         => (bool) $row->counts_as_task,
                'task_done'              => (bool) $row->task_done,
            ];
        })->values();

        $shiftsPresent = $rows->pluck('report_shift')->filter()->unique()->sort()->values()->all();

        return view('replenishment.match-detail', [
            'reportDate'    => $reportDate,
            'branch'        => $branch,
            'rows'          => $rows,
            'shiftsPresent' => $shiftsPresent,
        ]);
    }
}
