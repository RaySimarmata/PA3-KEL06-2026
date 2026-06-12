<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perkuliahan_monitoring_snapshots', function (Blueprint $table) {
            $table->id();

            // 🔑 IDENTITAS DOSEN (KEY UTAMA)
            $table->string('pegawai_id', 50)->index();

            // PRODI + PERIODE
            $table->string('prodi_kode', 20)->index();
            $table->bigInteger('prodi_id')->index();

            $table->string('semester', 5)->index(); // '1' atau '2'
            $table->string('tahun_ajaran', 20)->index();

            // MATKUL
            $table->string('kuliah_id', 50)->index();
            $table->string('kode_mk', 50)->index();
            $table->string('nama_matkul')->nullable();
            $table->string('tingkat', 5)->nullable(); // '1', '2', '3', '4'

            // JENIS MATERI
            $table->enum('jenis_materi', [
                'Materi Teori',
                'Materi Praktikum'
            ])->index();

            // STATUS UPLOAD
            $table->enum('status_upload', [
                'BELUM UPLOAD',
                'SUDAH UPLOAD',
                'ERROR'
            ])->default('BELUM UPLOAD')->index();

            // REMINDER
            $table->boolean('reminder_sent')->default(false)->index();

            // OPTIONAL: raw API backup
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // 🚫 ANTI DUPLICATE (PENTING)
            $table->unique([
                'pegawai_id',
                'kode_mk',
                'semester',
                'tahun_ajaran',
                'jenis_materi'
            ], 'unique_perkuliahan_snapshot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perkuliahan_monitoring_snapshots');
    }
};
