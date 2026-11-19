<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const MENU_PERMISSIONS = [
        'dashboard' => [
            'label'   => 'Dashboard',
            'actions' => [
                'view'   => 'Lihat Dashboard',
                'delete' => 'Hapus Batch Matching',
            ],
        ],
        'matching' => [
            'label'   => 'Replenishment Matching',
            'actions' => [
                'view'   => 'Lihat Halaman & History',
                'create' => 'Upload & Jalankan Matching',
                'delete' => 'Hapus Riwayat Matching',
            ],
        ],
        'report_history' => [
            'label'   => 'Replenishment Report History',
            'actions' => [
                'view'     => 'Lihat Daftar & Detail',
                'download' => 'Unduh File Excel',
            ],
        ],
        'analysis_history' => [
            'label'   => 'Analysis Replenishment History',
            'actions' => [
                'view'     => 'Lihat Daftar & Detail',
                'download' => 'Unduh File Excel',
            ],
        ],
        'master_branches' => [
            'label'   => 'Master Cabang',
            'actions' => [
                'view'   => 'Lihat Cabang',
                'create' => 'Tambah Cabang',
                'edit'   => 'Edit & Toggle Cabang',
                'delete' => 'Hapus Cabang',
            ],
        ],
        'master_shifts' => [
            'label'   => 'Master Data Shift',
            'actions' => [
                'view'   => 'Lihat Jadwal Shift',
                'create' => 'Tambah Shift',
                'edit'   => 'Edit Aturan Shift',
                'delete' => 'Hapus Shift',
            ],
        ],
        'users' => [
            'label'   => 'User & Role Management',
            'actions' => [
                'view'   => 'Lihat User & Role',
                'create' => 'Tambah User',
                'edit'   => 'Edit User & Permission Role',
                'delete' => 'Hapus User',
            ],
        ],
        'logs' => [
            'label'   => 'Application Log',
            'actions' => [
                'view'   => 'Lihat Log Aktivitas',
                'delete' => 'Bersihkan Log',
            ],
        ],
    ];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'permissions',
        'is_system',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system'   => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }

    /**
     * Build a full permission matrix with all actions set to the given default.
     */
    public static function buildMatrix(bool $default = false): array
    {
        $matrix = [];
        foreach (self::MENU_PERMISSIONS as $menu => $meta) {
            $matrix[$menu] = [];
            foreach (array_keys($meta['actions']) as $action) {
                $matrix[$menu][$action] = $default;
            }
        }
        return $matrix;
    }

    /**
     * Default definitions for the 4 core roles.
     */
    public static function defaultDefinitions(): array
    {
        $admin = self::buildMatrix(true);

        $operator = self::buildMatrix(false);
        $operator['dashboard']        = ['view' => true, 'delete' => true];
        $operator['matching']         = ['view' => true, 'create' => true, 'delete' => true];
        $operator['report_history']   = ['view' => true, 'download' => true];
        $operator['analysis_history'] = ['view' => true, 'download' => true];

        // Supervisor: hanya bisa melihat semua menu kecuali User & Application Log
        $supervisor = self::buildMatrix(false);
        $supervisor['dashboard']        = ['view' => true, 'delete' => false];
        $supervisor['matching']         = ['view' => true, 'create' => false, 'delete' => false];
        $supervisor['report_history']   = ['view' => true, 'download' => true];
        $supervisor['analysis_history'] = ['view' => true, 'download' => true];
        $supervisor['master_branches']  = ['view' => true, 'create' => false, 'edit' => false, 'delete' => false];
        $supervisor['master_shifts']    = ['view' => true, 'create' => false, 'edit' => false, 'delete' => false];

        // Manager: bisa melihat semua menu kecuali User & Application Log, dan bisa menghapus
        $manager = self::buildMatrix(false);
        $manager['dashboard']        = ['view' => true, 'delete' => true];
        $manager['matching']         = ['view' => true, 'create' => false, 'delete' => true];
        $manager['report_history']   = ['view' => true, 'download' => true];
        $manager['analysis_history'] = ['view' => true, 'download' => true];
        $manager['master_branches']  = ['view' => true, 'create' => false, 'edit' => false, 'delete' => true];
        $manager['master_shifts']    = ['view' => true, 'create' => false, 'edit' => false, 'delete' => true];

        return [
            [
                'slug'        => 'admin',
                'name'        => 'Admin (Super User)',
                'description' => 'Akses penuh ke seluruh menu, master data, user, role, dan log.',
                'permissions' => $admin,
                'is_system'   => true,
            ],
            [
                'slug'        => 'operator',
                'name'        => 'Operator',
                'description' => 'Upload & jalankan matching replenishment serta kelola riwayat matching.',
                'permissions' => $operator,
                'is_system'   => true,
            ],
            [
                'slug'        => 'supervisor',
                'name'        => 'Supervisor',
                'description' => 'Hanya melihat semua menu operasional & master data (tanpa hapus, user, dan log).',
                'permissions' => $supervisor,
                'is_system'   => true,
            ],
            [
                'slug'        => 'manager',
                'name'        => 'Manager',
                'description' => 'Melihat semua menu operasional & master data serta dapat menghapus data (tanpa user dan log).',
                'permissions' => $manager,
                'is_system'   => true,
            ],
        ];
    }

    /**
     * Seed the 4 default roles if not yet present.
     */
    public static function seedDefaults(): void
    {
        foreach (self::defaultDefinitions() as $def) {
            self::updateOrCreate(
                ['slug' => $def['slug']],
                [
                    'name'        => $def['name'],
                    'description' => $def['description'],
                    'permissions' => $def['permissions'],
                    'is_system'   => $def['is_system'],
                ]
            );
        }
    }
}
