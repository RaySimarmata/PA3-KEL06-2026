<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ajaran;
use App\Models\Prodi;
use Carbon\Carbon;

class AjaranSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $prodi = Prodi::all();
        
        if ($prodi->isEmpty()) {
            $this->command->warn('No prodi found. Please seed prodi first.');
            return;
        }

        $tahunAjaran = ['2022/2023', '2023/2024', '2024/2025', '2025/2026'];
        $semester = [1, 2]; // 1 = Ganjil, 2 = Genap

        foreach ($prodi as $p) {
            foreach ($tahunAjaran as $tahun) {
                foreach ($semester as $sem) {
                    // Tentukan tanggal mulai dan akhir berdasarkan semester
                    $year = explode('/', $tahun)[0];
                    
                    if ($sem == 1) { // Semester Ganjil
                        $tanggalMulai = Carbon::create($year, 9, 1); // September
                        $tanggalAkhir = Carbon::create($year + 1, 1, 31); // Januari tahun berikutnya
                    } else { // Semester Genap
                        $tanggalMulai = Carbon::create($year + 1, 2, 1); // Februari
                        $tanggalAkhir = Carbon::create($year + 1, 6, 30); // Juni
                    }

                    Ajaran::create([
                        'prodi_id' => $p->id,
                        'tahun_ajaran' => $tahun,
                        'semester' => $sem,
                        'tanggal_mulai' => $tanggalMulai,
                        'tanggal_akhir' => $tanggalAkhir,
                        'status' => $tahun == '2024/2025' ? 'aktif' : 'selesai',
                    ]);
                }
            }
        }

        $this->command->info('Ajaran seeder completed successfully!');
    }
}