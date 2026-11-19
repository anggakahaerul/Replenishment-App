<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Import;
use App\Services\Replenishment\ReplenishmentImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Replenishment Analytics Dashboard ("Riwayat Analisis").
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $search = trim($request->get('search', ''));
        $tab = $request->get('tab', 'all'); // all, matched, unmatch, melebihi_poc
        $selectedBranchId = $request->get('branch_id', '');

        $branchesQuery = Branch::where('is_active', true)->orderBy('code');
        if ($user && !$user->hasGlobalBranchAccess()) {
            $branchesQuery->where('id', $user->branch_id);
            $selectedBranchId = (string) $user->branch_id;
        }
        $branches = $branchesQuery->get();

        $activeBranchFilter = ($selectedBranchId !== '' && is_numeric($selectedBranchId))
            ? (int) $selectedBranchId
            : null;

        $query = DB::table('replenishment_daily_summaries')
            ->when($activeBranchFilter !== null, fn($q) => $q->where('branch_id', $activeBranchFilter))
            ->orderByDesc('report_date')
            ->orderBy('category');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('category', 'LIKE', "%{$search}%")
                  ->orWhere('report_date', 'LIKE', "%{$search}%");
            });
        }

        $allRows = $query->get();

        // Indonesian month names abbreviation
        $bulanMap = [
            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
            '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu',
            '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
        ];

        $items = $allRows->map(function ($row) use ($bulanMap) {
            $dateCarbon = null;
            try {
                $dateCarbon = Carbon::parse($row->report_date);
            } catch (\Throwable) {
                // fall through
            }

            $day = $dateCarbon ? $dateCarbon->format('d') : substr($row->report_date, 8, 2);
            $monthNum = $dateCarbon ? $dateCarbon->format('m') : substr($row->report_date, 5, 2);
            $year = $dateCarbon ? $dateCarbon->format('Y') : substr($row->report_date, 0, 4);
            $monthText = $bulanMap[$monthNum] ?? $monthNum;

            $pendingS1 = max(0, (int) $row->shift_1_total - (int) $row->shift_1_done);
            $pendingS2 = max(0, (int) $row->shift_2_total - (int) $row->shift_2_done);
            $unmatch   = (int) $row->pending_count;

            return (object) [
                'id'                 => $row->id,
                'raw_date'           => $row->report_date,
                'day'                => $day,
                'month_year'         => $monthText . ' ' . $year,
                'category'           => ucfirst(strtolower($row->category)), // 'Dry' / 'Frozen'
                'shift_1_match'      => (int) $row->shift_1_done,
                'shift_2_match'      => (int) $row->shift_2_done,
                'shift_1_total'      => (int) $row->shift_1_total,
                'shift_2_total'      => (int) $row->shift_2_total,
                'shift_1_compl'      => $row->shift_1_completion !== null ? number_format($row->shift_1_completion, 1) . '%' : '-',
                'shift_2_compl'      => $row->shift_2_completion !== null ? number_format($row->shift_2_completion, 1) . '%' : '-',
                'shift_1_compl_num'  => $row->shift_1_completion !== null ? (float) $row->shift_1_completion : 0,
                'shift_2_compl_num'  => $row->shift_2_completion !== null ? (float) $row->shift_2_completion : 0,
                'total_tasks'        => (int) $row->total_tasks,
                'match_count'        => (int) $row->match_count,
                'pending_s1'         => $pendingS1,
                'pending_s2'         => $pendingS2,
                'match_pct'          => number_format((float) $row->match_percentage, 1) . '%',
                'unmatch'            => $unmatch,
                'surplus_poc_count'  => (int) $row->surplus_poc_count,
            ];
        });

        // Compute cumulative total for today or latest date
        $latestDate = $items->first()?->raw_date;
        $latestRows = $latestDate ? $items->filter(fn($i) => $i->raw_date === $latestDate) : collect();

        $cShift1Match = $latestRows->sum('shift_1_match');
        $cShift2Match = $latestRows->sum('shift_2_match');
        $cShift1Total = $latestRows->sum('shift_1_total');
        $cShift2Total = $latestRows->sum('shift_2_total');
        $cTotalTasks  = $latestRows->sum('total_tasks');
        $cMatchCount  = $latestRows->sum('match_count');
        $cPendingS1   = $latestRows->sum('pending_s1');
        $cPendingS2   = $latestRows->sum('pending_s2');
        $cUnmatch     = $latestRows->sum('unmatch');

        $cShift1Compl = $cShift1Total > 0 ? number_format(($cShift1Match / $cShift1Total) * 100, 1) . '%' : '-';
        $cShift2Compl = $cShift2Total > 0 ? number_format(($cShift2Match / $cShift2Total) * 100, 1) . '%' : '-';
        $cMatchPct    = $cTotalTasks > 0 ? number_format(($cMatchCount / $cTotalTasks) * 100, 1) . '%' : '-';

        $cumulative = (object) [
            'date_label'    => $latestDate ? 'Hari Ini: Dry + Frozen' : 'Kumulatif: Dry + Frozen',
            'shift_1_match' => $cShift1Match,
            'shift_2_match' => $cShift2Match,
            'shift_1_total' => $cShift1Total,
            'shift_2_total' => $cShift2Total,
            'shift_1_compl' => $cShift1Compl,
            'shift_2_compl' => $cShift2Compl,
            'total_tasks'   => $cTotalTasks,
            'match_count'   => $cMatchCount,
            'pending_s1'    => $cPendingS1,
            'pending_s2'    => $cPendingS2,
            'match_pct'     => $cMatchPct,
            'unmatch'       => $cUnmatch,
        ];

        // Query activity counts for latest date per shift (Put Away, Transfer, Melebihi POC)
        $shiftActivities = $latestDate
            ? DB::table('replenishment_match_results')
                ->where('report_date', $latestDate)
                ->when($activeBranchFilter !== null, fn($q) => $q->where('branch_id', $activeBranchFilter))
                ->whereIn('report_shift', ['SHIFT 1', 'SHIFT 2'])
                ->selectRaw("
                    report_shift,
                    SUM(CASE WHEN status = 'PUT AWAY' THEN 1 ELSE 0 END) as put_away,
                    SUM(CASE WHEN status = 'TRANSFER' THEN 1 ELSE 0 END) as transfer,
                    SUM(CASE WHEN status = 'MELEBIHI POC' THEN 1 ELSE 0 END) as melebihi_poc
                ")
                ->groupBy('report_shift')
                ->get()
                ->keyBy('report_shift')
            : collect();

        $s1Status = $cShift1Total > 0 ? 'Selesai' : 'Belum Ada Data';
        $s2Status = $cShift2Total > 0 ? 'Selesai' : 'Belum Ada Data';

        $s1PutAway = (int) ($shiftActivities['SHIFT 1']->put_away ?? 0);
        $s1Transfer = (int) ($shiftActivities['SHIFT 1']->transfer ?? 0);
        $s1Melebihi = (int) ($shiftActivities['SHIFT 1']->melebihi_poc ?? 0);

        $s2PutAway = (int) ($shiftActivities['SHIFT 2']->put_away ?? 0);
        $s2Transfer = (int) ($shiftActivities['SHIFT 2']->transfer ?? 0);
        $s2Melebihi = (int) ($shiftActivities['SHIFT 2']->melebihi_poc ?? 0);

        $shift1Card = (object) [
            'name'          => 'Shift 1',
            'time_range'    => '06:00 - 14:00 WIB',
            'status'        => $s1Status,
            'total_tasks'   => $cShift1Total,
            'match_count'   => $cShift1Match,
            'completion'    => $cShift1Compl !== '-' ? $cShift1Compl : '0.0%',
            'completion_num'=> $cShift1Total > 0 ? round(($cShift1Match / $cShift1Total) * 100, 1) : 0,
            'pending_count' => $cPendingS1,
            'put_away'      => $s1PutAway,
            'transfer'      => $s1Transfer,
            'melebihi_poc'  => $s1Melebihi,
        ];

        $shift2Card = (object) [
            'name'          => 'Shift 2',
            'time_range'    => '14:00 - 22:00 WIB',
            'status'        => $s2Status,
            'total_tasks'   => $cShift2Total,
            'match_count'   => $cShift2Match,
            'completion'    => $cShift2Compl !== '-' ? $cShift2Compl : '0.0%',
            'completion_num'=> $cShift2Total > 0 ? round(($cShift2Match / $cShift2Total) * 100, 1) : 0,
            'pending_count' => $cPendingS2,
            'put_away'      => $s2PutAway,
            'transfer'      => $s2Transfer,
            'melebihi_poc'  => $s2Melebihi,
        ];

        $allShiftCard = (object) [
            'name'          => 'All Shift',
            'time_range'    => '06:00 - 22:00 WIB',
            'status'        => $cTotalTasks > 0 ? ($latestDate ?? 'Kumulatif') : 'Belum Ada Data',
            'total_tasks'   => $cTotalTasks,
            'match_count'   => $cMatchCount,
            'completion'    => $cMatchPct !== '-' ? $cMatchPct : '0.0%',
            'completion_num'=> $cTotalTasks > 0 ? round(($cMatchCount / $cTotalTasks) * 100, 1) : 0,
            'pending_count' => $cUnmatch,
            'put_away'      => $s1PutAway + $s2PutAway,
            'transfer'      => $s1Transfer + $s2Transfer,
            'melebihi_poc'  => $s1Melebihi + $s2Melebihi,
        ];

        // Build per-date breakdown for the 3 Shift Cards (supports daily / weekly / monthly / custom date range)
        $allShiftActivities = DB::table('replenishment_match_results')
            ->when($activeBranchFilter !== null, fn($q) => $q->where('branch_id', $activeBranchFilter))
            ->whereIn('report_shift', ['SHIFT 1', 'SHIFT 2'])
            ->selectRaw("
                report_date,
                report_shift,
                SUM(CASE WHEN status = 'PUT AWAY' THEN 1 ELSE 0 END) as put_away,
                SUM(CASE WHEN status = 'TRANSFER' THEN 1 ELSE 0 END) as transfer,
                SUM(CASE WHEN status = 'MELEBIHI POC' THEN 1 ELSE 0 END) as melebihi_poc
            ")
            ->groupBy('report_date', 'report_shift')
            ->get()
            ->groupBy('report_date');

        $cardDailyData = $items->groupBy('raw_date')
            ->sortKeys()
            ->map(function ($dayRows, $dateStr) use ($allShiftActivities) {
                $acts = $allShiftActivities->get($dateStr, collect())->keyBy('report_shift');

                $s1Total = (int) $dayRows->sum('shift_1_total');
                $s1Match = (int) $dayRows->sum('shift_1_match');
                $s1Pend  = (int) $dayRows->sum('pending_s1');
                $s1Put   = (int) ($acts['SHIFT 1']->put_away ?? 0);
                $s1Trf   = (int) ($acts['SHIFT 1']->transfer ?? 0);
                $s1Poc   = (int) ($acts['SHIFT 1']->melebihi_poc ?? 0);

                $s2Total = (int) $dayRows->sum('shift_2_total');
                $s2Match = (int) $dayRows->sum('shift_2_match');
                $s2Pend  = (int) $dayRows->sum('pending_s2');
                $s2Put   = (int) ($acts['SHIFT 2']->put_away ?? 0);
                $s2Trf   = (int) ($acts['SHIFT 2']->transfer ?? 0);
                $s2Poc   = (int) ($acts['SHIFT 2']->melebihi_poc ?? 0);

                $allTotal = (int) $dayRows->sum('total_tasks');
                $allMatch = (int) $dayRows->sum('match_count');
                $allPend  = (int) $dayRows->sum('unmatch');

                return [
                    'date'  => (string) $dateStr,
                    'month' => substr((string) $dateStr, 0, 7),
                    's1'    => ['total' => $s1Total, 'match' => $s1Match, 'pending' => $s1Pend, 'put_away' => $s1Put, 'transfer' => $s1Trf, 'melebihi_poc' => $s1Poc],
                    's2'    => ['total' => $s2Total, 'match' => $s2Match, 'pending' => $s2Pend, 'put_away' => $s2Put, 'transfer' => $s2Trf, 'melebihi_poc' => $s2Poc],
                    'all'   => ['total' => $allTotal, 'match' => $allMatch, 'pending' => $allPend, 'put_away' => $s1Put + $s2Put, 'transfer' => $s1Trf + $s2Trf, 'melebihi_poc' => $s1Poc + $s2Poc],
                ];
            })
            ->values()
            ->all();

        // Build dynamic trend data from actual database rows (all available dates for range/month filter)
        $allTrendDatesRaw = DB::table('replenishment_daily_summaries')
            ->when($activeBranchFilter !== null, fn($q) => $q->where('branch_id', $activeBranchFilter))
            ->select('report_date')
            ->distinct()
            ->orderBy('report_date')
            ->get()
            ->pluck('report_date')
            ->values()
            ->all();

        $trendDatesRaw = array_slice($allTrendDatesRaw, -7);

        $trendSummaryRows = !empty($allTrendDatesRaw)
            ? DB::table('replenishment_daily_summaries')
                ->when($activeBranchFilter !== null, fn($q) => $q->where('branch_id', $activeBranchFilter))
                ->whereIn('report_date', $allTrendDatesRaw)
                ->get()
            : collect();

        $bulanFullMap = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
        ];

        $dateLabels = array_map(function ($d) use ($bulanMap) {
            $day = substr($d, 8, 2);
            $m = substr($d, 5, 2);
            return ltrim($day, '0') . ' ' . ($bulanMap[$m] ?? $m);
        }, $trendDatesRaw);

        $extractMetrics = function ($dayRows, string $shiftKey, string $catKey) {
            $filtered = $catKey !== 'all'
                ? $dayRows->filter(fn($r) => strtolower($r->category) === $catKey)
                : $dayRows;

            if ($shiftKey === 'shift1') {
                $m = (int) $filtered->sum('shift_1_done');
                $t = (int) $filtered->sum('shift_1_total');
                $u = max(0, $t - $m);
            } elseif ($shiftKey === 'shift2') {
                $m = (int) $filtered->sum('shift_2_done');
                $t = (int) $filtered->sum('shift_2_total');
                $u = max(0, $t - $m);
            } else {
                $m = (int) $filtered->sum('match_count');
                $t = (int) $filtered->sum('total_tasks');
                $u = (int) $filtered->sum('pending_count');
            }

            return [$m, $u, $t];
        };

        $trendPoints = [];
        $trendMonthsMap = [];
        foreach ($allTrendDatesRaw as $d) {
            $dayRows = $trendSummaryRows->where('report_date', $d);
            $monthKey = substr($d, 0, 7);
            $mCode = substr($d, 5, 2);
            $yCode = substr($d, 0, 4);
            $monthLabel = ($bulanFullMap[$mCode] ?? $mCode) . ' ' . $yCode;
            $trendMonthsMap[$monthKey] = $monthLabel;

            $day = substr($d, 8, 2);
            $label = ltrim($day, '0') . ' ' . ($bulanMap[$mCode] ?? $mCode);

            $point = [
                'date'  => $d,
                'label' => $label,
                'month' => $monthKey,
            ];
            foreach (['all', 'shift1', 'shift2'] as $sk) {
                $point[$sk] = [];
                foreach (['all', 'dry', 'frozen'] as $ck) {
                    $point[$sk][$ck] = $extractMetrics($dayRows, $sk, $ck);
                }
            }
            $trendPoints[] = $point;
        }

        $trendMonths = [];
        foreach (array_reverse($trendMonthsMap, true) as $k => $lbl) {
            $trendMonths[] = ['key' => $k, 'label' => $lbl];
        }

        $buildSeries = function (string $shiftKey, string $catKey) use ($trendDatesRaw, $trendSummaryRows, $extractMetrics) {
            $matchedArr = [];
            $unmatchArr = [];
            $totalArr   = [];
            $sumMatched = 0;
            $sumTotal   = 0;

            foreach ($trendDatesRaw as $d) {
                $dayRows = $trendSummaryRows->where('report_date', $d);
                [$m, $u, $t] = $extractMetrics($dayRows, $shiftKey, $catKey);

                $matchedArr[] = $m;
                $unmatchArr[] = $u;
                $totalArr[]   = $t;
                $sumMatched  += $m;
                $sumTotal    += $t;
            }

            $avgRate = $sumTotal > 0 ? number_format(($sumMatched / $sumTotal) * 100, 1) . '%' : '0.0%';

            return [
                'matched'  => $matchedArr,
                'unmatch'  => $unmatchArr,
                'total'    => $totalArr,
                'avg_rate' => $avgRate,
            ];
        };

        $allAll = $buildSeries('all', 'all');

        $trendData = [
            'dates'    => $dateLabels,
            'points'   => $trendPoints,
            'months'   => $trendMonths,
            'avg_rate' => $allAll['avg_rate'],
            'all' => [
                'all'    => $allAll,
                'dry'    => $buildSeries('all', 'dry'),
                'frozen' => $buildSeries('all', 'frozen'),
            ],
            'shift1' => [
                'all'    => $buildSeries('shift1', 'all'),
                'dry'    => $buildSeries('shift1', 'dry'),
                'frozen' => $buildSeries('shift1', 'frozen'),
            ],
            'shift2' => [
                'all'    => $buildSeries('shift2', 'all'),
                'dry'    => $buildSeries('shift2', 'dry'),
                'frozen' => $buildSeries('shift2', 'frozen'),
            ],
        ];

        // ── Fetch History Matching Per Tanggal & Shift (Batch Summary) ──────
        $branchMap = Branch::pluck('code', 'id')->all();

        $allImports = Import::query()
            ->withExists('storedFile')
            ->when($activeBranchFilter !== null, fn($q) => $q->where('branch_id', $activeBranchFilter))
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        // Key imports by "{branch_id}|{report_date}|{report_shift}|{import_type}"
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
            ->when($activeBranchFilter !== null, fn($q) => $q->where('branch_id', $activeBranchFilter))
            ->selectRaw("
                branch_id,
                report_date,
                report_shift,
                COUNT(*) as total_rows,
                SUM(CASE WHEN counts_as_task = 1 THEN 1 ELSE 0 END) as total_tasks,
                SUM(CASE WHEN status = 'MATCH' THEN 1 ELSE 0 END) as match_count,
                SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'MELEBIHI POC' THEN 1 ELSE 0 END) as surplus_poc_count,
                SUM(CASE WHEN status = 'TRANSFER' THEN 1 ELSE 0 END) as transfer_count,
                SUM(CASE WHEN status = 'PUT AWAY' THEN 1 ELSE 0 END) as put_away_count,
                MAX(created_at) as imported_at
            ")
            ->groupBy('branch_id', 'report_date', 'report_shift')
            ->orderByDesc('report_date')
            ->orderByDesc('report_shift')
            ->get();

        $matchHistoryItems = $matchBatchesRaw->map(function ($batch, $idx) use ($bulanMap, $branchMap, $importLookup) {
            $dateStr = (string) $batch->report_date;
            $day = substr($dateStr, 8, 2);
            $monthNum = substr($dateStr, 5, 2);
            $year = substr($dateStr, 0, 4);
            $monthText = $bulanMap[$monthNum] ?? $monthNum;

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
                'id'                     => $idx + 1,
                'branch_id'              => $batch->branch_id !== null ? (int) $batch->branch_id : null,
                'branch_code'            => $batch->branch_id ? ($branchMap[$batch->branch_id] ?? '-') : '-',
                'report_date'            => $dateStr,
                'day'                    => $day,
                'month_year'             => $monthText . ' ' . $year,
                'report_shift'           => $batch->report_shift ?? '-',
                'total_rows'             => (int) $batch->total_rows,
                'total_tasks'            => $totalTasks,
                'match_count'            => $matchCount,
                'pending_count'          => $pendingCount,
                'surplus_poc_count'      => $surplusCount,
                'transfer_count'         => (int) $batch->transfer_count,
                'put_away_count'         => (int) $batch->put_away_count,
                'match_pct_num'          => $matchPctNum,
                'match_pct'              => $matchPct,
                'imported_at'            => $importedAtFormatted,
                'report_import_id'       => $repImp?->id,
                'report_file_name'       => $repImp?->file_name ?? ("Replenish Report - {$dateStr} - {$batch->report_shift}.xlsx"),
                'report_row_count'       => (int) ($repImp?->row_count ?? 0),
                'report_url'             => $repImp ? route('replenishment.history.show', $repImp->id) : route('replenishment.report-history'),
                'report_download_url'    => ($repImp && $repImp->has_file) ? route('replenishment.history.download', $repImp->id) : null,
                'analysis_import_id'     => $anaImp?->id,
                'analysis_file_name'     => $anaImp?->file_name ?? ("Analisa Report - {$dateStr} - {$batch->report_shift}.xlsx"),
                'analysis_row_count'     => (int) ($anaImp?->row_count ?? 0),
                'analysis_url'           => $anaImp ? route('replenishment.history.show', $anaImp->id) : route('replenishment.analysis-history'),
                'analysis_download_url'  => ($anaImp && $anaImp->has_file) ? route('replenishment.history.download', $anaImp->id) : null,
            ];
        })->values();

        return view('dashboard.index', compact(
            'items',
            'cumulative',
            'shift1Card',
            'shift2Card',
            'allShiftCard',
            'cardDailyData',
            'trendData',
            'matchHistoryItems',
            'search',
            'tab',
            'branches',
            'selectedBranchId'
        ));
    }

    /**
     * Delete a single row from replenishment_match_results and recalculate daily summary.
     */
    public function destroyMatchResult(Request $request, int $id, ReplenishmentImportService $importService)
    {
        $user = auth()->user();
        $row = DB::table('replenishment_match_results')->where('id', $id)->first();

        if (!$row) {
            return back()->with('error', 'Data matching tidak ditemukan.');
        }

        if ($user && !$user->canAccessBranch($row->branch_id)) {
            abort(403, 'Anda tidak memiliki akses ke cabang ini.');
        }

        $branchId = $row->branch_id !== null ? (int) $row->branch_id : null;
        $reportDate = (string) $row->report_date;
        $reportShift = (string) $row->report_shift;

        DB::transaction(function () use ($id, $row, $branchId, $reportDate, $reportShift, $importService) {
            DB::table('replenishment_match_results')->where('id', $id)->delete();

            // Check if any rows remain for this shift
            $remainingInShift = DB::table('replenishment_match_results')
                ->where('report_date', $reportDate)
                ->where('report_shift', $reportShift)
                ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
                ->exists();

            if (!$remainingInShift) {
                $this->cleanupShiftImports($branchId, $reportDate, $reportShift);
            }

            $importService->recalculateDailySummary($reportDate, $branchId);
        });

        ActivityLog::record(
            ActivityLog::ACTION_DELETE,
            "Hapus SKU {$row->item_no} ({$reportDate} • {$reportShift})",
            ['match_result_id' => $id, 'sku' => $row->item_no, 'report_date' => $reportDate, 'report_shift' => $reportShift],
            ActivityLog::LEVEL_WARNING
        );

        return back()->with('success', "Baris matching SKU {$row->item_no} ({$reportDate} • {$reportShift}) berhasil dihapus.");
    }

    /**
     * Delete an entire matching batch (all rows for branch + report_date + report_shift + associated Imports).
     */
    public function destroyMatchBatch(Request $request, ReplenishmentImportService $importService)
    {
        $user = auth()->user();
        $reportDate = (string) $request->input('report_date');
        $reportShift = (string) $request->input('report_shift');
        $branchIdInput = $request->input('branch_id');
        $branchId = ($branchIdInput !== null && $branchIdInput !== '' && $branchIdInput !== 'null')
            ? (int) $branchIdInput
            : null;

        if ($user && !$user->canAccessBranch($branchId)) {
            abort(403, 'Anda tidak memiliki akses ke cabang ini.');
        }

        $deletedCount = 0;

        DB::transaction(function () use ($branchId, $reportDate, $reportShift, $importService, &$deletedCount) {
            $deletedCount = DB::table('replenishment_match_results')
                ->where('report_date', $reportDate)
                ->when($reportShift !== '' && $reportShift !== 'ALL', fn($q) => $q->where('report_shift', $reportShift))
                ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
                ->delete();

            $this->cleanupShiftImports($branchId, $reportDate, $reportShift);
            $importService->recalculateDailySummary($reportDate, $branchId);
        });

        $shiftLabel = ($reportShift === 'ALL' || $reportShift === '') ? 'Semua Shift' : $reportShift;

        ActivityLog::record(
            ActivityLog::ACTION_DELETE,
            "Hapus history {$reportDate} • {$shiftLabel} ({$deletedCount} baris)",
            ['branch_id' => $branchId, 'report_date' => $reportDate, 'report_shift' => $shiftLabel, 'deleted_rows' => $deletedCount],
            ActivityLog::LEVEL_WARNING
        );

        return back()->with('success', "Data History Matching {$reportDate} ({$shiftLabel}) sebanyak {$deletedCount} baris berhasil dihapus. Anda kini dapat mengunggah ulang shift tersebut bila diperlukan.");
    }

    /**
     * Remove Import records and physical files for a given branch, date, and shift.
     */
    private function cleanupShiftImports(?int $branchId, string $reportDate, string $reportShift): void
    {
        $imports = Import::query()
            ->where('report_date', $reportDate)
            ->when($reportShift !== '' && $reportShift !== 'ALL', fn($q) => $q->where('report_shift', $reportShift))
            ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId), fn($q) => $q->whereNull('branch_id'))
            ->get();

        foreach ($imports as $imp) {
            if ($imp->file_path && Storage::disk('local')->exists($imp->file_path)) {
                Storage::disk('local')->delete($imp->file_path);
            }
            $imp->delete();
        }
    }
}
