<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RAGASEvaluationTest;
use Illuminate\Support\Facades\DB;

class RAGASReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ragas:report
                            {--kategori= : Filter by specific kategori}
                            {--export= : Export to file (txt, json, csv)}
                            {--detailed : Show detailed per-scenario results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display RAGAS evaluation report in console';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->displayHeader();
        
        $query = RAGASEvaluationTest::evaluated();
        
        if ($this->option('kategori')) {
            $query->where('kategori', $this->option('kategori'));
        }
        
        $tests = $query->get();
        
        if ($tests->isEmpty()) {
            $this->error('❌ No evaluation data found!');
            $this->info('💡 Run: php artisan ragas:evaluate --quick');
            return 1;
        }

        // Display summary
        $this->displaySummary($tests);
        
        // Display by category
        $this->displayByCategory($tests);
        
        // Display detailed if requested
        if ($this->option('detailed')) {
            $this->displayDetailed($tests);
        }
        
        // Export if requested
        if ($format = $this->option('export')) {
            $this->exportReport($tests, $format);
        }
        
        $this->displayFooter();
        
        return 0;
    }

    private function displayHeader()
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║         RAGAS EVALUATION REPORT - RAG SYSTEM QUALITY          ║');
        $this->info('║              Fakultas Vokasi - Sistem Laporan Akademik       ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    private function displaySummary($tests)
    {
        $this->info('📊 SUMMARY METRICS');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $avgFaith = $tests->avg('faithfulness');
        $avgHallu = $tests->avg('hallucination_rate');
        $avgPrec = $tests->avg('context_precision');
        $avgRecall = $tests->avg('context_recall');
        $avgF1 = $tests->avg('f1_score');
        $avgAnswerRel = $tests->avg('answer_relevancy');
        $avgContextRel = $tests->avg('context_relevancy');
        
        $ragasScore = ($avgFaith + $avgAnswerRel + $avgRecall + $avgPrec + $avgContextRel) / 5;
        
        $data = [
            ['Metric', 'Value', 'Status'],
            ['Faithfulness', $this->formatPercent($avgFaith), $this->getStatus($avgFaith, false)],
            ['Hallucination Rate', $this->formatPercent($avgHallu), $this->getStatus($avgHallu, true)],
            ['Context Precision', $this->formatPercent($avgPrec), $this->getStatus($avgPrec, false)],
            ['Context Recall', $this->formatPercent($avgRecall), $this->getStatus($avgRecall, false)],
            ['Context Relevancy', $this->formatPercent($avgContextRel), $this->getStatus($avgContextRel, false)],
            ['Answer Relevancy', $this->formatPercent($avgAnswerRel), $this->getStatus($avgAnswerRel, false)],
            ['F1 Score', $this->formatPercent($avgF1), $this->getStatus($avgF1, false)],
            ['━━━━━━━━━━━━━━━', '━━━━━━━━━━', '━━━━━━━━━━━━'],
            ['RAGAS SCORE', $this->formatPercent($ragasScore), $this->getStatus($ragasScore, false)],
        ];
        
        $this->table($data[0], array_slice($data, 1));
        $this->newLine();
    }

    private function displayByCategory($tests)
    {
        $this->info('📈 PERFORMANCE BY CATEGORY');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $byCategory = $tests->groupBy('kategori')->map(function ($group) {
            return [
                'count' => $group->count(),
                'avg_faith' => $group->avg('faithfulness'),
                'avg_hallu' => $group->avg('hallucination_rate'),
                'ragas_score' => $group->avg(fn($t) => $t->ragas_score),
            ];
        });
        
        $tableData = [];
        foreach ($byCategory as $kategori => $stats) {
            $tableData[] = [
                $kategori,
                $stats['count'],
                $this->formatPercent($stats['avg_faith']),
                $this->formatPercent($stats['avg_hallu']),
                $this->formatPercent($stats['ragas_score']),
                $this->getStatus($stats['avg_faith'], false),
            ];
        }
        
        // Sort by RAGAS score descending
        usort($tableData, fn($a, $b) => floatval(rtrim($b[4], '%')) <=> floatval(rtrim($a[4], '%')));
        
        $this->table(
            ['Kategori', 'Tests', 'Faithfulness', 'Hallucination', 'RAGAS Score', 'Status'],
            $tableData
        );
        $this->newLine();
    }

    private function displayDetailed($tests)
    {
        $this->info('📋 DETAILED RESULTS PER SCENARIO');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $tableData = [];
        foreach ($tests as $index => $test) {
            $tableData[] = [
                $index + 1,
                substr($test->question, 0, 40) . '...',
                $test->kategori,
                $this->formatPercent($test->faithfulness),
                $this->formatPercent($test->hallucination_rate),
                $this->formatPercent($test->f1_score),
            ];
        }
        
        $this->table(
            ['#', 'Question', 'Kategori', 'Faith.', 'Hallu.', 'F1'],
            $tableData
        );
        $this->newLine();
    }

    private function displayFooter()
    {
        $totalTests = RAGASEvaluationTest::evaluated()->count();
        $lastEvaluation = RAGASEvaluationTest::evaluated()->latest('updated_at')->first();
        $aiModel = $lastEvaluation ? $lastEvaluation->ai_model : (env('LLM_MODEL', 'gpt-4o-mini') . ' (' . env('LLM_PROVIDER', 'openai') . ')');
        
        $this->info('ℹ️  SYSTEM INFO');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("  • Total Evaluations: {$totalTests}");
        $this->line("  • Last Updated: " . ($lastEvaluation ? $lastEvaluation->updated_at->format('d M Y H:i:s') : 'N/A'));
        $this->line("  • Framework: RAGAS v1.0");
        $this->line("  • Model: {$aiModel}");
        $this->newLine();
        
        $this->comment('💡 Tips:');
        $this->line('  • Run with --detailed flag for per-scenario results');
        $this->line('  • Use --kategori=X to filter by category');
        $this->line('  • Export with --export=json|csv|txt');
        $this->newLine();
    }

    private function exportReport($tests, $format)
    {
        $this->info("📄 Exporting report to {$format}...");
        
        $filename = 'ragas_report_' . now()->format('Ymd_His') . '.' . $format;
        $path = storage_path('app/reports/' . $filename);
        
        // Create directory if not exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        switch ($format) {
            case 'json':
                $this->exportJson($tests, $path);
                break;
            case 'csv':
                $this->exportCsv($tests, $path);
                break;
            case 'txt':
                $this->exportTxt($tests, $path);
                break;
            default:
                $this->error("Unsupported format: {$format}");
                return;
        }
        
        $this->info("✅ Report exported to: {$path}");
    }

    private function exportJson($tests, $path)
    {
        $data = [
            'generated_at' => now()->toIso8601String(),
            'total_tests' => $tests->count(),
            'summary' => [
                'faithfulness' => $tests->avg('faithfulness'),
                'hallucination_rate' => $tests->avg('hallucination_rate'),
                'context_precision' => $tests->avg('context_precision'),
                'context_recall' => $tests->avg('context_recall'),
                'f1_score' => $tests->avg('f1_score'),
            ],
            'tests' => $tests->map(fn($t) => [
                'id' => $t->id,
                'question' => $t->question,
                'kategori' => $t->kategori,
                'faithfulness' => $t->faithfulness,
                'hallucination_rate' => $t->hallucination_rate,
                'context_precision' => $t->context_precision,
                'context_recall' => $t->context_recall,
                'f1_score' => $t->f1_score,
                'ragas_score' => $t->ragas_score,
            ])->toArray(),
        ];
        
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function exportCsv($tests, $path)
    {
        $handle = fopen($path, 'w');
        
        // Header
        fputcsv($handle, ['ID', 'Question', 'Kategori', 'Faithfulness', 'Hallucination', 'Precision', 'Recall', 'F1 Score', 'RAGAS Score']);
        
        // Data
        foreach ($tests as $test) {
            fputcsv($handle, [
                $test->id,
                $test->question,
                $test->kategori,
                $test->faithfulness,
                $test->hallucination_rate,
                $test->context_precision,
                $test->context_recall,
                $test->f1_score,
                $test->ragas_score,
            ]);
        }
        
        fclose($handle);
    }

    private function exportTxt($tests, $path)
    {
        $content = "RAGAS EVALUATION REPORT\n";
        $content .= "Generated: " . now()->format('d F Y H:i:s') . "\n";
        $content .= str_repeat('=', 80) . "\n\n";
        
        $content .= "SUMMARY\n";
        $content .= sprintf("Faithfulness: %.2f%%\n", $tests->avg('faithfulness') * 100);
        $content .= sprintf("Hallucination: %.2f%%\n", $tests->avg('hallucination_rate') * 100);
        $content .= sprintf("F1 Score: %.2f%%\n\n", $tests->avg('f1_score') * 100);
        
        $content .= "DETAILED RESULTS\n";
        $content .= str_repeat('-', 80) . "\n";
        
        foreach ($tests as $index => $test) {
            $content .= sprintf("%d. %s\n", $index + 1, $test->question);
            $content .= sprintf("   Kategori: %s\n", $test->kategori);
            $content .= sprintf("   Faithfulness: %.2f%% | Hallucination: %.2f%% | F1: %.2f%%\n\n",
                $test->faithfulness * 100,
                $test->hallucination_rate * 100,
                $test->f1_score * 100
            );
        }
        
        file_put_contents($path, $content);
    }

    private function formatPercent($value)
    {
        return number_format($value * 100, 2) . '%';
    }

    private function getStatus($value, $reverse = false)
    {
        if ($reverse) {
            // Lower is better (for hallucination)
            if ($value <= 0.10) return '✅ Excellent';
            if ($value <= 0.15) return '✅ Good';
            if ($value <= 0.20) return '⚠️  Fair';
            return '❌ Poor';
        } else {
            // Higher is better
            if ($value >= 0.90) return '✅ Excellent';
            if ($value >= 0.80) return '✅ Good';
            if ($value >= 0.70) return '⚠️  Fair';
            return '❌ Poor';
        }
    }
}
