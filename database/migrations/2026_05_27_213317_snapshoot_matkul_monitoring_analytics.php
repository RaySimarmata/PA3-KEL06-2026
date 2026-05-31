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
        Schema::create('perkuliahan_monitoring_details', function (Blueprint $table) {
    $table->id();

    $table->string('pegawai_id');
    $table->string('nama_dosen')->nullable();

    $table->string('kode_mk');
    $table->string('nama_matkul');

    $table->string('prodi_kode')->nullable();
    $table->integer('prodi_id')->nullable();

    $table->string('semester');
    $table->string('tahun_ajaran');

    $table->integer('tingkat')->nullable();

    $table->enum('jenis_materi', [
        'Materi Teori',
        'Materi Praktikum'
    ]);

    $table->integer('minggu');

    $table->enum('status_upload', [
        'OK',
        'TERLAMBAT',
        'BELUM_UPLOAD'
    ]);

    $table->boolean('is_tepat_waktu')->default(false);

    $table->timestamp('tanggal_upload')->nullable();

    $table->json('raw_data')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perkuliahan_monitoring_details', function (Blueprint $table) {
            //
        });
    }
};
