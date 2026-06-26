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
            'report_type' => 'nullable|string|in:laporan_triwulan,laporan_semester,laporan_vmts',
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
            $reportType = $request->report_type ?? 'laporan_triwulan';

            // Initialize validation service
            $imageValidationService = new \App\Services\ImageContentValidationService($this->ocrService);

            // Store uploaded images with validation
            $imagePaths = [];
            $rejectedImages = [];
            $validationDetails = [];
            
            foreach ($request->file('images') as $index => $image) {
                $originalFilename = $image->getClientOriginalName();
                $filename = "ocr_{$laporanId}_{$index}_" . time() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs("ocr_uploads/{$laporanId}", $filename, 'public');
                $fullPath = storage_path('app/public/' . $path);
                
                // VALIDATE IMAGE CONTENT
                Log::info('Validating uploaded image', [
                    'original_filename' => $originalFilename,
                    'stored_filename' => $filename,
                    'laporan_id' => $laporanId,
                    'report_type' => $reportType
                ]);
                
                $validationResult = $imageValidationService->validateImageRelevance(
                    $fullPath,
                    $reportType
                );
                
                Log::info('Image validation result', [
                    'filename' => $originalFilename,
                    'is_valid' => $validationResult['is_valid'],
                    'confidence' => $validationResult['confidence'],
                    'relevance_score' => $validationResult['relevance_score'] ?? 0,
                    'reason' => $validationResult['reason']
                ]);
                
                // Store validation details for response
                $validationDetails[] = [
                    'filename' => $originalFilename,
                    'is_valid' => $validationResult['is_valid'],
                    'confidence' => $validationResult['confidence'],
                    'relevance_score' => $validationResult['relevance_score'] ?? 0,
                    'ocr_method' => $validationResult['ocr_method'] ?? 'unknown'
                ];
                
                // Reject if not valid with high confidence (>= 70%)
                if (!$validationResult['is_valid'] && $validationResult['confidence'] >= 0.7) {
                    // Delete the uploaded file
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                    
                    $rejectedImages[] = [
                        'filename' => $originalFilename,
                        'reason' => $validationResult['reason'],
                        'confidence' => round($validationResult['confidence'] * 100, 1) . '%',
                        'relevance_score' => round(($validationResult['relevance_score'] ?? 0) * 100, 1) . '%'
                    ];
                    
                    Log::warning('Image rejected during upload', [
                        'filename' => $originalFilename,
                        'reason' => $validationResult['reason'],
                        'confidence' => $validationResult['confidence'],
                        'relevance_score' => $validationResult['relevance_score'] ?? 0
                    ]);
                    
                    continue; // Skip this image
                }
                
                // Image is valid or has low confidence (uncertain), add to processing list
                $imagePaths[] = $fullPath;
                
                // Log warning if confidence is low
                if ($validationResult['confidence'] < 0.5) {
                    Log::warning('Image accepted with low confidence', [
                        'filename' => $originalFilename,
                        'confidence' => $validationResult['confidence'],
                        'reason' => 'Validasi tidak dapat memastikan relevansi gambar'
                    ]);
                }
            }
            
            // If all images were rejected, return error
            if (empty($imagePaths) && !empty($rejectedImages)) {
                $errorMessage = "❌ Semua gambar ditolak karena tidak relevan dengan Laporan Triwulan\n\n";
                $errorMessage .= "📋 Detail Penolakan:\n";
                foreach ($rejectedImages as $rejected) {
                    $errorMessage .= "• {$rejected['filename']}\n";
                    $errorMessage .= "  Confidence: {$rejected['confidence']}\n";
                    $errorMessage .= "  Relevance: {$rejected['relevance_score']}\n\n";
                }
                $errorMessage .= "✅ Silakan upload gambar yang relevan seperti:\n";
                $errorMessage .= "• Dokumentasi kegiatan kampus/akademik\n";
                $errorMessage .= "• Daftar hadir perkuliahan\n";
                $errorMessage .= "• Grafik/chart data monitoring\n";
                $errorMessage .= "• Screenshot sistem akademik\n";
                $errorMessage .= "• Dokumen RPS/Silabus\n";
                $errorMessage .= "• Dokumentasi evaluasi mutu\n";
                $errorMessage .= "• Tabel data akademik\n";
                $errorMessage .= "• Surat atau memo resmi kampus";
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'rejected_images' => $rejectedImages,
                    'validation_details' => $validationDetails
                ], 400);
            }
            
            // If some images were rejected, include warning in response
            $warningMessage = null;
            if (!empty($rejectedImages)) {
                $warningMessage = "⚠️ " . count($rejectedImages) . " gambar ditolak karena tidak relevan: " . 
                    implode(', ', array_column($rejectedImages, 'filename'));
            }

            Log::info("Images uploaded for OCR integration", [
                'laporan_id' => $laporanId,
                'image_count' => count($imagePaths),
                'rejected_count' => count($rejectedImages),
                'report_type' => $reportType
            ]);

            // Process images dengan Enhanced Laporan Service
            $result = $this->enhancedLaporanService->processImagesForLaporan($laporanId, $imagePaths);

            if (!$result['success']) {
                // Clean up uploaded files if processing fails
                foreach ($imagePaths as $imagePath) {
                    if (file_exists($imagePath)) {
                        @unlink($imagePath);
                    }
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Images uploaded and integrated with Asisten Pembuatan Laporan',
                'data' => $result['data'],
                'warning' => $warningMessage,
                'rejected_images' => $rejectedImages,
                'validation_details' => $validationDetails,
                'stats' => [
                    'total_uploaded' => count($request->file('images')),
                    'accepted' => count($imagePaths),
                    'rejected' => count($rejectedImages)
                ]
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
