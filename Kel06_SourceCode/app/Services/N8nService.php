<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nService
{
    protected $baseUrl;
    protected $apiKey;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = env('N8N_WEBHOOK_URL', 'http://localhost:5678');
        $this->apiKey = env('N8N_API_KEY', '');
        $this->timeout = env('N8N_TIMEOUT', 300); // 5 minutes default
    }

    /**
     * Trigger n8n workflow untuk generate laporan GJM
     */
    public function triggerLaporanGeneration($laporanId, $data)
    {
        Log::info("Triggering n8n workflow for laporan generation", [
            'laporan_id' => $laporanId,
            'workflow' => 'generate-laporan-gjm'
        ]);

        try {
            $webhookUrl = $this->baseUrl . '/webhook/generate-laporan-gjm';
            
            $payload = [
                'laporan_id' => $laporanId,
                'tipe_laporan' => $data['tipe_laporan'],
                'periode_tanggal' => $data['periode_tanggal'],
                'program_studi' => $data['program_studi'],
                'judul_laporan' => $data['judul_laporan'],
                'instruksi_prompt' => $data['instruksi_prompt'],
                'dokumen_path' => $data['dokumen_path'] ?? null,
                'callback_url' => route('api.n8n.callback.laporan'),
                'app_url' => env('APP_URL'),
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post($webhookUrl, $payload);

            if (!$response->successful()) {
                throw new \Exception("n8n webhook failed: " . $response->body());
            }

            $result = $response->json();

            Log::info("n8n workflow triggered successfully", [
                'laporan_id' => $laporanId,
                'execution_id' => $result['executionId'] ?? 'unknown'
            ]);

            return [
                'success' => true,
                'execution_id' => $result['executionId'] ?? null,
                'message' => 'Workflow triggered successfully'
            ];

        } catch (\Exception $e) {
            Log::error("Failed to trigger n8n workflow", [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Trigger n8n workflow untuk generate laporan GKM Kuesioner
     */
    public function triggerLaporanKuesioneGeneration($laporanId, $data)
    {
        Log::info("Triggering n8n workflow for kuesioner generation", [
            'laporan_id' => $laporanId
        ]);

        try {
            $webhookUrl = $this->baseUrl . '/webhook/generate-laporan-kuesioner';
            
            $payload = [
                'laporan_id' => $laporanId,
                'kuesioner_upload_id' => $data['kuesioner_upload_id'],
                'template_id' => $data['template_id'],
                'callback_url' => route('api.n8n.callback.kuesioner'),
                'app_url' => env('APP_URL'),
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders($this->getHeaders())
                ->post($webhookUrl, $payload);

            if (!$response->successful()) {
                throw new \Exception("n8n webhook failed: " . $response->body());
            }

            return [
                'success' => true,
                'execution_id' => $response->json()['executionId'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error("Failed to trigger n8n kuesioner workflow", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check n8n health status
     */
    public function checkHealth()
    {
        try {
            $response = Http::timeout(5)
                ->get($this->baseUrl . '/healthz');

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning("n8n health check failed", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get workflow execution status
     */
    public function getExecutionStatus($executionId)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders())
                ->get($this->baseUrl . '/api/v1/executions/' . $executionId);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Failed to get execution status", [
                'execution_id' => $executionId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get request headers
     */
    protected function getHeaders()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->apiKey) {
            $headers['X-N8N-API-KEY'] = $this->apiKey;
        }

        return $headers;
    }
}
