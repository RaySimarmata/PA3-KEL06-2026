<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KuesioneUpload;
use App\Services\AIAgentService;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReprocessKuesioneWithAI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kuesioner:reprocess-ai {id? : ID kuesioner yang akan diproses ulang}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess kuesioner analysis menggunakan Pure AI Agent';

    protected $aiAgent;

    /**
     * Create a new command instance.
     */
    public function __construct(AIAgentService $aiAgent)
    {
        parent::__construct();
        $this->aiAgent = $aiAgent;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $kuesioneId = $this->argument('id');

        if ($kuesioneId) {
            // Process single kuesioner
            $this->processSingleKuesioner($kuesioneId);
        } else {
            // Process all error/uploaded kuesioner
            $this->processAllErrorKuesioner();
        }

        return 0;
    }

    private function processSingleKuesioner($id)
    {
        $kuesioner = KuesioneUpload::with(['user', 'user.prodi'])->find($id);

        if (!$kuesioner) {
            $this->error("Kuesioner dengan ID {$id} tidak ditemukan");
            return;
        }

        $this->info("Processing kuesioner: {$kuesioner->nama_file}");
        $this->info("Status saat ini: {$kuesioner->status}");

        try {
            $kuesioner->update(['status' => 'processing']);

            // Baca file Excel
            $filePath = storage_path('app/public/' . $kuesioner->file_path);
            
            if (!file_exists($filePath)) {
                throw new \Exception('File tidak ditemukan');
            }

            $this->info("Reading Excel file...");
            $excelData = $this->bacaExcelAman($filePath);
            
            $this->info("Data loaded: " . count($excelData['data']) . " responden");
            $this->info("Calling AI Agent for analysis...");

            // PURE AI AGENT
            $analysisResult = $this->aiAgent->analyzeKuesioner($kuesioner, $excelData);

            // Update hasil analisis
            $kuesioner->update([
                'hasil_analisis' => $analysisResult,
                'status' => 'completed'
            ]);

            $this->info("✓ Kuesioner berhasil diproses oleh AI Agent");
            $this->info("Index Kepuasan: " . ($analysisResult['statistik']['index_kepuasan'] ?? 'N/A') . "%");

        } catch (\Exception $e) {
            $this->error("✗ Gagal memproses kuesioner: " . $e->getMessage());
            
            $kuesioner->update([
                'status' => 'error',
                'hasil_analisis' => [
                    'error' => 'AI Agent gagal: ' . $e->getMessage(),
                    'ringkasan' => 'Terjadi kesalahan saat AI Agent memproses kuesioner',
                    'poin_positif' => ['File berhasil diupload'],
                    'area_perbaikan' => ['Periksa koneksi AI Agent'],
                    'rekomendasi' => ['Coba proses ulang'],
                    'statistik' => [
                        'index_kepuasan' => 0,
                        'total_responden' => 0
                    ]
                ]
            ]);
        }
    }

    private function processAllErrorKuesioner()
    {
        $kuesioners = KuesioneUpload::with(['user', 'user.prodi'])
            ->whereIn('status', ['error', 'uploaded'])
            ->get();

        if ($kuesioners->isEmpty()) {
            $this->info("Tidak ada kuesioner yang perlu diproses ulang");
            return;
        }

        $this->info("Ditemukan " . $kuesioners->count() . " kuesioner yang perlu diproses");

        $bar = $this->output->createProgressBar($kuesioners->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($kuesioners as $kuesioner) {
            try {
                $kuesioner->update(['status' => 'processing']);

                $filePath = storage_path('app/public/' . $kuesioner->file_path);
                
                if (!file_exists($filePath)) {
                    throw new \Exception('File tidak ditemukan');
                }

                $excelData = $this->bacaExcelAman($filePath);
                $analysisResult = $this->aiAgent->analyzeKuesioner($kuesioner, $excelData);

                $kuesioner->update([
                    'hasil_analisis' => $analysisResult,
                    'status' => 'completed'
                ]);

                $success++;

            } catch (\Exception $e) {
                $kuesioner->update([
                    'status' => 'error',
                    'hasil_analisis' => [
                        'error' => 'AI Agent gagal: ' . $e->getMessage(),
                        'statistik' => ['index_kepuasan' => 0]
                    ]
                ]);
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Proses selesai:");
        $this->info("✓ Berhasil: {$success}");
        $this->info("✗ Gagal: {$failed}");
    }

    private function bacaExcelAman($filePath)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $data = [];
            $headers = [];
            
            // Baca header
            $headerRow = $worksheet->getRowIterator(1, 1)->current();
            $cellIterator = $headerRow->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            foreach ($cellIterator as $cell) {
                try {
                    $value = $cell->getValue();
                    $headers[] = is_null($value) ? '' : (string)$value;
                } catch (\Exception $e) {
                    $headers[] = '';
                }
                
                if (count($headers) >= 20) break;
            }
            
            // Baca data
            $maxRow = min($worksheet->getHighestRow(), 100);
            for ($rowNum = 2; $rowNum <= $maxRow; $rowNum++) {
                try {
                    $rowData = [];
                    $row = $worksheet->getRowIterator($rowNum, $rowNum)->current();
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    
                    $colCount = 0;
                    foreach ($cellIterator as $cell) {
                        if ($colCount >= count($headers)) break;
                        
                        try {
                            $value = $cell->getValue();
                            $rowData[] = is_null($value) ? '' : (string)$value;
                        } catch (\Exception $e) {
                            $rowData[] = '';
                        }
                        $colCount++;
                    }
                    
                    if (!empty($rowData[0]) && !is_numeric($rowData[0]) && 
                        (strpos(strtolower($rowData[0]), 'anonymous') !== false || 
                         strpos(strtolower($rowData[0]), 'peserta') !== false)) {
                        $data[] = $rowData;
                    }
                    
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            return ['headers' => $headers, 'data' => $data];
            
        } catch (\Exception $e) {
            throw new \Exception('Gagal membaca Excel: ' . $e->getMessage());
        }
    }
}
