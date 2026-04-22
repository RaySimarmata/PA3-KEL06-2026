<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * OCR Service untuk ekstraksi teks dari gambar
 * Mendukung berbagai provider OCR
 */
class OCRService
{
    private string $provider;
    private array $config;

    public function __construct()
    {
        $this->provider = env('OCR_PROVIDER', 'tesseract'); // tesseract, google_vision, azure_vision
        $this->config = [
            'google_vision' => [
                'api_key' => env('GOOGLE_VISION_API_KEY', ''),
                'endpoint' => 'https://vision.googleapis.com/v1/images:annotate'
            ],
            'azure_vision' => [
                'api_key' => env('AZURE_VISION_API_KEY', ''),
                'endpoint' => env('AZURE_VISION_ENDPOINT', ''),
            ],
            'tesseract' => [
                'command' => 'tesseract',
                'languages' => env('TESSERACT_LANGUAGES', 'eng+ind'), // English + Indonesian
            ]
        ];
    }

    /**
     * Extract text from image file
     *
     * @param string $imagePath Path to image file
     * @return array ['text' => string, 'confidence' => float, 'success' => bool, 'method' => string]
     */
    public function extractTextFromImage(string $imagePath): array
    {
        if (!file_exists($imagePath)) {
            return [
                'text' => '',
                'confidence' => 0.0,
                'success' => false,
                'method' => 'none',
                'error' => 'File not found: ' . $imagePath
            ];
        }

        Log::info('OCR: Starting text extraction', [
            'file' => $imagePath,
            'provider' => $this->provider,
            'file_size' => filesize($imagePath)
        ]);

        // Try primary provider
        $result = $this->extractWithProvider($imagePath, $this->provider);
        
        // If primary fails, try fallback providers
        if (!$result['success']) {
            $fallbackProviders = $this->getFallbackProviders();
            
            foreach ($fallbackProviders as $provider) {
                Log::info("OCR: Trying fallback provider: {$provider}");
                $result = $this->extractWithProvider($imagePath, $provider);
                
                if ($result['success']) {
                    break;
                }
            }
        }

        // Clean and validate extracted text
        if ($result['success'] && !empty($result['text'])) {
            $result['text'] = $this->cleanExtractedText($result['text']);
            
            // Validate if text extraction was meaningful
            if (strlen(trim($result['text'])) < 3) {
                $result['success'] = false;
                $result['error'] = 'Extracted text too short or empty';
            }
        }

        Log::info('OCR: Extraction completed', [
            'success' => $result['success'],
            'method' => $result['method'],
            'text_length' => strlen($result['text']),
            'confidence' => $result['confidence']
        ]);

        return $result;
    }

    /**
     * Extract text using specific provider
     */
    private function extractWithProvider(string $imagePath, string $provider): array
    {
        try {
            switch ($provider) {
                case 'google_vision':
                    return $this->extractWithGoogleVision($imagePath);
                
                case 'azure_vision':
                    return $this->extractWithAzureVision($imagePath);
                
                case 'tesseract':
                    return $this->extractWithTesseract($imagePath);
                
                default:
                    return [
                        'text' => '',
                        'confidence' => 0.0,
                        'success' => false,
                        'method' => $provider,
                        'error' => 'Unknown OCR provider: ' . $provider
                    ];
            }
        } catch (\Exception $e) {
            Log::error("OCR {$provider} failed", [
                'error' => $e->getMessage(),
                'file' => $imagePath
            ]);
            
            return [
                'text' => '',
                'confidence' => 0.0,
                'success' => false,
                'method' => $provider,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract text using Google Vision API
     */
    private function extractWithGoogleVision(string $imagePath): array
    {
        $apiKey = $this->config['google_vision']['api_key'];
        
        if (empty($apiKey)) {
            return [
                'text' => '',
                'confidence' => 0.0,
                'success' => false,
                'method' => 'google_vision',
                'error' => 'Google Vision API key not configured'
            ];
        }

        // Encode image to base64
        $imageData = base64_encode(file_get_contents($imagePath));
        
        $response = Http::timeout(60)->post($this->config['google_vision']['endpoint'], [
            'requests' => [
                [
                    'image' => [
                        'content' => $imageData
                    ],
                    'features' => [
                        [
                            'type' => 'TEXT_DETECTION',
                            'maxResults' => 1
                        ]
                    ]
                ]
            ]
        ], [
            'key' => $apiKey
        ]);

        if (!$response->successful()) {
            throw new \Exception('Google Vision API error: ' . $response->body());
        }

        $data = $response->json();
        
        if (isset($data['responses'][0]['textAnnotations'][0]['description'])) {
            $text = $data['responses'][0]['textAnnotations'][0]['description'];
            $confidence = $data['responses'][0]['textAnnotations'][0]['confidence'] ?? 0.8;
            
            return [
                'text' => $text,
                'confidence' => $confidence,
                'success' => true,
                'method' => 'google_vision'
            ];
        }

        return [
            'text' => '',
            'confidence' => 0.0,
            'success' => false,
            'method' => 'google_vision',
            'error' => 'No text detected by Google Vision'
        ];
    }

    /**
     * Extract text using Azure Computer Vision
     */
    private function extractWithAzureVision(string $imagePath): array
    {
        $apiKey = $this->config['azure_vision']['api_key'];
        $endpoint = $this->config['azure_vision']['endpoint'];
        
        if (empty($apiKey) || empty($endpoint)) {
            return [
                'text' => '',
                'confidence' => 0.0,
                'success' => false,
                'method' => 'azure_vision',
                'error' => 'Azure Vision API credentials not configured'
            ];
        }

        // Read image as binary
        $imageData = file_get_contents($imagePath);
        
        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $apiKey,
            'Content-Type' => 'application/octet-stream'
        ])->timeout(60)->post($endpoint . '/vision/v3.2/ocr', $imageData, [
            'language' => 'unk', // Auto-detect
            'detectOrientation' => 'true'
        ]);

        if (!$response->successful()) {
            throw new \Exception('Azure Vision API error: ' . $response->body());
        }

        $data = $response->json();
        $text = '';
        
        if (isset($data['regions'])) {
            foreach ($data['regions'] as $region) {
                foreach ($region['lines'] as $line) {
                    foreach ($line['words'] as $word) {
                        $text .= $word['text'] . ' ';
                    }
                    $text .= "\n";
                }
            }
        }

        if (!empty(trim($text))) {
            return [
                'text' => trim($text),
                'confidence' => 0.85, // Azure doesn't provide word-level confidence in OCR
                'success' => true,
                'method' => 'azure_vision'
            ];
        }

        return [
            'text' => '',
            'confidence' => 0.0,
            'success' => false,
            'method' => 'azure_vision',
            'error' => 'No text detected by Azure Vision'
        ];
    }

    /**
     * Extract text using Tesseract OCR (local)
     */
    private function extractWithTesseract(string $imagePath): array
    {
        // Check if tesseract is available
        if (!$this->commandExists('tesseract')) {
            return [
                'text' => '',
                'confidence' => 0.0,
                'success' => false,
                'method' => 'tesseract',
                'error' => 'Tesseract OCR not installed. Install with: apt-get install tesseract-ocr (Linux) or brew install tesseract (macOS)'
            ];
        }

        $outputPath = $imagePath . '_ocr';
        $languages = $this->config['tesseract']['languages'];
        
        // Build tesseract command
        $command = "tesseract \"{$imagePath}\" \"{$outputPath}\" -l {$languages} --psm 3";
        
        // Execute tesseract
        exec($command . ' 2>&1', $output, $returnCode);
        
        $textFile = $outputPath . '.txt';
        
        if ($returnCode === 0 && file_exists($textFile)) {
            $text = file_get_contents($textFile);
            
            // Clean up temp file
            @unlink($textFile);
            
            if (!empty(trim($text))) {
                return [
                    'text' => trim($text),
                    'confidence' => 0.75, // Tesseract doesn't provide confidence easily
                    'success' => true,
                    'method' => 'tesseract'
                ];
            }
        }

        // Clean up temp file if exists
        if (file_exists($textFile)) {
            @unlink($textFile);
        }

        return [
            'text' => '',
            'confidence' => 0.0,
            'success' => false,
            'method' => 'tesseract',
            'error' => 'Tesseract failed to extract text. Output: ' . implode(' ', $output)
        ];
    }

    /**
     * Get fallback providers in order of preference
     */
    private function getFallbackProviders(): array
    {
        $all = ['google_vision', 'azure_vision', 'tesseract'];
        
        // Remove current provider from fallbacks
        return array_filter($all, fn($p) => $p !== $this->provider);
    }

    /**
     * Check if command exists in system
     */
    private function commandExists(string $command): bool
    {
        $os = strtoupper(substr(PHP_OS, 0, 3));
        
        if ($os === 'WIN') {
            exec("where {$command}", $output, $returnCode);
        } else {
            exec("which {$command}", $output, $returnCode);
        }
        
        return $returnCode === 0;
    }

    /**
     * Clean extracted text
     */
    private function cleanExtractedText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        
        // Normalize line breaks
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        
        // Remove more than 2 consecutive line breaks
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        // Escape curly braces to prevent template errors
        $text = str_replace(['{', '}'], ['{{', '}}'], $text);
        
        return trim($text);
    }

    /**
     * Get available OCR providers
     */
    public function getAvailableProviders(): array
    {
        $providers = [];
        
        // Check Google Vision
        if (!empty($this->config['google_vision']['api_key'])) {
            $providers[] = 'google_vision';
        }
        
        // Check Azure Vision
        if (!empty($this->config['azure_vision']['api_key']) && !empty($this->config['azure_vision']['endpoint'])) {
            $providers[] = 'azure_vision';
        }
        
        // Check Tesseract
        if ($this->commandExists('tesseract')) {
            $providers[] = 'tesseract';
        }
        
        return $providers;
    }

    /**
     * Get current provider info
     */
    public function getProviderInfo(): array
    {
        return [
            'current_provider' => $this->provider,
            'available_providers' => $this->getAvailableProviders(),
            'config' => [
                'google_vision_configured' => !empty($this->config['google_vision']['api_key']),
                'azure_vision_configured' => !empty($this->config['azure_vision']['api_key']) && !empty($this->config['azure_vision']['endpoint']),
                'tesseract_available' => $this->commandExists('tesseract'),
            ]
        ];
    }
}