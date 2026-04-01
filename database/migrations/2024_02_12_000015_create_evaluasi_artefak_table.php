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
        Schema::create('evaluasi_artefak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rps_id')->constrained('rps')->onDelete('cascade');
            $table->foreignId('materi_id')->nullable()->constrained('materi')->onDelete('cascade');
            $table->foreignId('evaluator_id')->constrained('dosen')->onDelete('cascade');
            $table->enum('jenis_artefak', ['rps', 'materi', 'soal_ujian'])->default('rps');
            $table->integer('skor_evaluasi')->nullable();
            $table->text('catatan_evaluasi')->nullable();
            $table->enum('status_evaluasi', ['approve', 'revisi', 'ditolak'])->default('revisi');
            $table->date('tanggal_evaluasi')->nullable();
            $table->text('saran_perbaikan')->nullable();
            $table->date('tanggal_revisi_selesai')->nullable();
            $table->integer('jumlah_revisi')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluasi_artefak');
    }
};
