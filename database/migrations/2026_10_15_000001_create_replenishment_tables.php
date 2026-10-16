<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Locations Master
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('location_code')->unique();
            $table->string('location_name')->nullable();
            $table->string('storage_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Imports Metadata
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('import_type'); // replenishment_report | replenishment_analysis
            $table->string('file_name');
            $table->string('report_date')->nullable();
            $table->string('file_hash')->nullable();
            $table->string('status')->default('completed'); // pending, processing, completed, failed
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['import_type', 'status']);
        });

        // 3. Replenishment Details (From Replenishment Report)
        Schema::create('replenishment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('imports')->cascadeOnDelete();
            $table->string('item_no')->index();
            $table->string('item_description')->nullable();
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('source_location_code')->nullable();
            $table->string('destination_location_code')->nullable();
            $table->text('suggested_sources')->nullable();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->string('unit', 50)->default('CTN');
            $table->decimal('available_qty', 12, 4)->nullable();
            $table->decimal('in_location_qty', 12, 4)->nullable();
            $table->decimal('safety_level', 12, 4)->nullable();
            $table->decimal('max_level', 12, 4)->nullable();
            $table->unsignedInteger('row_number');
            $table->timestamps();

            $table->index(['item_no', 'destination_location_code']);
        });

        // 4. Replenishment Analysis Details (From Analisa Replenishment)
        Schema::create('replenishment_analysis_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('imports')->cascadeOnDelete();
            $table->string('item_no')->index();
            $table->string('item_description')->nullable();
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('source_location_code')->nullable();
            $table->string('destination_location_code')->nullable();
            $table->decimal('whole_qty', 12, 4)->default(0);
            $table->string('whole_uom', 50)->nullable();
            $table->decimal('loose_qty', 12, 4)->nullable();
            $table->string('loose_uom', 50)->nullable();
            $table->decimal('ratio', 12, 4)->nullable();
            $table->string('storage', 50)->nullable();
            $table->string('tag_validation', 100)->nullable();
            $table->string('tran_date', 50)->nullable();
            $table->unsignedInteger('row_number');
            $table->timestamps();

            $table->index(['item_no', 'destination_location_code']);
        });

        // 5. Reconciliation Runs
        Schema::create('reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_import_id')->constrained('imports')->cascadeOnDelete();
            $table->foreignId('analysis_import_id')->constrained('imports')->cascadeOnDelete();
            $table->string('status')->default('completed'); // pending, processing, completed, failed
            $table->json('summary')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 6. Reconciliation Results
        Schema::create('reconciliation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('reconciliation_runs')->cascadeOnDelete();
            $table->foreignId('replenishment_detail_id')->nullable()->constrained('replenishment_details')->nullOnDelete();
            $table->foreignId('analysis_detail_id')->nullable()->constrained('replenishment_analysis_details')->nullOnDelete();
            $table->string('sku')->index();
            $table->string('nama_produk')->nullable();
            $table->string('from_location_report')->nullable();
            $table->string('from_location_report_type')->nullable();
            $table->string('from_location_analysis')->nullable();
            $table->string('from_location_analysis_type')->nullable();
            $table->string('to_location')->nullable();
            $table->string('to_location_type')->nullable();
            $table->decimal('qty_replenish', 12, 4)->nullable();
            $table->string('qty_replenish_unit', 50)->default('CTN');
            $table->decimal('qty_analysis', 12, 4)->nullable();
            $table->string('qty_analysis_unit', 50)->default('CTN');
            $table->decimal('difference', 12, 4)->nullable();
            $table->string('status', 50); // matched, unmatch, melebihi_poc, duplicate_or_ambiguous, report_only, analysis_only
            $table->text('notes')->nullable();
            $table->unsignedInteger('row_order')->default(0);
            $table->timestamps();

            $table->index(['run_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_results');
        Schema::dropIfExists('reconciliation_runs');
        Schema::dropIfExists('replenishment_analysis_details');
        Schema::dropIfExists('replenishment_details');
        Schema::dropIfExists('imports');
        Schema::dropIfExists('locations');
    }
};
