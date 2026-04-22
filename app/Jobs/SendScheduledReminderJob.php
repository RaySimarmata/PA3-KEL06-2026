<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\JadwalReminder;
use App\Models\Dosen;
use App\Helpers\EmailHelper;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendScheduledReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jadwal;

    /**
     * Create a new job instance.
     */
    public function __construct(JadwalReminder $jadwal)
    {
        $this->jadwal = $jadwal;
        
        // Set delay sampai waktu pengiriman
        $scheduledTime = Carbon::parse($jadwal->tanggal_mulai->format('Y-m-d') . ' ' . $jadwal->jam_pengiriman);
        $now = Carbon::now();
        
        if ($scheduledTime->isFuture()) {
            $delaySeconds = $now->diffInSeconds($scheduledTime);
            $this->delay($delaySeconds);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Processing scheduled reminder job", [
                'jadwal_id' => $this->jadwal->id,
                'tipe' => $this->jadwal->tipe_reminder
            ]);

            // Cek apakah jadwal masih aktif
            $jadwal = JadwalReminder::find($this->jadwal->id);
            
            if (!$jadwal || !$jadwal->is_active) {
                Log::info("Jadwal tidak aktif atau sudah dihapus", ['jadwal_id' => $this->jadwal->id]);
                return;
            }

            // Cek apakah sudah dikirim hari ini
            if ($jadwal->last_sent_at && Carbon::parse($jadwal->last_sent_at)->isToday()) {
                Log::info("Email sudah dikirim hari ini", ['jadwal_id' => $jadwal->id]);
                return;
            }

            // Ambil dosen berdasarkan tipe reminder
            $dosenList = $this->getDosenByReminderType($jadwal);

            if ($dosenList->isEmpty()) {
                Log::warning("No dosen found for reminder", [
                    'jadwal_id' => $jadwal->id,
                    'tipe' => $jadwal->tipe_reminder
                ]);
                return;
            }

            // Kirim email ke setiap dosen
            $sentCount = 0;
            $failedCount = 0;

            foreach ($dosenList as $dosen) {
                try {
                    $result = $this->sendReminderByType($jadwal->tipe_reminder, $dosen);
                    
                    if ($result) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error("Failed to send reminder to dosen", [
                        'dosen' => $dosen->nama_lengkap,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Update last_sent_at
            $jadwal->update(['last_sent_at' => now()]);

            Log::info("Reminder job completed", [
                'jadwal_id' => $jadwal->id,
                'sent' => $sentCount,
                'failed' => $failedCount
            ]);

        } catch (\Exception $e) {
            Log::error("Error in SendScheduledReminderJob", [
                'jadwal_id' => $this->jadwal->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Ambil dosen berdasarkan tipe reminder
     */
    private function getDosenByReminderType($jadwal)
    {
        $tipe = strtolower(str_replace(' ', '_', $jadwal->tipe_reminder));

        // Untuk perwalian, hanya ambil dosen wali
        if ($tipe === 'perwalian' || $tipe === 'perwaliaan') {
            return Dosen::where('prodi_id', $jadwal->prodi_id)
                ->where('status', 'aktif')
                ->where('is_dosen_wali', true)
                ->whereNotNull('kelas_wali')
                ->get();
        }

        // Untuk tipe lainnya, ambil semua dosen aktif
        return Dosen::where('prodi_id', $jadwal->prodi_id)
            ->where('status', 'aktif')
            ->get();
    }

    /**
     * Kirim reminder berdasarkan tipe menggunakan AI Agent
     */
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
            
            default:
                Log::error("Unknown reminder type", ['tipe' => $tipe]);
                return false;
        }
    }
}
