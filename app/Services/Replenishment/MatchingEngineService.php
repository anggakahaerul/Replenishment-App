<?php

namespace App\Services\Replenishment;

/**
 * In-memory Matching Engine for Replenishment Reconciliation.
 *
 * Input:
 *   - $reportTasks[]  — flat list of tasks parsed from the Report (already filtered to correct shift)
 *   - $analysisRows[] — flat list of rows from the Analisa (already filtered to correct shift)
 *
 * Algorithm:
 *   1. Build a HashMap: strictKey → queue of Report task candidates (with index tracking)
 *   2. FIFO allocation: each eligible Analysis row tries to claim 1 candidate
 *      - A candidate is claimable only if: its candidateIndex is unused AND its logicalTaskKey is unused
 *   3. Classify every Analysis row (7 statuses)
 *   4. Classify unused Report tasks → PENDING or MELEBIHI POC
 *
 * Output: flat array of match result rows ready for bulk insert.
 */
class MatchingEngineService
{
    // ──────────────────────────────────────────────────────────────────────────
    // ENTRY POINT
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Run the matching engine.
     *
     * @param list<array> $reportTasks   Each task: [ item_no, from_location, to_location,
     *                                    qty_report, task_key, group_key, group_needed, ... ]
     * @param list<array> $analysisRows  Each row:  [ item_no, from_location, to_location,
     *                                    whole_qty, tag_validation, storage, ... ]
     * @param string      $reportDate    yyyy-MM-dd
     * @param string      $reportShift   'SHIFT 1' | 'SHIFT 2'
     * @param string      $batchId
     * @param string      $category      'DRY' | 'FROZEN'
     * @return list<array>  Ready for DB insert into replenishment_match_results
     */
    public function run(
        array $reportTasks,
        array $analysisRows,
        string $reportDate,
        string $reportShift,
        string $batchId,
        string $category
    ): array {
        // ── Step 1: Build strict-key → candidate queue ──────────────────────
        // candidateQueue[ strictKey ] = list of { candidateIndex, taskKey, groupKey,
        //                                         qty_report, source_expired, item_no,
        //                                         item_description, from_location, to_location }
        $candidateQueue = [];

        foreach ($reportTasks as $idx => $task) {
            $strictKey = $this->makeStrictKey(
                $task['item_no'],
                $task['from_location'],
                $task['to_location']
            );

            $candidateQueue[$strictKey][] = [
                'candidate_index' => $idx,
                'task_key'        => $task['task_key'],
                'group_key'       => $task['group_key'],
                'group_needed'    => $task['group_needed'],
                'qty_report'      => $task['qty_report'],
                'source_expired'  => $task['source_expired'] ?? null,
                'item_no'         => $task['item_no'],
                'item_description'=> $task['item_description'],
                'from_location'   => $task['from_location'],
                'to_location'     => $task['to_location'],
            ];
        }

        // ── Step 2: FIFO Allocation ──────────────────────────────────────────
        /** @var array<string, bool> $allocatedLogicalTasks  taskKey → true */
        $allocatedLogicalTasks = [];
        /** @var array<int, bool> $allocatedCandidateIndexes  candidateIndex → true */
        $allocatedCandidateIndexes = [];

        // Track which candidateQueue pointers have been scanned (for FIFO)
        $queuePointers = []; // strictKey → next scan start index

        $matchResults = []; // ANALYSIS record rows

        $now = now()->toDateTimeString();

        foreach ($analysisRows as $anaRow) {
            $itemNo       = $anaRow['item_no'];
            $fromLoc      = $anaRow['from_location']  ?? '';
            $toLoc        = $anaRow['to_location']    ?? '';
            $wholeQty     = (float) ($anaRow['whole_qty'] ?? 0);
            $tagRaw       = $anaRow['tag_validation'] ?? '';
            $storage      = $anaRow['storage']        ?? null;
            $rawCreatedAt = $anaRow['created_at']     ?? null;
            $sourceCreatedAt = $rawCreatedAt instanceof \DateTimeInterface
                ? $rawCreatedAt->format('Y-m-d H:i:s')
                : (is_string($rawCreatedAt) && $rawCreatedAt !== '' ? $rawCreatedAt : null);
            $sourceCreatedBy = isset($anaRow['created_by']) && trim((string) $anaRow['created_by']) !== ''
                ? trim((string) $anaRow['created_by'])
                : null;
            $isEligible   = strtolower(trim($tagRaw)) === 'replenishment';

            $strictKey = $this->makeStrictKey($itemNo, $fromLoc, $toLoc);

            // Classify the analysis row
            if (!$isEligible) {
                // PUT AWAY or TRANSFER
                $fromAlphaNum = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $fromLoc));
                $status = $fromAlphaNum === 'RCVDOCK' ? 'PUT AWAY' : 'TRANSFER';

                $matchResults[] = $this->buildAnalysisRow(
                    batchId: $batchId,
                    reportDate: $reportDate,
                    reportShift: $reportShift,
                    category: $category,
                    itemNo: $itemNo,
                    itemDescription: $anaRow['item_description'] ?? null,
                    fromLocation: $fromLoc,
                    toLocation: $toLoc,
                    taskKey: null,
                    groupKey: null,
                    qtyReport: null,
                    qtyAnalysis: $wholeQty,
                    qtyCheck: 'N/A',
                    status: $status,
                    countsAsTask: false,
                    taskDone: false,
                    tagValidation: $tagRaw,
                    storage: $storage,
                    now: $now,
                    sourceCreatedAt: $sourceCreatedAt,
                    sourceCreatedBy: $sourceCreatedBy
                );
                continue;
            }

            // Eligible (tag = Replenishment) — try to claim a candidate
            $candidates = $candidateQueue[$strictKey] ?? [];

            if (empty($candidates)) {
                // strictKey not in Report at all → INITIATIVE
                $matchResults[] = $this->buildAnalysisRow(
                    batchId: $batchId,
                    reportDate: $reportDate,
                    reportShift: $reportShift,
                    category: $category,
                    itemNo: $itemNo,
                    itemDescription: $anaRow['item_description'] ?? null,
                    fromLocation: $fromLoc,
                    toLocation: $toLoc,
                    taskKey: null,
                    groupKey: null,
                    qtyReport: null,
                    qtyAnalysis: $wholeQty,
                    qtyCheck: 'N/A',
                    status: 'INITIATIVE',
                    countsAsTask: false,
                    taskDone: false,
                    tagValidation: $tagRaw,
                    storage: $storage,
                    now: $now,
                    sourceCreatedAt: $sourceCreatedAt,
                    sourceCreatedBy: $sourceCreatedBy
                );
                continue;
            }

            // Try FIFO allocation
            $pointer   = $queuePointers[$strictKey] ?? 0;
            $claimed   = null;

            for ($i = $pointer; $i < count($candidates); $i++) {
                $cand = $candidates[$i];
                if (
                    !isset($allocatedCandidateIndexes[$cand['candidate_index']]) &&
                    !isset($allocatedLogicalTasks[$cand['task_key']])
                ) {
                    $claimed = $cand;
                    $queuePointers[$strictKey] = $i + 1;
                    $allocatedCandidateIndexes[$cand['candidate_index']] = true;
                    $allocatedLogicalTasks[$cand['task_key']] = true;
                    break;
                }
            }

            if ($claimed !== null) {
                // MATCH
                $qtyCheck = $this->computeQtyCheck($claimed['qty_report'], $wholeQty);
                $matchResults[] = $this->buildAnalysisRow(
                    batchId: $batchId,
                    reportDate: $reportDate,
                    reportShift: $reportShift,
                    category: $category,
                    itemNo: $itemNo,
                    itemDescription: $anaRow['item_description'] ?? null,
                    fromLocation: $fromLoc,
                    toLocation: $toLoc,
                    taskKey: $claimed['task_key'],
                    groupKey: $claimed['group_key'],
                    qtyReport: $claimed['qty_report'],
                    qtyAnalysis: $wholeQty,
                    qtyCheck: $qtyCheck,
                    status: 'MATCH',
                    countsAsTask: true,
                    taskDone: true,
                    tagValidation: $tagRaw,
                    storage: $storage,
                    now: $now,
                    sourceCreatedAt: $sourceCreatedAt,
                    sourceCreatedBy: $sourceCreatedBy
                );
            } else {
                // All quota for this key exhausted → DUPLICATE RAW
                // Check if strictKey exists in report at all (it does, we checked above)
                $matchResults[] = $this->buildAnalysisRow(
                    batchId: $batchId,
                    reportDate: $reportDate,
                    reportShift: $reportShift,
                    category: $category,
                    itemNo: $itemNo,
                    itemDescription: $anaRow['item_description'] ?? null,
                    fromLocation: $fromLoc,
                    toLocation: $toLoc,
                    taskKey: null,
                    groupKey: null,
                    qtyReport: null,
                    qtyAnalysis: $wholeQty,
                    qtyCheck: 'N/A',
                    status: 'DUPLICATE RAW',
                    countsAsTask: false,
                    taskDone: false,
                    tagValidation: $tagRaw,
                    storage: $storage,
                    now: $now,
                    sourceCreatedAt: $sourceCreatedAt,
                    sourceCreatedBy: $sourceCreatedBy
                );
            }
        }

        // ── Step 3: Classify unused Report tasks → PENDING / MELEBIHI POC ──
        $reportTaskRows = $this->classifyUnusedReportTasks(
            reportTasks: $reportTasks,
            allocatedLogicalTasks: $allocatedLogicalTasks,
            batchId: $batchId,
            reportDate: $reportDate,
            reportShift: $reportShift,
            category: $category,
            now: $now
        );

        return array_merge($matchResults, $reportTaskRows);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PENDING / MELEBIHI POC CLASSIFICATION
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Classify each unused logicalTaskKey as PENDING or MELEBIHI POC.
     *
     * Per groupKey:
     *   needed       = groupNeeded
     *   suggested    = sum of qty of all unique logicalTaskKeys in group
     *   matched      = sum of qty of allocated logicalTaskKeys in group
     *   overSuggested = (matched > 0 && suggested > needed) ? (suggested - needed) : 0
     *   groupRemaining = needed - matched - overSuggested
     *
     * Loop unallocated taskKeys (each unique occurrence):
     *   if groupRemaining > 0 (or needed <= 0) → PENDING, groupRemaining -= task.qty
     *   else → MELEBIHI POC
     */
    private function classifyUnusedReportTasks(
        array $reportTasks,
        array $allocatedLogicalTasks,
        string $batchId,
        string $reportDate,
        string $reportShift,
        string $category,
        string $now
    ): array {
        // Collect unique task-keys and their data
        // taskKeyData[ taskKey ] = [ group_key, group_needed, qty_report, item_no, ... ]
        $taskKeyData = [];
        foreach ($reportTasks as $task) {
            $tk = $task['task_key'];
            if (!isset($taskKeyData[$tk])) {
                $taskKeyData[$tk] = $task;
            }
        }

        // Per group: compute needed, suggested, matched
        $groupStats = [];
        foreach ($taskKeyData as $tk => $task) {
            $gk = $task['group_key'];
            if (!isset($groupStats[$gk])) {
                $groupStats[$gk] = [
                    'needed'    => $task['group_needed'],
                    'suggested' => 0.0,
                    'matched'   => 0.0,
                ];
            }
            $groupStats[$gk]['suggested'] += (float) $task['qty_report'];
            if (isset($allocatedLogicalTasks[$tk])) {
                $groupStats[$gk]['matched'] += (float) $task['qty_report'];
            }
        }

        // Compute groupRemaining per group
        $groupRemaining = [];
        foreach ($groupStats as $gk => $stats) {
            $needed    = $stats['needed'];
            $suggested = $stats['suggested'];
            $matched   = $stats['matched'];
            $overSuggested = ($matched > 0 && $suggested > $needed) ? ($suggested - $needed) : 0.0;
            $groupRemaining[$gk] = $needed - $matched - $overSuggested;
        }

        $rows = [];

        foreach ($taskKeyData as $tk => $task) {
            // Skip allocated tasks
            if (isset($allocatedLogicalTasks[$tk])) {
                continue;
            }

            $gk       = $task['group_key'];
            $qty      = (float) $task['qty_report'];
            $needed   = $groupStats[$gk]['needed'] ?? 0;
            $remaining = $groupRemaining[$gk] ?? 0.0;

            if ($remaining > 0 || $needed <= 0) {
                $status       = 'PENDING';
                $countsAsTask = true;
                $taskDone     = false;
                $groupRemaining[$gk] -= $qty;
            } else {
                $status       = 'MELEBIHI POC';
                $countsAsTask = false;
                $taskDone     = false;
            }

            $rows[] = [
                'batch_id'        => $batchId,
                'report_date'     => $reportDate,
                'report_shift'    => $reportShift,
                'category'        => $category,
                'record_type'     => 'REPORT_TASK',
                'item_no'         => $task['item_no'],
                'item_description'=> $task['item_description'] ?? null,
                'from_location'   => $task['from_location'],
                'to_location'     => $task['to_location'],
                'task_key'        => $tk,
                'group_key'       => $gk,
                'qty_report'      => $qty,
                'qty_analysis'    => null,
                'qty_check'       => 'N/A',
                'status'          => $status,
                'counts_as_task'  => $countsAsTask,
                'task_done'       => $taskDone,
                'tag_validation'  => null,
                'storage'         => null,
                'source_expired'    => $task['source_expired'] ?? null,
                'source_created_at' => null,
                'source_created_by' => null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        return $rows;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build the strictKey for matching:
     * UPPER(strip_spaces(item_no)) + "|" + UPPER(strip_nonAlphaNum(from_location)) + "|" + UPPER(strip_nonAlphaNum(to_location))
     */
    public function makeStrictKey(string $itemNo, string $fromLocation, string $toLocation): string
    {
        $normalizedItem = strtoupper(preg_replace('/\s+/', '', $itemNo));
        $normalizedFrom = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $fromLocation));
        $normalizedTo   = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $toLocation));
        return $normalizedItem . '|' . $normalizedFrom . '|' . $normalizedTo;
    }

    /**
     * Compute qty check: SAME / DIFF / N/A
     */
    private function computeQtyCheck(?float $qtyReport, float $qtyAnalysis): string
    {
        if ($qtyReport === null) {
            return 'N/A';
        }
        return abs($qtyReport - $qtyAnalysis) < 0.0001 ? 'SAME' : 'DIFF';
    }

    /**
     * Build a flat record row for the ANALYSIS record type.
     */
    private function buildAnalysisRow(
        string $batchId,
        string $reportDate,
        string $reportShift,
        string $category,
        string $itemNo,
        ?string $itemDescription,
        string $fromLocation,
        string $toLocation,
        ?string $taskKey,
        ?string $groupKey,
        ?float $qtyReport,
        float $qtyAnalysis,
        string $qtyCheck,
        string $status,
        bool $countsAsTask,
        bool $taskDone,
        ?string $tagValidation,
        ?string $storage,
        string $now,
        ?string $sourceCreatedAt = null,
        ?string $sourceCreatedBy = null
    ): array {
        return [
            'batch_id'          => $batchId,
            'report_date'       => $reportDate,
            'report_shift'      => $reportShift,
            'category'          => $category,
            'record_type'       => 'ANALYSIS',
            'item_no'           => $itemNo,
            'item_description'  => $itemDescription,
            'from_location'     => $fromLocation,
            'to_location'       => $toLocation,
            'task_key'          => $taskKey,
            'group_key'         => $groupKey,
            'qty_report'        => $qtyReport,
            'qty_analysis'      => $qtyAnalysis,
            'qty_check'         => $qtyCheck,
            'status'            => $status,
            'counts_as_task'    => $countsAsTask,
            'task_done'         => $taskDone,
            'tag_validation'    => $tagValidation,
            'storage'           => $storage,
            'source_expired'    => null,
            'source_created_at' => $sourceCreatedAt,
            'source_created_by' => $sourceCreatedBy,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];
    }
}
