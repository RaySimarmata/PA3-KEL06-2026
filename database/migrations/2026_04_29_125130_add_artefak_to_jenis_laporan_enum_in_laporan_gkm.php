<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah ENUM jenis_laporan agar mendukung nilai 'artefak'
        DB::statement("ALTER TABLE laporan_gkm MODIFY COLUMN jenis_laporan ENUM('bulanan','semester','tahunan','artefak') DEFAULT 'bulanan'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE laporan_gkm MODIFY COLUMN jenis_laporan ENUM('bulanan','semester','tahunan') DEFAULT 'bulanan'");
    }
};
