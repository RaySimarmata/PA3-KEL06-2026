<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProdiSeeder extends Seeder
{
    public function run(): void
    {
        $prodi = [
            [
                'kode_prodi' => 'TRPL',
                'nama_prodi' => 'Teknologi Rekayasa Perangkat Lunak',
                'nama_singkat' => 'TRPL',
                'deskripsi' => 'Program Studi Teknologi Rekayasa Perangkat Lunak',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('prodi')->insertOrIgnore($prodi);
    }
}
