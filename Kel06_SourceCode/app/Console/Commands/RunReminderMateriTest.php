<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class RunReminderMateriTest extends Command
{
    protected $signature = 'test:reminder-materi';
    protected $description = 'Simulate reminderMateri logic for UTS (week8) and UAS (week16) without DB changes';

    public function handle()
    {
        $this->info('Running simulation for reminderMateri');

        $this->simulateForWeek(8, 'UTS (week 8) - check weeks 1..7');
        $this->line('');
        $this->simulateForWeek(16, 'UAS (week 16) - check weeks 1..15');

        $this->info('Simulation finished.');

        return 0;
    }

    private function simulateForWeek(int $currentWeek, string $label)
    {
        $this->info("Simulating: $label");

        if ($currentWeek === 8) {
            $checkRange = range(1, 7);
        } elseif ($currentWeek === 16) {
            $checkRange = range(1, 15);
        } else {
            $this->warn('Not a reminder week in this simulation.');
            return;
        }

        // Create in-memory fake snapshots
        $snapshots = collect([
            (object) [
                'pegawai_id' => 'P001',
                'dosen' => (object) ['nama' => 'Dr. A', 'email' => 'a@example.com'],
                'nama_matkul' => 'Matematika',
                'kode_mk' => 'MK001',
                'semester' => '1',
                'tahun_ajaran' => '2025/2026',
                'tingkat' => '1',
                'jenis_materi' => 'Materi Teori',
                'prodi_id' => 4,
                'status_upload' => 'BELUM UPLOAD',
                'reminder_sent' => false,
                'raw_data' => ['weeks' => $this->makeWeeksWithMissing([1])], // missing week 1
            ],
            (object) [
                'pegawai_id' => 'P002',
                'dosen' => (object) ['nama' => 'Dr. B', 'email' => 'b@example.com'],
                'nama_matkul' => 'Fisika',
                'kode_mk' => 'MK002',
                'semester' => '1',
                'tahun_ajaran' => '2025/2026',
                'tingkat' => '2',
                'jenis_materi' => 'Materi Praktikum',
                'prodi_id' => 4,
                'status_upload' => 'BELUM UPLOAD',
                'reminder_sent' => false,
                'raw_data' => ['weeks' => $this->makeWeeksWithMissing([5,9])], // missing week 5 (in UTS range), week9 (outside UTS range)
            ],
            (object) [
                'pegawai_id' => 'P003',
                'dosen' => (object) ['nama' => 'Dr. C', 'email' => 'c@example.com'],
                'nama_matkul' => 'Kimia',
                'kode_mk' => 'MK003',
                'semester' => '1',
                'tahun_ajaran' => '2025/2026',
                'tingkat' => '3',
                'jenis_materi' => 'Materi Teori',
                'prodi_id' => 4,
                'status_upload' => 'BELUM UPLOAD',
                'reminder_sent' => true, // already sent -> should be skipped
                'raw_data' => ['weeks' => $this->makeWeeksWithMissing([2])],
            ],
        ]);

        $filtered = $snapshots->filter(function($snapshot) use ($checkRange) {
            if ($snapshot->status_upload !== 'BELUM UPLOAD') return false;
            if (!empty($snapshot->reminder_sent)) return false;

            $weeks = $snapshot->raw_data['weeks'] ?? null;
            if (!is_array($weeks)) return false;

            foreach ($checkRange as $w) {
                $idx = $w - 1;
                if (!isset($weeks[$idx]) || $weeks[$idx] === 0 || $weeks[$idx] === null) {
                    return true;
                }
            }
            return false;
        });

        $this->info('Reminders to be sent for:');
        foreach ($filtered as $s) {
            $this->line("- {$s->dosen->nama} ({$s->dosen->email}) - {$s->nama_matkul} [{$s->kode_mk}] - jenis: {$s->jenis_materi}");
        }

        if ($filtered->isEmpty()) {
            $this->line('  (none)');
        }
    }

    private function makeWeeksWithMissing(array $missingIndexes)
    {
        // create full array of 16 weeks with 1 = uploaded, 0 = missing
        $weeks = array_fill(0, 16, 1);
        foreach ($missingIndexes as $m) {
            $idx = $m - 1;
            if ($idx >= 0 && $idx < 16) $weeks[$idx] = 0;
        }
        return $weeks;
    }
}
