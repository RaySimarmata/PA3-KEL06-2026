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
        Schema::table('laporan_gjm', function (Blueprint $table) {
            // RAGAS Evaluation Metrics for VMTS
            $table->decimal('ragas_faithfulness', 5, 4)->nullable()->after('ai_preview_draft')->comment('Factual consistency of answer with context (0.0-1.0)');
            $table->decimal('ragas_answer_relevancy', 5, 4)->nullable()->after('ragas_faithfulness')->comment('Relevance of answer to question (0.0-1.0)');
            $table->decimal('ragas_context_precision', 5, 4)->nullable()->after('ragas_answer_relevancy')->comment('Precision of retrieved context (0.0-1.0)');
            $table->decimal('ragas_context_recall', 5, 4)->nullable()->after('ragas_context_precision')->comment('Recall of retrieved context (0.0-1.0)');
            $table->decimal('ragas_context_relevancy', 5, 4)->nullable()->after('ragas_context_recall')->comment('Relevance of retrieved context (0.0-1.0)');
            $table->decimal('ragas_overall_score', 5, 4)->nullable()->after('ragas_context_relevancy')->comment('Overall RAGAS score (weighted average)');
            
            // RAG Context Metadata
            $table->integer('rag_chunks_count')->nullable()->after('ragas_overall_score')->comment('Number of context chunks used');
            $table->decimal('rag_avg_similarity', 5, 4)->nullable()->after('rag_chunks_count')->comment('Average similarity score of retrieved chunks');
            $table->json('rag_contexts')->nullable()->after('rag_avg_similarity')->comment('Retrieved context chunks (for analysis)');
            
            // Evaluation Metadata
            $table->string('ragas_evaluation_type', 50)->nullable()->after('rag_contexts')->comment('Type of evaluation: ai_based or heuristic');
            $table->timestamp('ragas_evaluated_at')->nullable()->after('ragas_evaluation_type')->comment('When RAGAS evaluation was performed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_gjm', function (Blueprint $table) {
            $table->dropColumn([
                'ragas_faithfulness',
                'ragas_answer_relevancy',
                'ragas_context_precision',
                'ragas_context_recall',
                'ragas_context_relevancy',
                'ragas_overall_score',
                'rag_chunks_count',
                'rag_avg_similarity',
                'rag_contexts',
                'ragas_evaluation_type',
                'ragas_evaluated_at',
            ]);
        });
    }
};
