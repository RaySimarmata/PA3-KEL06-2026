<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'vmts' to jenis_laporan enum in laporan_gjm table
        DB::statement("ALTER TABLE laporan_gjm MODIFY COLUMN jenis_laporan ENUM('bulanan', 'triwulan', 'semester', 'tahunan', 'vmts') DEFAULT 'triwulan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to previous enum (remove 'vmts')
        DB::statement("ALTER TABLE laporan_gjm MODIFY COLUMN jenis_laporan ENUM('bulanan', 'triwulan', 'semester', 'tahunan') DEFAULT 'triwulan'");
    }
};
