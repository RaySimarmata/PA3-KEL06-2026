<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JadwalReminder;
use App\Models\User;

class JadwalReminderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gkmUser = User::where('role', 'GKM')->first();

        if (!$gkmUser) {
            $this->command->warn('User GKM tidak ditemukan. Silakan jalankan UserSeeder terlebih dahulu.');
            return;
        }

        $jadwalReminders = [
            [
                'nama_jadwal' => 'Reminder Upload Materi di CIS',
                'tipe_reminder' => 'Upload Materi',
                'jam_pengiriman' => '14:00:00',
                'tanggal_mulai' => now()->addDays(3),
                'tanggal_selesai' => null,
                'is_active' => true,
                'dibuat_oleh' => $gkmUser->id,
            ],
            [
                'nama_jadwal' => 'Reminder Kaprodi Review Soal',
                'tipe_reminder' => 'Review Soal',
                'jam_pengiriman' => '15:00:00',
                'tanggal_mulai' => now()->addDays(7),
                'tanggal_selesai' => null,
                'is_active' => false,
                'dibuat_oleh' => $gkmUser->id,
            ],
        ];

        foreach ($jadwalReminders as $jadwal) {
            JadwalReminder::create($jadwal);
        }

        $this->command->info('Jadwal Reminder berhasil di-seed!');
    }
}
