<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncAllSemester extends Command
{
    protected $signature = 'sync:all-semester {--tahun=}';
    protected $description = 'Sync jadwal dosen untuk semester Ganjil dan Genap sekaligus';

    public function handle()
    {
        $tahun = $this->option('tahun');
        
        if (!$tahun) {
            $periodeAktif = \App\Models\PeriodeAkademik::where('is_active', true)->first();
            
            if (!$periodeAktif) {
                $this->error('❌ Periode akademik aktif tidak ditemukan');
                $this->info('💡 Gunakan: php artisan sync:all-semester --tahun=2025');
                return 1;
            }
            
            $tahun = $periodeAktif->tahun_ajaran;
        }

        $this->info("🔄 Mulai sync SEMUA semester untuk tahun ajaran {$tahun}");
        $this->newLine();

        // Sync Semester 1 (Ganjil)
        $this->info("📚 [1/2] Sync Semester GANJIL...");
        $this->call('sync:jadwal-dosen-now', [
            '--semester' => '1',
            '--tahun' => $tahun
        ]);
        
        $this->newLine(2);
        
        // Sync Semester 2 (Genap)
        $this->info("📚 [2/2] Sync Semester GENAP...");
        $this->call('sync:jadwal-dosen-now', [
            '--semester' => '2',
            '--tahun' => $tahun
        ]);

        $this->newLine(2);
        
        // Summary
        $totalSemester1 = \DB::table('jadwal_dosen')
            ->where('semester', '1')
            ->where('tahun_ajaran', $tahun)
            ->count();
            
        $totalSemester2 = \DB::table('jadwal_dosen')
            ->where('semester', '2')
            ->where('tahun_ajaran', $tahun)
            ->count();
        
        $this->info("✅ SYNC SELESAI!");
        $this->newLine();
        
        $this->table(
            ['Semester', 'Jumlah Jadwal'],
            [
                ['Ganjil (1)', $totalSemester1],
                ['Genap (2)', $totalSemester2],
                ['TOTAL', $totalSemester1 + $totalSemester2],
            ]
        );

        $this->newLine();
        $this->info("💡 Jangan lupa clear cache:");
        $this->line("   php artisan cache:clear");

        return 0;
    }
}
