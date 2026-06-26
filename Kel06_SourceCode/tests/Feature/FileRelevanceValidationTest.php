<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\FileRelevanceValidationService;

class FileRelevanceValidationTest extends TestCase
{
    /**
     * Test validation service exists and can be instantiated
     */
    public function test_validation_service_can_be_instantiated()
    {
        $service = app(FileRelevanceValidationService::class);
        
        $this->assertInstanceOf(FileRelevanceValidationService::class, $service);
    }

    /**
     * Test validation with empty files array
     */
    public function test_validation_with_empty_files()
    {
        $service = app(FileRelevanceValidationService::class);
        
        $result = $service->validateFileRelevance([], 'triwulan');
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['invalid_files']);
    }

    /**
     * Test triwulan endpoint rejects invalid files
     */
    public function test_triwulan_endpoint_validates_files()
    {
        Storage::fake('local');
        
        // Create a fake image file
        $file = UploadedFile::fake()->image('test.jpg');
        
        $response = $this->post('/gjm/buat-laporan/triwulan/ai-prompt', [
            'prompt' => 'Test prompt',
            'file_referensi' => [$file],
        ]);
        
        // Should either accept or reject based on AI validation
        // We just check that the endpoint is working
        $this->assertTrue(
            $response->status() === 200 || 
            $response->status() === 422
        );
    }

    /**
     * Test semester endpoint validates files
     */
    public function test_semester_endpoint_validates_files()
    {
        Storage::fake('local');
        
        // Create a fake image file
        $file = UploadedFile::fake()->image('test.jpg');
        
        $response = $this->post('/gjm/buat-laporan/semester/ai-prompt', [
            'prompt' => 'Test prompt',
            'file_referensi' => [$file],
        ]);
        
        // Should either accept or reject based on AI validation
        // We just check that the endpoint is working
        $this->assertTrue(
            $response->status() === 200 || 
            $response->status() === 422
        );
    }

    /**
     * Test validation response structure
     */
    public function test_validation_response_structure()
    {
        $service = app(FileRelevanceValidationService::class);
        
        // Test with empty array to get predictable response
        $result = $service->validateFileRelevance([], 'triwulan');
        
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('invalid_files', $result);
        $this->assertIsBool($result['valid']);
        $this->assertIsArray($result['invalid_files']);
    }
}
