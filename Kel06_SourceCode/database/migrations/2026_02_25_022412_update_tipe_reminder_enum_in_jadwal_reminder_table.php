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
        // Untuk MySQL, kita perlu mengubah kolom enum
        DB::statement("ALTER TABLE jadwal_reminder MODIFY COLUMN tipe_reminder ENUM('rps_review', 'materi_upload', 'perwaliaan', 'persiapan_kuliah', 'review_soal', 'kuisioner', 'RPS', 'Upload Materi', 'Perwalian', 'Review Soal') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE jadwal_reminder MODIFY COLUMN tipe_reminder ENUM('rps_review', 'materi_upload', 'perwaliaan', 'persiapan_kuliah', 'review_soal', 'kuisioner') NULL");
    }
};
