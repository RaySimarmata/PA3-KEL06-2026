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
        Schema::create('rps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matakuliah_id')->constrained('matakuliah')->onDelete('cascade');
            $table->foreignId('ajaran_id')->constrained('ajaran')->onDelete('cascade');
            $table->foreignId('dosen_id')->constrained('dosen')->onDelete('cascade');
            $table->text('deskripsi')->nullable();
            $table->text('capaian_pembelajaran')->nullable();
            $table->text('strategi_pembelajaran')->nullable();
            $table->text('penugasan')->nullable();
            $table->text('penilaian')->nullable();
            $table->string('file_rps')->nullable();
            $table->enum('status_rps', ['draft', 'menunggu_review', 'sudah_divalidasi', 'revisi'])->default('draft');
            $table->date('tanggal_upload')->nullable();
            $table->date('tanggal_validasi')->nullable();
            $table->text('catatan_validasi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rps');
    }
};
