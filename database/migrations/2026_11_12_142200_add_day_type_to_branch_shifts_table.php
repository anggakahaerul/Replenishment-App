<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_shifts', function (Blueprint $table) {
            // 'weekday' = Senin - Jumat (Regular), 'saturday' = Khusus Sabtu, 'all' = Setiap Hari
            $table->string('day_type', 20)->default('weekday')->after('shift_name');
            $table->index(['branch_id', 'day_type', 'is_active'], 'bs_branch_day_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('branch_shifts', function (Blueprint $table) {
            $table->dropIndex('bs_branch_day_active_idx');
            $table->dropColumn('day_type');
        });
    }
};

