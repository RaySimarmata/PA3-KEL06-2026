<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalReminder;
use App\Models\Dosenn as Dosen;
use App\Helpers\EmailHelper;

class TestSendReminder extends Command
{
    protected $signature = 'test:send-reminder {jadwal_id}';
    protected $description = 'Test kirim reminder untuk jadwal tertentu (bypass waktu)';

    public function handle()
    {
        $jadwalId = $this->argument('jadwal_id');
        
        $jadwal = JadwalReminder::find($jadwalId);
        
        if (!$jadwal) {
            $this->error("Jadwal dengan ID {$jadwalId} tidak ditemukan!");
            return 1;
        }
        
        $this->info("Testing reminder: {$jadwal->nama_jadwal}");
        $this->info("Tipe: {$jadwal->tipe_reminder}");
        $this->info("Prodi ID: {$jadwal->prodi_id}");
        
        // Ambil dosen berdasarkan prodi
        $dosenList = Dosen::where('prodi_id', $jadwal->prodi_id)
            ->where('status', 'aktif')
            ->get();
        
        $this->info("Total dosen ditemukan: " . $dosenList->count());
        
        if ($dosenList->isEmpty()) {
            $this->warn("Tidak ada dosen aktif di prodi ini!");
            return 0;
        }
        
        // Tampilkan daftar dosen
        $this->info("\nDaftar dosen yang akan menerima email:");
        foreach ($dosenList as $dosen) {
            $this->line("  - {$dosen->nama_lengkap} ({$dosen->kontak_email})");
        }
        
        if (!$this->confirm('Lanjutkan kirim email?', true)) {
            $this->info('Dibatalkan.');
            return 0;
        }
        
        // Kirim email
        $sentCount = 0;
        $failedCount = 0;
        
        foreach ($dosenList as $dosen) {
            $this->info("Mengirim ke: {$dosen->kontak_email}...");
            
            try {
                $result = $this->sendReminderByType($jadwal->tipe_reminder, $dosen);
                
                if ($result) {
                    $sentCount++;
                    $this->info("  ✓ Berhasil");
                } else {
                    $failedCount++;
                    $this->error("  ✗ Gagal");
                }
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("  ✗ Error: " . $e->getMessage());
            }
        }
        
        $this->info("\n=== HASIL ===");
        $this->info("Berhasil: {$sentCount}");
        $this->info("Gagal: {$failedCount}");
        
        // Update last_sent_at
        $jadwal->update(['last_sent_at' => now()]);
        
        return 0;
    }
    
    private function sendReminderByType($tipe, $dosen)
    {
        $tipe = strtolower(str_replace(' ', '_', $tipe));
        
        switch ($tipe) {
            case 'rps':
            case 'rps_review':
                return EmailHelper::sendReminderRPS($dosen);
            
            case 'upload_materi':
            case 'materi_upload':
                return EmailHelper::sendReminderUploadMateri($dosen);
            
            case 'review_soal':
                return EmailHelper::sendReminderReviewSoal($dosen);
            
            case 'perwalian':
            case 'perwaliaan':
                return EmailHelper::sendReminderPerwalian($dosen);
            
            default:
                $this->error("Tipe reminder tidak dikenali: {$tipe}");
                return false;
        }
    }
}
