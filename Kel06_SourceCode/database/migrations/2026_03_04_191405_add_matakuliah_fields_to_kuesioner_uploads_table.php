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
        Schema::table('kuesioner_uploads', function (Blueprint $table) {
            $table->string('nama_matakuliah')->nullable()->after('periode');
            $table->string('kode_matakuliah')->nullable()->after('nama_matakuliah');
            $table->string('dosen_pengampu')->nullable()->after('kode_matakuliah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kuesioner_uploads', function (Blueprint $table) {
            $table->dropColumn(['nama_matakuliah', 'kode_matakuliah', 'dosen_pengampu']);
        });
    }
};
