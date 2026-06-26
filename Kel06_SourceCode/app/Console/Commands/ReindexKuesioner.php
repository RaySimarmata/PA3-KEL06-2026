<?php

namespace App\Console\Commands;

use App\Models\KuesioneUpload;
use App\Services\VectorDatabaseService;
use App\Services\EmbeddingService;
use App\Services\ChunkingService;
use Illuminate\Console\Command;

class ReindexKuesioner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kuesioner:reindex 
                            {--periode= : Periode to reindex (format: YYYY-MM)}
                            {--prodi= : Prodi ID to filter}
                            {--all : Reindex all kuesioner}
                            {--force : Force reindex even if already indexed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex kuesioner for RAG system (create embeddings and chunks)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!env('VECTOR_DB_ENABLED', false)) {
            $this->error('Vector DB is not enabled. Set VECTOR_DB_ENABLED=true in .env');
            return 1;
        }

        $this->info('=== Kuesioner Reindexing ===');
        $this->newLine();

        // Initialize services
        $embeddingService = new EmbeddingService();
        $chunkingService = new ChunkingService();
        $vectorDbService = new VectorDatabaseService($embeddingService, $chunkingService);

        // Build query
        $query = KuesioneUpload::where('status', 'completed')
            ->whereNotNull('hasil_analisis');

        // Apply filters
        if ($this->option('periode')) {
            $periode = $this->option('periode');
            $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$periode]);
            $this->info("Filtering by periode: {$periode}");
        }

        if ($this->option('prodi')) {
            $prodiId = $this->option('prodi');
            $query->whereHas('user', function($q) use ($prodiId) {
                $q->where('prodi_id', $prodiId);
            });
            $this->info("Filtering by prodi ID: {$prodiId}");
        }

        if (!$this->option('all') && !$this->option('periode')) {
            // Default: current month
            $periode = date('Y-m');
            $query->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$periode]);
            $this->info("Filtering by current periode: {$periode}");
        }

        $kuesioneList = $query->get();
        $total = $kuesioneList->count();

        if ($total == 0) {
            $this->warn('No kuesioner found matching the criteria');
            return 0;
        }

        $this->info("Found {$total} kuesioner to process");
        $this->newLine();

        // Progress bar
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $indexed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($kuesioneList as $kuesioner) {
            // Check if already indexed
            $existingChunks = \App\Models\DocumentChunk::where('kuesioner_upload_id', $kuesioner->id)->count();

            if ($existingChunks > 0 && !$this->option('force')) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Delete existing chunks if force reindex
            if ($existingChunks > 0 && $this->option('force')) {
                $vectorDbService->deleteKuesioneIndex($kuesioner->id);
            }

            // Index kuesioner
            try {
                $success = $vectorDbService->indexKuesioner($kuesioner);
                if ($success) {
                    $indexed++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("\nFailed to index kuesioner {$kuesioner->id}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('=== Reindexing Summary ===');
        $this->table(
            ['Status', 'Count'],
            [
                ['Total', $total],
                ['Indexed', $indexed],
                ['Skipped', $skipped],
                ['Failed', $failed],
            ]
        );

        if ($indexed > 0) {
            $this->info("✅ Successfully indexed {$indexed} kuesioner");
        }

        if ($skipped > 0) {
            $this->comment("ℹ️  Skipped {$skipped} already indexed kuesioner (use --force to reindex)");
        }

        if ($failed > 0) {
            $this->error("❌ Failed to index {$failed} kuesioner");
            return 1;
        }

        return 0;
    }
}
