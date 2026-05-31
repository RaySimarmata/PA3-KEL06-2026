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
        // Tabel untuk menyimpan test cases evaluasi
        Schema::create('ai_evaluation_tests', function (Blueprint $table) {
            $table->id();
            $table->string('test_name');
            $table->enum('test_type', ['ocr', 'ai_response', 'retrieval']); // Jenis test
            $table->enum('feature', ['triwulan', 'semester', 'vmts']); // Fitur yang ditest
            
            // Input data
            $table->text('input_query')->nullable(); // Query/prompt yang ditest
            $table->text('input_image_path')->nullable(); // Path gambar untuk OCR test
            $table->longText('ground_truth')->nullable(); // Expected result / teks referensi
            
            // Output data
            $table->longText('actual_output')->nullable(); // Hasil aktual dari sistem
            $table->text('ocr_result')->nullable(); // Hasil OCR jika ada
            
            // Metrics
            $table->decimal('wer_score', 5, 4)->nullable(); // Word Error Rate untuk OCR
            $table->decimal('precision_score', 5, 4)->nullable(); // Precision untuk retrieval
            $table->integer('relevance_score')->nullable(); // 1-5 untuk human evaluation
            $table->integer('completeness_score')->nullable(); // 1-5
            $table->integer('clarity_score')->nullable(); // 1-5
            $table->integer('accuracy_score')->nullable(); // 1-5
            $table->boolean('has_hallucination')->default(false); // Apakah ada hallucination
            
            // Metadata
            $table->text('notes')->nullable(); // Catatan evaluator
            $table->string('evaluated_by')->nullable(); // Siapa yang evaluasi
            $table->timestamp('evaluated_at')->nullable();
            $table->enum('status', ['pending', 'evaluated', 'approved'])->default('pending');
            
            $table->timestamps();
            
            $table->index(['test_type', 'feature']);
            $table->index('status');
        });

        // Tabel untuk menyimpan hasil evaluasi agregat
        Schema::create('ai_evaluation_results', function (Blueprint $table) {
            $table->id();
            $table->string('evaluation_name');
            $table->enum('feature', ['triwulan', 'semester', 'vmts', 'all']);
            $table->date('evaluation_date');
            
            // OCR Metrics
            $table->decimal('avg_wer', 5, 4)->nullable();
            $table->integer('total_ocr_tests')->default(0);
            
            // AI Response Metrics
            $table->decimal('avg_relevance', 5, 2)->nullable();
            $table->decimal('avg_completeness', 5, 2)->nullable();
            $table->decimal('avg_clarity', 5, 2)->nullable();
            $table->decimal('avg_accuracy', 5, 2)->nullable();
            $table->integer('total_ai_tests')->default(0);
            $table->integer('hallucination_count')->default(0);
            
            // Retrieval Metrics
            $table->decimal('avg_precision', 5, 4)->nullable();
            $table->integer('total_retrieval_tests')->default(0);
            
            // Comparison (Before vs After)
            $table->json('before_metrics')->nullable();
            $table->json('after_metrics')->nullable();
            $table->json('improvements')->nullable();
            
            // Analysis
            $table->text('summary')->nullable();
            $table->text('strengths')->nullable();
            $table->text('limitations')->nullable();
            $table->text('recommendations')->nullable();
            
            $table->timestamps();
            
            $table->index(['feature', 'evaluation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_evaluation_results');
        Schema::dropIfExists('ai_evaluation_tests');
    }
};
