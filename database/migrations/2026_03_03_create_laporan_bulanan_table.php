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
        Schema::create('laporan_bulanan', function (Blueprint $table) {
            $table->id();
            
            // Metadata
            $table->string('periode', 7); // Format: 2026-03
            $table->string('bulan', 20); // Maret
            $table->integer('tahun'); // 2026
            $table->unsignedBigInteger('prodi_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('template_id')->nullable();
            
            // Data Agregat
            $table->integer('total_kuesioner')->default(0);
            $table->integer('total_responden')->default(0);
            $table->decimal('index_kepuasan_rata_rata', 5, 2)->nullable();
            $table->decimal('persen_kepuasan_rata_rata', 5, 2)->nullable();
            
            // Hasil Laporan (JSON)
            $table->json('hasil_laporan')->nullable();
            
            // File
            $table->string('file_word')->nullable();
            $table->string('file_pdf')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'processing', 'completed', 'error'])->default('pending');
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('periode');
            $table->index('prodi_id');
            $table->index('status');
            $table->unique(['periode', 'prodi_id']);
            
            // Foreign Keys
            $table->foreign('prodi_id')->references('id')->on('prodi')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('template_laporan')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_bulanan');
    }
};
