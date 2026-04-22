<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Services\ExternalAPIService;

class SyncDosen extends Command
{
    protected $signature = 'sync:dosen';
    protected $description = 'Sinkronisasi data dosen dari API ke database';

    public function handle(ExternalAPIService $apiService)
    {
        $this->info('Mulai sync dosen...');

        try {
            // Use the service's getDosen method which handles token refresh automatically
            $data = $apiService->getDosenFromAPI();

            if ($data === null) {
                $this->error('Gagal ambil data dari API - service returned null');
                return;
            }

            if (empty($data)) {
                $this->warn('Data dosen kosong ⚠️');
                return;
            }

            $total = count($data);
            $success = 0;
            $skipped = 0;

            $this->info("Jumlah data: $total");

            foreach ($data as $d) {

                // skip kalau tidak ada dosen_id
                if (empty($d['dosen_id'])) {
                    $skipped++;
                    continue;
                }

                DB::table('dosenn')->updateOrInsert(
                    ['dosen_id' => $d['dosen_id']],
                    [
                        'pegawai_id' => ($d['pegawai_id'] ?? '-') === '-' ? null : (int) $d['pegawai_id'],
'user_id'    => ($d['user_id'] ?? '-') === '-' ? null : (int) $d['user_id'],
'prodi_id'   => ($d['prodi_id'] ?? '-') === '-' ? null : (int) $d['prodi_id'],

                        'nip' => ($d['nip'] ?? '-') === '-' ? null : $d['nip'],
                        'nidn' => ($d['nidn'] ?? '-') === '-' ? null : $d['nidn'],

                        'nama' => ($d['nama'] ?? '-') === '-' ? null : $d['nama'],

                        // gunakan dari API langsung
                        'inisial_nama' => ($d['alias'] ?? '-') === '-' 
                            ? null 
                            : $d['alias'],

                        'email' => ($d['email'] ?? '-') === '-' ? null : $d['email'],
                        'prodi' => $d['prodi'] ?? null,

                        'jabatan_akademik' => ($d['jabatan_akademik'] ?? '-') === '-' ? null : $d['jabatan_akademik'],
                        'jabatan_akademik_desc' => ($d['jabatan_akademik_desc'] ?? '-') === '-' ? null : $d['jabatan_akademik_desc'],

                        'jenjang_pendidikan' => !empty($d['jenjang_pendidikan'])
                            ? rtrim($d['jenjang_pendidikan'], ', ')
                            : null,

                        'updated_at' => now(),
                    ]
                );

                $success++;
            }

            // hasil akhir
            $this->info('==============================');
            $this->info("Total   : $total");
            $this->info("Berhasil: $success ✅");
            $this->warn("Skipped : $skipped ⚠️");
            $this->info('==============================');

            $this->info('Sync dosen selesai 🚀');

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}