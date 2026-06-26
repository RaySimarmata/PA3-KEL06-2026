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
        Schema::table('document_chunks', function (Blueprint $table) {
            // Add source_type and source_id for flexible source tracking
            $table->string('source_type')->nullable()->after('kuesioner_upload_id')
                ->comment('Type of source: laporan_triwulan, laporan_semester, kuesioner, etc.');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type')
                ->comment('ID of the source record');
            
            // Add index for faster queries
            $table->index(['source_type', 'source_id'], 'idx_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_chunks', function (Blueprint $table) {
            $table->dropIndex('idx_source');
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
