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
        Schema::table('imports', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('status')->index();
        });

        Schema::table('import_files', function (Blueprint $table) {
            $table->longText('parsed_payload')->nullable()->after('file_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_files', function (Blueprint $table) {
            $table->dropColumn('parsed_payload');
        });

        Schema::table('imports', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
