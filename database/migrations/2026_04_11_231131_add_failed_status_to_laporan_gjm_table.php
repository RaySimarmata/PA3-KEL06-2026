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
        // Add 'failed' to status_laporan enum
        DB::statement("ALTER TABLE laporan_gjm MODIFY COLUMN status_laporan ENUM('draft', 'processing', 'menunggu_review', 'approved', 'revisi', 'failed') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to previous enum
        DB::statement("ALTER TABLE laporan_gjm MODIFY COLUMN status_laporan ENUM('draft', 'processing', 'menunggu_review', 'approved', 'revisi') DEFAULT 'draft'");
    }
};
