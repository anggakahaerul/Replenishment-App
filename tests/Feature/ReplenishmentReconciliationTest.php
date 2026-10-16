<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\Location;
use App\Models\ReconciliationResult;
use App\Models\ReconciliationRun;
use App\Models\ReplenishmentAnalysisDetail;
use App\Models\ReplenishmentDetail;
use App\Services\Replenishment\ExcelParserService;
use App\Services\Replenishment\ReplenishmentImportService;
use App\Services\Replenishment\ReplenishmentReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ReplenishmentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create a temporary synthetic Replenishment Report Excel.
     */
    private function createSyntheticReportExcel(array $rows, ?string $date = '2026-10-15'): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Metadata rows
        $sheet->setCellValue('B1', 'Replenishment List');
        $sheet->setCellValue('A3', 'Date :');
        $sheet->setCellValue('B3', $date);
        $sheet->setCellValue('A4', 'Site :');
        $sheet->setCellValue('B4', 'SDA-CT');

        // Header row at row 5
        $headers = ['ItemNo', 'Description Item', 'Location', 'Expired', 'Available', 'In Location', 'Safety Level', 'Max Level', 'Refill Needed'];
        foreach ($headers as $colIdx => $header) {
            $colLetter = chr(ord('A') + $colIdx);
            $sheet->setCellValue($colLetter . '5', $header);
        }

        // Data rows
        $currRow = 6;
        foreach ($rows as $item) {
            $sheet->setCellValue('A' . $currRow, $item['item_no']);
            $sheet->setCellValue('B' . $currRow, $item['desc']);
            $sheet->setCellValue('C' . $currRow, $item['destination_loc']);
            $sheet->setCellValue('E' . $currRow, $item['available'] ?? 0);
            $sheet->setCellValue('F' . $currRow, $item['in_loc'] ?? 0);
            $sheet->setCellValue('G' . $currRow, $item['safety'] ?? 0);
            $sheet->setCellValue('H' . $currRow, $item['max'] ?? 100);
            $sheet->setCellValue('I' . $currRow, $item['qty']);
            $currRow++;

            if (!empty($item['source_loc'])) {
                $sheet->setCellValue('C' . $currRow, 'Suggested Source');
                $currRow++;
                $sheet->setCellValue('B' . $currRow, 'Location');
                $sheet->setCellValue('C' . $currRow, 'Needed Qty');
                $currRow++;
                $sheet->setCellValue('B' . $currRow, $item['source_loc']);
                $sheet->setCellValue('C' . $currRow, $item['qty'] . ' CTN');
                $currRow++;
            }
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'rep_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return $tmpPath;
    }

    /**
     * Helper to create a temporary synthetic Analisa Replenishment Excel.
     */
    private function createSyntheticAnalysisExcel(array $rows, ?string $date = '2026-10-15'): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        // Header row at row 1
        $headers = [
            'Tran Date', 'Inventory ID', 'Description', 'Warehouse',
            'Loose Qty', 'Loose UOM', 'Ratio', 'Whole Qty', 'Whole UOM',
            'Location', 'To Location ID', 'Created', 'Storage', 'Tag Validation'
        ];

        foreach ($headers as $colIdx => $header) {
            $colLetter = chr(ord('A') + $colIdx);
            $sheet->setCellValue($colLetter . '1', $header);
        }

        $currRow = 2;
        foreach ($rows as $item) {
            $sheet->setCellValue('A' . $currRow, $date);
            $sheet->setCellValue('B' . $currRow, $item['item_no']);
            $sheet->setCellValue('C' . $currRow, $item['desc']);
            $sheet->setCellValue('D' . $currRow, 'SDA-CT');
            $sheet->setCellValue('E' . $currRow, $item['loose_qty'] ?? ($item['qty'] * 12));
            $sheet->setCellValue('F' . $currRow, 'PAK');
            $sheet->setCellValue('G' . $currRow, 12);
            $sheet->setCellValue('H' . $currRow, $item['qty']);
            $sheet->setCellValue('I' . $currRow, 'CTN');
            $sheet->setCellValue('J' . $currRow, $item['source_loc'] ?? 'RCV DOCK');
            $sheet->setCellValue('K' . $currRow, $item['destination_loc']);
            $sheet->setCellValue('L' . $currRow, now()->toDateTimeString());
            $sheet->setCellValue('M' . $currRow, 'DRY');
            $sheet->setCellValue('N' . $currRow, $item['tag'] ?? 'Replenishment');
            $currRow++;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'ana_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return $tmpPath;
    }

    public function test_it_parses_excel_and_stores_in_database()
    {
        $reportPath = $this->createSyntheticReportExcel([
            [
                'item_no' => '00123',
                'desc' => 'Test Item 1',
                'destination_loc' => 'D-B-28-1',
                'source_loc' => 'PF-A-1-1',
                'qty' => 45,
            ]
        ]);

        $analysisPath = $this->createSyntheticAnalysisExcel([
            [
                'item_no' => '00123',
                'desc' => 'Test Item 1',
                'source_loc' => 'PF-A-1-1',
                'destination_loc' => 'D-B-28-1',
                'qty' => 45,
            ]
        ]);

        $reportFile = new UploadedFile($reportPath, 'report.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $analysisFile = new UploadedFile($analysisPath, 'analysis.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        /** @var ReplenishmentImportService $service */
        $service = app(ReplenishmentImportService::class);

        $reportImport = $service->importReport($reportFile);
        $analysisImport = $service->importAnalysis($analysisFile);

        $this->assertDatabaseHas('imports', ['id' => $reportImport->id, 'import_type' => 'replenishment_report']);
        $this->assertDatabaseHas('imports', ['id' => $analysisImport->id, 'import_type' => 'replenishment_analysis']);

        // Check locations created in unified locations master
        $this->assertDatabaseHas('locations', ['location_code' => 'D-B-28-1']);
        $this->assertDatabaseHas('locations', ['location_code' => 'PF-A-1-1']);

        // Check details
        $this->assertDatabaseHas('replenishment_details', [
            'import_id' => $reportImport->id,
            'item_no' => '00123',
            'destination_location_code' => 'D-B-28-1',
            'quantity' => 45.0000,
        ]);

        @unlink($reportPath);
        @unlink($analysisPath);
    }

    public function test_it_reconciles_matched_mismatched_and_over_poc()
    {
        $reportPath = $this->createSyntheticReportExcel([
            // Matched
            [
                'item_no' => 'SKU01',
                'desc' => 'Exact Match Product',
                'destination_loc' => 'D-A-01-1',
                'source_loc' => 'SRC-01',
                'qty' => 50,
            ],
            // Unmatched (Report has 30, Analisa has 20)
            [
                'item_no' => 'SKU02',
                'desc' => 'Shortage Product',
                'destination_loc' => 'D-A-02-1',
                'source_loc' => 'SRC-02',
                'qty' => 30,
            ],
            // Over POC (Report has 10, Analisa has 25)
            [
                'item_no' => 'SKU03',
                'desc' => 'Over POC Product',
                'destination_loc' => 'D-A-03-1',
                'source_loc' => 'SRC-03',
                'qty' => 10,
            ],
            // Report Only
            [
                'item_no' => 'SKU04',
                'desc' => 'Report Only Product',
                'destination_loc' => 'D-A-04-1',
                'source_loc' => 'SRC-04',
                'qty' => 15,
            ]
        ]);

        $analysisPath = $this->createSyntheticAnalysisExcel([
            [
                'item_no' => 'SKU01',
                'desc' => 'Exact Match Product',
                'source_loc' => 'SRC-01',
                'destination_loc' => 'D-A-01-1',
                'qty' => 50,
            ],
            [
                'item_no' => 'SKU02',
                'desc' => 'Shortage Product',
                'source_loc' => 'SRC-02',
                'destination_loc' => 'D-A-02-1',
                'qty' => 20,
            ],
            [
                'item_no' => 'SKU03',
                'desc' => 'Over POC Product',
                'source_loc' => 'SRC-03',
                'destination_loc' => 'D-A-03-1',
                'qty' => 25,
            ],
            // Analysis Only
            [
                'item_no' => 'SKU05',
                'desc' => 'Analysis Only Product',
                'source_loc' => 'SRC-05',
                'destination_loc' => 'D-A-05-1',
                'qty' => 40,
            ]
        ]);

        $reportFile = new UploadedFile($reportPath, 'report.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $analysisFile = new UploadedFile($analysisPath, 'analysis.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        /** @var ReplenishmentImportService $service */
        $service = app(ReplenishmentImportService::class);

        $reportImport = $service->importReport($reportFile);
        $analysisImport = $service->importAnalysis($analysisFile);

        $result = $service->runMatching($reportImport, $analysisImport);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['summary']['total_rows']);
        $this->assertEquals(1, $result['summary']['matched_rows']);
        $this->assertEquals(3, $result['summary']['unmatched_rows']); // SKU02, SKU04, SKU05
        $this->assertEquals(1, $result['summary']['over_poc_rows']); // SKU03

        // Results persisted to database
        $this->assertDatabaseHas('reconciliation_results', [
            'sku' => 'SKU01',
            'status' => 'matched',
            'qty_replenish' => 50,
            'qty_analysis' => 50,
        ]);

        $this->assertDatabaseHas('reconciliation_results', [
            'sku' => 'SKU03',
            'status' => 'melebihi_poc',
            'qty_replenish' => 10,
            'qty_analysis' => 25,
        ]);

        @unlink($reportPath);
        @unlink($analysisPath);
    }

    public function test_it_persists_reconciliation_and_retrieves_on_page_load()
    {
        $reportPath = $this->createSyntheticReportExcel([
            [
                'item_no' => 'SKU99',
                'desc' => 'Persistent Item',
                'destination_loc' => 'D-Z-99-1',
                'qty' => 10,
            ]
        ]);
        $analysisPath = $this->createSyntheticAnalysisExcel([
            [
                'item_no' => 'SKU99',
                'desc' => 'Persistent Item',
                'destination_loc' => 'D-Z-99-1',
                'qty' => 10,
            ]
        ]);

        $reportFile = new UploadedFile($reportPath, 'report.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $analysisFile = new UploadedFile($analysisPath, 'analysis.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        // Upload through HTTP endpoint
        $response = $this->postJson(route('replenishment.import'), [
            'report_file' => $reportFile,
            'analysis_file' => $analysisFile,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('summary.matched_rows', 1);

        // Now hit index GET route to ensure persisted run is loaded
        $pageResponse = $this->get(route('replenishment.index'));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('SKU99');

        @unlink($reportPath);
        @unlink($analysisPath);
    }

    public function test_it_reconciles_real_shift1_excel_files()
    {
        $realReportPath = 'D:/Replenishment Report/Shift 1/Replenishment Report (2026-10-13) (1).xlsx';
        $realAnalysisPath = 'D:/Replenishment Report/Shift 1/Analisa Replenishment 20261013.xlsx';

        if (!file_exists($realReportPath) || !file_exists($realAnalysisPath)) {
            $this->markTestSkipped('Real Excel files not found on machine.');
        }

        $reportFile = new UploadedFile($realReportPath, 'Replenishment Report (2026-10-13) (1).xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        $analysisFile = new UploadedFile($realAnalysisPath, 'Analisa Replenishment 20261013.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        /** @var ReplenishmentImportService $service */
        $service = app(ReplenishmentImportService::class);

        $reportImport = $service->importReport($reportFile);
        $analysisImport = $service->importAnalysis($analysisFile);

        $this->assertGreaterThan(50, $reportImport->row_count);
        $this->assertGreaterThan(100, $analysisImport->row_count);

        $result = $service->runMatching($reportImport, $analysisImport);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['summary']['total_rows']);
        $this->assertGreaterThan(0, $result['summary']['matched_rows']);

        // Check locations were populated in locations master
        $this->assertGreaterThan(10, Location::count());
    }

    public function test_it_renders_history_and_detail_screens()
    {
        $reportImport = Import::create([
            'import_type' => 'replenishment_report',
            'file_name' => 'TestReport.xlsx',
            'report_date' => '2026-10-15',
            'status' => 'completed',
            'row_count' => 1,
            'imported_at' => now(),
        ]);

        ReplenishmentDetail::create([
            'import_id' => $reportImport->id,
            'item_no' => 'SKU-TEST-1',
            'item_description' => 'Test Product',
            'destination_location_code' => 'D-A-01-1',
            'quantity' => 10,
            'unit' => 'CTN',
            'row_number' => 1,
        ]);

        $analysisImport = Import::create([
            'import_type' => 'replenishment_analysis',
            'file_name' => 'TestAnalysis.xlsx',
            'report_date' => '2026-10-15',
            'status' => 'completed',
            'row_count' => 1,
            'imported_at' => now(),
        ]);

        ReplenishmentAnalysisDetail::create([
            'import_id' => $analysisImport->id,
            'item_no' => 'SKU-TEST-1',
            'item_description' => 'Test Product',
            'source_location_code' => 'RCV DOCK',
            'destination_location_code' => 'D-A-01-1',
            'whole_qty' => 10,
            'whole_uom' => 'CTN',
            'row_number' => 1,
        ]);

        // 1. Report history list
        $responseReport = $this->get(route('replenishment.report-history'));
        $responseReport->assertStatus(200);
        $responseReport->assertSee('TestReport.xlsx');

        // 2. Analysis history list
        $responseAnalysis = $this->get(route('replenishment.analysis-history'));
        $responseAnalysis->assertStatus(200);
        $responseAnalysis->assertSee('TestAnalysis.xlsx');

        // 3. Report detail screen
        $responseReportDetail = $this->get(route('replenishment.history.show', $reportImport->id));
        $responseReportDetail->assertStatus(200);
        $responseReportDetail->assertSee('SKU-TEST-1');
        $responseReportDetail->assertSee('Test Product');

        // 4. Analysis detail screen
        $responseAnalysisDetail = $this->get(route('replenishment.history.show', $analysisImport->id));
        $responseAnalysisDetail->assertStatus(200);
        $responseAnalysisDetail->assertSee('SKU-TEST-1');
        $responseAnalysisDetail->assertSee('RCV DOCK');
    }
}
