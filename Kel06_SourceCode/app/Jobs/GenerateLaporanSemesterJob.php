<?php

namespace App\Jobs;

use App\Services\LaporanSemesterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateLaporanSemesterJob implements ShouldQueue
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
    public function handle(LaporanSemesterService $laporanService)
    {
        Log::info("GenerateLaporanSemesterJob started", ['laporan_id' => $this->laporanId]);

        try {
            $result = $laporanService->generate($this->laporanId);
            
            Log::info("GenerateLaporanSemesterJob completed successfully", [
                'laporan_id' => $this->laporanId,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            Log::error("GenerateLaporanSemesterJob failed", [
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
        Log::error("GenerateLaporanSemesterJob failed permanently", [
            'laporan_id' => $this->laporanId,
            'error' => $exception->getMessage()
        ]);
    }
}
