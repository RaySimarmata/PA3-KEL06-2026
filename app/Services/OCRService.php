<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OCRService
{
    private $supportedFormats = ['png', 'jpg', 'jpeg', 'pdf'];
    private $languages = ['ind', 'eng']; // Indonesian and English
    private $cacheEnabled = true;
    private $cacheTTL = 604800; // 7 days in seconds

    /**
     * Extract text from image.
     * Tries Tesseract if available; otherwise returns a descriptive fallback.
     */
    public function extractText(string $imagePath): array
    {
        $startTime = microtime(true);

        try {
            Log::info("=== OCR Text Extraction Started ===", [
                'image_path' => $imagePath,
                'file_exists' => file_exists($imagePath)
            ]);

            // Validate file exists
            if (!file_exists($imagePath)) {
                throw new \Exception("Image file not found: {$imagePath}");
            }

            // Check file format
            $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            if (!in_array($extension, $this->supportedFormats)) {
                throw new \Exception("Unsupported file format: {$extension}. Supported: " . implode(', ', $this->supportedFormats));
            }

            // Check cache
            $cacheKey = $this->getCacheKey($imagePath);
            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                Log::info("OCR cache hit", ['image' => basename($imagePath)]);
                return $cached;
            }

            // Try Tesseract if available
            if ($this->isTesseractAvailable()) {
                $preprocessedPath = $this->preprocessImage($imagePath);
                $text = $this->runTesseract($preprocessedPath);

                if ($preprocessedPath !== $imagePath && file_exists($preprocessedPath)) {
                    @unlink($preprocessedPath);
                }

                $processingTime = round((microtime(true) - $startTime) * 1000, 2);

                $result = [
                    'success'            => true,
                    'text'               => $text,
                    'method'             => 'tesseract',
                    'confidence'         => 85,
                    'length'             => strlen($text),
                    'word_count'         => str_word_count($text),
                    'processing_time_ms' => $processingTime,
                    'image_file'         => basename($imagePath),
                ];

                if ($this->cacheEnabled) {
                    Cache::put($cacheKey, $result, $this->cacheTTL);
                }

                Log::info("OCR (Tesseract) completed", [
                    'image'      => basename($imagePath),
                    'text_length'=> strlen($text),
                ]);

                return $result;
            }

            // Tesseract not installed — use GD to get image info as fallback
            $fallbackText = $this->extractWithGDFallback($imagePath);
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            $result = [
                'success'            => true,
                'text'               => $fallbackText,
                'method'             => 'gd_fallback',
                'confidence'         => 0,
                'length'             => strlen($fallbackText),
                'word_count'         => str_word_count($fallbackText),
                'processing_time_ms' => $processingTime,
                'image_file'         => basename($imagePath),
            ];

            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $result, $this->cacheTTL);
            }

            Log::info("OCR (GD fallback) completed", [
                'image'      => basename($imagePath),
                'text_length'=> strlen($fallbackText),
            ]);

            return $result;

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error("OCR extraction failed", [
                'image' => basename($imagePath),
                'error' => $e->getMessage(),
            ]);

            return [
                'success'            => false,
                'text'               => '',
                'method'             => 'failed',
                'confidence'         => 0,
                'length'             => 0,
                'word_count'         => 0,
                'processing_time_ms' => $processingTime,
                'image_file'         => basename($imagePath),
                'error'              => $e->getMessage(),
            ];
        }
    }

    /**
     * Alias used by TextExtractionService::extractFromImage()
     */
    public function extractTextFromImage(string $imagePath): array
    {
        return $this->extractText($imagePath);
    }

    /**
     * Extract text from multiple images (batch processing)
     */
    public function extractTextBatch(array $imagePaths): array
    {
        $startTime = microtime(true);
        $results = [];
        $totalText = '';

        Log::info("=== Batch OCR Started ===", [
            'total_images' => count($imagePaths)
        ]);

        foreach ($imagePaths as $index => $imagePath) {
            $result = $this->extractText($imagePath);
            $results[] = $result;
            
            if ($result['success']) {
                // Ensure text is string before concatenating
                $text = $result['text'] ?? '';
                if (is_array($text)) {
                    $text = json_encode($text);
                } elseif (!is_string($text)) {
                    $text = (string)$text;
                }
                $totalText .= $text . "\n\n";
            }
        }

        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        Log::info("Batch OCR completed", [
            'total_images' => count($imagePaths),
            'successful' => $successCount,
            'failed' => count($imagePaths) - $successCount,
            'total_time_ms' => $totalTime,
            'avg_time_per_image_ms' => round($totalTime / count($imagePaths), 2)
        ]);

        // Ensure totalText is always a string before strlen
        if (!is_string($totalText)) {
            $totalText = '';
        }

        return [
            // Always succeed at batch level so upstream doesn't throw hard errors.
            // Individual image errors are reported in 'results'.
            'success'                   => true,
            'results'                   => $results,
            'combined_text'             => trim($totalText),
            'total_length'              => strlen($totalText),
            'successful_count'          => $successCount,
            'failed_count'              => count($imagePaths) - $successCount,
            'total_processing_time_ms'  => $totalTime,
        ];
    }

    /**
     * Preprocess image for better OCR accuracy
     */
    private function preprocessImage(string $imagePath): string
    {
        try {
            // Check if ImageMagick or GD is available
            if (!extension_loaded('imagick') && !extension_loaded('gd')) {
                Log::warning("No image processing extension available, skipping preprocessing");
                return $imagePath;
            }

            $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            
            // Skip preprocessing for PDFs
            if ($extension === 'pdf') {
                return $imagePath;
            }

            // Create temp file for preprocessed image
            $tempPath = sys_get_temp_dir() . '/ocr_preprocessed_' . time() . '_' . basename($imagePath);

            if (extension_loaded('imagick')) {
                $this->preprocessWithImageMagick($imagePath, $tempPath);
            } else {
                $this->preprocessWithGD($imagePath, $tempPath);
            }

            if (file_exists($tempPath)) {
                Log::info("Image preprocessed", [
                    'original' => basename($imagePath),
                    'preprocessed' => basename($tempPath)
                ]);
                return $tempPath;
            }

            return $imagePath;

        } catch (\Exception $e) {
            Log::warning("Image preprocessing failed, using original", [
                'error' => $e->getMessage()
            ]);
            return $imagePath;
        }
    }

    /**
     * Preprocess with ImageMagick
     */
    private function preprocessWithImageMagick(string $inputPath, string $outputPath): void
    {
        $image = new \Imagick($inputPath);
        
        // Convert to grayscale
        $image->transformImageColorspace(\Imagick::COLORSPACE_GRAY);
        
        // Increase contrast
        $image->normalizeImage();
        $image->contrastImage(1);
        
        // Denoise
        $image->despeckleImage();
        
        // Sharpen
        $image->sharpenImage(0, 1);
        
        // Save
        $image->writeImage($outputPath);
        $image->clear();
    }

    /**
     * Preprocess with GD
     */
    private function preprocessWithGD(string $inputPath, string $outputPath): void
    {
        $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
        
        // Load image
        $image = match($extension) {
            'png' => imagecreatefrompng($inputPath),
            'jpg', 'jpeg' => imagecreatefromjpeg($inputPath),
            default => throw new \Exception("Unsupported format for GD: {$extension}")
        };

        if (!$image) {
            throw new \Exception("Failed to load image with GD");
        }

        // Convert to grayscale
        imagefilter($image, IMG_FILTER_GRAYSCALE);
        
        // Increase contrast
        imagefilter($image, IMG_FILTER_CONTRAST, -20);
        
        // Sharpen
        imagefilter($image, IMG_FILTER_MEAN_REMOVAL);
        
        // Save
        imagepng($image, $outputPath);
        imagedestroy($image);
    }

    /**
     * Check if the Tesseract binary is actually available on the system.
     * We test the real binary, not just whether the PHP wrapper class exists.
     */
    private function isTesseractAvailable(): bool
    {
        // Cache result per-request to avoid repeated execs
        static $result = null;
        if ($result !== null) {
            return $result;
        }

        exec('tesseract --version 2>&1', $out, $code);
        $result = ($code === 0);

        Log::info('Tesseract availability check', [
            'available' => $result,
            'output'   => implode(' ', array_slice($out, 0, 2)),
        ]);

        return $result;
    }

    /**
     * GD-based fallback: returns image metadata as descriptive text so AI
     * at least knows a file was attached even without Tesseract.
     */
    private function extractWithGDFallback(string $imagePath): string
    {
        try {
            $info = @getimagesize($imagePath);
            $filename = basename($imagePath);
            $filesize = round(filesize($imagePath) / 1024, 1);

            if ($info) {
                [$width, $height] = $info;
                return "[Gambar: {$filename} | Ukuran: {$width}x{$height}px | {$filesize}KB | Catatan: OCR (Tesseract) tidak terinstall, teks dari gambar tidak dapat diekstrak secara otomatis. Mohon deskripsikan isi gambar dalam instruksi Anda agar AI dapat membantu membuat laporan.]";
            }

            return "[Gambar: {$filename} | {$filesize}KB | Catatan: OCR tidak tersedia. Mohon deskripsikan isi gambar dalam instruksi Anda.]";
        } catch (\Exception $e) {
            return '[Gambar terlampir — OCR tidak tersedia di sistem ini.]';
        }
    }

    /**
     * Run Tesseract OCR
     */
    private function runTesseract(string $imagePath): string
    {
        try {
            if (class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) {
                $ocr = new \thiagoalessio\TesseractOCR\TesseractOCR($imagePath);
                $ocr->lang(implode('+', $this->languages));
                $ocr->psm(3);
                $text = $ocr->run();

                if (strlen(trim($text)) < 10) {
                    Log::warning("OCR extracted very short text", [
                        'text_length' => strlen($text),
                    ]);
                }

                return $this->cleanOCRText($text);
            }

            // Fallback to CLI
            return $this->runTesseractCLI($imagePath);

        } catch (\Exception $e) {
            Log::error("Tesseract OCR failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Run Tesseract via command line (fallback)
     */
    private function runTesseractCLI(string $imagePath): string
    {
        $outputFile = sys_get_temp_dir() . '/ocr_output_' . time();
        $languages = implode('+', $this->languages);
        
        // Build command
        $command = sprintf(
            'tesseract %s %s -l %s --psm 3',
            escapeshellarg($imagePath),
            escapeshellarg($outputFile),
            escapeshellarg($languages)
        );

        Log::info("Running Tesseract CLI", ['command' => $command]);

        // Execute
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("Tesseract CLI failed: " . implode("\n", $output));
        }

        // Read output
        $textFile = $outputFile . '.txt';
        if (!file_exists($textFile)) {
            throw new \Exception("Tesseract output file not found");
        }

        $text = file_get_contents($textFile);
        @unlink($textFile);

        return $this->cleanOCRText($text);
    }

    /**
     * Clean OCR extracted text
     */
    private function cleanOCRText(string $text): string
    {
        // Fix common OCR errors
        $replacements = [
            // Number/letter confusion
            '/\b0(?=[a-zA-Z])/' => 'O',  // 0 -> O before letters
            '/\bl(?=\d)/' => '1',         // l -> 1 before numbers
            '/\bS(?=\d)/' => '5',         // S -> 5 before numbers
            
            // Remove excessive whitespace
            '/\s+/' => ' ',
            '/\n{3,}/' => "\n\n",
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        return trim($text);
    }

    /**
     * Parse OCR text into structured format
     */
    public function parseStructuredText(string $text): array
    {
        $lines = explode("\n", $text);
        $paragraphs = [];
        $currentParagraph = '';

        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                if (!empty($currentParagraph)) {
                    $paragraphs[] = trim($currentParagraph);
                    $currentParagraph = '';
                }
            } else {
                $currentParagraph .= $line . ' ';
            }
        }

        if (!empty($currentParagraph)) {
            $paragraphs[] = trim($currentParagraph);
        }

        return [
            'paragraphs' => $paragraphs,
            'paragraph_count' => count($paragraphs),
            'total_length' => strlen($text),
        ];
    }

    /**
     * Get cache key for image
     */
    private function getCacheKey(string $imagePath): string
    {
        $hash = md5_file($imagePath);
        return "ocr_text_{$hash}";
    }

    /**
     * Clear OCR cache
     */
    public function clearCache(): void
    {
        // This would require a cache tag system or manual tracking
        Log::info("OCR cache clear requested");
    }

    /**
     * Get OCR statistics
     */
    public function getStatistics(): array
    {
        return [
            'supported_formats'    => $this->supportedFormats,
            'languages'            => $this->languages,
            'cache_enabled'        => $this->cacheEnabled,
            'cache_ttl_days'       => $this->cacheTTL / 86400,
            'tesseract_available'  => $this->isTesseractAvailable(),
            'imagick_available'    => extension_loaded('imagick'),
            'gd_available'         => extension_loaded('gd'),
        ];
    }
}
