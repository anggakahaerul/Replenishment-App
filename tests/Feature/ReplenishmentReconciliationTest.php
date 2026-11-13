<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\ReplenishmentAnalysisDetail;
use App\Models\ReplenishmentDetail;
use App\Services\Replenishment\ReplenishmentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ReplenishmentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected \App\Models\User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = \App\Models\User::factory()->create([
            'role' => \App\Models\User::ROLE_ADMIN,
        ]);
        $this->actingAs($this->adminUser);
    }

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
        $sheet->setCellValue('B3', $date . ' 08:30:00');
        $sheet->setCellValue('A4', 'Site :');
        $sheet->setCellValue('B4', 'SDA-CT');
        $sheet->setCellValue('C4', 'Print By :');
        $sheet->setCellValue('D4', 'ADMIN');

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

            if (!empty($item['sources'])) {
                $sheet->setCellValue('C' . $currRow, 'Suggested Source');
                $currRow++;
                $sheet->setCellValue('B' . $currRow, 'Location');
                $sheet->setCellValue('C' . $currRow, 'Needed Qty');
                $currRow++;
                foreach ($item['sources'] as $src) {
                    $sheet->setCellValue('B' . $currRow, $src['source_loc']);
                    $sheet->setCellValue('C' . $currRow, $src['qty']);
                    $currRow++;
                }
            } elseif (!empty($item['source_loc'])) {
                $sheet->setCellValue('C' . $currRow, 'Suggested Source');
                $currRow++;
                $sheet->setCellValue('B' . $currRow, 'Location');
                $sheet->setCellValue('C' . $currRow, 'Needed Qty');
                $currRow++;
                $sheet->setCellValue('B' . $currRow, $item['source_loc']);
                $sheet->setCellValue('C' . $currRow, $item['qty']);
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
            $sheet->setCellValue('L' . $currRow, $item['created'] ?? ($date . ' 09:30:00'));
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

        $result = $service->processUpload($reportFile, $analysisFile);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('imports', ['import_type' => 'replenishment_report']);
        $this->assertDatabaseHas('imports', ['import_type' => 'replenishment_analysis']);
        $this->assertDatabaseHas('replenishment_match_results', ['item_no' => '00123']);
        $this->assertDatabaseHas('replenishment_daily_summaries', ['category' => 'DRY']);
        $this->assertDatabaseHas('replenishment_details', ['item_no' => '00123']);
        $this->assertDatabaseHas('replenishment_analysis_details', ['item_no' => '00123']);

        $reportImport = Import::where('import_type', 'replenishment_report')->first();
        $this->assertNotNull($reportImport->file_path);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('local')->exists($reportImport->file_path));

        $downloadResponse = $this->get(route('replenishment.history.download', $reportImport->id));
        $downloadResponse->assertStatus(200);

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
            // Pending / Shortage
            [
                'item_no' => 'SKU02',
                'desc' => 'Shortage Product',
                'destination_loc' => 'D-A-02-1',
                'source_loc' => 'SRC-02',
                'qty' => 30,
            ],
            // Over POC (Needed: 10, Source A: 10, Source B: 15)
            [
                'item_no' => 'SKU03',
                'desc' => 'Over POC Product',
                'destination_loc' => 'D-A-03-1',
                'qty' => 10,
                'sources' => [
                    ['source_loc' => 'SRC-03A', 'qty' => 10],
                    ['source_loc' => 'SRC-03B', 'qty' => 15],
                ],
            ],
            // Report Only (Pending)
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
                'source_loc' => 'SRC-03A',
                'destination_loc' => 'D-A-03-1',
                'qty' => 10,
            ],
            // Analysis Only (Initiative)
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

        $result = $service->processUpload($reportFile, $analysisFile);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('replenishment_match_results', [
            'item_no' => 'SKU01',
            'status' => 'MATCH',
        ]);
        $this->assertDatabaseHas('replenishment_match_results', [
            'item_no' => 'SKU03',
            'status' => 'MELEBIHI POC',
        ]);
        $this->assertDatabaseHas('replenishment_match_results', [
            'item_no' => 'SKU05',
            'status' => 'INITIATIVE',
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
                'source_loc' => 'SRC-99',
                'qty' => 10,
            ]
        ]);
        $analysisPath = $this->createSyntheticAnalysisExcel([
            [
                'item_no' => 'SKU99',
                'desc' => 'Persistent Item',
                'source_loc' => 'SRC-99',
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
        $this->assertEquals(1, $response->json('summary.MATCH'));

        // Hit index GET route to ensure dashboard loads
        $pageResponse = $this->get(route('replenishment.index'));
        $pageResponse->assertStatus(200);

        @unlink($reportPath);
        @unlink($analysisPath);
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

    public function test_it_renders_analytics_dashboard_with_summaries()
    {
        \Illuminate\Support\Facades\DB::table('replenishment_daily_summaries')->insert([
            'report_date' => '2026-10-16',
            'category' => 'DRY',
            'shift_1_total' => 172,
            'shift_1_done' => 162,
            'shift_1_completion' => 94.2,
            'shift_2_total' => 158,
            'shift_2_done' => 142,
            'shift_2_completion' => 89.9,
            'total_tasks' => 330,
            'match_count' => 304,
            'pending_count' => 26,
            'match_percentage' => 92.1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Riwayat Analisis');
        $response->assertSee('Dry');
        $response->assertSee('162');
        $response->assertSee('142');
        $response->assertSee('92.1%');
    }

    public function test_admin_can_access_user_management()
    {
        $response = $this->actingAs($this->adminUser)->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertSee('Manajemen Pengguna');
        $response->assertSee($this->adminUser->name);
    }

    public function test_operator_and_viewer_cannot_access_user_management()
    {
        $operator = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_OPERATOR]);
        $viewer = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_VIEWER]);

        $this->actingAs($operator)->get(route('users.index'))->assertStatus(403);
        $this->actingAs($viewer)->get(route('users.index'))->assertStatus(403);
    }

    public function test_viewer_cannot_upload_replenishment_files()
    {
        $viewer = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_VIEWER]);

        $reportPath = $this->createSyntheticReportExcel([]);
        $analysisPath = $this->createSyntheticAnalysisExcel([]);

        $response = $this->actingAs($viewer)->post(route('replenishment.import'), [
            'report_file' => new UploadedFile($reportPath, 'report.xlsx', null, null, true),
            'analysis_file' => new UploadedFile($analysisPath, 'analysis.xlsx', null, null, true),
        ]);

        $response->assertStatus(403);

        @unlink($reportPath);
        @unlink($analysisPath);
    }

    public function test_admin_can_create_edit_and_delete_user()
    {
        // 1. Create
        $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@replenishment.test',
            'role' => \App\Models\User::ROLE_OPERATOR,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);
        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'budi@replenishment.test', 'role' => 'operator']);

        $user = \App\Models\User::where('email', 'budi@replenishment.test')->first();

        // 2. Update
        $responseUpdate = $this->actingAs($this->adminUser)->put(route('users.update', $user), [
            'name' => 'Budi Santoso Updated',
            'email' => 'budi.new@replenishment.test',
            'role' => \App\Models\User::ROLE_VIEWER,
        ]);
        $responseUpdate->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'budi.new@replenishment.test', 'role' => 'viewer']);

        // 3. Delete
        $responseDelete = $this->actingAs($this->adminUser)->delete(route('users.destroy', $user));
        $responseDelete->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_themselves()
    {
        $response = $this->actingAs($this->adminUser)->delete(route('users.destroy', $this->adminUser));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
    }

    public function test_admin_can_access_application_log()
    {
        \App\Models\ActivityLog::record(
            \App\Models\ActivityLog::ACTION_SYSTEM,
            'Test system log description for admin check',
            ['key' => 'val'],
            \App\Models\ActivityLog::LEVEL_INFO,
            $this->adminUser
        );

        $response = $this->actingAs($this->adminUser)->get(route('logs.index'));
        $response->assertStatus(200);
        $response->assertSee('Application Log');
        $response->assertSee('Test system log description for admin check');
    }

    public function test_operator_and_viewer_cannot_access_application_log()
    {
        $operator = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_OPERATOR]);
        $viewer = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_VIEWER]);

        $this->actingAs($operator)->get(route('logs.index'))->assertStatus(403);
        $this->actingAs($viewer)->get(route('logs.index'))->assertStatus(403);
    }

    public function test_login_and_logout_records_activity_log()
    {
        $this->app['auth']->logout();
        $this->flushSession();

        $user = \App\Models\User::factory()->create([
            'email' => 'logintest@replenishment.test',
            'password' => bcrypt('password123'),
        ]);

        $loginResponse = $this->post(route('login.post'), [
            'email' => 'logintest@replenishment.test',
            'password' => 'password123',
        ]);
        $loginResponse->assertRedirect();
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('activity_logs', [
            'action' => \App\Models\ActivityLog::ACTION_LOGIN,
            'user_name' => $user->name,
            'level' => 'success',
        ]);

        $logoutResponse = $this->post(route('logout'));
        $logoutResponse->assertRedirect(route('login'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => \App\Models\ActivityLog::ACTION_LOGOUT,
            'user_name' => $user->name,
        ]);
    }

    public function test_admin_can_clear_activity_logs()
    {
        $responseClearActivity = $this->actingAs($this->adminUser)->post(route('logs.clear'));
        $responseClearActivity->assertRedirect(route('logs.index'));
        $this->assertDatabaseHas('activity_logs', [
            'action' => \App\Models\ActivityLog::ACTION_SYSTEM,
        ]);
    }
}

