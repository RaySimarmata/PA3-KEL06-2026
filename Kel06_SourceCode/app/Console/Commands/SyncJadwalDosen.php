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
    /*
    |--------------------------------------------------------------------------
    | AMBIL PERIODE AKADEMIK AKTIF
    |--------------------------------------------------------------------------
    */
    $periodeAktif = \App\Models\PeriodeAkademik::where('is_active', true)
        ->first();

    if (!$periodeAktif) {

        $this->error('Periode akademik aktif tidak ditemukan');

        return;
    }

    $semester = $periodeAktif->semester;
    $tahun = $periodeAktif->tahun_ajaran;

    /*
    |--------------------------------------------------------------------------
    | AMBIL SEMUA DOSEN
    |--------------------------------------------------------------------------
    */
    $dosenList = Dosenn::select('pegawai_id')->get();

    foreach ($dosenList as $dosen) {

        SyncJadwalDosenJob::dispatch(
            $dosen->pegawai_id,
            $semester,
            $tahun
        );
    }

    $this->info(
        "Sync jadwal dosen periode {$tahun} semester {$semester} berhasil dikirim ke queue"
    );
}
}