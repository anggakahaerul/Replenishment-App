<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\Role::seedDefaults();

        User::updateOrCreate(
            ['email' => 'admin@replenishment.test'],
            [
                'name' => 'Admin Gudang',
                'password' => bcrypt('password'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        User::updateOrCreate(
            ['email' => 'operator@replenishment.test'],
            [
                'name' => 'Operator Replenishment',
                'password' => bcrypt('password'),
                'role' => User::ROLE_OPERATOR,
            ]
        );

        User::updateOrCreate(
            ['email' => 'supervisor@replenishment.test'],
            [
                'name' => 'Supervisor Gudang',
                'password' => bcrypt('password'),
                'role' => User::ROLE_SUPERVISOR,
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@replenishment.test'],
            [
                'name' => 'Manager Operasional',
                'password' => bcrypt('password'),
                'role' => User::ROLE_MANAGER,
            ]
        );

        // Seed Branches & Shifts Master Data
        $dcNorth = \App\Models\Branch::updateOrCreate(
            ['code' => 'DC-NORTH'],
            [
                'name' => 'DC North 4 - Cikarang',
                'timezone' => 'Asia/Jakarta',
                'description' => 'Hub logistik utama regional Jabodetabek & Jawa Barat',
                'is_active' => true,
            ]
        );

        \App\Models\BranchShift::updateOrCreate(
            ['branch_id' => $dcNorth->id, 'shift_name' => 'SHIFT 1'],
            [
                'start_time' => '06:00',
                'end_time' => '14:00',
                'cutoff_time' => '14:00',
                'pre_shift_minutes' => 120,
                'normalized_shift' => 'SHIFT 1',
                'crosses_midnight' => false,
                'is_active' => true,
            ]
        );

        \App\Models\BranchShift::updateOrCreate(
            ['branch_id' => $dcNorth->id, 'shift_name' => 'SHIFT 2'],
            [
                'start_time' => '14:00',
                'end_time' => '22:00',
                'cutoff_time' => '22:00',
                'pre_shift_minutes' => 120,
                'normalized_shift' => 'SHIFT 2',
                'crosses_midnight' => false,
                'is_active' => true,
            ]
        );

        \App\Models\BranchShift::updateOrCreate(
            ['branch_id' => $dcNorth->id, 'shift_name' => 'SHIFT 3'],
            [
                'start_time' => '22:00',
                'end_time' => '06:00',
                'cutoff_time' => '06:00',
                'pre_shift_minutes' => 120,
                'normalized_shift' => 'SHIFT 2', // Normalisasi shift 3 ke shift 2
                'crosses_midnight' => true,
                'is_active' => true,
            ]
        );

        // Branch 2: JKT-GI
        // Update any old DC-JKT or create JKT-GI
        $jktBranch = \App\Models\Branch::where('code', 'DC-JKT')->orWhere('code', 'JKT-GI')->first();
        if ($jktBranch) {
            $jktBranch->update([
                'code' => 'JKT-GI',
                'name' => 'JKT-GI',
                'description' => 'Fasilitas Replenishment JKT-GI',
            ]);
        } else {
            $jktBranch = \App\Models\Branch::create([
                'code' => 'JKT-GI',
                'name' => 'JKT-GI',
                'timezone' => 'Asia/Jakarta',
                'description' => 'Fasilitas Replenishment JKT-GI',
                'is_active' => true,
            ]);
        }

        \App\Models\BranchShift::updateOrCreate(
            ['branch_id' => $jktBranch->id, 'shift_name' => 'SHIFT 1'],
            [
                'start_time' => '07:00',
                'end_time' => '15:00',
                'cutoff_time' => '15:00',
                'pre_shift_minutes' => 60,
                'normalized_shift' => 'SHIFT 1',
                'crosses_midnight' => false,
                'is_active' => true,
            ]
        );

        \App\Models\BranchShift::updateOrCreate(
            ['branch_id' => $jktBranch->id, 'shift_name' => 'SHIFT 2'],
            [
                'start_time' => '15:00',
                'end_time' => '23:00',
                'cutoff_time' => '23:00',
                'pre_shift_minutes' => 60,
                'normalized_shift' => 'SHIFT 2',
                'crosses_midnight' => false,
                'is_active' => true,
            ]
        );

        // Branch 3: SDA-CT
        // Update any old DC-SBY or create SDA-CT
        $sdaBranch = \App\Models\Branch::where('code', 'DC-SBY')->orWhere('code', 'SDA-CT')->first();
        if ($sdaBranch) {
            $sdaBranch->update([
                'code' => 'SDA-CT',
                'name' => 'SDA-CT',
                'description' => 'Fasilitas Replenishment SDA-CT',
            ]);
        } else {
            $sdaBranch = \App\Models\Branch::create([
                'code' => 'SDA-CT',
                'name' => 'SDA-CT',
                'timezone' => 'Asia/Jakarta',
                'description' => 'Fasilitas Replenishment SDA-CT',
                'is_active' => true,
            ]);
        }

        \App\Models\BranchShift::updateOrCreate(
            ['branch_id' => $sdaBranch->id, 'shift_name' => 'SHIFT 1', 'day_type' => 'weekday'],
            [
                'start_time' => '06:00',
                'end_time' => '14:00',
                'cutoff_time' => '14:00',
                'pre_shift_minutes' => 120,
                'normalized_shift' => 'SHIFT 1',
                'crosses_midnight' => false,
                'is_active' => true,
            ]
        );

        \App\Models\BranchShift::updateOrCreate(
            ['branch_id' => $sdaBranch->id, 'shift_name' => 'SHIFT 2', 'day_type' => 'weekday'],
            [
                'start_time' => '14:00',
                'end_time' => '22:00',
                'cutoff_time' => '22:00',
                'pre_shift_minutes' => 120,
                'normalized_shift' => 'SHIFT 2',
                'crosses_midnight' => false,
                'is_active' => true,
            ]
        );

        \App\Models\BranchShift::updateOrCreate(
            ['branch_id' => $sdaBranch->id, 'shift_name' => 'SHIFT 3', 'day_type' => 'weekday'],
            [
                'start_time' => '22:00',
                'end_time' => '06:00',
                'cutoff_time' => '06:00',
                'pre_shift_minutes' => 120,
                'normalized_shift' => 'SHIFT 2',
                'crosses_midnight' => true,
                'is_active' => true,
            ]
        );

        // Jadwal Khusus Hari Sabtu untuk SDA-CT
        \App\Models\BranchShift::updateOrCreate(
            ['branch_id' => $sdaBranch->id, 'shift_name' => 'SHIFT 1', 'day_type' => 'saturday'],
            [
                'start_time' => '06:00',
                'end_time' => '11:00',
                'cutoff_time' => '11:00',
                'pre_shift_minutes' => 120,
                'normalized_shift' => 'SHIFT 1',
                'crosses_midnight' => false,
                'is_active' => true,
            ]
        );

        \App\Models\BranchShift::updateOrCreate(
            ['branch_id' => $sdaBranch->id, 'shift_name' => 'SHIFT 2', 'day_type' => 'saturday'],
            [
                'start_time' => '11:00',
                'end_time' => '16:00',
                'cutoff_time' => '16:00',
                'pre_shift_minutes' => 120,
                'normalized_shift' => 'SHIFT 2',
                'crosses_midnight' => false,
                'is_active' => true,
            ]
        );

        \App\Models\BranchShift::updateOrCreate(
            ['branch_id' => $sdaBranch->id, 'shift_name' => 'SHIFT 3', 'day_type' => 'saturday'],
            [
                'start_time' => '16:00',
                'end_time' => '21:00',
                'cutoff_time' => '21:00',
                'pre_shift_minutes' => 120,
                'normalized_shift' => 'SHIFT 2',
                'crosses_midnight' => false,
                'is_active' => true,
            ]
        );

        // Seed initial activity logs
        if (\App\Models\ActivityLog::count() === 0) {
            $admin = User::where('email', 'admin@replenishment.test')->first();
            $operator = User::where('email', 'operator@replenishment.test')->first();

            \App\Models\ActivityLog::create([
                'user_id' => $admin?->id,
                'user_name' => $admin?->name ?? 'Admin Gudang',
                'action' => \App\Models\ActivityLog::ACTION_SYSTEM,
                'level' => \App\Models\ActivityLog::LEVEL_INFO,
                'description' => 'Sistem Replenishment Apps diinisialisasi dan siap digunakan.',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'context' => ['version' => '1.0.0', 'environment' => 'production'],
                'created_at' => now()->subHours(5),
            ]);

            \App\Models\ActivityLog::create([
                'user_id' => $admin?->id,
                'user_name' => $admin?->name ?? 'Admin Gudang',
                'action' => \App\Models\ActivityLog::ACTION_LOGIN,
                'level' => \App\Models\ActivityLog::LEVEL_SUCCESS,
                'description' => 'Pengguna Admin Gudang (admin@replenishment.test) berhasil masuk ke sistem.',
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'context' => ['role' => 'admin'],
                'created_at' => now()->subHours(4),
            ]);

            \App\Models\ActivityLog::create([
                'user_id' => $operator?->id,
                'user_name' => $operator?->name ?? 'Operator Replenishment',
                'action' => \App\Models\ActivityLog::ACTION_LOGIN,
                'level' => \App\Models\ActivityLog::LEVEL_SUCCESS,
                'description' => 'Pengguna Operator Replenishment (operator@replenishment.test) berhasil masuk ke sistem.',
                'ip_address' => '192.168.1.15',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'context' => ['role' => 'operator'],
                'created_at' => now()->subHours(3),
            ]);

            \App\Models\ActivityLog::create([
                'user_id' => $operator?->id,
                'user_name' => $operator?->name ?? 'Operator Replenishment',
                'action' => \App\Models\ActivityLog::ACTION_IMPORT,
                'level' => \App\Models\ActivityLog::LEVEL_SUCCESS,
                'description' => 'Mengunggah & rekonsiliasi data: Replenishment_Report_191026.xlsx & Analisa_Replenishment_191026.xlsx. Hasil: 142 matched, 16 pending.',
                'ip_address' => '192.168.1.15',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'context' => [
                    'report_filename' => 'Replenishment_Report_191026.xlsx',
                    'analysis_filename' => 'Analisa_Replenishment_191026.xlsx',
                    'match_count' => 142,
                    'pending_count' => 16,
                    'percentage' => 89.87,
                ],
                'created_at' => now()->subHours(2),
            ]);

            \App\Models\ActivityLog::create([
                'user_id' => $operator?->id,
                'user_name' => $operator?->name ?? 'Operator Replenishment',
                'action' => \App\Models\ActivityLog::ACTION_DOWNLOAD,
                'level' => \App\Models\ActivityLog::LEVEL_INFO,
                'description' => "Mengunduh berkas fisik 'Replenishment_Report_191026.xlsx' (ID Import: 1, Tipe: replenishment_report).",
                'ip_address' => '192.168.1.15',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'context' => ['import_id' => 1, 'file_name' => 'Replenishment_Report_191026.xlsx'],
                'created_at' => now()->subMinutes(45),
            ]);
        }
    }
}
