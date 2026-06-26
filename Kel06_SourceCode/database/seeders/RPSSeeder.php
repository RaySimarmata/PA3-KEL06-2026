<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RPS;
use App\Models\Matakuliah;
use App\Models\Dosen;
use App\Models\Ajaran;
use Carbon\Carbon;

class RPSSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Pastikan ada matakuliah
        $matakuliah = Matakuliah::all();
        
        if ($matakuliah->isEmpty()) {
            $this->command->warn('No matakuliah found. Please seed matakuliah first.');
            return;
        }

        // Pastikan ada dosen
        $dosen = Dosen::all();
        
        if ($dosen->isEmpty()) {
            $this->command->warn('No dosen found. Please seed dosen first.');
            return;
        }

        // Pastikan ada ajaran
        $ajaran = Ajaran::first();
        
        if (!$ajaran) {
            $this->command->warn('No ajaran found. Please seed ajaran first.');
            return;
        }

        foreach ($matakuliah as $mk) {
            // 80% RPS sudah ada file, 20% belum
            $hasFile = rand(1, 100) <= 80;
            
            $randomDosen = $dosen->random();
            
            RPS::create([
                'matakuliah_id' => $mk->id,
                'ajaran_id' => $ajaran->id,
                'dosen_id' => $randomDosen->id,
                'deskripsi' => 'RPS untuk mata kuliah ' . $mk->nama_mk,
                'capaian_pembelajaran' => 'Mahasiswa mampu memahami dan menerapkan konsep ' . $mk->nama_mk,
                'strategi_pembelajaran' => 'Ceramah, diskusi, praktikum, dan studi kasus',
                'penugasan' => 'Tugas individu dan kelompok',
                'penilaian' => 'UTS 30%, UAS 40%, Tugas 20%, Kehadiran 10%',
                'file_rps' => $hasFile ? 'uploads/rps/rps_' . strtolower(str_replace(' ', '_', $mk->nama_mk)) . '.pdf' : null,
                'status_rps' => $hasFile ? 'sudah_divalidasi' : 'draft',
                'tanggal_upload' => $hasFile ? Carbon::now()->subDays(rand(1, 90)) : null,
                'tanggal_validasi' => $hasFile ? Carbon::now()->subDays(rand(1, 60)) : null,
                'catatan_validasi' => $hasFile ? 'RPS telah disetujui' : null,
                'created_at' => Carbon::now()->subDays(rand(1, 90)),
                'updated_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);
        }

        $this->command->info('RPS seeder completed successfully!');
    }
}