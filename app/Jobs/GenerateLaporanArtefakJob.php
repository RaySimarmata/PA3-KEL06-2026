<?php

namespace App\Jobs;

use App\Services\LaporanArtefakService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateLaporanArtefakJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $laporanId;

    /**
     * Create a new job instance.
     */
    public function __construct($laporanId)
    {
        $this->laporanId = $laporanId;
    }

    /**
     * Execute the job.
     */
    public function handle(LaporanArtefakService $laporanService)
    {
        try {
            Log::info('GenerateLaporanArtefakJob started', [
                'laporan_id' => $this->laporanId
            ]);

            $laporanService->generateLaporan($this->laporanId);

            Log::info('GenerateLaporanArtefakJob completed', [
                'laporan_id' => $this->laporanId
            ]);

        } catch (\Exception $e) {
            Log::error('GenerateLaporanArtefakJob failed', [
                'laporan_id' => $this->laporanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('GenerateLaporanArtefakJob failed permanently', [
            'laporan_id' => $this->laporanId,
            'error' => $exception->getMessage()
        ]);
    }
}
