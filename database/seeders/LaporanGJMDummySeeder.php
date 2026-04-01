<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LaporanGJM;
use App\Models\User;
use Carbon\Carbon;

class LaporanGJMDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get GJM user
        $gjmUser = User::where('role', 'GJM')->first();
        
        if (!$gjmUser) {
            $gjmUser = User::create([
                'name' => 'Admin GJM',
                'email' => 'gjm@example.com',
                'password' => bcrypt('password'),
                'role' => 'GJM',
            ]);
        }

        // Get or create ajaran
        $ajaran = \App\Models\Ajaran::first();
        if (!$ajaran) {
            $ajaran = \App\Models\Ajaran::create([
                'tahun_ajaran' => '2023/2024',
                'semester' => 'ganjil',
                'is_active' => true,
            ]);
        }

        $laporanData = [
            [
                'jenis_laporan' => 'triwulan',
                'program_studi' => 'Teknik Informatika',
                'ringkasan_mutu_institusi' => 'Laporan Bulanan Januari 2024',
                'periode_mulai' => '2024-01-01',
                'periode_akhir' => '2024-03-31',
                'status_laporan' => 'approved',
                'created_at' => Carbon::parse('2024-02-01'),
            ],
            [
                'jenis_laporan' => 'semester',
                'program_studi' => 'Sistem Informasi',
                'ringkasan_mutu_institusi' => 'Analisis Strategis Q4 2023',
                'periode_mulai' => '2023-10-01',
                'periode_akhir' => '2024-03-31',
                'status_laporan' => 'approved',
                'created_at' => Carbon::parse('2024-01-15'),
            ],
            [
                'jenis_laporan' => 'semester',
                'program_studi' => 'Teknik Elektro',
                'ringkasan_mutu_institusi' => 'Evaluasi Kinerja Tahunan',
                'periode_mulai' => '2023-08-01',
                'periode_akhir' => '2024-01-31',
                'status_laporan' => 'draft',
                'created_at' => Carbon::parse('2024-01-10'),
            ],
            [
                'jenis_laporan' => 'triwulan',
                'program_studi' => 'Teknik Mesin',
                'ringkasan_mutu_institusi' => 'Laporan Operasional Mingguan',
                'periode_mulai' => '2023-10-01',
                'periode_akhir' => '2023-12-31',
                'status_laporan' => 'approved',
                'created_at' => Carbon::parse('2024-01-05'),
            ],
        ];

        foreach ($laporanData as $data) {
            LaporanGJM::create([
                'ajaran_id' => $ajaran->id,
                'jenis_laporan' => $data['jenis_laporan'],
                'program_studi' => $data['program_studi'],
                'periode_mulai' => $data['periode_mulai'],
                'periode_akhir' => $data['periode_akhir'],
                'ringkasan_mutu_institusi' => $data['ringkasan_mutu_institusi'],
                'analisis_kepatuhan' => 'Analisis kepatuhan untuk ' . $data['ringkasan_mutu_institusi'],
                'temuan_utama' => 'Temuan utama dari evaluasi periode ini',
                'rekomendasi_perbaikan' => 'Rekomendasi perbaikan berdasarkan temuan',
                'rencana_tindakan' => 'Rencana tindakan untuk periode selanjutnya',
                'status_laporan' => $data['status_laporan'],
                'created_by' => $gjmUser->id,
                'instruksi_prompt' => 'Generate laporan dengan fokus pada peningkatan kualitas',
                'created_at' => $data['created_at'],
                'updated_at' => $data['created_at'],
            ]);
        }
    }
}