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
     * Process uploaded file(s) (paired, report-only, or analysis-only) and return matching result summary.
     *
     * @throws \RuntimeException on validation failure
     */
    public function processUpload(
        ?UploadedFile $reportFile = null,
        ?UploadedFile $analysisFile = null,
        ?int $branchId = null,
        ?string $targetShift = null,
        string $categoryMode = 'AUTO'
    ): array {
        $startTime = microtime(true);
        $categoryMode = in_array(strtoupper($categoryMode), ['DRY', 'FROZEN', 'AUTO'], true)
            ? strtoupper($categoryMode)
            : 'AUTO';
        $storageCategory = $categoryMode === 'AUTO' ? 'MIXED' : $categoryMode;

        if ($reportFile === null && $analysisFile === null) {
            throw new \RuntimeException('Minimal unggah salah satu berkas: Replenishment Report atau Analisa Replenishment.');
        }

        $reportParsed   = null;
        $reportBinary   = null;
        $analysisParsed = null;
        $analysisBinary = null;

        if ($reportFile !== null) {
            if (!$reportFile->isValid()) {
                throw new \RuntimeException('Upload file Replenishment Report gagal: ' . $reportFile->getErrorMessage());
            }
            $reportPath   = $reportFile->getRealPath() ?: $reportFile->getPathname();
            $reportParsed = $this->parserService->parseReportInMemory($reportPath);
            $reportBinary = $reportFile->getContent();
        }

        if ($analysisFile !== null) {
            if (!$analysisFile->isValid()) {
                throw new \RuntimeException('Upload file Analysis Replenishment gagal: ' . $analysisFile->getErrorMessage());
            }
            $analysisPath   = $analysisFile->getRealPath() ?: $analysisFile->getPathname();
            $analysisParsed = $this->parserService->parseAnalysisInMemory($analysisPath);
            $analysisBinary = $analysisFile->getContent();
        }

        // ── Validate & detect metadata ────────────────────────────────────────
        $reportMeta   = $reportParsed['metadata'] ?? ['printedAt' => null, 'site' => '', 'printedBy' => '', 'batchId' => null];
        $analysisMeta = $analysisParsed['metadata'] ?? ['tran_date' => null, 'warehouse' => ''];
        $reportTasks  = $reportParsed['tasks'] ?? [];
        $reportItems  = $reportParsed['items'] ?? [];
        $analysisRows = $analysisParsed['rows'] ?? [];

        [$printedAt, $site, $printedBy, $batchId] = [
            $reportMeta['printedAt'],
            $reportMeta['site'],
            $reportMeta['printedBy'],
            $reportMeta['batchId'],
        ];

        $tranDate  = $analysisMeta['tran_date'];
        $warehouse = $analysisMeta['warehouse'];

        // 1. Validate: non-empty & correct file types (not swapped)
        if ($reportFile !== null && empty($reportTasks) && empty($reportItems)) {
            throw new \RuntimeException(
                'File Replenishment Report kosong atau format kolom tidak dikenali. Pastikan Anda tidak salah menukar posisi file Report dan Analisa.'
            );
        }

        if ($analysisFile !== null && empty($analysisRows)) {
            throw new \RuntimeException(
                'File Analisa Replenishment kosong atau format kolom tidak dikenali. Pastikan file Analisa memiliki kolom Item, Location, Qty, dan Date.'
            );
        }

        // 2. Validate: single tran_date in analysis
        if ($analysisFile !== null) {
            $uniqueTranDates = array_unique(array_filter(array_column($analysisRows, 'tran_date')));
            if (count($uniqueTranDates) > 1) {
                throw new \RuntimeException(
                    'File Analisa mengandung lebih dari 1 tanggal transaksi (' . implode(', ', $uniqueTranDates) . '). Filter file Excel Analisa untuk 1 tanggal saja.'
                );
            }
            if (!$tranDate) {
                throw new \RuntimeException(
                    'Tanggal transaksi (Tran Date) tidak ditemukan di file Analisa Replenishment.'
                );
            }
        }

        // 3. Validate paired consistency when both files are uploaded
        $reportDate = $printedAt ? $printedAt->format('Y-m-d') : null;
        if ($reportFile !== null && $analysisFile !== null) {
            if ($reportDate && $tranDate && $reportDate !== $tranDate) {
                throw new \RuntimeException(
                    "Tanggal tidak cocok: File Report dicetak tanggal {$reportDate}, sedangkan File Analisa bertanggal {$tranDate}. Pastikan kedua file berasal dari tanggal operasional yang sama."
                );
            }

            $normSite = $site ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $site)) : '';
            $normWh   = $warehouse ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $warehouse)) : '';
            if ($normSite !== '' && $normWh !== '' && $normSite !== $normWh) {
                throw new \RuntimeException(
                    "Cabang/Site tidak cocok antara kedua file: Report tertulis '{$site}', sedangkan Analisa tertulis '{$warehouse}'."
                );
            }
        }

        // 4. Auto-resolve or validate Branch against file Site/Warehouse
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

        // 5. Prevent uploading the exact same binary file twice (MD5 hash check)
        $reportHashIncoming   = $reportBinary !== null ? md5($reportBinary) : null;
        $analysisHashIncoming = $analysisBinary !== null ? md5($analysisBinary) : null;

        if ($reportHashIncoming !== null) {
            $duplicateReportHash = Import::where('import_type', 'replenishment_report')
                ->where('file_hash', $reportHashIncoming)
                ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId))
                ->first();

            if ($duplicateReportHash) {
                throw new \RuntimeException(
                    "File Replenishment Report '{$reportFile->getClientOriginalName()}' persis sama dengan file yang sudah pernah di-import sebelumnya ({$duplicateReportHash->report_date} • {$duplicateReportHash->report_shift})."
                );
            }
        }

        if ($analysisHashIncoming !== null) {
            $duplicateAnalysisHash = Import::where('import_type', 'replenishment_analysis')
                ->where('file_hash', $analysisHashIncoming)
                ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId))
                ->first();

            if ($duplicateAnalysisHash) {
                throw new \RuntimeException(
                    "File Analisa Replenishment '{$analysisFile->getClientOriginalName()}' persis sama dengan file yang sudah pernah di-import sebelumnya ({$duplicateAnalysisHash->report_date} • {$duplicateAnalysisHash->report_shift})."
                );
            }
        }

        // ── Branch A: Report is uploaded (Paired OR Report-only) ──────────────
        if ($reportFile !== null) {
            $resolvedDate = $reportDate ?? $tranDate ?? now('Asia/Jakarta')->format('Y-m-d');
            $reportShift  = $printedAt
                ? $this->shiftService->detectReportShift($printedAt, $branchId)
                : 'SHIFT 1';

            $archiveCategories  = $categoryMode === 'AUTO' ? ['MIXED', 'DRY', 'FROZEN'] : ['MIXED', $categoryMode];

            if ($printedAt !== null) {
                $incomingPrintedJakarta = $printedAt->copy()->timezone('Asia/Jakarta');
                $incomingMinuteKey      = $incomingPrintedJakarta->format('Y-m-d H:i');
                $incomingTimeLabel      = $incomingPrintedJakarta->format('H:i');

                $existingReportsForShift = Import::where('import_type', 'replenishment_report')
                    ->where('report_date', $resolvedDate)
                    ->where('report_shift', $reportShift)
                    ->whereIn('storage_category', $archiveCategories)
                    ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
                    ->whereNotNull('printed_at')
                    ->orderByDesc('id')
                    ->get();

                $samePrintedTime = $existingReportsForShift->first(function (Import $imp) use ($incomingMinuteKey) {
                    return $imp->printed_at->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i') === $incomingMinuteKey;
                });

                if ($samePrintedTime) {
                    throw new \RuntimeException(
                        "Report {$reportShift} tanggal {$resolvedDate} dengan jam cetak {$incomingTimeLabel} WIB sudah pernah di-import sebelumnya (File: {$samePrintedTime->file_name}). Gunakan file cetakan jam terbaru jika ingin memperbarui."
                    );
                }

                $activeReport = $existingReportsForShift->firstWhere('is_active', true);
                if ($activeReport && $activeReport->printed_at) {
                    $activePrintedJakarta = $activeReport->printed_at->copy()->timezone('Asia/Jakarta');
                    if ($incomingPrintedJakarta->lt($activePrintedJakarta)) {
                        $activeTimeLabel = $activePrintedJakarta->format('H:i');
                        throw new \RuntimeException(
                            "Tidak dapat menimpa Report {$reportShift} tanggal {$resolvedDate} dengan cetakan yang lebih lama ({$incomingTimeLabel} WIB). Report aktif saat ini dicetak lebih baru pada pukul {$activeTimeLabel} WIB."
                        );
                    }
                }
            }

            $filteredNewAnalysis = [];
            if ($analysisFile !== null) {
                $filteredNewAnalysis = $this->filterAnalysisByShift($analysisRows, $reportShift, $branchId);
                $rowsWithTimestamp = array_filter($analysisRows, fn($r) => !empty($r['created_at']));
                if (count($rowsWithTimestamp) > 0 && count($filteredNewAnalysis) === 0) {
                    $printedTimeStr = $printedAt ? $printedAt->format('H:i') : '-';
                    throw new \RuntimeException(
                        "Tidak ada baris transaksi Analisa yang masuk ke jadwal {$reportShift} (Report dicetak pukul {$printedTimeStr}). Kemungkinan Anda mengunggah file Report {$reportShift} bersama file Analisa dari shift lain, atau jadwal Jam Kerja & Cut-Off Shift cabang ini perlu disesuaikan di menu Master Data Shift."
                    );
                }
            }

            $existingAnalysisRows = $this->loadExistingAnalysisRows($branchId, $resolvedDate, $reportShift);
            $mergedAnalysisRows   = $this->mergeAnalysisRows($existingAnalysisRows, $filteredNewAnalysis);

            $allResults = $this->runMatchingForCategories($reportTasks, $mergedAnalysisRows, $resolvedDate, $reportShift, $batchId, $branchId, $categoryMode);
            $affectedCategories = $categoryMode === 'AUTO' ? ['DRY', 'FROZEN'] : [$categoryMode];
            $archiveCategories  = $categoryMode === 'AUTO' ? ['MIXED', 'DRY', 'FROZEN'] : ['MIXED', $categoryMode];

            return DB::transaction(function () use (
                $reportFile,
                $analysisFile,
                $reportBinary,
                $analysisBinary,
                $reportHashIncoming,
                $analysisHashIncoming,
                $branchId,
                $reportMeta,
                $analysisMeta,
                $reportTasks,
                $reportItems,
                $filteredNewAnalysis,
                $allResults,
                $resolvedDate,
                $reportShift,
                $batchId,
                $tranDate,
                $startTime,
                $storageCategory,
                $affectedCategories,
                $archiveCategories
            ) {
                $hadPreviousReport = Import::where('import_type', 'replenishment_report')
                    ->where('report_date', $resolvedDate)
                    ->where('report_shift', $reportShift)
                    ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
                    ->whereIn('storage_category', $archiveCategories)
                    ->where('is_active', true)
                    ->exists();

                Import::where('import_type', 'replenishment_report')
                    ->where('report_date', $resolvedDate)
                    ->where('report_shift', $reportShift)
                    ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
                    ->whereIn('storage_category', $archiveCategories)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                $wasReplaced = DB::table('replenishment_match_results')
                    ->where('report_date', $resolvedDate)
                    ->where('report_shift', $reportShift)
                    ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
                    ->whereIn('category', $affectedCategories)
                    ->exists();

                $this->replaceMatchSnapshot($branchId, $resolvedDate, $reportShift, $allResults, $affectedCategories);
                $this->recalculateDailySummary($resolvedDate, $branchId);

                $defaultMime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                $reportExt   = strtolower($reportFile->getClientOriginalExtension() ?: 'xlsx');
                $standardizedReportName = "Replenish Report - {$resolvedDate} - {$reportShift}.{$reportExt}";

                $reportRowCount = count($reportItems) > 0 ? count($reportItems) : count($reportTasks);
                $reportImport = Import::create([
                    'branch_id'        => $branchId,
                    'import_type'      => 'replenishment_report',
                    'batch_id'         => $batchId,
                    'file_name'        => $standardizedReportName,
                    'file_path'        => null,
                    'file_hash'        => $reportHashIncoming,
                    'report_date'      => $resolvedDate,
                    'report_shift'     => $reportShift,
                    'storage_category' => $storageCategory,
                    'site'             => $reportMeta['site'],
                    'printed_by'       => $reportMeta['printedBy'],
                    'printed_at'       => $reportMeta['printedAt'],
                    'status'           => 'completed',
                    'is_active'        => true,
                    'row_count'        => $reportRowCount,
                    'imported_at'      => now(),
                ]);

                $reportImport->storedFile()->create([
                    'mime_type'      => $reportFile->getMimeType() ?: $defaultMime,
                    'file_size'      => strlen($reportBinary),
                    'file_content'   => $reportBinary,
                    'parsed_payload' => ['tasks' => $reportTasks],
                ]);

                $this->persistReportDetails($reportImport->id, $reportItems);

                $analysisImport = null;
                $standardizedAnalysisName = null;
                if ($analysisFile !== null) {
                    $analysisExt = strtolower($analysisFile->getClientOriginalExtension() ?: 'xlsx');
                    $standardizedAnalysisName = "Analisa Report - {$resolvedDate} - {$reportShift}.{$analysisExt}";

                    $analysisImport = Import::create([
                        'branch_id'        => $branchId,
                        'import_type'      => 'replenishment_analysis',
                        'batch_id'         => $batchId,
                        'file_name'        => $standardizedAnalysisName,
                        'file_path'        => null,
                        'file_hash'        => $analysisHashIncoming,
                        'report_date'      => $resolvedDate,
                        'report_shift'     => $reportShift,
                        'storage_category' => $storageCategory,
                        'tran_date'        => $tranDate,
                        'warehouse'        => $analysisMeta['warehouse'],
                        'status'           => 'completed',
                        'is_active'        => true,
                        'row_count'        => count($filteredNewAnalysis),
                        'imported_at'      => now(),
                    ]);

                    $analysisImport->storedFile()->create([
                        'mime_type'      => $analysisFile->getMimeType() ?: $defaultMime,
                        'file_size'      => strlen($analysisBinary),
                        'file_content'   => $analysisBinary,
                        'parsed_payload' => ['rows' => $this->serializeAnalysisRows($filteredNewAnalysis)],
                    ]);

                    $this->persistAnalysisDetails($analysisImport->id, $filteredNewAnalysis);
                } else {
                    $latestAnalysis = Import::where('import_type', 'replenishment_analysis')
                        ->where('report_date', $resolvedDate)
                        ->where('report_shift', $reportShift)
                        ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
                        ->where('is_active', true)
                        ->latest('id')
                        ->first();
                    $analysisImport = $latestAnalysis;
                    $standardizedAnalysisName = $latestAnalysis?->file_name;
                }

                $duration = round(microtime(true) - $startTime, 2);
                $summary  = $this->buildSummary($allResults, $resolvedDate, $reportShift, $batchId, $duration);

                return [
                    'success'            => true,
                    'upload_mode'        => $analysisFile !== null ? 'paired' : 'report_only',
                    'was_replaced'       => $wasReplaced || $hadPreviousReport,
                    'batch_id'           => $batchId,
                    'branch_id'          => $branchId,
                    'report_date'        => $resolvedDate,
                    'report_shift'       => $reportShift,
                    'report_import_id'   => $reportImport->id,
                    'analysis_import_id' => $analysisImport?->id,
                    'report_file_name'   => $standardizedReportName,
                    'analysis_file_name' => $standardizedAnalysisName,
                    'summary'            => $summary,
                    'results'            => $this->mapFrontendResults($allResults),
                    'pagination'         => [
                        'total'        => count($allResults),
                        'per_page'     => count($allResults),
                        'current_page' => 1,
                        'total_pages'  => 1,
                    ],
                ];
            });
        }

        // ── Branch B: Analysis-only incremental upload ────────────────────────
        $resolvedDate = $tranDate;
        $activeReportsQuery = Import::with('storedFile')
            ->where('import_type', 'replenishment_report')
            ->where('is_active', true)
            ->where('report_date', $resolvedDate)
            ->when($categoryMode !== 'AUTO', fn($q) => $q->whereIn('storage_category', [$categoryMode, 'MIXED']))
            ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'));

        if ($targetShift !== null) {
            $activeReport = (clone $activeReportsQuery)
                ->where('report_shift', $targetShift)
                ->latest('id')
                ->first();

            if (!$activeReport) {
                throw new \RuntimeException(
                    "Belum ada Replenishment Report aktif untuk {$targetShift} tanggal {$resolvedDate}. Silakan upload Replenishment Report {$targetShift} terlebih dahulu."
                );
            }

            $filteredNewAnalysis = $this->filterAnalysisByShift($analysisRows, $targetShift, $branchId);
            $rowsWithTimestamp   = array_filter($analysisRows, fn($r) => !empty($r['created_at']));
            if (count($rowsWithTimestamp) > 0 && count($filteredNewAnalysis) === 0) {
                throw new \RuntimeException(
                    "Tidak ada baris transaksi Analisa yang masuk ke jadwal {$targetShift} pada tanggal {$resolvedDate}. Periksa kembali target shift atau jam transaksi pada file Analisa."
                );
            }

            $targetReports = collect([$activeReport]);
        } else {
            $activeReports = $activeReportsQuery
                ->orderBy('report_shift')
                ->latest('id')
                ->get()
                ->unique('report_shift')
                ->values();

            if ($activeReports->isEmpty()) {
                throw new \RuntimeException(
                    "Belum ada Replenishment Report untuk tanggal {$resolvedDate}. Silakan upload Replenishment Report terlebih dahulu sebelum mengunggah Analisa."
                );
            }

            $rowsWithTimestamp = array_filter($analysisRows, fn($r) => !empty($r['created_at']));
            $matchedReports = $activeReports->filter(function (Import $rep) use ($analysisRows, $branchId, $rowsWithTimestamp) {
                if (count($rowsWithTimestamp) === 0) {
                    return true;
                }
                return count($this->filterAnalysisByShift($analysisRows, $rep->report_shift, $branchId)) > 0;
            })->values();

            if ($matchedReports->isEmpty()) {
                $availableShifts = $activeReports->pluck('report_shift')->implode(', ');
                throw new \RuntimeException(
                    "Tidak ada baris transaksi Analisa yang masuk ke jadwal shift Report yang tersedia ({$availableShifts}) pada tanggal {$resolvedDate}."
                );
            }

            $targetReports = $matchedReports;
        }

        return DB::transaction(function () use (
            $targetReports,
            $analysisFile,
            $analysisBinary,
            $analysisHashIncoming,
            $analysisRows,
            $analysisMeta,
            $branchId,
            $resolvedDate,
            $tranDate,
            $startTime,
            $categoryMode
        ) {
            $defaultMime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $analysisExt = strtolower($analysisFile->getClientOriginalExtension() ?: 'xlsx');
            $lastResponse = null;

            foreach ($targetReports as $activeReport) {
                $shift = $activeReport->report_shift ?: 'SHIFT 1';
                $reportTasks = $this->extractReportTasksFromImport($activeReport);
                $filteredNewAnalysis = $this->filterAnalysisByShift($analysisRows, $shift, $branchId);

                $existingAnalysisRows = $this->loadExistingAnalysisRows($branchId, $resolvedDate, $shift);
                $mergedAnalysisRows   = $this->mergeAnalysisRows($existingAnalysisRows, $filteredNewAnalysis);

                $effectiveMode = $categoryMode !== 'AUTO'
                    ? $categoryMode
                    : (in_array($activeReport->storage_category, ['DRY', 'FROZEN'], true) ? $activeReport->storage_category : 'AUTO');
                $affectedCategories = $effectiveMode === 'AUTO' ? ['DRY', 'FROZEN'] : [$effectiveMode];
                $effectiveStorageCategory = $effectiveMode === 'AUTO' ? 'MIXED' : $effectiveMode;

                $batchId = $activeReport->batch_id ?: ('REP-' . str_replace('-', '', $resolvedDate) . '-' . str_replace(' ', '', $shift));
                $allResults = $this->runMatchingForCategories($reportTasks, $mergedAnalysisRows, $resolvedDate, $shift, $batchId, $branchId, $effectiveMode);

                $this->replaceMatchSnapshot($branchId, $resolvedDate, $shift, $allResults, $affectedCategories);

                $standardizedAnalysisName = "Analisa Report - {$resolvedDate} - {$shift}.{$analysisExt}";
                $analysisImport = Import::create([
                    'branch_id'        => $branchId,
                    'import_type'      => 'replenishment_analysis',
                    'batch_id'         => $batchId,
                    'file_name'        => $standardizedAnalysisName,
                    'file_path'        => null,
                    'file_hash'        => $analysisHashIncoming,
                    'report_date'      => $resolvedDate,
                    'report_shift'     => $shift,
                    'storage_category' => $effectiveStorageCategory,
                    'tran_date'        => $tranDate,
                    'warehouse'        => $analysisMeta['warehouse'],
                    'status'           => 'completed',
                    'is_active'        => true,
                    'row_count'        => count($filteredNewAnalysis),
                    'imported_at'      => now(),
                ]);

                $analysisImport->storedFile()->create([
                    'mime_type'      => $analysisFile->getMimeType() ?: $defaultMime,
                    'file_size'      => strlen($analysisBinary),
                    'file_content'   => $analysisBinary,
                    'parsed_payload' => ['rows' => $this->serializeAnalysisRows($filteredNewAnalysis)],
                ]);

                $this->persistAnalysisDetails($analysisImport->id, $filteredNewAnalysis);

                $duration = round(microtime(true) - $startTime, 2);
                $summary  = $this->buildSummary($allResults, $resolvedDate, $shift, $batchId, $duration);

                $lastResponse = [
                    'success'            => true,
                    'upload_mode'        => 'analysis_only',
                    'was_replaced'       => true,
                    'batch_id'           => $batchId,
                    'branch_id'          => $branchId,
                    'report_date'        => $resolvedDate,
                    'report_shift'       => $shift,
                    'report_import_id'   => $activeReport->id,
                    'analysis_import_id' => $analysisImport->id,
                    'report_file_name'   => $activeReport->file_name,
                    'analysis_file_name' => $standardizedAnalysisName,
                    'summary'            => $summary,
                    'results'            => $this->mapFrontendResults($allResults),
                    'pagination'         => [
                        'total'        => count($allResults),
                        'per_page'     => count($allResults),
                        'current_page' => 1,
                        'total_pages'  => 1,
                    ],
                ];
            }

            $this->recalculateDailySummary($resolvedDate, $branchId);

            return $lastResponse;
        });
    }
    // ──────────────────────────────────────────────────────────────────────────
    // INCREMENTAL MERGE & SNAPSHOT HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function filterAnalysisByShift(array $analysisRows, string $shift, ?int $branchId): array
    {
        return array_values(array_filter($analysisRows, function (array $row) use ($shift, $branchId) {
            $createdAt = $row['created_at'] ?? null;
            if (!$createdAt) {
                return false;
            }
            if (!$createdAt instanceof \Carbon\Carbon) {
                $createdAt = \Carbon\Carbon::parse($createdAt, 'Asia/Jakarta');
            }
            return $this->shiftService->isAnalysisMatchingShift($createdAt, $shift, $branchId);
        }));
    }

    private function loadExistingAnalysisRows(?int $branchId, string $reportDate, string $shift): array
    {
        $imports = Import::with('storedFile')
            ->where('import_type', 'replenishment_analysis')
            ->where('is_active', true)
            ->where('report_date', $reportDate)
            ->where('report_shift', $shift)
            ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
            ->orderBy('id')
            ->get();

        $merged = [];
        foreach ($imports as $imp) {
            $payloadRows = $imp->storedFile?->parsed_payload['rows'] ?? null;
            if (is_array($payloadRows)) {
                $rows = $this->deserializeAnalysisRows($payloadRows);
            } else {
                $rows = DB::table('replenishment_analysis_details')
                    ->where('import_id', $imp->id)
                    ->orderBy('id')
                    ->get()
                    ->map(fn($d) => [
                        'item_no'          => $d->item_no,
                        'item_description' => $d->item_description,
                        'from_location'    => $d->source_location_code ?? '',
                        'to_location'      => $d->destination_location_code ?? '',
                        'whole_qty'        => (float) $d->whole_qty,
                        'whole_uom'        => $d->whole_uom ?? 'CTN',
                        'loose_qty'        => (float) $d->loose_qty,
                        'loose_uom'        => $d->loose_uom ?? 'PCS',
                        'ratio'            => (float) $d->ratio,
                        'storage'          => $d->storage,
                        'tag_validation'   => $d->tag_validation ?? '',
                        'tran_date'        => $d->tran_date,
                        'created_at'       => null,
                        'row_number'       => (int) $d->row_number,
                    ])->all();
            }

            $merged = $this->mergeAnalysisRows($merged, $rows);
        }

        return $merged;
    }

    private function mergeAnalysisRows(array $existingRows, array $newRows): array
    {
        if (empty($existingRows)) {
            return array_values($newRows);
        }
        if (empty($newRows)) {
            return array_values($existingRows);
        }

        $budget = [];
        foreach ($existingRows as $row) {
            $sig = $this->makeAnalysisRowSignature($row);
            $budget[$sig] = ($budget[$sig] ?? 0) + 1;
        }

        $merged = array_values($existingRows);
        foreach ($newRows as $row) {
            $sig = $this->makeAnalysisRowSignature($row);
            if (($budget[$sig] ?? 0) > 0) {
                $budget[$sig]--;
                continue;
            }
            $merged[] = $row;
        }

        return $merged;
    }

    private function makeAnalysisRowSignature(array $row): string
    {
        $item = strtoupper(preg_replace('/\s+/', '', (string) ($row['item_no'] ?? '')));
        $from = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) ($row['from_location'] ?? '')));
        $to   = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) ($row['to_location'] ?? '')));
        $qty  = number_format((float) ($row['whole_qty'] ?? 0), 4, '.', '');
        $tag  = strtolower(trim((string) ($row['tag_validation'] ?? '')));

        $createdAt = $row['created_at'] ?? '';
        if ($createdAt instanceof \Carbon\Carbon) {
            $createdAt = $createdAt->format('Y-m-d H:i:s');
        }

        return "{$item}|{$from}|{$to}|{$qty}|{$tag}|{$createdAt}";
    }

    private function serializeAnalysisRows(array $rows): array
    {
        return array_map(function (array $row) {
            if (isset($row['created_at']) && $row['created_at'] instanceof \Carbon\Carbon) {
                $row['created_at'] = $row['created_at']->format('Y-m-d H:i:s');
            }
            return $row;
        }, $rows);
    }

    private function deserializeAnalysisRows(array $rows): array
    {
        return array_map(function (array $row) {
            if (!empty($row['created_at']) && is_string($row['created_at'])) {
                $row['created_at'] = \Carbon\Carbon::parse($row['created_at'], 'Asia/Jakarta');
            }
            return $row;
        }, $rows);
    }

    private function extractReportTasksFromImport(Import $reportImport): array
    {
        $payloadTasks = $reportImport->storedFile?->parsed_payload['tasks'] ?? null;
        if (is_array($payloadTasks)) {
            return $payloadTasks;
        }

        $details = DB::table('replenishment_details')
            ->where('import_id', $reportImport->id)
            ->orderBy('id')
            ->get();

        $tasks = [];
        foreach ($details as $idx => $d) {
            $sources  = $d->suggested_sources ? json_decode($d->suggested_sources, true) : [];
            $toLoc    = (string) ($d->destination_location_code ?? '');
            $needed   = (float) $d->quantity;
            $groupKey = strtoupper(preg_replace('/\s+/', '', $d->item_no)) . '|' . strtoupper(preg_replace('/[^A-Z0-9]/i', '', $toLoc));

            if (!empty($sources)) {
                foreach ($sources as $sIdx => $src) {
                    $fromLoc = (string) ($src['location'] ?? $d->source_location_code ?? '');
                    $qtyRep  = (float) ($src['qty'] ?? $needed);
                    $tasks[] = [
                        'item_no'          => $d->item_no,
                        'item_description' => $d->item_description,
                        'from_location'    => $fromLoc,
                        'to_location'      => $toLoc,
                        'qty_report'       => $qtyRep,
                        'group_needed'     => $needed,
                        'group_key'        => $groupKey,
                        'task_key'         => "{$groupKey}|" . strtoupper(preg_replace('/[^A-Z0-9]/i', '', $fromLoc)) . "|{$idx}_{$sIdx}",
                        'source_expired'   => $src['expired'] ?? null,
                    ];
                }
            } else {
                $fromLoc = (string) ($d->source_location_code ?? '');
                $tasks[] = [
                    'item_no'          => $d->item_no,
                    'item_description' => $d->item_description,
                    'from_location'    => $fromLoc,
                    'to_location'      => $toLoc,
                    'qty_report'       => $needed,
                    'group_needed'     => $needed,
                    'group_key'        => $groupKey,
                    'task_key'         => "{$groupKey}|" . strtoupper(preg_replace('/[^A-Z0-9]/i', '', $fromLoc)) . "|{$idx}",
                    'source_expired'   => null,
                ];
            }
        }

        return $tasks;
    }

    private function runMatchingForCategories(
        array $reportTasks,
        array $analysisRows,
        string $resolvedDate,
        string $shift,
        string $batchId,
        ?int $branchId,
        string $categoryMode = 'AUTO'
    ): array {
        if ($categoryMode === 'DRY' || $categoryMode === 'FROZEN') {
            $allResults = $this->matchingEngine->run($reportTasks, $analysisRows, $resolvedDate, $shift, $batchId, $categoryMode);
            foreach ($allResults as &$resRow) {
                $resRow['branch_id'] = $branchId;
            }
            unset($resRow);

            return $allResults;
        }

        $dryAnalysis    = [];
        $frozenAnalysis = [];

        foreach ($analysisRows as $row) {
            $cat = $this->categoryService->detectCategory(
                $row['to_location'],
                $row['from_location'],
                $row['storage'] ?? null
            );
            if ($cat === 'FROZEN') {
                $frozenAnalysis[] = $row;
            } else {
                $dryAnalysis[] = $row;
            }
        }

        $dryTasks    = $this->filterTasksByCategory($reportTasks, 'DRY');
        $frozenTasks = $this->filterTasksByCategory($reportTasks, 'FROZEN');

        $dryResults    = $this->matchingEngine->run($dryTasks, $dryAnalysis, $resolvedDate, $shift, $batchId, 'DRY');
        $frozenResults = $this->matchingEngine->run($frozenTasks, $frozenAnalysis, $resolvedDate, $shift, $batchId, 'FROZEN');

        $allResults = array_merge($dryResults, $frozenResults);
        foreach ($allResults as &$resRow) {
            $resRow['branch_id'] = $branchId;
        }
        unset($resRow);

        return $allResults;
    }

    private function replaceMatchSnapshot(?int $branchId, string $resolvedDate, string $shift, array $allResults, array $categories = ['DRY', 'FROZEN']): void
    {
        DB::table('replenishment_match_results')
            ->where('report_date', $resolvedDate)
            ->where('report_shift', $shift)
            ->whereIn('category', $categories)
            ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
            ->delete();

        foreach (array_chunk($allResults, 500) as $chunk) {
            DB::table('replenishment_match_results')->insert($chunk);
        }
    }

    private function persistReportDetails(int $importId, array $reportItems): void
    {
        if (empty($reportItems)) {
            return;
        }
        $now = now();
        foreach (array_chunk($reportItems, 500) as $chunk) {
            $insertData = array_map(fn($row) => [
                'import_id'                 => $importId,
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
            ], $chunk);
            DB::table('replenishment_details')->insert($insertData);
        }
    }

    private function persistAnalysisDetails(int $importId, array $analysisRows): void
    {
        if (empty($analysisRows)) {
            return;
        }
        $now = now();
        foreach (array_chunk($analysisRows, 500) as $chunk) {
            $insertData = array_map(fn($row) => [
                'import_id'                 => $importId,
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
            ], $chunk);
            DB::table('replenishment_analysis_details')->insert($insertData);
        }
    }

    private function mapFrontendResults(array $allResults): array
    {
        return array_map(function ($row, $idx) {
            $hasReportSource = in_array($row['record_type'], ['REPORT_TASK', 'REPORT'], true)
                || $row['status'] === 'MATCH';
            $hasAnalysisSource = $row['record_type'] === 'ANALYSIS';

            return [
                'no' => $idx + 1,
                'sku' => $row['item_no'],
                'nama_produk' => $row['item_description'] ?? '-',
                'from_location_report' => $hasReportSource ? ($row['from_location'] ?: '-') : '-',
                'from_location_report_type' => $hasReportSource ? 'Report' : '',
                'from_location_analysis' => $hasAnalysisSource ? ($row['from_location'] ?: '-') : '-',
                'from_location_analysis_type' => $hasAnalysisSource ? 'Analisa' : '',
                'to_location_report' => $hasReportSource ? ($row['to_location'] ?: '-') : '-',
                'to_location_report_type' => $hasReportSource ? ($row['category'] ?? '') : '',
                'to_location_analysis' => $hasAnalysisSource ? ($row['to_location'] ?: '-') : '-',
                'to_location_analysis_type' => $hasAnalysisSource ? ($row['category'] ?? '') : '',
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
