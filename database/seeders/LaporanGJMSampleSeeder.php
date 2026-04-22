<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LaporanGJM;
use App\Models\Prodi;
use Carbon\Carbon;

class LaporanGJMSampleSeeder extends Seeder
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

        $laporanTypes = [
            'Laporan Triwulan I',
            'Laporan Triwulan II', 
            'Laporan Triwulan III',
            'Laporan Triwulan IV',
            'Laporan Semester Ganjil',
            'Laporan Semester Genap',
            'Laporan Tahunan'
        ];

        // Generate laporan untuk 6 bulan terakhir
        for ($month = 5; $month >= 0; $month--) {
            $date = Carbon::now()->subMonths($month);
            
            // 1-2 laporan per bulan
            $count = rand(1, 2);
            
            for ($i = 0; $i < $count; $i++) {
                $type = $laporanTypes[array_rand($laporanTypes)];
                $randomProdi = $prodi->random();
                
                LaporanGJM::create([
                    'judul' => $type . ' - ' . $randomProdi->nama_prodi . ' ' . $date->format('Y'),
                    'periode' => $date->format('Y/m'),
                    'prodi_id' => $randomProdi->id,
                    'konten' => $this->generateLaporanContent($type, $randomProdi->nama_prodi, $date),
                    'status' => rand(1, 100) <= 80 ? 'completed' : 'draft',
                    'file_path' => 'uploads/laporan_gjm/' . $date->format('Y/m') . '/laporan_' . $i . '.pdf',
                    'created_at' => $date->addDays(rand(1, 28)),
                    'updated_at' => $date->addDays(rand(1, 28)),
                ]);
            }
        }

        $this->command->info('LaporanGJM sample seeder completed successfully!');
    }

    private function generateLaporanContent($type, $prodiName, $date)
    {
        return "# {$type} - {$prodiName}\n\n" .
               "**Periode:** {$date->format('F Y')}\n\n" .
               "## Ringkasan Eksekutif\n\n" .
               "Laporan ini menyajikan evaluasi komprehensif terhadap kinerja akademik dan operasional Program Studi {$prodiName} " .
               "untuk periode {$date->format('F Y')}.\n\n" .
               "## Pencapaian Utama\n\n" .
               "- Tingkat kepatuhan RPS: " . rand(75, 95) . "%\n" .
               "- Rata-rata nilai kuisioner mahasiswa: " . number_format(rand(350, 450)/100, 1) . "/5.0\n" .
               "- Jumlah mahasiswa aktif: " . rand(150, 300) . " mahasiswa\n" .
               "- Tingkat kelulusan: " . rand(85, 98) . "%\n\n" .
               "## Rekomendasi\n\n" .
               "1. Meningkatkan kualitas pembelajaran melalui pelatihan dosen\n" .
               "2. Optimalisasi penggunaan teknologi dalam proses belajar mengajar\n" .
               "3. Penguatan kerjasama dengan industri\n\n" .
               "## Kesimpulan\n\n" .
               "Program Studi {$prodiName} menunjukkan kinerja yang baik dengan beberapa area yang perlu ditingkatkan " .
               "untuk mencapai standar excellence yang diharapkan.";
    }
}