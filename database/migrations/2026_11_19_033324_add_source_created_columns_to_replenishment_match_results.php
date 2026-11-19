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
        Schema::table('replenishment_match_results', function (Blueprint $table) {
            $table->timestamp('source_created_at')->nullable()->after('source_expired');
            $table->string('source_created_by', 100)->nullable()->after('source_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('replenishment_match_results', function (Blueprint $table) {
            $table->dropColumn(['source_created_at', 'source_created_by']);
        });
    }
};
