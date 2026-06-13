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

    $table->string('pegawai_id', 50);
    $table->string('nama_dosen')->nullable();

    $table->bigInteger('kuliah_id')->nullable();

    $table->string('kode_mk', 50);
    $table->string('nama_matkul');

    $table->integer('prodi_id')->nullable();
    $table->string('prodi_kode')->nullable();

    $table->string('semester', 10);
    $table->string('tahun_ajaran', 20);

    $table->integer('tingkat')->nullable();

    $table->enum('jenis_materi', [
        'Materi Teori',
        'Materi Praktikum'
    ]);

    /*
    |--------------------------------------------------------------------------
    | Statistik Kepatuhan
    |--------------------------------------------------------------------------
    */

    $table->integer('total_minggu')->default(0);

    $table->integer('jumlah_upload')->default(0);

    $table->integer('jumlah_terlambat')->default(0);

    $table->integer('jumlah_belum_upload')->default(0);

    $table->decimal('persentase_kepatuhan', 5, 2)
        ->default(0);

    $table->enum('status_kepatuhan', [
        'PATUH',
        'BELUM PATUH',
        'KURANG PATUH'
    ])->default('PATUH');

    /*
    |--------------------------------------------------------------------------
    | Detail Mingguan
    |--------------------------------------------------------------------------
    | contoh:
    | [1,1,1,0,1,2,1,1,0,1,1,1,1,0,1,1]
    |--------------------------------------------------------------------------
    */

    $table->json('detail_weeks')->nullable();

    /*
    |--------------------------------------------------------------------------
    | Raw API Data
    |--------------------------------------------------------------------------
    */

    $table->json('raw_data')->nullable();

    $table->timestamps();

    $table->unique([
        'pegawai_id',
        'kode_mk',
        'semester',
        'tahun_ajaran',
        'jenis_materi'
    ], 'uniq_perkuliahan_compliance');
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
