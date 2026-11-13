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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('branch_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('shift_name'); // e.g. SHIFT 1, SHIFT 2, SHIFT 3
            $table->string('start_time'); // e.g. '06:00'
            $table->string('end_time');   // e.g. '14:00'
            $table->string('cutoff_time')->nullable(); // e.g. '14:00' (batas akhir transaksi analisa replenishment)
            $table->integer('pre_shift_minutes')->default(120); // Window toleransi cetak report (menit)
            $table->string('normalized_shift')->nullable(); // e.g. 'SHIFT 2' if normalized
            $table->boolean('crosses_midnight')->default(false); // e.g. 22:00 s/d 06:00
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
        });

        Schema::table('imports', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::dropIfExists('branch_shifts');
        Schema::dropIfExists('branches');
    }
};
