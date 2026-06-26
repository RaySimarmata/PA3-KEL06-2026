<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuesioner_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->string('file_path');
            $table->string('periode');
            $table->unsignedBigInteger('prodi_id');
            $table->text('deskripsi')->nullable();
            $table->integer('total_responden')->default(0);
            $table->json('hasil_analisis')->nullable(); // Untuk menyimpan hasil AI analysis
            $table->enum('status', ['uploaded', 'processing', 'completed', 'error'])->default('uploaded');
            $table->timestamps();
            
            $table->foreign('prodi_id')->references('id')->on('prodi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuesioner_uploads');
    }
};