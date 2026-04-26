<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Services\OCRService;
use App\Services\UnifiedAIService;
use App\Services\EnhancedLaporanService;
use App\Models\LaporanGJM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OCRUploadController extends Controller
{
    protected $ocrService;
    protected $aiService;
    protected $enhancedLaporanService;

    public function __construct(
        OCRService $ocrService,
        UnifiedAIService $aiService,
        EnhancedLaporanService $enhancedLaporanService
    ) {
        $this->ocrService = $ocrService;
        $this->aiService = $aiService;
        $this->enhancedLaporanService = $enhancedLaporanService;
    }

    /**
     * Upload images dan integrate dengan Asisten Pembuatan Laporan
     */
    public function uploadImages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:15',
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:10240', // 10MB max
            'laporan_id' => 'required|exists:laporan_gjm,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $laporanId = $request->laporan_id;
            $laporan = LaporanGJM::findOrFail($laporanId);

            // Store uploaded images
            $imagePaths = [];
            foreach ($request->file('images') as $index => $image) {
                $filename = "ocr_{$laporanId}_{$index}_" . time() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs("ocr_uploads/{$laporanId}", $filename, 'public');
                $imagePaths[] = storage_path('app/public/' . $path);
            }

            Log::info("Images uploaded for OCR integration", [
                'laporan_id' => $laporanId,
                'image_count' => count($imagePaths)
            ]);

            // Process images dengan Enhanced Laporan Service
            $result = $this->enhancedLaporanService->processImagesForLaporan($laporanId, $imagePaths);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Images uploaded and integrated with Asisten Pembuatan Laporan',
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            Log::error("OCR upload integration failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process images: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate enhanced preview dengan OCR data
     */
    public function generateEnhancedPreview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'laporan_id' => 'required|exists:laporan_gjm,id',
            'user_prompt' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $laporanId = $request->laporan_id;
            $userPrompt = $request->user_prompt ?? '';

            Log::info("Generating enhanced preview with OCR", [
                'laporan_id' => $laporanId,
                'has_user_prompt' => !empty($userPrompt)
            ]);

            // Generate enhanced preview
            $result = $this->enhancedLaporanService->generateEnhancedPreview($laporanId, $userPrompt);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Enhanced preview generated with OCR data',
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            Log::error("Enhanced preview generation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate enhanced preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get laporan statistics (AI preview + OCR data)
     */
    public function getLaporanStats(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'laporan_id' => 'required|exists:laporan_gjm,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $laporanId = $request->laporan_id;
            $stats = $this->enhancedLaporanService->getLaporanStats($laporanId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AI usage statistics
     */
    public function getUsageStats()
    {
        try {
            $stats = $this->aiService->getProviderInfo();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get OCR statistics
     */
    public function getOCRStats()
    {
        try {
            $stats = $this->ocrService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear laporan data (AI preview + OCR + indexed chunks)
     */
    public function clearLaporanData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'laporan_id' => 'required|exists:laporan_gjm,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $laporanId = $request->laporan_id;
            $result = $this->enhancedLaporanService->clearLaporanData($laporanId);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan data cleared successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to clear laporan data'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
