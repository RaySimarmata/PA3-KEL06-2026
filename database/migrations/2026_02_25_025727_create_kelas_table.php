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
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->constrained('prodi')->onDelete('cascade');
            $table->string('kode_kelas', 50); // Contoh: 41TRPL1, 31TI2
            $table->integer('tingkat'); // 1, 2, 3, 4
            $table->string('program_studi', 50); // TRPL, TI, TK, dll
            $table->integer('tahun_angkatan'); // 2021, 2022, 2023, dst
            $table->enum('status', ['aktif', 'tidak_aktif'])->default('aktif');
            $table->timestamps();

            // Index untuk pencarian
            $table->index('prodi_id');
            $table->index('kode_kelas');
            $table->unique(['prodi_id', 'kode_kelas']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
