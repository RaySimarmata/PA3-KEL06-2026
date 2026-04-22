<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed Prodi first
        $this->call([
            ProdiSeeder::class,
            DosenKaprodiWaliSeeder::class,
            GKMProdiSeeder::class,
            JadwalReminderSeeder::class,
            KelasSeeder::class,
            KuesionerUploadSeeder::class,
            TemplateLaporanSeeder::class,
            LaporanGJMSeeder::class,
            ResetAdminPasswordSeeder::class,
        ]);

        // Admin
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // GKM Reviewer
        User::firstOrCreate(
            ['email' => 'gkm@example.com'],
            [
                'name' => 'GKM Reviewer',
                'username' => 'gkmreviewer',
                'password' => Hash::make('password'),
                'role' => 'gkm_reviewer',
                'is_active' => true,
            ]
        );

        // GJM Reviewer
        User::firstOrCreate(
            ['email' => 'gjm@example.com'],
            [
                'name' => 'GJM Reviewer',
                'username' => 'gjmreviewer',
                'password' => Hash::make('password'),
                'role' => 'gjm_reviewer',
                'is_active' => true,
            ]
        );
    }
}
