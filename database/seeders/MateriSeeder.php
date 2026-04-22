<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Materi;
use App\Models\Matakuliah;
use App\Models\Dosen;
use App\Models\RPS;
use Carbon\Carbon;

class MateriSeeder extends Seeder
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

        $materiTopics = [
            'Pengenalan dan Konsep Dasar',
            'Teori dan Landasan',
            'Implementasi dan Praktik',
            'Studi Kasus',
            'Evaluasi dan Assessment',
            'Project dan Tugas Akhir',
            'Review dan Diskusi',
            'Presentasi Mahasiswa'
        ];

        foreach ($matakuliah as $mk) {
            // Cari RPS untuk matakuliah ini
            $rps = RPS::where('matakuliah_id', $mk->id)->first();
            
            // Generate 6-10 materi per matakuliah
            $materiCount = rand(6, 10);
            
            for ($i = 1; $i <= $materiCount; $i++) {
                // 75% materi sudah ada file, 25% belum
                $hasFile = rand(1, 100) <= 75;
                
                $randomDosen = $dosen->random();
                $topic = $materiTopics[array_rand($materiTopics)];
                
                Materi::create([
                    'rps_id' => $rps ? $rps->id : null,
                    'matakuliah_id' => $mk->id,
                    'dosen_id' => $randomDosen->id,
                    'judul_materi' => "Pertemuan {$i}: {$topic}",
                    'deskripsi_materi' => "Materi pembelajaran {$topic} untuk mata kuliah {$mk->nama_mk}",
                    'file_materi' => $hasFile ? "uploads/materi/{$mk->kode_mk}/pertemuan_{$i}.pdf" : null,
                    'jenis_file' => $hasFile ? 'pdf' : null,
                    'status_upload_materi' => $hasFile ? 'uploaded' : 'not_uploaded',
                    'tanggal_upload_materi' => $hasFile ? Carbon::now()->subDays(rand(1, 60)) : null,
                    'lokasi_upload' => $hasFile ? 'server' : null,
                    'created_at' => Carbon::now()->subDays(rand(1, 90)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 30)),
                ]);
            }
        }

        $this->command->info('Materi seeder completed successfully!');
    }
}