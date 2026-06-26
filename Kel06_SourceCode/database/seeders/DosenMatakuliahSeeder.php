<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Dosen;
use App\Models\Matakuliah;

class DosenMatakuliahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua dosen
        $dosenList = Dosen::all();
        
        // Ambil beberapa mata kuliah
        $matkulList = Matakuliah::take(10)->get();
        
        if ($matkulList->isEmpty()) {
            $this->command->warn('Tidak ada data mata kuliah. Membuat data dummy...');
            
            // Buat mata kuliah dummy
            $matkulDummy = [
                ['kode_mk' => 'TRP001', 'nama_mk' => 'Pemrograman Web'],
                ['kode_mk' => 'TRP002', 'nama_mk' => 'Basis Data'],
                ['kode_mk' => 'TRP003', 'nama_mk' => 'Algoritma dan Struktur Data'],
                ['kode_mk' => 'TRP004', 'nama_mk' => 'Rekayasa Perangkat Lunak'],
                ['kode_mk' => 'TRP005', 'nama_mk' => 'Sistem Operasi'],
            ];
            
            foreach ($matkulDummy as $mk) {
                Matakuliah::firstOrCreate(
                    ['kode_mk' => $mk['kode_mk']],
                    [
                        'nama_mk' => $mk['nama_mk'],
                        'sks' => 3,
                        'semester' => 1,
                        'status' => 'aktif'
                    ]
                );
            }
            
            $matkulList = Matakuliah::take(5)->get();
        }
        
        foreach ($dosenList as $dosen) {
            // Setiap dosen diberikan 1-2 mata kuliah secara random
            $maxMatkul = min(2, $matkulList->count());
            $randomCount = rand(1, $maxMatkul);
            $randomMatkul = $matkulList->random($randomCount);
            $dosen->matakuliah()->sync($randomMatkul->pluck('id'));
            
            $this->command->info("Dosen {$dosen->nama_lengkap} diberi " . $randomMatkul->count() . " mata kuliah");
        }
        
        $this->command->info('Relasi dosen-matakuliah berhasil dibuat');
    }
}