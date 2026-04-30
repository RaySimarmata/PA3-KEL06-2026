<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_gkm', function (Blueprint $table) {
            // ajaran_id dan dosen_ketua tidak wajib untuk laporan artefak
            if (Schema::hasColumn('laporan_gkm', 'ajaran_id')) {
                $table->unsignedBigInteger('ajaran_id')->nullable()->change();
            }
            if (Schema::hasColumn('laporan_gkm', 'dosen_ketua')) {
                $table->unsignedBigInteger('dosen_ketua')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('laporan_gkm', function (Blueprint $table) {
            if (Schema::hasColumn('laporan_gkm', 'ajaran_id')) {
                $table->unsignedBigInteger('ajaran_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('laporan_gkm', 'dosen_ketua')) {
                $table->unsignedBigInteger('dosen_ketua')->nullable(false)->change();
            }
        });
    }
};
