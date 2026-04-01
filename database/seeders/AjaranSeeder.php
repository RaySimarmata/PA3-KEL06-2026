<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ajaran;

class AjaranSeeder extends Seeder
{
    public function run()
    {
        $ajaran = [
            [
                'tahun_ajaran' => 2024,
                'semester' => 'ganjil',
                'tanggal_mulai' => '2024-08-01',
                'tanggal_akhir' => '2024-12-31',
                'status' => 'aktif',
            ],
            [
                'tahun_ajaran' => 2024,
                'semester' => 'genap',
                'tanggal_mulai' => '2025-01-01',
                'tanggal_akhir' => '2025-06-30',
                'status' => 'non_aktif',
            ],
            [
                'tahun_ajaran' => 2023,
                'semester' => 'ganjil',
                'tanggal_mulai' => '2023-08-01',
                'tanggal_akhir' => '2023-12-31',
                'status' => 'non_aktif',
            ],
            [
                'tahun_ajaran' => 2023,
                'semester' => 'genap',
                'tanggal_mulai' => '2024-01-01',
                'tanggal_akhir' => '2024-06-30',
                'status' => 'non_aktif',
            ],
        ];

        foreach ($ajaran as $data) {
            Ajaran::updateOrCreate(
                ['tahun_ajaran' => $data['tahun_ajaran'], 'semester' => $data['semester']],
                $data
            );
        }
    }
}