<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('laporan_bulanan')) {
            DB::statement("ALTER TABLE `laporan_bulanan` MODIFY `periode` VARCHAR(100) NOT NULL");
            DB::statement("ALTER TABLE `laporan_bulanan` MODIFY `bulan` VARCHAR(100) NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('laporan_bulanan')) {
            DB::statement("ALTER TABLE `laporan_bulanan` MODIFY `periode` VARCHAR(7) NOT NULL");
            DB::statement("ALTER TABLE `laporan_bulanan` MODIFY `bulan` VARCHAR(20) NOT NULL");
        }
    }
};
