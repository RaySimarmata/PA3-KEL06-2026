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
        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rps_id')->constrained('rps')->onDelete('cascade');
            $table->string('judul_materi');
            $table->text('deskripsi')->nullable();
            $table->integer('pertemuan_ke')->nullable();
            $table->string('file_materi')->nullable();
            $table->string('file_format')->nullable(); // PDF, PPT, DOC, dll
            $table->text('capaian_pembelajaran')->nullable();
            $table->date('tanggal_upload')->nullable();
            $table->enum('status', ['belum_upload', 'sudah_upload', 'perlu_revisi'])->default('belum_upload');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materi');
    }
};
