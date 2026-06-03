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
            // Drop foreign key first if exists
            if (Schema::hasColumn('laporan_gkm', 'prodi_id')) {
                $table->dropForeign(['prodi_id']);
                $table->dropColumn('prodi_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_gkm', function (Blueprint $table) {
            // Re-add prodi_id column if rollback is needed
            $table->foreignId('prodi_id')->nullable()->after('id')->constrained('prodi')->onDelete('cascade');
        });
    }
};
