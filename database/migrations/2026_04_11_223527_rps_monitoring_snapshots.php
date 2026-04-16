<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rps_monitoring_snapshots', function (Blueprint $table) {
            $table->id();

            // 🔑 IDENTITAS DOSEN (KEY UTAMA)
            $table->bigInteger('pegawai_id')->index();

            // PRODI + PERIODE
            $table->string('prodi_kode')->index();
            $table->bigInteger('prodi_id')->index();

            $table->integer('semester')->index();
            $table->string('tahun_ajaran')->index();

            // MATKUL
            $table->string('kuliah_id')->index();
            $table->string('kode_mk')->index();
            $table->string('nama_matkul')->nullable();
            $table->boolean('reminder_sent')->default(false)->index();

            // STATUS RPS
            $table->enum('status_rps', [
                'BELUM UPLOAD',
                'SUDAH UPLOAD',
                'ERROR'
            ])->default('BELUM UPLOAD');

            // OPTIONAL: raw API backup
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // 🚫 ANTI DUPLICATE (PENTING)
            $table->unique([
                'pegawai_id',
                'kode_mk',
                'semester',
                'tahun_ajaran'
            ], 'unique_rps_snapshot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rps_monitoring_snapshots');
    }
};