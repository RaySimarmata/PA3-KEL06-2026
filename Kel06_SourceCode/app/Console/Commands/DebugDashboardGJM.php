<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HasilAnalisisMongo;
use Illuminate\Support\Facades\Cache;

class DebugDashboardGJM extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gjm:debug-dashboard {--clear-cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug Dashboard GJM - Lihat struktur data MongoDB dan clear cache';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=================================');
        $this->info('DEBUG DASHBOARD GJM');
        $this->info('=================================');
        
        // Clear cache jika diminta
        if ($this->option('clear-cache')) {
            Cache::forget('filter_list_tahun');
            Cache::forget('filter_list_prodi');
            
            $this->info('✓ Cache cleared!');
        }
        
        // Check total records
        $total = HasilAnalisisMongo::count();
        $this->info("\nTotal records di MongoDB: {$total}");
        
        if ($total == 0) {
            $this->error('❌ TIDAK ADA DATA di collection hasil_analisis_lengkap');
            $this->warn('Silakan jalankan analisis Spark terlebih dahulu!');
            return;
        }
        
        // Sample data
        $this->info("\n--- Sample Data (5 records) ---");
        $samples = HasilAnalisisMongo::query()->limit(5)->get();
        
        foreach ($samples as $i => $sample) {
            $this->info("\nRecord #" . ($i + 1));
            $this->line("  Kuesioner ID: " . ($sample->kuesioner_id ?? 'N/A'));
            $this->line("  Dosen: " . ($sample->dosen_pengajar ?? 'N/A'));
            $this->line("  Kode MK: " . ($sample->kode_mk ?? 'N/A'));
            $this->line("  Tahun: " . ($sample->tahun ?? 'N/A'));
            $this->line("  Semester: " . ($sample->semester ?? 'N/A'));
            
            $prodiData = $sample->prodi;
            
            $this->line("  Prodi (type): " . gettype($prodiData));
            
            if (is_object($prodiData)) {
                $this->line("  Prodi.kode: " . ($prodiData->kode ?? 'TIDAK ADA'));
                $this->line("  Prodi.nama: " . ($prodiData->nama ?? 'TIDAK ADA'));
            } elseif (is_array($prodiData)) {
                $this->line("  Prodi['kode']: " . ($prodiData['kode'] ?? 'TIDAK ADA'));
            } elseif (is_string($prodiData)) {
                $this->line("  Prodi (string): " . $prodiData);
            } else {
                $this->error("  ❌ Prodi format tidak dikenali!");
            }
        }
        
        // Check unique values
        $this->info("\n--- Unique Values ---");
        
        $tahunList = HasilAnalisisMongo::query()->pluck('tahun')->unique()->sort()->values();
        $this->line("Tahun: " . $tahunList->implode(', '));
        
        $semesterList = HasilAnalisisMongo::query()->pluck('semester')->unique()->sort()->values();
        $this->line("Semester: " . $semesterList->implode(', '));
        
        // Extract prodi codes
        $allProdi = HasilAnalisisMongo::query()->pluck('prodi');
        $prodiCodes = [];
        
        foreach ($allProdi as $prodiData) {
            if (is_object($prodiData)) {
                $kode = $prodiData->kode ?? null;
            } elseif (is_array($prodiData)) {
                $kode = $prodiData['kode'] ?? null;
            } elseif (is_string($prodiData)) {
                $kode = $prodiData;
            } else {
                $kode = null;
            }
            
            if (!empty($kode)) {
                $prodiCodes[] = $kode;
            }
        }
        
        $uniqueProdi = collect($prodiCodes)->unique()->sort()->values();
        $this->line("Prodi: " . $uniqueProdi->implode(', '));
        
        // Check dosen
        $dosenList = HasilAnalisisMongo::query()
            ->pluck('dosen_pengajar')
            ->filter()
            ->unique()
            ->count();
        $this->line("Total Dosen Unik: {$dosenList}");
        
        $this->info("\n=================================");
        $this->info("✓ Debug selesai!");
        $this->info("=================================");
        
        // Recommendations
        if ($uniqueProdi->isEmpty()) {
            $this->error("\n⚠️  WARNING: Tidak ada data prodi yang valid!");
            $this->warn("Kemungkinan masalah:");
            $this->warn("1. Field 'prodi' di MongoDB tidak terisi");
            $this->warn("2. Format field 'prodi' tidak sesuai");
            $this->warn("3. Jalankan analisis Spark lagi");
        }
        
        if ($dosenList == 0) {
            $this->error("\n⚠️  WARNING: Tidak ada data dosen!");
            $this->warn("Field 'dosen_pengajar' kosong atau null");
        }
        
        $this->info("\nUntuk clear cache, jalankan:");
        $this->comment("php artisan gjm:debug-dashboard --clear-cache");
    }
}
