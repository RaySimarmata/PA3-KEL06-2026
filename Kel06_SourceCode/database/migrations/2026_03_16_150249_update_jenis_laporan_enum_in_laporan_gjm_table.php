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
        // Update enum to include 'triwulan'
        DB::statement("ALTER TABLE laporan_gjm MODIFY COLUMN jenis_laporan ENUM('bulanan', 'triwulan', 'semester', 'tahunan') DEFAULT 'triwulan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum
        DB::statement("ALTER TABLE laporan_gjm MODIFY COLUMN jenis_laporan ENUM('bulanan', 'semester', 'tahunan') DEFAULT 'bulanan'");
    }
};