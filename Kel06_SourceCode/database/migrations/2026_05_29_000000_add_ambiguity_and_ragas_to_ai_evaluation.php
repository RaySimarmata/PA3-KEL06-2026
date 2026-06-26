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
        // Add Ambiguity and RAGAS metrics to ai_evaluation_tests
        Schema::table('ai_evaluation_tests', function (Blueprint $table) {
            // Ambiguity Metrics
            $table->integer('ambiguity_score')->nullable()->after('accuracy_score'); // 1-5 (1=very clear, 5=very ambiguous)
            $table->json('ambiguous_parts')->nullable()->after('ambiguity_score'); // Array of ambiguous phrases
            $table->text('ambiguity_notes')->nullable()->after('ambiguous_parts');
            
            // RAGAS Metrics
            $table->decimal('ragas_faithfulness', 5, 4)->nullable()->after('ambiguity_notes'); // 0-1
            $table->decimal('ragas_answer_relevancy', 5, 4)->nullable()->after('ragas_faithfulness'); // 0-1
            $table->decimal('ragas_context_precision', 5, 4)->nullable()->after('ragas_answer_relevancy'); // 0-1
            $table->decimal('ragas_context_recall', 5, 4)->nullable()->after('ragas_context_precision'); // 0-1
            $table->decimal('ragas_context_relevancy', 5, 4)->nullable()->after('ragas_context_recall'); // 0-1
            $table->decimal('ragas_overall_score', 5, 4)->nullable()->after('ragas_context_relevancy'); // Average of all RAGAS metrics
            
            // RAG Context metadata
            $table->json('rag_context_used')->nullable()->after('ragas_overall_score'); // Context chunks used
            $table->integer('rag_chunks_count')->nullable()->after('rag_context_used');
            $table->decimal('rag_avg_similarity', 5, 4)->nullable()->after('rag_chunks_count');
        });

        // Add RAGAS aggregate metrics to ai_evaluation_results
        Schema::table('ai_evaluation_results', function (Blueprint $table) {
            // Ambiguity Metrics
            $table->decimal('avg_ambiguity', 5, 2)->nullable()->after('hallucination_count');
            $table->integer('high_ambiguity_count')->default(0)->after('avg_ambiguity'); // Score >= 4
            
            // RAGAS Metrics
            $table->decimal('avg_ragas_faithfulness', 5, 4)->nullable()->after('high_ambiguity_count');
            $table->decimal('avg_ragas_answer_relevancy', 5, 4)->nullable()->after('avg_ragas_faithfulness');
            $table->decimal('avg_ragas_context_precision', 5, 4)->nullable()->after('avg_ragas_answer_relevancy');
            $table->decimal('avg_ragas_context_recall', 5, 4)->nullable()->after('avg_ragas_context_precision');
            $table->decimal('avg_ragas_context_relevancy', 5, 4)->nullable()->after('avg_ragas_context_recall');
            $table->decimal('avg_ragas_overall', 5, 4)->nullable()->after('avg_ragas_context_relevancy');
            $table->integer('total_ragas_tests')->default(0)->after('avg_ragas_overall');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_evaluation_tests', function (Blueprint $table) {
            $table->dropColumn([
                'ambiguity_score',
                'ambiguous_parts',
                'ambiguity_notes',
                'ragas_faithfulness',
                'ragas_answer_relevancy',
                'ragas_context_precision',
                'ragas_context_recall',
                'ragas_context_relevancy',
                'ragas_overall_score',
                'rag_context_used',
                'rag_chunks_count',
                'rag_avg_similarity',
            ]);
        });

        Schema::table('ai_evaluation_results', function (Blueprint $table) {
            $table->dropColumn([
                'avg_ambiguity',
                'high_ambiguity_count',
                'avg_ragas_faithfulness',
                'avg_ragas_answer_relevancy',
                'avg_ragas_context_precision',
                'avg_ragas_context_recall',
                'avg_ragas_context_relevancy',
                'avg_ragas_overall',
                'total_ragas_tests',
            ]);
        });
    }
};
