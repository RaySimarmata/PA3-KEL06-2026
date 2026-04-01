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
        Schema::create('jadwal_reminder', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe_reminder', [
                'rps_review',
                'materi_upload',
                'perwaliaan',
                'persiapan_kuliah',
                'review_soal',
                'kuisioner'
            ])->nullable();
            $table->integer('hari_sebelum_deadline')->nullable(); // Pengingat berapa hari sebelum
            $table->enum('frekuensi', ['sekali', 'harian', 'mingguan', 'bulanan'])->default('sekali');
            $table->time('waktu_pengiriman')->nullable();
            $table->text('template_pesan')->nullable();
            $table->enum('status', ['aktif', 'tidak_aktif'])->default('aktif');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_reminder');
    }
};
