<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add metadata columns to imports table
        Schema::table('imports', function (Blueprint $table) {
            $table->string('batch_id')->nullable()->after('import_type');
            $table->string('report_shift', 20)->nullable()->after('batch_id');   // SHIFT 1 / SHIFT 2
            $table->string('site', 100)->nullable()->after('report_shift');
            $table->string('printed_by', 100)->nullable()->after('site');
            $table->timestamp('printed_at')->nullable()->after('printed_by');   // Asia/Jakarta stored as UTC
            $table->string('tran_date', 20)->nullable()->after('printed_at');   // yyyy-MM-dd dari analisa
            $table->string('warehouse', 100)->nullable()->after('tran_date');
        });

        // 2. Drop old reconciliation tables (diganti oleh match_results)
        Schema::dropIfExists('reconciliation_results');
        Schema::dropIfExists('reconciliation_runs');

        // 3. Main match results table — setiap baris = 1 record hasil matching
        Schema::create('replenishment_match_results', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->index();
            $table->string('report_date', 20)->index();                  // yyyy-MM-dd
            $table->string('report_shift', 20)->index();                 // SHIFT 1 / SHIFT 2
            $table->string('category', 20)->index();                     // DRY / FROZEN

            // record_type: ANALYSIS (dari analisa) | REPORT_TASK (task dari report yg tidak terpakai)
            $table->string('record_type', 20);

            $table->string('item_no', 100)->index();
            $table->string('item_description', 255)->nullable();
            $table->string('from_location', 100)->nullable();
            $table->string('to_location', 100)->nullable();

            // Identitas task dari Report
            $table->string('task_key', 255)->nullable()->index();        // logicalTaskKey
            $table->string('group_key', 255)->nullable()->index();       // groupKey

            // Quantity
            $table->decimal('qty_report', 12, 4)->nullable();            // qty dari Report
            $table->decimal('qty_analysis', 12, 4)->nullable();          // qty dari Analisa
            $table->string('qty_check', 10)->nullable();                 // SAME / DIFF / N/A

            // Status: MATCH | PENDING | MELEBIHI POC | INITIATIVE | DUPLICATE RAW | PUT AWAY | TRANSFER
            $table->string('status', 50)->index();
            $table->boolean('counts_as_task')->default(false);
            $table->boolean('task_done')->default(false);

            // Data tambahan dari analisa
            $table->string('tag_validation', 100)->nullable();
            $table->string('storage', 50)->nullable();
            $table->string('source_expired', 100)->nullable();

            $table->timestamps();

            $table->index(['report_date', 'report_shift', 'category']);
            $table->index(['report_date', 'category', 'counts_as_task']);
        });

        // 4. Daily summary snapshot — KPI dashboard per (report_date, category)
        Schema::create('replenishment_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('report_date', 20)->index();
            $table->string('category', 20);                              // DRY / FROZEN

            // Per-shift data
            $table->unsignedInteger('shift_1_total')->default(0);
            $table->unsignedInteger('shift_1_done')->default(0);
            $table->decimal('shift_1_completion', 8, 2)->nullable();    // null = BELUM IMPORT

            $table->unsignedInteger('shift_2_total')->default(0);
            $table->unsignedInteger('shift_2_done')->default(0);
            $table->decimal('shift_2_completion', 8, 2)->nullable();    // null = BELUM IMPORT

            // KPI harian (rekonsiliasi lintas shift)
            $table->unsignedInteger('total_tasks')->default(0);
            $table->unsignedInteger('match_count')->default(0);
            $table->unsignedInteger('pending_count')->default(0);
            $table->decimal('match_percentage', 8, 1)->default(0);

            // Counter aktivitas tambahan
            $table->unsignedInteger('initiative_count')->default(0);
            $table->unsignedInteger('put_away_count')->default(0);
            $table->unsignedInteger('transfer_count')->default(0);
            $table->unsignedInteger('duplicate_raw_count')->default(0);
            $table->unsignedInteger('surplus_poc_count')->default(0);

            $table->timestamps();

            $table->unique(['report_date', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replenishment_daily_summaries');
        Schema::dropIfExists('replenishment_match_results');

        // Recreate dropped tables (simplified rollback)
        Schema::create('reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_import_id')->constrained('imports')->cascadeOnDelete();
            $table->foreignId('analysis_import_id')->constrained('imports')->cascadeOnDelete();
            $table->string('status')->default('completed');
            $table->json('summary')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reconciliation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('reconciliation_runs')->cascadeOnDelete();
            $table->string('sku')->index();
            $table->string('status', 50);
            $table->timestamps();
        });

        Schema::table('imports', function (Blueprint $table) {
            $table->dropColumn([
                'batch_id', 'report_shift', 'site', 'printed_by',
                'printed_at', 'tran_date', 'warehouse',
            ]);
        });
    }
};
