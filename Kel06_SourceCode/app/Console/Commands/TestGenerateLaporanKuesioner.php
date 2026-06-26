<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LaporanKuesioneService;
use App\Services\KuesioneWordGenerationService;
use App\Models\LaporanBulanan;
use App\Models\User;
use Carbon\Carbon;

class TestGenerateLaporanKuesioner extends Command
{
    protected $signature = 'test:generate-laporan-kuesioner {periode?} {--prodi-id=} {--template-id=}';
    protected $description = 'Test generate laporan kuesioner dengan data real';

    public function handle()
    {
        $periode = $this->argument('periode') ?? Carbon::now()->format('Y-m');
        $prodiId = $this->option('prodi-id') ?? 4; // Default TRPL
        $templateId = $this->option('template-id');

        $this->info("=== Test Generate Laporan Kuesioner ===");
        $this->info("Periode: {$periode}");
        $this->info("Prodi ID: {$prodiId}");
        $this->info("Template ID: " . ($templateId ?? 'None'));
        $this->newLine();

        try {
            // Step 1: Collect data
            $this->info("Step 1: Collecting kuesioner data...");
            $service = app(LaporanKuesioneService::class);
            $kuesioneList = $service->collectKuesioneData($periode, $prodiId);
            $this->info("✓ Found {$kuesioneList->count()} kuesioner records");
            $this->newLine();

            if ($kuesioneList->isEmpty()) {
                $this->error("No kuesioner data found for periode {$periode}");
                $this->info("Try running: php artisan test:generate-laporan-kuesioner 2025-01");
                return 1;
            }

            // Show sample data
            $this->info("Sample data (first 3 records):");
            foreach ($kuesioneList->take(3) as $k) {
                $this->line("  - {$k->kode_matakuliah} | {$k->nama_matakuliah} | Tingkat: {$k->tingkat} | Index: {$k->index_kepuasan}");
            }
            $this->newLine();

            // Step 2: Aggregate
            $this->info("Step 2: Aggregating statistics...");
            $aggregatedData = $service->aggregateStatistik($kuesioneList);
            $this->info("✓ Total Kuesioner: {$aggregatedData['total_kuesioner']}");
            $this->info("✓ Total Responden: {$aggregatedData['total_responden']}");
            $this->info("✓ Index Kepuasan Rata-rata: {$aggregatedData['index_kepuasan_rata_rata']}");
            $this->newLine();

            // Step 3: Generate laporan with AI
            $this->info("Step 3: Generating laporan with AI...");
            $result = $service->generateLaporan($periode, $prodiId, $templateId);
            $this->info("✓ AI generation completed");
            $this->newLine();

            // Step 4: Create laporan record
            $this->info("Step 4: Creating laporan record...");
            $periodeObj = Carbon::createFromFormat('Y-m', $periode);
            $user = User::where('prodi_id', $prodiId)->first();
            
            if (!$user) {
                $this->error("No user found with prodi_id {$prodiId}");
                return 1;
            }

            $laporan = LaporanBulanan::create([
                'user_id' => $user->id,
                'periode' => $periode,
                'bulan' => $periodeObj->month,
                'tahun' => $periodeObj->year,
                'judul_laporan' => 'Test Laporan Monitoring Kuesioner ' . $periodeObj->format('F Y'),
                'template_id' => $templateId,
                'hasil_laporan' => array_merge(
                    $result['hasil_laporan'],
                    ['_aggregated_data' => $result['aggregated_data']]
                ),
                'status' => 'completed',
            ]);
            $this->info("✓ Laporan created with ID: {$laporan->id}");
            $this->newLine();

            // Step 5: Generate Word
            $this->info("Step 5: Generating Word document...");
            $wordService = app(KuesioneWordGenerationService::class);
            $success = $wordService->generateWordDocument($laporan);
            
            if ($success) {
                $this->info("✓ Word document generated successfully");
                $this->info("✓ File path: {$laporan->file_word}");
                $this->newLine();
                
                $downloadUrl = route('gkm.laporan-kuesioner.download', ['id' => $laporan->id, 'format' => 'word']);
                $this->info("Download URL:");
                $this->line($downloadUrl);
            } else {
                $this->error("✗ Failed to generate Word document");
                return 1;
            }

            $this->newLine();
            $this->info("=== Test Completed Successfully ===");
            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
