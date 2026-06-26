<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RAGASEvaluationTest;

class RAGASEvaluationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data dari dokumentasi Tabel 5.x
        $scenarios = [
            ['question' => 'Buat laporan triwulan terbaru', 'kategori' => 'Laporan Triwulan', 'faithfulness' => 0.74, 'hallucination_rate' => 0.15, 'context_precision' => 0.88, 'context_recall' => 0.91, 'f1_score' => 0.80, 'answer_relevancy' => 0.82, 'context_relevancy' => 0.89],
            ['question' => 'perbaiki bagian pendahuluan', 'kategori' => 'Laporan Bulanan', 'faithfulness' => 0.76, 'hallucination_rate' => 0.10, 'context_precision' => 0.80, 'context_recall' => 0.87, 'f1_score' => 0.83, 'answer_relevancy' => 0.86, 'context_relevancy' => 0.82],
            ['question' => 'Buatkan laporan VMTS semester ini', 'kategori' => 'Laporan VMTS', 'faithfulness' => 0.73, 'hallucination_rate' => 0.12, 'context_precision' => 0.78, 'context_recall' => 0.79, 'f1_score' => 0.73, 'answer_relevancy' => 0.76, 'context_relevancy' => 0.70],
            ['question' => 'bagian tindak lanjut perbaiki', 'kategori' => 'Laporan Bulanan', 'faithfulness' => 0.73, 'hallucination_rate' => 0.17, 'context_precision' => 0.70, 'context_recall' => 0.78, 'f1_score' => 0.74, 'answer_relevancy' => 0.77, 'context_relevancy' => 0.72],
            ['question' => 'Buatkan laporan kuesioner terbaru', 'kategori' => 'Laporan Kuesioner', 'faithfulness' => 0.80, 'hallucination_rate' => 0.11, 'context_precision' => 0.79, 'context_recall' => 0.84, 'f1_score' => 0.81, 'answer_relevancy' => 0.85, 'context_relevancy' => 0.80],
            ['question' => 'perbaiki bagian pelaksanan dan ubah jadi 2026', 'kategori' => 'Laporan Semester', 'faithfulness' => 0.79, 'hallucination_rate' => 0.14, 'context_precision' => 0.75, 'context_recall' => 0.76, 'f1_score' => 0.70, 'answer_relevancy' => 0.73, 'context_relevancy' => 0.67],
            ['question' => 'Buatkan laporan artefak bulanan', 'kategori' => 'Laporan Bulanan', 'faithfulness' => 0.76, 'hallucination_rate' => 0.13, 'context_precision' => 0.82, 'context_recall' => 0.89, 'f1_score' => 0.85, 'answer_relevancy' => 0.87, 'context_relevancy' => 0.84],
            ['question' => 'Ringkas visi, misi, dan tujuan Program Studi TRPL', 'kategori' => 'Laporan VMTS', 'faithfulness' => 0.73, 'hallucination_rate' => 0.13, 'context_precision' => 0.87, 'context_recall' => 0.72, 'f1_score' => 0.89, 'answer_relevancy' => 0.81, 'context_relevancy' => 0.88],
            ['question' => 'Buatkan laporan semester genap terbaru', 'kategori' => 'Laporan Semester', 'faithfulness' => 0.81, 'hallucination_rate' => 0.13, 'context_precision' => 0.75, 'context_recall' => 0.83, 'f1_score' => 0.79, 'answer_relevancy' => 0.82, 'context_relevancy' => 0.77],
            ['question' => 'pada bagian rekomendasi masih belum bagus coba perbaiki lagi', 'kategori' => 'Laporan Semester', 'faithfulness' => 0.70, 'hallucination_rate' => 0.16, 'context_precision' => 0.77, 'context_recall' => 0.77, 'f1_score' => 0.72, 'answer_relevancy' => 0.74, 'context_relevancy' => 0.79],
        ];

        foreach ($scenarios as $scenario) {
            RAGASEvaluationTest::create([
                'question' => $scenario['question'],
                'kategori' => $scenario['kategori'],
                'faithfulness' => $scenario['faithfulness'],
                'answer_relevancy' => $scenario['answer_relevancy'],
                'context_precision' => $scenario['context_precision'],
                'context_recall' => $scenario['context_recall'],
                'context_relevancy' => $scenario['context_relevancy'],
                'hallucination_rate' => $scenario['hallucination_rate'],
                'f1_score' => $scenario['f1_score'],
                'chunks_used' => rand(3, 7),
                'avg_similarity' => rand(75, 95) / 100,
                'ai_model' => env('LLM_MODEL', 'gpt-4o-mini') . ' (' . env('LLM_PROVIDER', 'openai') . ')',
                'response_time_ms' => rand(800, 2500),
                'status' => 'evaluated',
                'expected_answer' => 'Expected answer for: ' . $scenario['question'],
                'actual_answer' => 'AI generated answer for: ' . $scenario['question'],
                'retrieved_context' => 'Retrieved context from knowledge base',
            ]);
        }
    }
}
