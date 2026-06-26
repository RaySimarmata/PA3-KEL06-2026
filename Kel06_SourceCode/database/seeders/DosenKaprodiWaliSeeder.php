<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Dosen;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DosenKaprodiWaliSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Contoh data untuk setiap prodi
        $prodiList = Prodi::all();

        foreach ($prodiList as $prodi) {
            // Contoh: Tambahkan 1 Kaprodi per prodi
            $this->createKaprodi($prodi);

            // Contoh: Tambahkan beberapa Dosen Wali per prodi
            $this->createDosenWali($prodi);
        }
    }

    private function createKaprodi($prodi)
    {
        // Cek apakah sudah ada Kaprodi
        $existingKaprodi = Dosen::where('prodi_id', $prodi->id)
            ->where('is_kaprodi', true)
            ->first();

        if ($existingKaprodi) {
            $this->command->info("Kaprodi untuk {$prodi->nama_prodi} sudah ada: {$existingKaprodi->nama_lengkap}");
            return;
        }

        $email = 'kaprodi.' . strtolower($prodi->kode_prodi) . '@example.com';
        
        // Buat user
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Kaprodi ' . $prodi->kode_prodi,
                'username' => 'kaprodi_' . strtolower($prodi->kode_prodi),
                'password' => Hash::make('password123'),
                'role' => 'dosen',
                'prodi_id' => $prodi->id,
            ]
        );

        // Buat dosen
        Dosen::create([
            'user_id' => $user->id,
            'prodi_id' => $prodi->id,
            'nama_lengkap' => 'Dr. Kaprodi ' . $prodi->kode_prodi . ', M.Kom',
            'nidn' => '0000000' . $prodi->id . '01',
            'kontak_email' => $email,
            'gelar_akademik' => 'Dr., M.Kom',
            'jabatan_akademik' => 'Lektor Kepala',
            'status' => 'aktif',
            'is_kaprodi' => true,
            'is_dosen_wali' => false,
        ]);

        $this->command->info("Kaprodi untuk {$prodi->nama_prodi} berhasil dibuat");
    }

    private function createDosenWali($prodi)
    {
        $kelasList = Dosen::getKelasListByProdi($prodi->id);

        if (empty($kelasList)) {
            $this->command->warn("Tidak ada daftar kelas untuk {$prodi->nama_prodi}");
            return;
        }

        // Ambil 2 kelas pertama sebagai contoh
        $kelasContoh = array_slice($kelasList, 0, 2);

        foreach ($kelasContoh as $index => $kelas) {
            // Cek apakah kelas sudah punya wali
            $existingWali = Dosen::where('prodi_id', $prodi->id)
                ->where('kelas_wali', $kelas)
                ->first();

            if ($existingWali) {
                $this->command->info("Dosen Wali untuk kelas {$kelas} sudah ada: {$existingWali->nama_lengkap}");
                continue;
            }

            $email = 'wali.' . strtolower($kelas) . '@example.com';
            
            // Buat user
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Dosen Wali ' . $kelas,
                    'username' => 'wali_' . strtolower($kelas),
                    'password' => Hash::make('password123'),
                    'role' => 'dosen',
                    'prodi_id' => $prodi->id,
                ]
            );

            // Buat dosen
            Dosen::create([
                'user_id' => $user->id,
                'prodi_id' => $prodi->id,
                'nama_lengkap' => 'Dosen Wali ' . $kelas . ', M.Kom',
                'nidn' => '0000000' . $prodi->id . (10 + $index),
                'kontak_email' => $email,
                'gelar_akademik' => 'M.Kom',
                'jabatan_akademik' => 'Asisten Ahli',
                'status' => 'aktif',
                'is_kaprodi' => false,
                'is_dosen_wali' => true,
                'kelas_wali' => $kelas,
            ]);

            $this->command->info("Dosen Wali untuk kelas {$kelas} berhasil dibuat");
        }
    }
}
