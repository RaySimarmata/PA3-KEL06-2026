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
        Schema::table('laporan_gjm', function (Blueprint $table) {
            // Add column to store file details (structure, metadata, sample data)
            $table->json('ai_file_details')->nullable()->after('ai_sections')->comment('File details: structure, metadata, sample data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_gjm', function (Blueprint $table) {
            $table->dropColumn('ai_file_details');
        });
    }
};
