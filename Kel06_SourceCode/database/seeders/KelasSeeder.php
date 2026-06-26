<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kelas;
use App\Models\Prodi;

class KelasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua prodi
        $prodis = Prodi::all();

        if ($prodis->isEmpty()) {
            $this->command->warn('Tidak ada prodi. Jalankan GKMProdiSeeder terlebih dahulu.');
            return;
        }

        foreach ($prodis as $prodi) {
            $programStudi = $prodi->kode_prodi; // TRPL, TI, NM
            
            // Buat kelas untuk setiap tingkat (1-4) dan 2 kelas per tingkat
            for ($tingkat = 1; $tingkat <= 4; $tingkat++) {
                for ($nomorKelas = 1; $nomorKelas <= 2; $nomorKelas++) {
                    $tahunAngkatan = date('Y') - (4 - $tingkat); // Hitung tahun angkatan
                    
                    // Format: 4[tingkat][program_studi][nomor_kelas]
                    // Contoh: 41TRPL1, 42TRPL2, 43TI1, dll
                    $kodeKelas = '4' . $tingkat . $programStudi . $nomorKelas;
                    
                    Kelas::create([
                        'prodi_id' => $prodi->id,
                        'kode_kelas' => $kodeKelas,
                        'tingkat' => $tingkat,
                        'program_studi' => $programStudi,
                        'tahun_angkatan' => $tahunAngkatan,
                        'status' => 'aktif',
                    ]);
                }
            }
            
            $this->command->info("Kelas untuk prodi {$programStudi} berhasil dibuat!");
        }

        $this->command->info('Semua kelas berhasil di-seed!');
    }
}
