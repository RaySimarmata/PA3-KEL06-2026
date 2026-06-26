<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalReminder;
use App\Models\RPS;
use App\Helpers\EmailHelper;
use Carbon\Carbon;

class SendScheduledReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim reminder otomatis berdasarkan jadwal yang telah ditentukan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $currentDate = $now->format('Y-m-d');
        $currentTime = $now->format('H:i');

        $this->info("Checking reminders at {$currentDate} {$currentTime}");

        // Ambil jadwal reminder yang aktif dan sesuai dengan waktu sekarang
        $jadwalReminders = JadwalReminder::where('status', 'aktif')
            ->where('tanggal_mulai', '<=', $currentDate)
            ->where(function($query) use ($currentDate) {
                $query->whereNull('tanggal_selesai')
                      ->orWhere('tanggal_selesai', '>=', $currentDate);
            })
            ->where('waktu_pengiriman', '<=', $currentTime)
            ->get();

        if ($jadwalReminders->isEmpty()) {
            $this->info('No reminders scheduled for this time.');
            return 0;
        }

        foreach ($jadwalReminders as $jadwal) {
            // Cek apakah reminder sudah dikirim hari ini
            $lastSent = $jadwal->last_sent_at ? Carbon::parse($jadwal->last_sent_at) : null;

            if ($lastSent && $lastSent->isToday()) {
                $this->info("Reminder {$jadwal->tipe_reminder} already sent today. Skipping...");
                continue;
            }

            $this->info("Processing reminder: {$jadwal->tipe_reminder}");

            try {
                $result = $this->sendReminder($jadwal);

                if ($result['success']) {
                    // Update last_sent_at
                    $jadwal->update([
                        'last_sent_at' => now(),
                    ]);

                    $this->info("✓ {$result['message']}");
                } else {
                    $this->error("✗ {$result['message']}");
                }
            } catch (\Exception $e) {
                $this->error("Error sending reminder {$jadwal->tipe_reminder}: " . $e->getMessage());
            }
        }

        $this->info('Reminder check completed.');
        return 0;
    }

    /**
     * Kirim reminder berdasarkan tipe
     */
    private function sendReminder($jadwal)
    {
        // Normalisasi tipe reminder
        $tipe = strtolower(str_replace(' ', '_', $jadwal->tipe_reminder));

        switch ($tipe) {
            case 'rps':
            case 'rps_review':
                return $this->sendRPSReminder($jadwal);

            case 'upload_materi':
            case 'materi_upload':
                return $this->sendMateriReminder($jadwal);

            case 'review_soal':
                return $this->sendReviewSoalReminder($jadwal);

            case 'perwalian':
            case 'perwaliaan':
                return $this->sendPerwalianReminder($jadwal);

            default:
                return [
                    'success' => false,
                    'message' => "Unknown reminder type: {$jadwal->tipe_reminder}"
                ];
        }
    }

    /**
     * Kirim reminder RPS
     */
    private function sendRPSReminder($jadwal)
    {
        // Ambil semua dosen aktif di prodi ini
        $dosenList = Dosen::where('prodi_id', $jadwal->prodi_id)
            ->where('status', 'aktif')
            ->get();

        if ($dosenList->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No dosen need RPS reminder'
            ];
        }

        $sentCount = 0;
        foreach ($dosenList as $dosen) {
            $result = EmailHelper::sendReminderRPS($dosen);
            if ($result) {
                $sentCount++;
            }
        }

        return [
            'success' => true,
            'message' => "RPS reminder sent to {$sentCount} dosen"
        ];
    }

    /**
     * Kirim reminder Upload Materi
     */
    private function sendMateriReminder($jadwal)
    {
        // Ambil semua dosen aktif di prodi ini
        $dosenList = Dosen::where('prodi_id', $jadwal->prodi_id)
            ->where('status', 'aktif')
            ->get();

        if ($dosenList->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No dosen need materi reminder'
            ];
        }

        $sentCount = 0;
        foreach ($dosenList as $dosen) {
            $result = EmailHelper::sendReminderUploadMateri($dosen);
            if ($result) {
                $sentCount++;
            }
        }

        return [
            'success' => true,
            'message' => "Materi reminder sent to {$sentCount} dosen"
        ];
    }

    /**
     * Kirim reminder Review Soal
     */
    private function sendReviewSoalReminder($jadwal)
    {
        $dosenList = Dosen::where('prodi_id', $jadwal->prodi_id)
            ->where('status', 'aktif')
            ->get();

        if ($dosenList->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No dosen need review soal reminder'
            ];
        }

        $sentCount = 0;
        foreach ($dosenList as $dosen) {
            $result = EmailHelper::sendReminderReviewSoal($dosen);
            if ($result) {
                $sentCount++;
            }
        }

        return [
            'success' => true,
            'message' => "Review soal reminder sent to {$sentCount} dosen"
        ];
    }

    /**
     * Kirim reminder Perwalian
     */
    private function sendPerwalianReminder($jadwal)
    {
        $dosenWali = Dosen::where('prodi_id', $jadwal->prodi_id)
            ->where('status', 'aktif')
            ->where('is_dosen_wali', true)
            ->whereNotNull('kelas_wali')
            ->get();

        if ($dosenWali->isEmpty()) {
            return [
                'success' => true,
                'message' => 'No dosen wali need perwalian reminder'
            ];
        }

        $sentCount = 0;
        foreach ($dosenWali as $dosen) {
            $result = EmailHelper::sendReminderPerwalian($dosen);
            if ($result) {
                $sentCount++;
            }
        }

        return [
            'success' => true,
            'message' => "Perwalian reminder sent to {$sentCount} dosen wali"
        ];
    }
}

