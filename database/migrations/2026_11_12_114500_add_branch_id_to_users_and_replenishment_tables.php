<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add branch_id to users (null = akses semua cabang / Global)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('role')->constrained('branches')->nullOnDelete();
        });

        // 2. Add branch_id to replenishment_match_results
        Schema::table('replenishment_match_results', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
        });

        // 3. Add branch_id to replenishment_daily_summaries & update unique index
        Schema::table('replenishment_daily_summaries', function (Blueprint $table) {
            $table->dropUnique(['report_date', 'category']);
        });

        Schema::table('replenishment_daily_summaries', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            $table->unique(['branch_id', 'report_date', 'category'], 'rds_branch_date_cat_unique');
        });
    }

    public function down(): void
    {
        Schema::table('replenishment_daily_summaries', function (Blueprint $table) {
            $table->dropUnique('rds_branch_date_cat_unique');
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            $table->unique(['report_date', 'category']);
        });

        Schema::table('replenishment_match_results', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }
};

