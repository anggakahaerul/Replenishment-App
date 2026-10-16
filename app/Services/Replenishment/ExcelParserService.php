<?php

namespace App\Services\Replenishment;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelParserService
{
    /**
     * Parse Replenishment Report (.xlsx/.xls)
     *
     * In Replenishment Report:
     * - Row 1-4 contains title & metadata (Date, Site)
     * - Row 5 contains headers: ItemNo, Description Item, Location, Expired, Available, In Location, Safety Level, Max Level, Refill Needed
     * - Main row has ItemNo, Description, Location (Destination Pick Face), Refill Needed (Qty)
     * - Sub-rows under it may specify 'Suggested Source' with Location (Source Rack) and Needed Qty.
     *
     * @param string $filePath
     * @return array{report_date: ?string, rows: array<int, array>}
     */
    public function parseReplenishmentReport(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $reportDate = $this->extractReportDateFromReportSheet($sheet);
        $headerRow = $this->detectHeaderRow($sheet, ['itemno', 'description item', 'refill needed', 'item no', 'sku']);

        if (!$headerRow) {
            // Fallback header search
            $headerRow = 5;
        }

        $highestRow = $sheet->getHighestRow();
        $blocks = [];
        $currentBlock = null;

        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $colA = trim((string)$sheet->getCell('A' . $r)->getValue());
            $colB = trim((string)$sheet->getCell('B' . $r)->getValue());
            $colC = trim((string)$sheet->getCell('C' . $r)->getValue());

            // Main row has numeric or alphanumeric ItemNo in Column A
            if ($colA !== '' && (is_numeric($colA) || strlen($colA) >= 3)) {
                // If we hit a new SKU and previous block already has sources collected
                if ($currentBlock !== null && $currentBlock['sku'] !== $colA && !empty($currentBlock['sources'])) {
                    $blocks[] = $currentBlock;
                    $currentBlock = null;
                }

                if ($currentBlock === null || $currentBlock['sku'] !== $colA) {
                    if ($currentBlock !== null) {
                        $blocks[] = $currentBlock;
                    }
                    $currentBlock = [
                        'sku' => $colA,
                        'desc' => $colB,
                        'destinations' => [],
                        'sources' => [],
                    ];
                }

                $currentBlock['destinations'][] = [
                    'row_number' => $r,
                    'item_no' => $colA,
                    'description' => $colB,
                    'destination_location' => $colC,
                    'quantity' => (float)$sheet->getCell('I' . $r)->getValue(),
                    'unit' => 'CTN',
                    'available_qty' => (float)$sheet->getCell('E' . $r)->getValue(),
                    'in_location_qty' => (float)$sheet->getCell('F' . $r)->getValue(),
                    'safety_level' => (float)$sheet->getCell('G' . $r)->getValue(),
                    'max_level' => (float)$sheet->getCell('H' . $r)->getValue(),
                ];
            } elseif ($colB === 'Location' || $colC === 'Suggested Source' || str_starts_with($colB, 'Total')) {
                continue;
            } elseif ($currentBlock !== null && $colB !== '' && $colB !== 'Location') {
                // Suggested source row: Col B is Source Location, Col C is Needed Qty
                $currentBlock['sources'][] = [
                    'row_number' => $r,
                    'source_location' => $colB,
                    'qty' => $sheet->getCell('C' . $r)->getValue(),
                ];
            }
        }

        if ($currentBlock !== null) {
            $blocks[] = $currentBlock;
        }

        // Flatten blocks: map sources to destinations accurately without leaving blanks
        $rows = [];
        foreach ($blocks as $block) {
            $sources = $block['sources'];
            $sourcePool = array_values(array_unique(array_filter(array_column($sources, 'source_location'))));

            foreach ($block['destinations'] as $idx => $dest) {
                if (count($sources) === 1) {
                    // All destinations draw from the single suggested source rack
                    $assignedSource = $sources[0]['source_location'];
                } elseif (count($sources) > 1) {
                    // M:N block - map sequentially by index, or fallback to first/pool
                    $assignedSource = $sources[$idx]['source_location'] ?? $sources[count($sources) - 1]['source_location'];
                } else {
                    $assignedSource = null;
                }

                $dest['source_location'] = $assignedSource;
                $dest['suggested_sources'] = $sourcePool;
                $rows[] = $dest;
            }
        }

        return [
            'report_date' => $reportDate,
            'rows' => $rows,
        ];
    }

    /**
     * Parse Analisa Replenishment (.xlsx/.xls)
     *
     * Sheet: 'Data' (or first sheet)
     * Headers usually on row 1:
     * Tran Date, Inventory ID, Description, Warehouse, Loose Qty, Loose UOM, Ratio, Whole Qty, Whole UOM, Location, To Location ID, Created, Storage, Tag Validation
     *
     * @param string $filePath
     * @return array{report_date: ?string, rows: array<int, array>}
     */
    public function parseAnalisaReplenishment(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('Data') ?? $spreadsheet->getActiveSheet();

        $headerRow = $this->detectHeaderRow($sheet, ['inventory id', 'tran date', 'to location id', 'whole qty', 'item no', 'sku']);
        if (!$headerRow) {
            $headerRow = 1;
        }

        // Map column names
        $colMap = $this->mapColumns($sheet, $headerRow, [
            'tran_date' => ['tran date', 'trandate', 'date'],
            'item_no' => ['inventory id', 'inventoryid', 'item no', 'itemno', 'sku'],
            'description' => ['description', 'desc', 'inventory desc'],
            'warehouse' => ['warehouse', 'warehouse id'],
            'loose_qty' => ['loose qty', 'looseqty', 'qty 1'],
            'loose_uom' => ['loose uom', 'unit 1'],
            'ratio' => ['ratio', 'ratio 3'],
            'whole_qty' => ['whole qty', 'wholeqty', 'qty', 'quantity', 'ship qty'],
            'whole_uom' => ['whole uom', 'uom', 'unit'],
            'source_location' => ['location', 'from location', 'from loc', 'locid'],
            'destination_location' => ['to location id', 'to location', 'to loc', 'destination'],
            'storage' => ['storage'],
            'tag_validation' => ['tag validation', 'tag', 'status'],
        ]);

        $highestRow = $sheet->getHighestRow();
        $rows = [];
        $firstDate = null;

        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $itemCol = $colMap['item_no'] ?? null;
            if (!$itemCol) {
                continue;
            }

            $rawItem = $sheet->getCell($itemCol . $r)->getValue();
            if ($rawItem === null || trim((string)$rawItem) === '') {
                continue;
            }

            $itemNo = trim((string)$rawItem);
            $desc = $this->getCellValue($sheet, $colMap['description'] ?? null, $r);
            $sourceLoc = $this->getCellValue($sheet, $colMap['source_location'] ?? null, $r);
            $destLoc = $this->getCellValue($sheet, $colMap['destination_location'] ?? null, $r);
            $wholeQty = (float)($this->getCellValue($sheet, $colMap['whole_qty'] ?? null, $r) ?? 0);
            $wholeUom = $this->getCellValue($sheet, $colMap['whole_uom'] ?? null, $r) ?: 'CTN';
            $looseQty = (float)($this->getCellValue($sheet, $colMap['loose_qty'] ?? null, $r) ?? 0);
            $looseUom = $this->getCellValue($sheet, $colMap['loose_uom'] ?? null, $r);
            $ratio = (float)($this->getCellValue($sheet, $colMap['ratio'] ?? null, $r) ?? 1);
            $storage = $this->getCellValue($sheet, $colMap['storage'] ?? null, $r);
            $tagValidation = $this->getCellValue($sheet, $colMap['tag_validation'] ?? null, $r);
            $tranDateVal = $this->getCellValue($sheet, $colMap['tran_date'] ?? null, $r);

            if ($tranDateVal && !$firstDate) {
                $firstDate = $this->formatExcelDate($tranDateVal);
            }

            $rows[] = [
                'row_number' => $r,
                'item_no' => $itemNo,
                'description' => $desc,
                'source_location' => $sourceLoc,
                'destination_location' => $destLoc,
                'whole_qty' => $wholeQty,
                'whole_uom' => $wholeUom,
                'loose_qty' => $looseQty,
                'loose_uom' => $looseUom,
                'ratio' => $ratio,
                'storage' => $storage,
                'tag_validation' => $tagValidation,
                'tran_date' => $firstDate ?? (string)$tranDateVal,
            ];
        }

        // Also check Parameters sheet for date if available
        $paramSheet = $spreadsheet->getSheetByName('Parameters');
        if ($paramSheet) {
            $paramDate = $paramSheet->getCell('B2')->getValue();
            if ($paramDate) {
                $firstDate = trim((string)$paramDate);
            }
        }

        return [
            'report_date' => $firstDate,
            'rows' => $rows,
        ];
    }

    /**
     * Detect header row by matching expected column tokens.
     */
    private function detectHeaderRow(Worksheet $sheet, array $expectedKeywords): ?int
    {
        for ($r = 1; $r <= min(15, $sheet->getHighestRow()); $r++) {
            $matched = 0;
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
            for ($c = 1; $c <= min(20, $highestColIndex); $c++) {
                $val = strtolower(trim((string)$sheet->getCell([$c, $r])->getValue()));
                foreach ($expectedKeywords as $kw) {
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
     * Map canonical keys to column letters based on aliases.
     */
    private function mapColumns(Worksheet $sheet, int $headerRow, array $aliasMap): array
    {
        $mapped = [];
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());

        // Pass 1: Exact matches
        for ($c = 1; $c <= $highestColIndex; $c++) {
            $val = strtolower(trim((string)$sheet->getCell([$c, $headerRow])->getValue()));
            if ($val === '') continue;

            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);

            foreach ($aliasMap as $canonical => $aliases) {
                if (isset($mapped[$canonical])) continue;

                foreach ($aliases as $alias) {
                    if ($val === $alias) {
                        $mapped[$canonical] = $colLetter;
                        break;
                    }
                }
            }
        }

        // Pass 2: Substring matches for unmapped canonical fields
        for ($c = 1; $c <= $highestColIndex; $c++) {
            $val = strtolower(trim((string)$sheet->getCell([$c, $headerRow])->getValue()));
            if ($val === '') continue;

            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);

            foreach ($aliasMap as $canonical => $aliases) {
                if (isset($mapped[$canonical])) continue;

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

    private function getCellValue(Worksheet $sheet, ?string $colLetter, int $row): ?string
    {
        if (!$colLetter) return null;
        $val = $sheet->getCell($colLetter . $row)->getValue();
        return $val !== null ? trim((string)$val) : null;
    }

    private function extractReportDateFromReportSheet(Worksheet $sheet): ?string
    {
        for ($r = 1; $r <= 4; $r++) {
            for ($c = 'A'; $c <= 'D'; $c++) {
                $val = trim((string)$sheet->getCell($c . $r)->getValue());
                if (str_starts_with(strtolower($val), 'date :') || str_starts_with(strtolower($val), 'date:')) {
                    $dateVal = $sheet->getCell(chr(ord($c) + 1) . $r)->getValue();
                    return $this->formatExcelDate($dateVal);
                }
            }
        }
        return null;
    }

    private function formatExcelDate(mixed $val): ?string
    {
        if (!$val) return null;
        if (is_numeric($val) && (float)$val > 30000 && (float)$val < 60000) {
            try {
                $dt = Date::excelToDateTimeObject((float)$val);
                return $dt->format('Y-m-d');
            } catch (\Throwable) {
                return (string)$val;
            }
        }
        return (string)$val;
    }
}
