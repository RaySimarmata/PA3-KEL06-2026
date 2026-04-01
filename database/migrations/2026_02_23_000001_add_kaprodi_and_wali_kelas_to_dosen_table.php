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
        Schema::table('dosen', function (Blueprint $table) {
            // Field untuk menandai apakah dosen adalah Kaprodi
            $table->boolean('is_kaprodi')->default(false)->after('status');
            
            // Field untuk menandai apakah dosen adalah Dosen Wali
            $table->boolean('is_dosen_wali')->default(false)->after('is_kaprodi');
            
            // Field untuk menyimpan kelas yang dibimbing (jika dosen wali)
            // Format: 41TRPL1, 41TRPL2, 31TI1, 31TK1, dll
            $table->string('kelas_wali')->nullable()->after('is_dosen_wali');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dosen', function (Blueprint $table) {
            $table->dropColumn(['is_kaprodi', 'is_dosen_wali', 'kelas_wali']);
        });
    }
};
