<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ImageContentValidationService;
use App\Services\OCRService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;

class ImageContentValidationTest extends TestCase
{
    protected $validationService;
    protected $ocrService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock OCRService (optional)
        $this->ocrService = Mockery::mock(OCRService::class);
        
        // Create validation service
        $this->validationService = new ImageContentValidationService($this->ocrService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: Logo Apple harus ditolak berdasarkan filename
     */
    public function test_apple_logo_should_be_rejected()
    {
        // Create dummy file path
        $imagePath = storage_path('app/test_images/apple-logo.png');
        
        // Mock file existence and properties
        if (!file_exists($imagePath)) {
            // Create dummy file for testing
            $this->createDummyImage($imagePath, 150, 150, 30);
        }
        
        // Validate
        $result = $this->validationService->validateImageRelevance($imagePath, 'laporan_triwulan');

        // Assertions
        $this->assertFalse($result['is_valid']);
        $this->assertGreaterThanOrEqual(0.7, $result['confidence']);
        $this->assertStringContainsString('apple', strtolower($result['reason']));
    }

    /**
     * Test: Screenshot Eclipse IDE harus ditolak berdasarkan filename
     */
    public function test_eclipse_ide_screenshot_should_be_rejected()
    {
        $imagePath = storage_path('app/test_images/eclipse-ide.png');
        
        if (!file_exists($imagePath)) {
            $this->createDummyImage($imagePath, 1920, 1080, 500);
        }
        
        $result = $this->validationService->validateImageRelevance($imagePath, 'laporan_triwulan');

        $this->assertFalse($result['is_valid']);
        $this->assertGreaterThanOrEqual(0.7, $result['confidence']);
        $this->assertStringContainsString('eclipse', strtolower($result['reason']));
    }

    /**
     * Test: Daftar hadir harus diterima berdasarkan filename
     */
    public function test_attendance_list_should_be_accepted()
    {
        $imagePath = storage_path('app/test_images/daftar-hadir.jpg');
        
        if (!file_exists($imagePath)) {
            $this->createDummyImage($imagePath, 1200, 800, 250);
        }
        
        $result = $this->validationService->validateImageRelevance($imagePath, 'laporan_triwulan');

        $this->assertTrue($result['is_valid']);
        $this->assertStringContainsString('daftar-hadir', strtolower($result['reason']));
    }

    /**
     * Test: Grafik data akademik harus diterima
     */
    public function test_academic_chart_should_be_accepted()
    {
        $imagePath = storage_path('app/test_images/grafik-akademik.png');
        
        if (!file_exists($imagePath)) {
            $this->createDummyImage($imagePath, 800, 600, 180);
        }
        
        $result = $this->validationService->validateImageRelevance($imagePath, 'laporan_triwulan');

        $this->assertTrue($result['is_valid']);
        $this->assertStringContainsString('grafik', strtolower($result['reason']));
    }

    /**
     * Test: Gambar kecil (logo) harus ditolak berdasarkan dimensi
     */
    public function test_small_square_image_should_be_rejected()
    {
        $imagePath = storage_path('app/test_images/small-logo.png');
        
        if (!file_exists($imagePath)) {
            $this->createDummyImage($imagePath, 150, 150, 20);
        }
        
        $result = $this->validationService->validateImageRelevance($imagePath, 'laporan_triwulan');

        // Should be rejected due to small dimensions
        $this->assertFalse($result['is_valid']);
    }

    /**
     * Test: Gambar dengan ukuran file sangat kecil harus ditolak
     */
    public function test_very_small_file_should_be_rejected()
    {
        $imagePath = storage_path('app/test_images/tiny-file.png');
        
        if (!file_exists($imagePath)) {
            $this->createDummyImage($imagePath, 100, 100, 10);
        }
        
        $result = $this->validationService->validateImageRelevance($imagePath, 'laporan_triwulan');

        // Should be rejected due to small file size
        $this->assertFalse($result['is_valid']);
    }

    /**
     * Test: Multiple images validation
     */
    public function test_validate_multiple_images()
    {
        $imagePaths = [
            storage_path('app/test_images/daftar-hadir.jpg'),
            storage_path('app/test_images/apple-logo.png'),
            storage_path('app/test_images/grafik-akademik.png'),
        ];
        
        // Create dummy files
        foreach ($imagePaths as $path) {
            if (!file_exists($path)) {
                $filename = basename($path);
                if (strpos($filename, 'apple') !== false) {
                    $this->createDummyImage($path, 150, 150, 30);
                } else {
                    $this->createDummyImage($path, 1200, 800, 250);
                }
            }
        }
        
        $results = $this->validationService->validateMultipleImages($imagePaths, 'laporan_triwulan');

        $this->assertCount(3, $results);
        $this->assertTrue($results[0]['is_valid']); // daftar-hadir
        $this->assertFalse($results[1]['is_valid']); // apple-logo
        $this->assertTrue($results[2]['is_valid']); // grafik
    }

    /**
     * Test: File not found
     */
    public function test_file_not_found_should_return_invalid()
    {
        $imagePath = storage_path('app/test_images/nonexistent.jpg');
        
        $result = $this->validationService->validateImageRelevance($imagePath, 'laporan_triwulan');

        $this->assertFalse($result['is_valid']);
        $this->assertEquals(1.0, $result['confidence']);
        $this->assertStringContainsString('tidak ditemukan', strtolower($result['reason']));
    }

    /**
     * Test: Neutral filename (no pattern match)
     */
    public function test_neutral_filename_should_be_accepted()
    {
        $imagePath = storage_path('app/test_images/IMG_001.jpg');
        
        if (!file_exists($imagePath)) {
            $this->createDummyImage($imagePath, 1920, 1080, 2000);
        }
        
        $result = $this->validationService->validateImageRelevance($imagePath, 'laporan_triwulan');

        // Should be accepted (no rejected pattern, normal size)
        $this->assertTrue($result['is_valid']);
    }

    /**
     * Test: OCR validation with rejected pattern
     */
    public function test_ocr_with_code_pattern_should_be_rejected()
    {
        $imagePath = storage_path('app/test_images/code-screenshot.png');
        
        if (!file_exists($imagePath)) {
            $this->createDummyImage($imagePath, 1920, 1080, 500);
        }
        
        // Mock OCR to return code-like text
        $this->ocrService->shouldReceive('extractText')
            ->once()
            ->andReturn([
                'success' => true,
                'text' => 'public class Main { public static void main(String[] args) { System.out.println("Hello"); } }'
            ]);
        
        $result = $this->validationService->validateImageRelevance($imagePath, 'laporan_triwulan');

        // Should be rejected due to OCR content
        $this->assertFalse($result['is_valid']);
    }

    /**
     * Test: OCR validation with academic keywords
     */
    public function test_ocr_with_academic_keywords_should_be_accepted()
    {
        $imagePath = storage_path('app/test_images/document.jpg');
        
        if (!file_exists($imagePath)) {
            $this->createDummyImage($imagePath, 1200, 800, 250);
        }
        
        // Mock OCR to return academic text
        $this->ocrService->shouldReceive('extractText')
            ->once()
            ->andReturn([
                'success' => true,
                'text' => 'DAFTAR HADIR Rapat Koordinasi Dosen Institut Teknologi Del Semester Ganjil 2024'
            ]);
        
        $result = $this->validationService->validateImageRelevance($imagePath, 'laporan_triwulan');

        // Should be accepted due to academic keywords
        $this->assertTrue($result['is_valid']);
    }

    /**
     * Helper: Create dummy image file for testing
     */
    private function createDummyImage(string $path, int $width, int $height, int $sizeKB)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Create image
        $image = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgColor);
        
        // Save image
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if ($extension === 'png') {
            imagepng($image, $path);
        } else {
            imagejpeg($image, $path, 90);
        }
        
        imagedestroy($image);
    }
}
