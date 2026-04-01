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
        Schema::create('perwaliaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->constrained('dosen')->onDelete('cascade');
            $table->foreignId('ajaran_id')->constrained('ajaran')->onDelete('cascade');
            $table->integer('jumlah_mahasiswa')->nullable();
            $table->date('tanggal_mulai_perwalian')->nullable();
            $table->date('tanggal_akhir_perwalian')->nullable();
            $table->enum('status_perwalian', ['belum_dimulai', 'sedang_berjalan', 'selesai'])->default('belum_dimulai');
            $table->integer('jumlah_sesi_konsultasi')->nullable();
            $table->text('catatan_perwalian')->nullable();
            $table->decimal('nilai_evaluasi', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perwaliaan');
    }
};
