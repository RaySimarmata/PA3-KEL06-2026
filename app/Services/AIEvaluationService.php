<?php

namespace App\Services;

use App\Models\AIEvaluationTest;
use App\Models\AIEvaluationResult;
use Illuminate\Support\Facades\Log;

class AIEvaluationService
{
    protected $ragasService;

    public function __construct(RAGASEvaluationService $ragasService = null)
    {
        $this->ragasService = $ragasService ?? app(RAGASEvaluationService::class);
    }

    /**
     * Create a new OCR evaluation test
     */
    public function createOCRTest(array $data): AIEvaluationTest
    {
        $test = AIEvaluationTest::create([
            'test_name' => $data['test_name'],
            'test_type' => 'ocr',
            'feature' => $data['feature'],
            'input_image_path' => $data['image_path'] ?? null,
            'ground_truth' => $data['ground_truth'],
            'ocr_result' => $data['ocr_result'],
            'status' => 'pending',
        ]);

        // Calculate WER automatically
        $test->calculateWER();

        return $test;
    }

    /**
     * Create AI response evaluation test
     */
    public function createAIResponseTest(array $data): AIEvaluationTest
    {
        return AIEvaluationTest::create([
            'test_name' => $data['test_name'],
            'test_type' => 'ai_response',
            'feature' => $data['feature'],
            'input_query' => $data['query'],
            'ground_truth' => $data['expected_response'] ?? null,
            'actual_output' => $data['actual_response'],
            'status' => 'pending',
        ]);
    }

    /**
     * Evaluate AI response with human scores
     */
    public function evaluateAIResponse(
        int $testId,
        int $relevance,
        int $completeness,
        int $clarity,
        int $accuracy,
        bool $hasHallucination = false,
        ?int $ambiguityScore = null,
        ?array $ambiguousParts = null,
        string $notes = '',
        string $evaluatedBy = 'System'
    ): AIEvaluationTest {
        $test = AIEvaluationTest::findOrFail($testId);

        $updateData = [
            'relevance_score' => $relevance,
            'completeness_score' => $completeness,
            'clarity_score' => $clarity,
            'accuracy_score' => $accuracy,
            'has_hallucination' => $hasHallucination,
            'notes' => $notes,
            'evaluated_by' => $evaluatedBy,
            'evaluated_at' => now(),
            'status' => 'evaluated',
        ];

        // Add ambiguity data if provided
        if ($ambiguityScore !== null) {
            $updateData['ambiguity_score'] = $ambiguityScore;
            $updateData['ambiguous_parts'] = $ambiguousParts;
        }

        $test->update($updateData);

        return $test;
    }

    /**
     * Evaluate AI response with RAGAS metrics
     */
    public function evaluateWithRAGAS(
        int $testId,
        string $question,
        string $answer,
        array $contexts,
        ?string $groundTruth = null
    ): AIEvaluationTest {
        $test = AIEvaluationTest::findOrFail($testId);

        // Run RAGAS evaluation
        $ragasMetrics = $this->ragasService->evaluateRAGResponse(
            $question,
            $answer,
            $contexts,
            $groundTruth
        );

        // Evaluate ambiguity
        $ambiguityResult = $this->ragasService->evaluateAmbiguity($answer);

        // Update test with RAGAS metrics
        $test->update([
            'ragas_faithfulness' => $ragasMetrics['faithfulness'],
            'ragas_answer_relevancy' => $ragasMetrics['answer_relevancy'],
            'ragas_context_precision' => $ragasMetrics['context_precision'],
            'ragas_context_recall' => $ragasMetrics['context_recall'],
            'ragas_context_relevancy' => $ragasMetrics['context_relevancy'],
            'ragas_overall_score' => $ragasMetrics['overall_score'],
            'ambiguity_score' => $ambiguityResult['ambiguity_score'],
            'ambiguous_parts' => $ambiguityResult['ambiguous_parts'],
            'ambiguity_notes' => $ambiguityResult['explanation'],
            'rag_context_used' => $contexts,
            'rag_chunks_count' => count($contexts),
        ]);

        // Calculate overall RAGAS score
        $test->calculateRAGASOverallScore();

        return $test;
    }

    /**
     * Create and evaluate AI response test with RAGAS
     */
    public function createAndEvaluateWithRAGAS(array $data): AIEvaluationTest
    {
        // Create test
        $test = $this->createAIResponseTest($data);

        // Evaluate with RAGAS
        $this->evaluateWithRAGAS(
            $test->id,
            $data['query'],
            $data['actual_response'],
            $data['contexts'] ?? [],
            $data['expected_response'] ?? null
        );

        return $test->fresh();
    }

    /**
     * Generate evaluation result summary
     */
    public function generateEvaluationResult(string $name, string $feature = 'all'): AIEvaluationResult
    {
        $query = AIEvaluationTest::evaluated();
        
        if ($feature !== 'all') {
            $query->forFeature($feature);
        }

        $tests = $query->get();

        // OCR Metrics
        $ocrTests = $tests->where('test_type', 'ocr');
        $avgWER = $ocrTests->avg('wer_score');

        // AI Response Metrics
        $aiTests = $tests->where('test_type', 'ai_response');
        $avgRelevance = $aiTests->avg('relevance_score');
        $avgCompleteness = $aiTests->avg('completeness_score');
        $avgClarity = $aiTests->avg('clarity_score');
        $avgAccuracy = $aiTests->avg('accuracy_score');
        $hallucinationCount = $aiTests->where('has_hallucination', true)->count();
        
        // Ambiguity Metrics
        $avgAmbiguity = $aiTests->avg('ambiguity_score');
        $highAmbiguityCount = $aiTests->where('ambiguity_score', '>=', 4)->count();

        // RAGAS Metrics
        $ragasTests = $aiTests->whereNotNull('ragas_overall_score');
        $avgRagasFaithfulness = $ragasTests->avg('ragas_faithfulness');
        $avgRagasAnswerRelevancy = $ragasTests->avg('ragas_answer_relevancy');
        $avgRagasContextPrecision = $ragasTests->avg('ragas_context_precision');
        $avgRagasContextRecall = $ragasTests->avg('ragas_context_recall');
        $avgRagasContextRelevancy = $ragasTests->avg('ragas_context_relevancy');
        $avgRagasOverall = $ragasTests->avg('ragas_overall_score');

        // Retrieval Metrics
        $retrievalTests = $tests->where('test_type', 'retrieval');
        $avgPrecision = $retrievalTests->avg('precision_score');

        // Generate analysis
        $summary = $this->generateSummary($tests);
        $strengths = $this->identifyStrengths($tests);
        $limitations = $this->identifyLimitations($tests);
        $recommendations = $this->generateRecommendations($tests);

        return AIEvaluationResult::create([
            'evaluation_name' => $name,
            'feature' => $feature,
            'evaluation_date' => now(),
            'avg_wer' => $avgWER,
            'total_ocr_tests' => $ocrTests->count(),
            'avg_relevance' => $avgRelevance,
            'avg_completeness' => $avgCompleteness,
            'avg_clarity' => $avgClarity,
            'avg_accuracy' => $avgAccuracy,
            'total_ai_tests' => $aiTests->count(),
            'hallucination_count' => $hallucinationCount,
            'avg_ambiguity' => $avgAmbiguity,
            'high_ambiguity_count' => $highAmbiguityCount,
            'avg_ragas_faithfulness' => $avgRagasFaithfulness,
            'avg_ragas_answer_relevancy' => $avgRagasAnswerRelevancy,
            'avg_ragas_context_precision' => $avgRagasContextPrecision,
            'avg_ragas_context_recall' => $avgRagasContextRecall,
            'avg_ragas_context_relevancy' => $avgRagasContextRelevancy,
            'avg_ragas_overall' => $avgRagasOverall,
            'total_ragas_tests' => $ragasTests->count(),
            'avg_precision' => $avgPrecision,
            'total_retrieval_tests' => $retrievalTests->count(),
            'summary' => $summary,
            'strengths' => $strengths,
            'limitations' => $limitations,
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Compare before and after metrics
     */
    public function compareBeforeAfter(int $beforeId, int $afterId): array
    {
        $before = AIEvaluationResult::findOrFail($beforeId);
        $after = AIEvaluationResult::findOrFail($afterId);

        $improvements = [];

        // OCR improvement
        if ($before->avg_wer && $after->avg_wer) {
            $improvements['wer'] = [
                'before' => $before->avg_wer,
                'after' => $after->avg_wer,
                'change' => $before->avg_wer - $after->avg_wer, // Lower is better
                'percent_change' => (($before->avg_wer - $after->avg_wer) / $before->avg_wer) * 100,
                'improved' => $after->avg_wer < $before->avg_wer,
            ];
        }

        // AI Quality improvements
        $metrics = ['relevance', 'completeness', 'clarity', 'accuracy'];
        foreach ($metrics as $metric) {
            $beforeKey = "avg_{$metric}";
            if ($before->$beforeKey && $after->$beforeKey) {
                $improvements[$metric] = [
                    'before' => $before->$beforeKey,
                    'after' => $after->$beforeKey,
                    'change' => $after->$beforeKey - $before->$beforeKey,
                    'percent_change' => (($after->$beforeKey - $before->$beforeKey) / $before->$beforeKey) * 100,
                    'improved' => $after->$beforeKey > $before->$beforeKey,
                ];
            }
        }

        // Hallucination rate
        $beforeHallucinationRate = $before->getHallucinationRate();
        $afterHallucinationRate = $after->getHallucinationRate();
        
        $improvements['hallucination_rate'] = [
            'before' => $beforeHallucinationRate,
            'after' => $afterHallucinationRate,
            'change' => $beforeHallucinationRate - $afterHallucinationRate,
            'percent_change' => $beforeHallucinationRate > 0 
                ? (($beforeHallucinationRate - $afterHallucinationRate) / $beforeHallucinationRate) * 100 
                : 0,
            'improved' => $afterHallucinationRate < $beforeHallucinationRate,
        ];

        return $improvements;
    }

    /**
     * Generate summary text
     */
    private function generateSummary($tests): string
    {
        $ocrTests = $tests->where('test_type', 'ocr');
        $aiTests = $tests->where('test_type', 'ai_response');

        $summary = "Evaluasi dilakukan terhadap {$tests->count()} test cases. ";

        if ($ocrTests->count() > 0) {
            $avgWER = $ocrTests->avg('wer_score');
            $werPercent = $avgWER * 100;
            $summary .= "OCR menunjukkan Word Error Rate rata-rata {$werPercent}%. ";
        }

        if ($aiTests->count() > 0) {
            $avgScore = $aiTests->avg(function($test) {
                return $test->getAverageHumanScore();
            });
            $summary .= "Kualitas AI response rata-rata {$avgScore}/5. ";
            
            // Hallucination
            $hallucinationCount = $aiTests->where('has_hallucination', true)->count();
            if ($hallucinationCount > 0) {
                $hallucinationRate = ($hallucinationCount / $aiTests->count()) * 100;
                $summary .= "Ditemukan hallucination pada " . number_format($hallucinationRate, 1) . "% kasus. ";
            } else {
                $summary .= "Tidak ditemukan hallucination. ";
            }

            // Ambiguity
            $avgAmbiguity = $aiTests->avg('ambiguity_score');
            if ($avgAmbiguity) {
                $summary .= "Tingkat ambiguitas rata-rata {$avgAmbiguity}/5. ";
                $highAmbiguityCount = $aiTests->where('ambiguity_score', '>=', 4)->count();
                if ($highAmbiguityCount > 0) {
                    $ambiguityRate = ($highAmbiguityCount / $aiTests->count()) * 100;
                    $summary .= "Ditemukan ambiguitas tinggi pada " . number_format($ambiguityRate, 1) . "% kasus. ";
                }
            }

            // RAGAS
            $ragasTests = $aiTests->whereNotNull('ragas_overall_score');
            if ($ragasTests->count() > 0) {
                $avgRagas = $ragasTests->avg('ragas_overall_score');
                $ragasPercent = $avgRagas * 100;
                $summary .= "RAGAS overall score: " . number_format($ragasPercent, 1) . "%. ";
                
                if ($avgRagas >= 0.8) {
                    $summary .= "RAG system performing excellently. ";
                } elseif ($avgRagas >= 0.6) {
                    $summary .= "RAG system performing adequately. ";
                } else {
                    $summary .= "RAG system needs improvement. ";
                }
            }
        }

        return $summary;
    }

    /**
     * Identify system strengths
     */
    private function identifyStrengths($tests): string
    {
        $strengths = [];

        $aiTests = $tests->where('test_type', 'ai_response');
        if ($aiTests->count() > 0) {
            $avgRelevance = $aiTests->avg('relevance_score');
            if ($avgRelevance >= 4.0) {
                $strengths[] = "Relevansi jawaban sangat baik (rata-rata {$avgRelevance}/5)";
            }

            $avgClarity = $aiTests->avg('clarity_score');
            if ($avgClarity >= 4.0) {
                $strengths[] = "Kejelasan bahasa sangat baik (rata-rata {$avgClarity}/5)";
            }

            $hallucinationRate = ($aiTests->where('has_hallucination', true)->count() / $aiTests->count()) * 100;
            if ($hallucinationRate < 10) {
                $strengths[] = "Tingkat hallucination rendah (" . number_format($hallucinationRate, 1) . "%)";
            }

            // Ambiguity
            $avgAmbiguity = $aiTests->avg('ambiguity_score');
            if ($avgAmbiguity && $avgAmbiguity <= 2.0) {
                $strengths[] = "Response sangat jelas dengan ambiguitas minimal (rata-rata {$avgAmbiguity}/5)";
            }

            // RAGAS
            $ragasTests = $aiTests->whereNotNull('ragas_overall_score');
            if ($ragasTests->count() > 0) {
                $avgRagas = $ragasTests->avg('ragas_overall_score');
                if ($avgRagas >= 0.8) {
                    $strengths[] = "RAGAS score excellent (" . number_format($avgRagas * 100, 1) . "%) - RAG system sangat efektif";
                }

                $avgFaithfulness = $ragasTests->avg('ragas_faithfulness');
                if ($avgFaithfulness >= 0.8) {
                    $strengths[] = "Faithfulness tinggi (" . number_format($avgFaithfulness * 100, 1) . "%) - Response konsisten dengan context";
                }

                $avgContextPrecision = $ragasTests->avg('ragas_context_precision');
                if ($avgContextPrecision >= 0.8) {
                    $strengths[] = "Context precision tinggi (" . number_format($avgContextPrecision * 100, 1) . "%) - Retrieval sangat akurat";
                }
            }
        }

        $ocrTests = $tests->where('test_type', 'ocr');
        if ($ocrTests->count() > 0) {
            $avgWER = $ocrTests->avg('wer_score');
            if ($avgWER < 0.1) {
                $strengths[] = "Akurasi OCR sangat tinggi (WER < 10%)";
            }
        }

        if (empty($strengths)) {
            return "Sistem menunjukkan performa standar.";
        }

        return implode(". ", $strengths) . ".";
    }

    /**
     * Identify system limitations
     */
    private function identifyLimitations($tests): string
    {
        $limitations = [];

        $aiTests = $tests->where('test_type', 'ai_response');
        if ($aiTests->count() > 0) {
            $avgCompleteness = $aiTests->avg('completeness_score');
            if ($avgCompleteness < 3.5) {
                $limitations[] = "Kelengkapan informasi perlu ditingkatkan (rata-rata {$avgCompleteness}/5)";
            }

            $hallucinationRate = ($aiTests->where('has_hallucination', true)->count() / $aiTests->count()) * 100;
            if ($hallucinationRate > 20) {
                $limitations[] = "Tingkat hallucination cukup tinggi (" . number_format($hallucinationRate, 1) . "%)";
            }

            // Ambiguity
            $avgAmbiguity = $aiTests->avg('ambiguity_score');
            if ($avgAmbiguity && $avgAmbiguity >= 3.5) {
                $limitations[] = "Response cenderung ambigu (rata-rata {$avgAmbiguity}/5)";
            }

            $highAmbiguityCount = $aiTests->where('ambiguity_score', '>=', 4)->count();
            if ($highAmbiguityCount > 0 && $aiTests->count() > 0) {
                $ambiguityRate = ($highAmbiguityCount / $aiTests->count()) * 100;
                if ($ambiguityRate > 20) {
                    $limitations[] = "Ambiguitas tinggi ditemukan pada " . number_format($ambiguityRate, 1) . "% kasus";
                }
            }

            // RAGAS
            $ragasTests = $aiTests->whereNotNull('ragas_overall_score');
            if ($ragasTests->count() > 0) {
                $avgRagas = $ragasTests->avg('ragas_overall_score');
                if ($avgRagas < 0.6) {
                    $limitations[] = "RAGAS score rendah (" . number_format($avgRagas * 100, 1) . "%) - RAG system perlu optimasi";
                }

                $avgContextRecall = $ragasTests->avg('ragas_context_recall');
                if ($avgContextRecall < 0.6) {
                    $limitations[] = "Context recall rendah (" . number_format($avgContextRecall * 100, 1) . "%) - Retrieval tidak lengkap";
                }

                $avgFaithfulness = $ragasTests->avg('ragas_faithfulness');
                if ($avgFaithfulness < 0.6) {
                    $limitations[] = "Faithfulness rendah (" . number_format($avgFaithfulness * 100, 1) . "%) - Response tidak konsisten dengan context";
                }
            }
        }

        $ocrTests = $tests->where('test_type', 'ocr');
        if ($ocrTests->count() > 0) {
            $avgWER = $ocrTests->avg('wer_score');
            if ($avgWER > 0.2) {
                $limitations[] = "Akurasi OCR perlu ditingkatkan (WER > 20%)";
            }
        }

        if (empty($limitations)) {
            return "Tidak ditemukan limitasi signifikan dalam evaluasi ini.";
        }

        return implode(". ", $limitations) . ".";
    }

    /**
     * Generate recommendations
     */
    private function generateRecommendations($tests): string
    {
        $recommendations = [];

        $aiTests = $tests->where('test_type', 'ai_response');
        if ($aiTests->count() > 0) {
            $avgCompleteness = $aiTests->avg('completeness_score');
            if ($avgCompleteness < 4.0) {
                $recommendations[] = "Perbaiki prompt engineering untuk meningkatkan kelengkapan jawaban";
            }

            $hallucinationCount = $aiTests->where('has_hallucination', true)->count();
            if ($hallucinationCount > 0) {
                $recommendations[] = "Implementasi fact-checking mechanism untuk mengurangi hallucination";
            }

            $avgAccuracy = $aiTests->avg('accuracy_score');
            if ($avgAccuracy < 4.0) {
                $recommendations[] = "Tingkatkan kualitas data training dan context yang diberikan ke AI";
            }

            // Ambiguity recommendations
            $avgAmbiguity = $aiTests->avg('ambiguity_score');
            if ($avgAmbiguity && $avgAmbiguity >= 3.0) {
                $recommendations[] = "Perbaiki clarity dengan instruksi yang lebih spesifik dan structured output format";
                $recommendations[] = "Tambahkan examples dan constraints untuk mengurangi ambiguitas";
            }

            // RAGAS recommendations
            $ragasTests = $aiTests->whereNotNull('ragas_overall_score');
            if ($ragasTests->count() > 0) {
                $avgContextRecall = $ragasTests->avg('ragas_context_recall');
                if ($avgContextRecall < 0.7) {
                    $recommendations[] = "Tingkatkan jumlah chunks yang di-retrieve (increase top_k parameter)";
                    $recommendations[] = "Turunkan similarity threshold untuk mendapatkan context yang lebih lengkap";
                }

                $avgContextPrecision = $ragasTests->avg('ragas_context_precision');
                if ($avgContextPrecision < 0.7) {
                    $recommendations[] = "Perbaiki kualitas embeddings dengan model yang lebih baik";
                    $recommendations[] = "Implementasi re-ranking untuk meningkatkan precision";
                }

                $avgFaithfulness = $ragasTests->avg('ragas_faithfulness');
                if ($avgFaithfulness < 0.7) {
                    $recommendations[] = "Tambahkan instruction untuk stick to the context dalam prompt";
                    $recommendations[] = "Implementasi citation mechanism untuk track source of information";
                }

                $avgAnswerRelevancy = $ragasTests->avg('ragas_answer_relevancy');
                if ($avgAnswerRelevancy < 0.7) {
                    $recommendations[] = "Perbaiki query understanding dengan query expansion atau reformulation";
                }
            }
        }

        $ocrTests = $tests->where('test_type', 'ocr');
        if ($ocrTests->count() > 0) {
            $avgWER = $ocrTests->avg('wer_score');
            if ($avgWER > 0.15) {
                $recommendations[] = "Perbaiki preprocessing gambar sebelum OCR (contrast, noise reduction)";
                $recommendations[] = "Pertimbangkan fine-tuning OCR model dengan data spesifik domain";
            }
        }

        if (empty($recommendations)) {
            return "Sistem sudah berjalan dengan baik. Lanjutkan monitoring dan evaluasi berkala.";
        }

        return implode(". ", $recommendations) . ".";
    }
}
