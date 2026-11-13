<?php

namespace App\Services\Replenishment;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * In-memory Excel parser for Replenishment Report and Analisa Replenishment.
 * No intermediate DB writes — returns plain PHP arrays.
 */
class ExcelParserService
{
    // ──────────────────────────────────────────────────────────────────────────
    // REPORT PARSER
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Parse a Replenishment Report file fully in-memory.
     *
     * Returns:
     *   metadata: [ printed_at (Carbon|null), site, printed_by, batch_id ]
     *   tasks:    [ item_no, item_description, from_location, to_location,
     *               qty_report, source_expired, task_key (logicalTaskKey),
     *               group_key, group_needed, from_occurrence ]
     *   items:    [ row_number, item_no, item_description, source_location_code,
     *               destination_location_code, suggested_sources, quantity,
     *               unit, available_qty, in_location_qty, safety_level, max_level ]
     *
     * @return array{metadata: array, tasks: list<array>, items: list<array>}
     */
    public function parseReportInMemory(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $metadata = $this->extractReportMetadata($sheet);
        $parsed   = $this->parseReportTasksAndItems($sheet);
        $tasks    = $parsed['tasks'];
        $items    = $parsed['items'];

        return compact('metadata', 'tasks', 'items');
    }

    /**
     * Extract Date, Site, Print By from the top header rows of the Report.
     * Generates batch_id = REP-{yyyyMMdd}-{HHmm}-{SITE}-{PRINTED_BY}
     */
    private function extractReportMetadata(Worksheet $sheet): array
    {
        $printedAt  = null;
        $site       = '';
        $printedBy  = '';

        $maxHeaderRow = min(10, $sheet->getHighestRow());
        $maxCol       = min(10, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn()));

        for ($r = 1; $r <= $maxHeaderRow; $r++) {
            for ($c = 1; $c <= $maxCol; $c++) {
                $val  = trim((string) $sheet->getCell([$c, $r])->getValue());
                $low  = strtolower($val);

                if (str_starts_with($low, 'date') || str_starts_with($low, 'date :')) {
                    // Value is usually in the next cell
                    $dateVal = $sheet->getCell([$c + 1, $r])->getValue();
                    if (!$dateVal) {
                        $dateVal = preg_replace('/^date\s*[:：]\s*/i', '', $val);
                    }
                    $printedAt = $this->parseReportHeaderDate($dateVal);
                }

                if (str_starts_with($low, 'site') || str_starts_with($low, 'site :')) {
                    $siteVal = trim((string) $sheet->getCell([$c + 1, $r])->getValue());
                    if (!$siteVal) {
                        $siteVal = trim(preg_replace('/^site\s*[:：]\s*/i', '', $val));
                    }
                    if ($siteVal) {
                        $site = strtoupper($siteVal);
                    }
                }

                if (str_starts_with($low, 'print by') || str_starts_with($low, 'printed by')) {
                    $pbVal = trim((string) $sheet->getCell([$c + 1, $r])->getValue());
                    if (!$pbVal) {
                        $pbVal = trim(preg_replace('/^print\s*by\s*[:：]\s*/i', '', $val));
                    }
                    if ($pbVal) {
                        $printedBy = $pbVal;
                    }
                }
            }
        }

        $batchId = sprintf(
            'REP-%s-%s-%s-%s',
            $printedAt ? $printedAt->format('Ymd') : now('Asia/Jakarta')->format('Ymd'),
            $printedAt ? $printedAt->format('Hi')  : now('Asia/Jakarta')->format('Hi'),
            preg_replace('/[^A-Z0-9]/', '', strtoupper($site)) ?: 'UNKNOWN',
            preg_replace('/[^A-Z0-9]/', '', strtoupper($printedBy)) ?: 'UNKNOWN'
        );

        return compact('printedAt', 'site', 'printedBy', 'batchId');
    }

    /**
     * Parse Report structure into a flat list of logical tasks.
     *
     * Report structure is hierarchical:
     *   [Item Main Row]  — Col A: item_no, B: desc, C: to_location, I: refill_needed
     *   ... (multiple item main rows with same item_no are grouped)
     *   [Suggested Source marker row] — contains "Suggested Source" text
     *   [Source detail rows] — Col A empty, B: from_location, C: qty, D: source_expired
    /**
     * Parse Report structure into tasks for matching and item rows for database persistence.
     *
     * @return array{tasks: list<array>, items: list<array>}
     */
    private function parseReportTasksAndItems(Worksheet $sheet): array
    {
        $headerRow = $this->detectHeaderRow($sheet, ['item', 'description', 'location', 'refill']) ?? 5;
        $highestRow = $sheet->getHighestRow();

        // Groups of items parsed from the report
        // Each group: [ currentItems[], suggestedSources[], suggestedTotalQty, groupKey ]
        $groups = [];

        /** @var array|null $currentGroup */
        $currentGroup = null;
        $inSuggestedBlock = false;
        $groupIndex = 0;

        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $colA = trim((string) $sheet->getCell('A' . $r)->getValue());
            $colB = trim((string) $sheet->getCell('B' . $r)->getValue());
            $colC = trim((string) $sheet->getCell('C' . $r)->getValue());
            $colD = trim((string) $sheet->getCell('D' . $r)->getValue());
            $colI = $sheet->getCell('I' . $r)->getValue();

            // Detect "Suggested Source" marker
            $rowText = strtolower($colA . $colB . $colC);
            if (str_contains($rowText, 'suggested source')) {
                if ($currentGroup !== null) {
                    $inSuggestedBlock = true;

                    // Check for "Total: <num>" in same row
                    $allCols = '';
                    for ($c = 1; $c <= 15; $c++) {
                        $allCols .= ' ' . trim((string) $sheet->getCell([$c, $r])->getValue());
                    }
                    if (preg_match('/total\s*[:\s]\s*([\d,\.]+)/i', $allCols, $m)) {
                        $currentGroup['suggested_total_qty'] = (float) str_replace(',', '', $m[1]);
                    }
                }
                continue;
            }

            // Detect header/separator rows
            if (
                $colA === '' && $colB === '' ||
                strtolower($colB) === 'location' ||
                str_starts_with(strtolower($colA), 'total')
            ) {
                continue;
            }

            // Main item row: Col A non-empty AND Col I (refill) is set
            $isMainRow = $colA !== '' && $colI !== null && $colI !== '';

            if ($isMainRow) {
                $refillNeeded = (float) $colI;

                if ($currentGroup === null || $inSuggestedBlock) {
                    // Save previous group
                    if ($currentGroup !== null) {
                        $groups[] = $currentGroup;
                        $groupIndex++;
                    }
                    $inSuggestedBlock = false;
                    $currentGroup = [
                        'group_key'           => 'G' . ($groupIndex + 1),
                        'current_items'       => [],
                        'suggested_sources'   => [],
                        'suggested_total_qty' => 0,
                    ];
                }

                $currentGroup['current_items'][] = [
                    'row_number'       => $r,
                    'item_no'          => $colA,
                    'item_description' => $colB,
                    'to_location'      => $colC,
                    'refill_needed'    => $refillNeeded,
                    'available_qty'    => (float) $sheet->getCell('E' . $r)->getValue(),
                    'in_location_qty'  => (float) $sheet->getCell('F' . $r)->getValue(),
                    'safety_level'     => (float) $sheet->getCell('G' . $r)->getValue(),
                    'max_level'        => (float) $sheet->getCell('H' . $r)->getValue(),
                ];
                continue;
            }

            // Source detail row inside a Suggested Source block
            if ($inSuggestedBlock && $currentGroup !== null && $colA === '' && $colB !== '') {
                $cleanC = str_replace(',', '', trim($colC));
                $sourceQty = preg_match('/([\d\.]+)/', $cleanC, $m) ? (float) $m[1] : 0;
                if ($sourceQty <= 0) {
                    continue;
                }
                $currentGroup['suggested_sources'][] = [
                    'from_location'  => $colB,
                    'qty'            => $sourceQty,
                    'source_expired' => $colD ?: null,
                ];
            }
        }

        // Save last group
        if ($currentGroup !== null) {
            $groups[] = $currentGroup;
        }

        return [
            'tasks' => $this->flattenGroupsToTasks($groups),
            'items' => $this->flattenGroupsToReportItems($groups),
        ];
    }

    /**
     * Flatten groups into items formatted for ReplenishmentDetail persistence.
     *
     * @param list<array> $groups
     * @return list<array>
     */
    private function flattenGroupsToReportItems(array $groups): array
    {
        $items = [];
        foreach ($groups as $group) {
            $sources = $group['suggested_sources'] ?? [];
            $sourceCodes = array_values(array_unique(array_filter(array_column($sources, 'from_location'))));
            $primarySource = $sourceCodes[0] ?? null;

            foreach ($group['current_items'] as $item) {
                $items[] = [
                    'row_number'                => $item['row_number'],
                    'item_no'                   => $item['item_no'],
                    'item_description'          => $item['item_description'],
                    'source_location_code'      => $primarySource,
                    'destination_location_code' => $item['to_location'],
                    'suggested_sources'         => $sourceCodes,
                    'quantity'                  => $item['refill_needed'],
                    'unit'                      => 'CTN',
                    'available_qty'             => $item['available_qty'],
                    'in_location_qty'           => $item['in_location_qty'],
                    'safety_level'              => $item['safety_level'],
                    'max_level'                 => $item['max_level'],
                ];
            }
        }

        return $items;
    }

    /**
     * Cross-join each source row with all destination (to_location) rows in the group.
     * Each source row produces exactly 1 logicalTaskKey shared across all its destinations.
     *
     * @param list<array> $groups
     * @return list<array>
     */
    private function flattenGroupsToTasks(array $groups): array
    {
        $tasks = [];

        foreach ($groups as $group) {
            $currentItems     = $group['current_items'];
            $sources          = $group['suggested_sources'];
            $groupKey         = $group['group_key'];
            $suggestedTotal   = $group['suggested_total_qty'] ?? 0;

            // Compute groupNeeded:
            // - use suggestedTotalQty if available
            // - otherwise sum refill_needed of all items in the group
            $groupNeeded = $suggestedTotal > 0
                ? $suggestedTotal
                : array_sum(array_column($currentItems, 'refill_needed'));

            // Determine the item_no (use first item's item_no as group representative)
            $representativeItemNo   = $currentItems[0]['item_no']   ?? '';
            $representativeItemDesc = $currentItems[0]['item_description'] ?? '';

            if (empty($sources)) {
                // Fallback: no suggested source → create one task per to_location
                foreach ($currentItems as $item) {
                    $taskKey = $this->makeLogicalTaskKey(
                        $item['item_no'],
                        'NO SUGGESTED SOURCE',
                        $item['refill_needed'],
                        null,
                        0
                    );
                    $tasks[] = [
                        'item_no'          => $item['item_no'],
                        'item_description' => $item['item_description'],
                        'from_location'    => 'NO SUGGESTED SOURCE',
                        'to_location'      => $item['to_location'],
                        'qty_report'       => $item['refill_needed'],
                        'source_expired'   => null,
                        'task_key'         => $taskKey,
                        'group_key'        => $groupKey,
                        'group_needed'     => $groupNeeded,
                        'from_occurrence'  => 0,
                    ];
                }
                continue;
            }

            // Cross-join: each source row × each to_location
            foreach ($sources as $fromOccurrence => $source) {
                $taskKey = $this->makeLogicalTaskKey(
                    $representativeItemNo,
                    $source['from_location'],
                    $source['qty'],
                    $source['source_expired'],
                    $fromOccurrence
                );

                foreach ($currentItems as $item) {
                    $tasks[] = [
                        'item_no'          => $item['item_no'],
                        'item_description' => $item['item_description'],
                        'from_location'    => $source['from_location'],
                        'to_location'      => $item['to_location'],
                        'qty_report'       => $source['qty'],
                        'source_expired'   => $source['source_expired'],
                        'task_key'         => $taskKey,
                        'group_key'        => $groupKey,
                        'group_needed'     => $groupNeeded,
                        'from_occurrence'  => $fromOccurrence,
                    ];
                }
            }
        }

        return $tasks;
    }

    /**
     * Generate a unique logicalTaskKey per from-source occurrence.
     * Format: lower(item_no)|lower(from_location)|lower(qty)|lower(source_expired)|from-occ-{n}
     */
    private function makeLogicalTaskKey(
        string $itemNo,
        string $fromLocation,
        float $qty,
        ?string $sourceExpired,
        int $occurrence
    ): string {
        return implode('|', [
            strtolower(trim($itemNo)),
            strtolower(trim($fromLocation)),
            strtolower((string) $qty),
            strtolower((string) ($sourceExpired ?? '')),
            'from-occ-' . $occurrence,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // ANALISA PARSER
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Parse an Analisa Replenishment file fully in-memory.
     *
     * Returns:
     *   metadata: [ tran_date (string yyyy-MM-dd|null), warehouse ]
     *   rows:     [ item_no, item_description, from_location, to_location,
     *               whole_qty, whole_uom, loose_qty, loose_uom, ratio,
     *               storage, tag_validation, tran_date, created_at (Carbon|null),
     *               row_number ]
     *
     * @return array{metadata: array, rows: list<array>}
     */
    public function parseAnalysisInMemory(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('Data') ?? $spreadsheet->getActiveSheet();

        $headerRow = $this->detectHeaderRow($sheet, ['inventory id', 'tran date', 'whole qty', 'location']) ?? 1;

        $colMap = $this->mapColumns($sheet, $headerRow, [
            'tran_date'            => ['tran date', 'trandate', 'date'],
            'item_no'              => ['inventory id', 'inventoryid', 'item no', 'itemno', 'sku'],
            'description'          => ['description', 'desc', 'inventory desc'],
            'warehouse'            => ['warehouse', 'warehouse id'],
            'loose_qty'            => ['loose qty', 'looseqty'],
            'loose_uom'            => ['loose uom'],
            'ratio'                => ['ratio'],
            'whole_qty'            => ['whole qty', 'wholeqty', 'qty', 'quantity'],
            'whole_uom'            => ['whole uom', 'uom', 'unit'],
            'from_location'        => ['location', 'from location', 'from loc', 'locid'],
            'to_location'          => ['to location id', 'to location', 'to loc', 'destination'],
            'created'              => ['created', 'created date', 'created datetime', 'created on'],
            'storage'              => ['storage'],
            'tag_validation'       => ['tag validation', 'tag', 'status'],
        ]);

        $highestRow = $sheet->getHighestRow();
        $rows       = [];
        $firstDate  = null;
        $warehouse  = '';

        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $itemCol = $colMap['item_no'] ?? null;
            if (!$itemCol) {
                continue;
            }

            $rawItem = $sheet->getCell($itemCol . $r)->getValue();
            if ($rawItem === null || trim((string) $rawItem) === '') {
                continue;
            }

            $itemNo   = trim((string) $rawItem);
            $tranRaw  = $this->getCellRawValue($sheet, $colMap['tran_date'] ?? null, $r);
            $tranDate = $this->parseAcumaticaDate($tranRaw);
            $createdRaw  = $this->getCellRawValue($sheet, $colMap['created'] ?? null, $r);
            $createdAt   = $this->parseAcumaticaDatetime($createdRaw);

            if ($tranDate && !$firstDate) {
                $firstDate = $tranDate;
            }

            $wh = $this->getCellStringValue($sheet, $colMap['warehouse'] ?? null, $r);
            if ($wh && !$warehouse) {
                $warehouse = strtoupper(trim($wh));
            }

            $rows[] = [
                'row_number'       => $r,
                'item_no'          => $itemNo,
                'item_description' => $this->getCellStringValue($sheet, $colMap['description'] ?? null, $r),
                'from_location'    => $this->getCellStringValue($sheet, $colMap['from_location'] ?? null, $r),
                'to_location'      => $this->getCellStringValue($sheet, $colMap['to_location'] ?? null, $r),
                'whole_qty'        => (float) ($this->getCellStringValue($sheet, $colMap['whole_qty'] ?? null, $r) ?? 0),
                'whole_uom'        => $this->getCellStringValue($sheet, $colMap['whole_uom'] ?? null, $r) ?: 'CTN',
                'loose_qty'        => (float) ($this->getCellStringValue($sheet, $colMap['loose_qty'] ?? null, $r) ?? 0),
                'loose_uom'        => $this->getCellStringValue($sheet, $colMap['loose_uom'] ?? null, $r),
                'ratio'            => (float) ($this->getCellStringValue($sheet, $colMap['ratio'] ?? null, $r) ?? 1),
                'storage'          => $this->getCellStringValue($sheet, $colMap['storage'] ?? null, $r),
                'tag_validation'   => $this->getCellStringValue($sheet, $colMap['tag_validation'] ?? null, $r),
                'tran_date'        => $tranDate,
                'created_at'       => $createdAt,
                'warehouse'        => strtoupper(trim($wh ?? '')),
            ];
        }

        $metadata = [
            'tran_date' => $firstDate,
            'warehouse' => $warehouse,
        ];

        return compact('metadata', 'rows');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DATE PARSING — ACUMATICA RULES
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Convert a numeric Excel serial into Carbon in Asia/Jakarta without UTC offset shift.
     */
    private function excelSerialToJakartaCarbon(float $serial): Carbon
    {
        $dt = ExcelDate::excelToDateTimeObject($serial);
        return Carbon::createFromFormat('Y-m-d H:i:s', $dt->format('Y-m-d H:i:s'), 'Asia/Jakarta');
    }

    /**
     * Parse a date value from the Report header (printed_at).
     * Returns Carbon in Asia/Jakarta or null.
     */
    private function parseReportHeaderDate(mixed $val): ?Carbon
    {
        if ($val === null || $val === '') {
            return null;
        }

        // Numeric Excel serial
        if (is_numeric($val) && (float) $val > 30000) {
            try {
                return $this->excelSerialToJakartaCarbon((float) $val);
            } catch (\Throwable) {
                // fall through
            }
        }

        return $this->parseAcumaticaDatetime((string) $val);
    }

    /**
     * Parse Acumatica date string to yyyy-MM-dd.
     *
     * Rules:
     *  - Numeric Excel serial → convert via PhpSpreadsheet
     *  - String with slash (A/B/YYYY ...):
     *      if A > 12 → DD/MM/YYYY
     *      if A <= 12 → MUST treat as MM/DD/YYYY (Acumatica default)
     */
    public function parseAcumaticaDate(mixed $val): ?string
    {
        if ($val === null || $val === '') {
            return null;
        }

        if (is_numeric($val) && (float) $val > 30000) {
            try {
                return $this->excelSerialToJakartaCarbon((float) $val)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $str = trim((string) $val);

        // Strip time portion if present
        $datePart = preg_split('/[\sT]/', $str)[0] ?? $str;

        if (str_contains($datePart, '/')) {
            $parts = explode('/', $datePart);
            if (count($parts) === 3) {
                [$a, $b, $year] = $parts;
                $aInt = (int) $a;
                if ($aInt > 12) {
                    // DD/MM/YYYY
                    $dt = sprintf('%04d-%02d-%02d', (int) $year, (int) $b, $aInt);
                } else {
                    // MM/DD/YYYY (Acumatica default)
                    $dt = sprintf('%04d-%02d-%02d', (int) $year, $aInt, (int) $b);
                }
                try {
                    return Carbon::createFromFormat('Y-m-d', $dt)->format('Y-m-d');
                } catch (\Throwable) {
                    return null;
                }
            }
        }

        // Attempt generic parse
        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Parse Acumatica datetime to a Carbon object (Asia/Jakarta).
     * Used for the "Created" column in the Analisa file.
     */
    public function parseAcumaticaDatetime(mixed $val): ?Carbon
    {
        if ($val === null || $val === '') {
            return null;
        }

        if (is_numeric($val) && (float) $val > 30000) {
            try {
                return $this->excelSerialToJakartaCarbon((float) $val);
            } catch (\Throwable) {
                return null;
            }
        }

        $str = trim((string) $val);

        // Split date and time
        $segments = preg_split('/[\sT]/', $str, 2);
        $datePart = $segments[0] ?? '';
        $timePart = $segments[1] ?? '00:00:00';

        if (str_contains($datePart, '/')) {
            $parts = explode('/', $datePart);
            if (count($parts) === 3) {
                [$a, $b, $year] = $parts;
                $aInt = (int) $a;
                $yInt = (int) $year;
                if ($yInt < 100) {
                    $yInt += 2000;
                }
                if ($aInt > 12) {
                    $dateFormatted = sprintf('%04d-%02d-%02d', $yInt, (int) $b, $aInt);
                } else {
                    $dateFormatted = sprintf('%04d-%02d-%02d', $yInt, $aInt, (int) $b);
                }
                try {
                    return Carbon::parse($dateFormatted . ' ' . $timePart, 'Asia/Jakarta');
                } catch (\Throwable) {
                    // fall through
                }
            }
        }

        try {
            return Carbon::parse($str, 'Asia/Jakarta');
        } catch (\Throwable) {
            return null;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Detect header row by matching at least 2 of the expected keywords.
     */
    private function detectHeaderRow(Worksheet $sheet, array $keywords): ?int
    {
        $maxRow = min(15, $sheet->getHighestRow());
        $maxCol = min(30, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn()));

        for ($r = 1; $r <= $maxRow; $r++) {
            $matched = 0;
            for ($c = 1; $c <= $maxCol; $c++) {
                $val = strtolower(trim((string) $sheet->getCell([$c, $r])->getValue()));
                foreach ($keywords as $kw) {
                    if (str_contains($val, $kw)) {
                        $matched++;
                        break;
                    }
                }
            }
            if ($matched >= 2) {
                return $r;
            }
        }
        return null;
    }

    /**
     * Map canonical field names to column letters using exact then substring matching.
     *
     * @param array<string, list<string>> $aliasMap
     * @return array<string, string>
     */
    private function mapColumns(Worksheet $sheet, int $headerRow, array $aliasMap): array
    {
        $mapped  = [];
        $maxCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());

        // Pass 1: exact match
        for ($c = 1; $c <= $maxCol; $c++) {
            $val = strtolower(trim((string) $sheet->getCell([$c, $headerRow])->getValue()));
            if ($val === '') {
                continue;
            }
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            foreach ($aliasMap as $canonical => $aliases) {
                if (isset($mapped[$canonical])) {
                    continue;
                }
                if (in_array($val, $aliases, true)) {
                    $mapped[$canonical] = $colLetter;
                }
            }
        }

        // Pass 2: substring match for unmapped fields
        for ($c = 1; $c <= $maxCol; $c++) {
            $val = strtolower(trim((string) $sheet->getCell([$c, $headerRow])->getValue()));
            if ($val === '') {
                continue;
            }
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            foreach ($aliasMap as $canonical => $aliases) {
                if (isset($mapped[$canonical])) {
                    continue;
                }
                foreach ($aliases as $alias) {
                    if (str_contains($val, $alias)) {
                        $mapped[$canonical] = $colLetter;
                        break;
                    }
                }
            }
        }

        return $mapped;
    }

    private function getCellStringValue(Worksheet $sheet, ?string $colLetter, int $row): ?string
    {
        if (!$colLetter) {
            return null;
        }
        $val = $sheet->getCell($colLetter . $row)->getValue();
        return $val !== null ? trim((string) $val) : null;
    }

    private function getCellRawValue(Worksheet $sheet, ?string $colLetter, int $row): mixed
    {
        if (!$colLetter) {
            return null;
        }
        return $sheet->getCell($colLetter . $row)->getValue();
    }
}
