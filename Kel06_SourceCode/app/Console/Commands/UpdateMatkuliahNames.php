<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KuesioneUpload;
use App\Models\Matakuliah;
use App\Services\ExternalAPIService;

class UpdateMatkuliahNames extends Command
{
    protected $signature = 'kuesioner:update-matkul-names';
    protected $description = 'Update nama matakuliah yang kosong di kuesioner_uploads';

    protected $apiService;

    public function __construct(ExternalAPIService $apiService)
    {
        parent::__construct();
        $this->apiService = $apiService;
    }

    public function handle()
    {
        $this->info('Memulai update nama matakuliah...');

        $kuesioners = KuesioneUpload::whereNull('nama_matakuliah')
            ->orWhere('nama_matakuliah', '')
            ->orWhere('nama_matakuliah', '-')
            ->get();

        $this->info('Ditemukan ' . $kuesioners->count() . ' kuesioner dengan nama_matakuliah kosong');

        $updated = 0;
        $failed = 0;

        foreach ($kuesioners as $k) {
            if (empty($k->kode_matakuliah)) {
                $this->warn("Kuesioner ID {$k->id}: kode_matakuliah kosong, skip");
                $failed++;
                continue;
            }

            // Coba dari database lokal
            $matkul = Matakuliah::where('kode_mk', $k->kode_matakuliah)->first();
            
            if ($matkul) {
                $k->update(['nama_matakuliah' => $matkul->nama_mk]);
                $this->info("✓ Kuesioner ID {$k->id}: {$matkul->nama_mk} (dari DB)");
                $updated++;
                continue;
            }

            // Jika tidak ada, coba dari API eksternal
            try {
                $user = $k->user;
                if (!$user || !$user->prodi) {
                    $this->warn("Kuesioner ID {$k->id}: user/prodi tidak ditemukan");
                    $failed++;
                    continue;
                }

                $prodiKode = $user->prodi->kode_prodi;
                $prodiIdMap = ['TRPL' => 4, 'TI' => 1, 'NM' => 3, 'TK' => 2];
                $prodiId = $prodiIdMap[$prodiKode] ?? 4;

                // Extract semester dan tahun dari periode
                $periode = $k->periode;
                $semester = $k->semester ?? ($k->periode ? (stripos($k->periode, 'Genap') !== false ? '2' : '1') : '1');
                $ta = $k->periode;

                $matkulData = $this->apiService->getMatkulByProdiSemTa($prodiId, $semester, $ta);

                if ($matkulData && is_array($matkulData)) {
                    $matkulData = isset($matkulData['data']) ? $matkulData['data'] : $matkulData;

                    foreach ($matkulData as $mk) {
                        if (is_object($mk)) $mk = (array) $mk;
                        
                        if (isset($mk['kode_mk']) && $mk['kode_mk'] == $k->kode_matakuliah) {
                            $namaMk = $mk['nama_matkul'] ?? $mk['nama_mk'] ?? null;
                            if ($namaMk) {
                                $k->update(['nama_matakuliah' => $namaMk]);
                                $this->info("✓ Kuesioner ID {$k->id}: {$namaMk} (dari API)");
                                $updated++;
                                break 2;
                            }
                        }
                    }
                }

                $this->warn("✗ Kuesioner ID {$k->id}: tidak ditemukan di API");
                $failed++;

            } catch (\Exception $e) {
                $this->error("✗ Kuesioner ID {$k->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("\n=== SUMMARY ===");
        $this->info("Total diproses: {$kuesioners->count()}");
        $this->info("Berhasil update: {$updated}");
        $this->info("Gagal: {$failed}");

        return 0;
    }
}
