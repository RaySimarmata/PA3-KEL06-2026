<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PeriodeAkademik;
use Carbon\Carbon;

class PeriodeAkademikSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default academic periods for common years
        $periods = [
            [
                'tahun_ajaran' => '2020',
                'semester' => 1,
                'semester_label' => 'Ganjil',
                'start_date' => '2020-08-01',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2020',
                'semester' => 2,
                'semester_label' => 'Genap',
                'start_date' => '2021-02-01',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2021',
                'semester' => 1,
                'semester_label' => 'Ganjil',
                'start_date' => '2021-08-01',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2021',
                'semester' => 2,
                'semester_label' => 'Genap',
                'start_date' => '2022-02-01',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2022',
                'semester' => 1,
                'semester_label' => 'Ganjil',
                'start_date' => '2022-08-01',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2022',
                'semester' => 2,
                'semester_label' => 'Genap',
                'start_date' => '2023-02-01',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2023',
                'semester' => 1,
                'semester_label' => 'Ganjil',
                'start_date' => '2023-08-01',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2023',
                'semester' => 2,
                'semester_label' => 'Genap',
                'start_date' => '2024-02-01',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2024',
                'semester' => 1,
                'semester_label' => 'Ganjil',
                'start_date' => '2024-08-01',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2024',
                'semester' => 2,
                'semester_label' => 'Genap',
                'start_date' => '2025-02-01',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2025',
                'semester' => 1,
                'semester_label' => 'Ganjil',
                'start_date' => '2025-08-01',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2025',
                'semester' => 2,
                'semester_label' => 'Genap',
                'start_date' => '2026-02-01',
                'is_active' => false,
            ],
            [
                'tahun_ajaran' => '2026',
                'semester' => 1,
                'semester_label' => 'Ganjil',
                'start_date' => '2026-08-01',
                'is_active' => true, // Set current period as active
            ],
        ];

        foreach ($periods as $period) {
            PeriodeAkademik::updateOrCreate(
                [
                    'tahun_ajaran' => $period['tahun_ajaran'],
                    'semester' => $period['semester'],
                ],
                $period
            );
        }

        $this->command->info('PeriodeAkademik seeder completed successfully!');
    }
}