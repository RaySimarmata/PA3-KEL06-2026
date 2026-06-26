<?php

namespace App\Console\Commands;

use App\Models\LaporanGJM;
use App\Services\AIEvaluationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EvaluateLaporanRAGAS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evaluation:ragas-laporan
                            {--jenis= : Jenis laporan (triwulan, semester, vmts, all)}
                            {--limit=10 : Jumlah laporan yang akan dievaluasi}
                            {--force : Force re-evaluate laporan yang sudah dievaluasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Jalankan evaluasi RAGAS pada laporan GJM yang sudah ada';

    protected $evaluationService;

    public function __construct(AIEvaluationService $evaluationService)
    {
        parent::__construct();
        $this->evaluationService = $evaluationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Memulai evaluasi RAGAS pada laporan GJM...');

        $jenis = $this->option('jenis') ?? 'all';
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        // Build query
        $query = LaporanGJM::query()
            ->where('status_laporan', 'completed')
            ->whereNotNull('ai_preview_draft')
            ->where('ai_preview_draft', '!=', '');

        // Filter by jenis if specified
        if ($jenis !== 'all') {
            $jenisMap = [
                'triwulan' => 'triwulan',
                'semester' => 'semester',
                'vmts' => 'VMTS',
            ];

            if (!isset($jenisMap[$jenis])) {
                $this->error("Jenis laporan tidak valid. Gunakan: triwulan, semester, vmts, atau all");
                return 1;
            }

            $query->where('jenis_laporan', $jenisMap[$jenis]);
        }

        // Exclude already evaluated if not force
        if (!$force) {
            $query->whereDoesntHave('aiEvaluationTests', function ($q) {
                $q->whereNotNull('ragas_overall_score');
            });
        }

        $laporans = $query->limit($limit)->get();

        if ($laporans->isEmpty()) {
            $this->warn('Tidak ada laporan yang perlu dievaluasi.');
            return 0;
        }

        $this->info("Ditemukan {$laporans->count()} laporan untuk dievaluasi.");

        $bar = $this->output->createProgressBar($laporans->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($laporans as $laporan) {
            try {
                $this->evaluateLaporan($laporan);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Evaluasi RAGAS gagal', [
                    'laporan_id' => $laporan->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("\nGagal mengevaluasi laporan ID {$laporan->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);
        $this->info("✅ Evaluasi selesai!");
        $this->info("Berhasil: {$successCount}");
        $this->info("Gagal: {$errorCount}");

        return 0;
    }

    /**
     * Evaluate a single laporan
     */
    private function evaluateLaporan(LaporanGJM $laporan): void
    {
        // Prepare data for evaluation
        $testName = "Evaluasi Laporan {$laporan->jenis_laporan} - {$laporan->judul}";
        
        // Get the prompt that was used (if available)
        $query = $laporan->prompt_used ?? $this->getDefaultPrompt($laporan);
        
        // Get the AI response
        $actualResponse = $laporan->ai_preview_draft;

        // Get context (from laporan data)
        $contexts = $this->buildContexts($laporan);

        // Get ground truth if available
        $groundTruth = $laporan->final_content ?? null;

        // Determine feature
        $feature = $this->getFeatureName($laporan->jenis_laporan);

        // Create and evaluate test with RAGAS
        $testData = [
            'test_name' => $testName,
            'feature' => $feature,
            'query' => $query,
            'actual_response' => $actualResponse,
            'expected_response' => $groundTruth,
            'contexts' => $contexts,
        ];

        $this->evaluationService->createAndEvaluateWithRAGAS($testData);

        // Link test to laporan
        $laporan->update([
            'ragas_evaluated_at' => now(),
        ]);
    }

    /**
     * Build contexts from laporan data
     */
    private function buildContexts(LaporanGJM $laporan): array
    {
        $contexts = [];

        // Add periode info
        $contexts[] = "Periode: {$laporan->periode} {$laporan->tahun}";

        // Add jenis and judul
        $contexts[] = "Jenis Laporan: {$laporan->jenis_laporan}";
        $contexts[] = "Judul: {$laporan->judul}";

        // Add prodi info if available
        if ($laporan->prodi) {
            $contexts[] = "Program Studi: {$laporan->prodi->nama_prodi}";
        }

        // Add created by info
        if ($laporan->user) {
            $contexts[] = "Dibuat oleh: {$laporan->user->name}";
        }

        // Add any reference documents
        if ($laporan->reference_documents) {
            $contexts[] = "Dokumen Referensi: " . implode(', ', $laporan->reference_documents);
        }

        // Add template info if available
        if ($laporan->template) {
            $contexts[] = "Template: {$laporan->template->nama_template}";
            if ($laporan->template->template_content) {
                $contexts[] = "Template Content: " . substr($laporan->template->template_content, 0, 500);
            }
        }

        return $contexts;
    }

    /**
     * Get default prompt for laporan type
     */
    private function getDefaultPrompt(LaporanGJM $laporan): string
    {
        $jenisMap = [
            'triwulan' => 'Buat laporan triwulan untuk periode {periode} tahun {tahun}',
            'semester' => 'Buat laporan semester untuk periode {periode} tahun {tahun}',
            'VMTS' => 'Buat laporan VMTS (Visi Misi Tujuan Sasaran) berdasarkan analisis kuesioner',
        ];

        $prompt = $jenisMap[$laporan->jenis_laporan] ?? 'Buat laporan';

        $prompt = str_replace('{periode}', $laporan->periode, $prompt);
        $prompt = str_replace('{tahun}', $laporan->tahun, $prompt);

        return $prompt;
    }

    /**
     * Get feature name from jenis laporan
     */
    private function getFeatureName(string $jenisLaporan): string
    {
        return match ($jenisLaporan) {
            'triwulan' => 'triwulan',
            'semester' => 'semester',
            'VMTS' => 'vmts',
            default => 'unknown',
        };
    }
}
