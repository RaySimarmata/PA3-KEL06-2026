<?php

namespace App\Jobs;

use App\Models\Dosenn;
use App\Models\JadwalDosen;
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

    public function __construct($pegawaiId, $semester, $tahun)
    {
        $this->pegawaiId = $pegawaiId;
        $this->semester = $semester;
        $this->tahun = $tahun;
    }

    public function handle()
    {
        $apiService = new ExternalAPIService();

        $jadwalList = $apiService->getJadwalByDosen(
            $this->pegawaiId,
            $this->semester,
            $this->tahun
        );

        foreach ($jadwalList ?? [] as $jadwal) {

            JadwalDosen::updateOrCreate(
                [
                    'pegawai_id' => $this->pegawaiId,
                    'kode_mk' => $jadwal['kode_mk'],
                    'semester' => $this->semester,
                    'tahun_ajaran' => $this->tahun,
                ],
                [
                    'kuliah_id' => $jadwal['kuliah_id'] ?? null,
                ]
            );
        }
    }
}