<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dosenn;
use App\Models\JadwalDosen;
use App\Services\ExternalAPIService;

class SyncJadwalDosenSync extends Command
{
    protected $signature = 'sync:jadwal-dosen-now {--semester=} {--tahun=}';
    protected $description = 'Sync jadwal dosen secara synchronous (langsung tanpa queue)';

    public function handle()
    {
        /*
        |--------------------------------------------------------------------------
        | AMBIL PERIODE AKADEMIK AKTIF ATAU GUNAKAN PARAMETER
        |--------------------------------------------------------------------------
        */
        $semester = $this->option('semester');
        $tahun = $this->option('tahun');

        // Jika tidak ada opsi yang diberikan, cari periode aktif
        if (!$semester || !$tahun) {
            $periodeAktif = \App\Models\PeriodeAkademik::where('is_active', true)->first();

            if (!$periodeAktif) {
                $this->error('❌ Periode akademik aktif tidak ditemukan');
                $this->line('💡 Gunakan opsi --semester dan --tahun untuk sync manual');
                $this->line('   Contoh: php artisan sync:jadwal-dosen-now --semester=1 --tahun=2025');
                return 1;
            }

            $semester = $semester ?? $periodeAktif->semester;
            $tahun = $tahun ?? $periodeAktif->tahun_ajaran;
        }

        $this->info("🔄 Mulai sync jadwal dosen");
        $this->info("📅 Periode: {$tahun} - Semester: {$semester}");
        $this->newLine();

        /*
        |--------------------------------------------------------------------------
        | AMBIL SEMUA DOSEN
        |--------------------------------------------------------------------------
        */
        $dosenList = Dosenn::select('pegawai_id', 'nama', 'jabatan_akademik')->get();
        $total = $dosenList->count();
        
        $this->info("👥 Total dosen: {$total}");
        $this->newLine();

        $apiService = new ExternalAPIService();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $successCount = 0;
        $skipCount = 0;
        $errorCount = 0;
        $totalJadwal = 0;

        foreach ($dosenList as $dosen) {
            try {
                /*
                |--------------------------------------------------------------------------
                | SKIP TENAGA PENGAJAR
                |--------------------------------------------------------------------------
                */
                if (strtoupper(trim($dosen->jabatan_akademik ?? '')) === 'A') {
                    $skipCount++;
                    $bar->advance();
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | AMBIL JADWAL DARI API
                |--------------------------------------------------------------------------
                */
                $jadwalList = $apiService->getJadwalByDosen(
                    $dosen->pegawai_id,
                    $semester,
                    $tahun
                );

                if (empty($jadwalList)) {
                    $bar->advance();
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | SIMPAN / UPDATE
                |--------------------------------------------------------------------------
                */
                foreach ($jadwalList as $jadwal) {
                    $kelas = strtoupper($jadwal['kelas'] ?? '');

                    /*
                    | HANYA AMBIL KELAS TRPL / TI / TK
                    */
                    if (
                        !str_contains($kelas, 'TRPL') &&
                        !str_contains($kelas, 'TI') &&
                        !str_contains($kelas, 'TK')
                    ) {
                        continue;
                    }

                    JadwalDosen::updateOrCreate(
                        [
                            'kuliah_id' => $jadwal['jadwal_id'] ?? null,
                            'pegawai_id' => $dosen->pegawai_id,
                        ],
                        [
                            'kode_mk' => $jadwal['kode_mk'] ?? null,
                            'semester' => $semester,
                            'tahun_ajaran' => $tahun,
                            'kelas' => $jadwal['kelas'] ?? null,
                            'is_manual' => false,
                        ]
                    );

                    $totalJadwal++;
                }

                $successCount++;

            } catch (\Exception $e) {
                $errorCount++;
                \Log::error("Failed to sync dosen {$dosen->pegawai_id}", [
                    'error' => $e->getMessage()
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */
        $this->info('✅ Sync selesai!');
        $this->newLine();
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Berhasil', $successCount],
                ['Dilewati (Tenaga Pengajar)', $skipCount],
                ['Error', $errorCount],
                ['Total Jadwal Tersimpan', $totalJadwal],
            ]
        );

        // Verify
        $this->newLine();
        $totalInDb = JadwalDosen::where('semester', $semester)
            ->where('tahun_ajaran', $tahun)
            ->count();
        
        $this->info("📊 Total jadwal di database: {$totalInDb}");

        return 0;
    }
}
