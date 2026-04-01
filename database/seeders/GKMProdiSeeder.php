<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Prodi;
use Illuminate\Support\Facades\Hash;

class GKMProdiSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Pastikan prodi sudah ada
        $prodiTRPL = Prodi::firstOrCreate(
            ['kode_prodi' => 'TRPL'],
            [
                'nama_prodi' => 'Teknologi Rekayasa Perangkat Lunak',
                'nama_singkat' => 'TRPL',
                'deskripsi' => 'Program Studi Teknologi Rekayasa Perangkat Lunak'
            ]
        );

        $prodiTI = Prodi::firstOrCreate(
            ['kode_prodi' => 'TI'],
            [
                'nama_prodi' => 'Teknologi Informasi',
                'nama_singkat' => 'TI',
                'deskripsi' => 'Program Studi Teknologi Informasi'
            ]
        );

        $prodiNM = Prodi::firstOrCreate(
            ['kode_prodi' => 'NM'],
            [
                'nama_prodi' => 'Teknologi Komputer',
                'nama_singkat' => 'NM',
                'deskripsi' => 'Program Studi Teknologi Komputer'
            ]
        );

        // Buat user GKM untuk setiap prodi
        $gkmUsers = [
            [
                'name' => 'GKM TRPL',
                'username' => 'gkm_trpl',
                'email' => 'gkm.trpl@example.com',
                'password' => Hash::make('password'),
                'role' => 'GKM',
                'prodi_id' => $prodiTRPL->id,
                'is_active' => true,
            ],
            [
                'name' => 'GKM TI',
                'username' => 'gkm_ti',
                'email' => 'gkm.ti@example.com',
                'password' => Hash::make('password'),
                'role' => 'GKM',
                'prodi_id' => $prodiTI->id,
                'is_active' => true,
            ],
            [
                'name' => 'GKM NM',
                'username' => 'gkm_nm',
                'email' => 'gkm.nm@example.com',
                'password' => Hash::make('password'),
                'role' => 'GKM',
                'prodi_id' => $prodiNM->id,
                'is_active' => true,
            ],
        ];

        foreach ($gkmUsers as $userData) {
            User::updateOrCreate(
                ['username' => $userData['username']],
                $userData
            );
        }

        $this->command->info('✓ 3 GKM users created successfully!');
        $this->command->info('  - GKM TRPL (username: gkm_trpl, password: password)');
        $this->command->info('  - GKM TI (username: gkm_ti, password: password)');
        $this->command->info('  - GKM NM (username: gkm_nm, password: password)');
    }
}
