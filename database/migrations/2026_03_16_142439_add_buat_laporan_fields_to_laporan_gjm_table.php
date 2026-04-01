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
            $table->string('program_studi')->nullable()->after('jenis_laporan');
            $table->string('dokumen_path')->nullable()->after('file_laporan');
            $table->unsignedBigInteger('created_by')->nullable()->after('jumlah_laporan_gkm_diterima');
            $table->text('instruksi_prompt')->nullable()->after('created_by');
            
            // Add foreign key for created_by
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_gjm', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['program_studi', 'dokumen_path', 'created_by', 'instruksi_prompt']);
        });
    }
};