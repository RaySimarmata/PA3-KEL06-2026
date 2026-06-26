<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\JabatanAkademik;

class SyncJabatanAkademik extends Command
{
    /**
     * Nama command
     */
    protected $signature = 'sync:jabatan-akademik';

    /**
     * Deskripsi command
     */
    protected $description = 'Sync data jabatan akademik dari library API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {

            $this->info('Mengambil data dari API...');

            $baseUrl = config('services.library.url');

            $response = Http::timeout(60)
                ->get($baseUrl . '/library-api/all-jabatan-akademik', [
                    'id' => '',
                    'nama' => '',
                    'desc' => ''
                ]);

            if (!$response->successful()) {

                $this->error('Gagal mengambil data API');

                return Command::FAILURE;
            }

            $result = $response->json();

            $list = $result['data']['jabatanAkademik'] ?? [];

            if (count($list) === 0) {

                $this->warn('Data kosong');

                return Command::SUCCESS;
            }

            foreach ($list as $item) {

                JabatanAkademik::updateOrCreate(
                    [
                        'kode' => $item['nama']
                    ],
                    [
                        'nama' => $item['desc']
                    ]
                );

                $this->line(
                    'Sync: ' .
                    $item['nama'] .
                    ' - ' .
                    $item['desc']
                );
            }

            $this->info('Sync selesai');

            return Command::SUCCESS;

        } catch (\Exception $e) {

            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}