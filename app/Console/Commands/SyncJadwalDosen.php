<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dosenn;
use App\Jobs\SyncJadwalDosenJob;

class SyncJadwalDosen extends Command
{
    protected $signature = 'sync:jadwal-dosen {semester} {tahun}';
    protected $description = 'Sync jadwal dosen secara async';

    public function handle()
    {
        $semester = $this->argument('semester');
        $tahun = $this->argument('tahun');

        $dosenList = Dosenn::select('pegawai_id')->get();

        foreach ($dosenList as $dosen) {

            // 🔥 dispatch ke queue (async)
            SyncJadwalDosenJob::dispatch(
                $dosen->pegawai_id,
                $semester,
                $tahun
            );
        }

        $this->info('Semua job berhasil dikirim ke queue!');
    }
}