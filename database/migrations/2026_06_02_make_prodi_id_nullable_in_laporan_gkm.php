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
        Schema::table('laporan_gkm', function (Blueprint $table) {
            // Make prodi_id nullable for laporan artefak (institution-wide reports)
            $table->foreignId('prodi_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_gkm', function (Blueprint $table) {
            // Revert prodi_id to not nullable
            $table->foreignId('prodi_id')->nullable(false)->change();
        });
    }
};
