<?php

namespace App\Jobs;

use App\Models\Dosenn;
use App\Models\JadwalDosen;
use App\Models\PeriodeAkademik;
use App\Services\ExternalAPIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncJadwalDosenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pegawaiId;
    protected $semester;
    protected $tahun;

    public function __construct(
        $pegawaiId,
        $semester = null,
        $tahun = null
    ) {
        $this->pegawaiId = $pegawaiId;
        $this->semester = $semester;
        $this->tahun = $tahun;
    }

    public function handle()
{
    /*
    |--------------------------------------------------------------------------
    | JIKA SEMESTER / TAHUN TIDAK DIKIRIM
    | MAKA AMBIL DARI PERIODE AKTIF
    |--------------------------------------------------------------------------
    */
    if (!$this->semester || !$this->tahun) {

        $periodeAktif = PeriodeAkademik::where('is_active', true)
            ->first();

        if (!$periodeAktif) {
            return;
        }

        $this->semester = $periodeAktif->semester;
        $this->tahun = $periodeAktif->tahun_ajaran;
    }

    /*
    |--------------------------------------------------------------------------
    | AMBIL DATA DOSEN
    |--------------------------------------------------------------------------
    */
    $dosen = Dosenn::where('pegawai_id', $this->pegawaiId)
        ->first();

    if (!$dosen) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | SKIP TENAGA PENGAJAR
    |--------------------------------------------------------------------------
    */
    if (
        strtoupper(trim($dosen->jabatan_akademik ?? '')) === 'A'
    ) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | AMBIL JADWAL DARI API
    |--------------------------------------------------------------------------
    */
    $apiService = new ExternalAPIService();

    $jadwalList = $apiService->getJadwalByDosen(
        $this->pegawaiId,
        $this->semester,
        $this->tahun
    );

    /*
    |--------------------------------------------------------------------------
    | SIMPAN / UPDATE
    |--------------------------------------------------------------------------
    */
    foreach ($jadwalList ?? [] as $jadwal) {

        $kelas = strtoupper($jadwal['kelas'] ?? '');

        /*
        |--------------------------------------------------------------------------
        | HANYA AMBIL KELAS TRPL / TI / TK
        |--------------------------------------------------------------------------
        */
        if (
            !str_contains($kelas, 'TRPL') &&
            !str_contains($kelas, 'TI') &&
            !str_contains($kelas, 'TK')
        ) {
            continue;
        }

        JadwalDosen::updateOrCreate(
            [
                'kuliah_id' => $jadwal['jadwal_id'] ?? null,
                'pegawai_id'   => $this->pegawaiId,
            ],
            [
                'kode_mk'      => $jadwal['kode_mk'] ?? null,
                'semester'     => $this->semester,
                'tahun_ajaran' => $this->tahun,
                'kelas'        => $jadwal['kelas'] ?? null,
                'is_manual'    => false,
            ]
        );
    }
}
}