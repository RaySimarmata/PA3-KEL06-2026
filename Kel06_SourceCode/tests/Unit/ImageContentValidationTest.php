<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ImageContentValidationService;
use App\Services\OCRService;
use Mockery;

class ImageContentValidationTest extends TestCase
{
    protected $validationService;
    protected $ocrServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock OCR Service
        $this->ocrServiceMock = Mockery::mock(OCRService::class);
        $this->validationService = new ImageContentValidationService($this->ocrServiceMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: Valid academic document should be accepted
     */
    public function test_valid_academic_document_is_accepted()
    {
        // Mock OCR result with academic content
        $this->ocrServiceMock->shouldReceive('extractText')
            ->once()
            ->andReturn([
                'success' => true,
                'text' => 'LAPORAN MONITORING PERKULIAHAN SEMESTER GANJIL 2025/2026
                          Mata Kuliah: Pemrograman Web
                          Dosen: Dr. Ahmad Santoso
                          Jumlah Mahasiswa: 45 orang
                          Persentase Kehadiran: 85%
                          Evaluasi: Baik',
                'method' => 'tesseract',
                'word_count' => 20
            ]);

        $result = $this->validationService->validateImageRelevance(
            '/fake/path/document.jpg',
            'laporan_triwulan'
        );

        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThanOrEqual(0.5, $result['confidence']);
        $this->assertGreaterThanOrEqual(0.3, $result['relevance_score']);
    }

    /**
     * Test: Logo image should be rejected
     */
    public function test_logo_image_is_rejected()
    {
        // Mock OCR result with logo text
        $this->ocrServiceMock->shouldReceive('extractText')
            ->once()
            ->andReturn([
                'success' => true,
                'text' => 'Apple Inc.',
                'method' => 'tesseract',
                'word_count' => 2
            ]);

        $result = $this->validationService->validateImageRelevance(
            '/fake/path/apple-logo.jpg',
            'laporan_triwulan'
        );

        $this->assertFalse($result['is_valid']);
        $this->assertGreaterThanOrEqual(0.7, $result['confidence']);
    }

    /**
     * Test: Image with irrelevant content should be rejected
     */
    public function test_irrelevant_content_is_rejected()
    {
        // Mock OCR result with shopping content
        $this->ocrServiceMock->shouldReceive('extractText')
            ->once()
            ->andReturn([
                'success' => true,
                'text' => 'SALE! 50% OFF
                          Buy iPhone 15 Pro Max
                          Limited Time Offer
                          Shop Now at our E-commerce Store
                          Free Shipping for all products',
                'method' => 'tesseract',
                'word_count' => 20
            ]);

        $result = $this->validationService->validateImageRelevance(
            '/fake/path/shopping.jpg',
            'laporan_triwulan'
        );

        $this->assertFalse($result['is_valid']);
        $this->assertLessThan(0.3, $result['relevance_score']);
    }

    /**
     * Test: OCR failure should allow image with low confidence
     */
    public function test_ocr_failure_allows_image_with_low_confidence()
    {
        // Mock OCR failure
        $this->ocrServiceMock->shouldReceive('extractText')
            ->once()
            ->andReturn([
                'success' => false,
                'text' => '',
                'method' => 'failed',
                'error' => 'OCR extraction failed'
            ]);

        $result = $this->validationService->validateImageRelevance(
            '/fake/path/corrupt.jpg',
            'laporan_triwulan'
        );

        $this->assertTrue($result['is_valid']);
        $this->assertLessThanOrEqual(0.5, $result['confidence']);
        $this->assertStringContainsString('OCR tidak dapat membaca', $result['reason']);
    }

    /**
     * Test: Short text should be checked for logo
     */
    public function test_short_text_is_checked_for_logo()
    {
        // Mock OCR result with very short text
        $this->ocrServiceMock->shouldReceive('extractText')
            ->once()
            ->andReturn([
                'success' => true,
                'text' => 'Logo',
                'method' => 'tesseract',
                'word_count' => 1
            ]);

        $result = $this->validationService->validateImageRelevance(
            '/fake/path/simple.jpg',
            'laporan_triwulan'
        );

        $this->assertFalse($result['is_valid']);
        $this->assertGreaterThanOrEqual(0.7, $result['confidence']);
        $this->assertStringContainsString('logo atau gambar sederhana', $result['reason']);
    }

    /**
     * Test: High-value keywords boost score
     */
    public function test_high_value_keywords_boost_score()
    {
        // Mock OCR result with high-value keywords
        $this->ocrServiceMock->shouldReceive('extractText')
            ->once()
            ->andReturn([
                'success' => true,
                'text' => 'LAPORAN TRIWULAN MONITORING MUTU GKM
                          Periode: Januari - Maret 2026
                          Evaluasi Semester Ganjil',
                'method' => 'tesseract',
                'word_count' => 12
            ]);

        $result = $this->validationService->validateImageRelevance(
            '/fake/path/report.jpg',
            'laporan_triwulan'
        );

        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThanOrEqual(0.7, $result['confidence']);
        $this->assertGreaterThanOrEqual(0.5, $result['relevance_score']);
    }

    /**
     * Test: Batch validation
     */
    public function test_batch_validation()
    {
        // Mock OCR results for multiple images
        $this->ocrServiceMock->shouldReceive('extractText')
            ->times(3)
            ->andReturn(
                // First image: valid
                [
                    'success' => true,
                    'text' => 'Laporan Monitoring Perkuliahan Mahasiswa Dosen',
                    'method' => 'tesseract',
                    'word_count' => 5
                ],
                // Second image: invalid (logo)
                [
                    'success' => true,
                    'text' => 'Apple',
                    'method' => 'tesseract',
                    'word_count' => 1
                ],
                // Third image: valid
                [
                    'success' => true,
                    'text' => 'Daftar Hadir Kuliah Semester Ganjil Universitas',
                    'method' => 'tesseract',
                    'word_count' => 6
                ]
            );

        $result = $this->validationService->validateImagesBatch(
            [
                '/fake/path/doc1.jpg',
                '/fake/path/logo.jpg',
                '/fake/path/doc2.jpg'
            ],
            'laporan_triwulan'
        );

        $this->assertEquals(3, $result['total_count']);
        $this->assertEquals(2, $result['valid_count']);
        $this->assertEquals(1, $result['invalid_count']);
    }

    /**
     * Test: Relevance score calculation
     */
    public function test_relevance_score_calculation()
    {
        // Test with varying amounts of relevant keywords
        $testCases = [
            // 0 relevant keywords
            [
                'text' => 'Random text without any relevant keywords',
                'expected_min_score' => 0.0,
                'expected_max_score' => 0.2
            ],
            // 3 relevant keywords
            [
                'text' => 'Mahasiswa dosen perkuliahan',
                'expected_min_score' => 0.3,
                'expected_max_score' => 0.7
            ],
            // 5+ relevant keywords
            [
                'text' => 'Mahasiswa dosen perkuliahan monitoring evaluasi semester',
                'expected_min_score' => 0.5,
                'expected_max_score' => 1.0
            ]
        ];

        foreach ($testCases as $testCase) {
            $this->ocrServiceMock->shouldReceive('extractText')
                ->once()
                ->andReturn([
                    'success' => true,
                    'text' => $testCase['text'],
                    'method' => 'tesseract',
                    'word_count' => str_word_count($testCase['text'])
                ]);

            $result = $this->validationService->validateImageRelevance(
                '/fake/path/test.jpg',
                'laporan_triwulan'
            );

            $this->assertGreaterThanOrEqual(
                $testCase['expected_min_score'],
                $result['relevance_score'],
                "Score should be >= {$testCase['expected_min_score']} for text: {$testCase['text']}"
            );
            
            $this->assertLessThanOrEqual(
                $testCase['expected_max_score'],
                $result['relevance_score'],
                "Score should be <= {$testCase['expected_max_score']} for text: {$testCase['text']}"
            );
        }
    }

    /**
     * Test: Penalty for irrelevant keywords
     */
    public function test_penalty_for_irrelevant_keywords()
    {
        // Mock OCR result with both relevant and irrelevant keywords
        $this->ocrServiceMock->shouldReceive('extractText')
            ->once()
            ->andReturn([
                'success' => true,
                'text' => 'Mahasiswa dosen perkuliahan Apple iPhone shopping game',
                'method' => 'tesseract',
                'word_count' => 7
            ]);

        $result = $this->validationService->validateImageRelevance(
            '/fake/path/mixed.jpg',
            'laporan_triwulan'
        );

        // Score should be reduced due to irrelevant keywords
        $this->assertLessThan(0.5, $result['relevance_score']);
    }

    /**
     * Test: Validation reason messages
     */
    public function test_validation_reason_messages()
    {
        // Test high relevance
        $this->ocrServiceMock->shouldReceive('extractText')
            ->once()
            ->andReturn([
                'success' => true,
                'text' => 'Laporan Monitoring Perkuliahan Mahasiswa Dosen Semester Evaluasi Mutu GKM',
                'method' => 'tesseract',
                'word_count' => 10
            ]);

        $result = $this->validationService->validateImageRelevance(
            '/fake/path/high-relevance.jpg',
            'laporan_triwulan'
        );

        $this->assertTrue($result['is_valid']);
        $this->assertStringContainsString('sangat relevan', strtolower($result['reason']));

        // Test low relevance (rejected)
        $this->ocrServiceMock->shouldReceive('extractText')
            ->once()
            ->andReturn([
                'success' => true,
                'text' => 'Shopping sale discount product buy now',
                'method' => 'tesseract',
                'word_count' => 6
            ]);

        $result = $this->validationService->validateImageRelevance(
            '/fake/path/low-relevance.jpg',
            'laporan_triwulan'
        );

        $this->assertFalse($result['is_valid']);
        $this->assertStringContainsString('tidak relevan', strtolower($result['reason']));
    }
}
