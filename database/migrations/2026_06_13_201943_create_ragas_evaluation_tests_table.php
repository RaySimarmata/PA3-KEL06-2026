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
        Schema::create('ragas_evaluation_tests', function (Blueprint $table) {
            $table->id();
            $table->string('question')->comment('Pertanyaan/skenario pengujian');
            $table->string('kategori')->comment('Kategori: Laporan Triwulan, Kuesioner, dll');
            $table->text('expected_answer')->nullable()->comment('Jawaban yang diharapkan');
            $table->text('actual_answer')->nullable()->comment('Jawaban yang dihasilkan AI');
            $table->text('retrieved_context')->nullable()->comment('Konteks yang diambil dari RAG');
            
            // RAGAS Metrics
            $table->decimal('faithfulness', 5, 4)->default(0)->comment('0-1: Kesesuaian dengan dokumen');
            $table->decimal('answer_relevancy', 5, 4)->default(0)->comment('0-1: Relevansi jawaban');
            $table->decimal('context_precision', 5, 4)->default(0)->comment('0-1: Ketepatan konteks');
            $table->decimal('context_recall', 5, 4)->default(0)->comment('0-1: Kelengkapan konteks');
            $table->decimal('context_relevancy', 5, 4)->default(0)->comment('0-1: Relevansi konteks');
            $table->decimal('hallucination_rate', 5, 4)->default(0)->comment('0-1: Tingkat halusinasi');
            $table->decimal('f1_score', 5, 4)->default(0)->comment('0-1: Harmonic mean precision & recall');
            
            // Metadata
            $table->integer('chunks_used')->default(0)->comment('Jumlah chunk yang digunakan');
            $table->decimal('avg_similarity', 5, 4)->default(0)->comment('Rata-rata similarity score');
            $table->string('ai_model')->nullable()->comment('Model AI yang digunakan');
            $table->integer('response_time_ms')->nullable()->comment('Waktu respons dalam ms');
            $table->enum('status', ['pending', 'evaluated', 'failed'])->default('pending');
            
            $table->timestamps();
            
            // Indexes
            $table->index('kategori');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ragas_evaluation_tests');
    }
};
