<?php

/**
 * Script to create test images for validation testing
 * 
 * This script creates dummy images with text labels for testing
 * the image content validation feature.
 * 
 * Usage: php scripts/create_test_validation_images.php
 */

// Create test_images directory if not exists
$testImagesDir = __DIR__ . '/../storage/app/test_images';
if (!is_dir($testImagesDir)) {
    mkdir($testImagesDir, 0755, true);
    echo "✓ Created directory: {$testImagesDir}\n";
}

// Test images to create
$testImages = [
    // Images that should be REJECTED
    [
        'filename' => 'apple-logo.png',
        'label' => 'APPLE LOGO',
        'description' => 'Logo perusahaan teknologi',
        'color' => [0, 0, 0], // Black
        'bg_color' => [255, 255, 255], // White
        'should_reject' => true
    ],
    [
        'filename' => 'eclipse-ide.png',
        'label' => 'ECLIPSE IDE',
        'description' => 'Screenshot IDE',
        'color' => [50, 50, 50],
        'bg_color' => [240, 240, 240],
        'should_reject' => true
    ],
    [
        'filename' => 'meme.jpg',
        'label' => 'FUNNY MEME',
        'description' => 'Gambar hiburan',
        'color' => [255, 0, 0],
        'bg_color' => [255, 255, 200],
        'should_reject' => true
    ],
    [
        'filename' => 'logo.png',
        'label' => 'TECH LOGO',
        'description' => 'Logo teknologi',
        'color' => [0, 0, 255],
        'bg_color' => [255, 255, 255],
        'should_reject' => true
    ],
    
    // Images that should be ACCEPTED
    [
        'filename' => 'daftar-hadir.jpg',
        'label' => 'DAFTAR HADIR',
        'description' => 'Dokumentasi kehadiran',
        'color' => [0, 100, 0],
        'bg_color' => [255, 255, 255],
        'should_reject' => false
    ],
    [
        'filename' => 'grafik-akademik.png',
        'label' => 'GRAFIK DATA',
        'description' => 'Chart akademik',
        'color' => [0, 0, 150],
        'bg_color' => [255, 255, 255],
        'should_reject' => false
    ],
    [
        'filename' => 'siakad-screenshot.png',
        'label' => 'SIAKAD SYSTEM',
        'description' => 'Screenshot sistem akademik',
        'color' => [0, 100, 100],
        'bg_color' => [240, 248, 255],
        'should_reject' => false
    ],
    [
        'filename' => 'kegiatan.jpg',
        'label' => 'KEGIATAN KAMPUS',
        'description' => 'Dokumentasi kegiatan',
        'color' => [100, 50, 0],
        'bg_color' => [255, 250, 240],
        'should_reject' => false
    ],
    [
        'filename' => 'semester.jpg',
        'label' => 'LAPORAN SEMESTER',
        'description' => 'Dokumentasi semester',
        'color' => [50, 0, 100],
        'bg_color' => [255, 240, 255],
        'should_reject' => false
    ],
    [
        'filename' => 'vmts.jpg',
        'label' => 'VISI MISI',
        'description' => 'Dokumentasi VMTS',
        'color' => [100, 100, 0],
        'bg_color' => [255, 255, 240],
        'should_reject' => false
    ],
    
    // Edge cases
    [
        'filename' => 'unclear.jpg',
        'label' => 'BLUR IMAGE',
        'description' => 'Gambar tidak jelas',
        'color' => [128, 128, 128],
        'bg_color' => [200, 200, 200],
        'should_reject' => false // Low confidence
    ],
    [
        'filename' => 'test.jpg',
        'label' => 'TEST IMAGE',
        'description' => 'Generic test',
        'color' => [0, 0, 0],
        'bg_color' => [255, 255, 255],
        'should_reject' => false
    ],
];

// Create images
$created = 0;
$failed = 0;

foreach ($testImages as $imageConfig) {
    $filepath = $testImagesDir . '/' . $imageConfig['filename'];
    
    try {
        // Create image (800x600)
        $width = 800;
        $height = 600;
        $image = imagecreatetruecolor($width, $height);
        
        // Set background color
        $bgColor = imagecolorallocate(
            $image,
            $imageConfig['bg_color'][0],
            $imageConfig['bg_color'][1],
            $imageConfig['bg_color'][2]
        );
        imagefill($image, 0, 0, $bgColor);
        
        // Set text color
        $textColor = imagecolorallocate(
            $image,
            $imageConfig['color'][0],
            $imageConfig['color'][1],
            $imageConfig['color'][2]
        );
        
        // Add label text (centered)
        $fontSize = 5; // Built-in font size
        $textWidth = imagefontwidth($fontSize) * strlen($imageConfig['label']);
        $textHeight = imagefontheight($fontSize);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2 - 50;
        imagestring($image, $fontSize, $x, $y, $imageConfig['label'], $textColor);
        
        // Add description (smaller, below label)
        $fontSize2 = 3;
        $textWidth2 = imagefontwidth($fontSize2) * strlen($imageConfig['description']);
        $x2 = ($width - $textWidth2) / 2;
        $y2 = $y + 40;
        imagestring($image, $fontSize2, $x2, $y2, $imageConfig['description'], $textColor);
        
        // Add validation status
        $status = $imageConfig['should_reject'] ? 'SHOULD BE REJECTED' : 'SHOULD BE ACCEPTED';
        $statusColor = $imageConfig['should_reject'] 
            ? imagecolorallocate($image, 255, 0, 0) 
            : imagecolorallocate($image, 0, 150, 0);
        $textWidth3 = imagefontwidth($fontSize2) * strlen($status);
        $x3 = ($width - $textWidth3) / 2;
        $y3 = $y2 + 30;
        imagestring($image, $fontSize2, $x3, $y3, $status, $statusColor);
        
        // Add border
        $borderColor = imagecolorallocate($image, 100, 100, 100);
        imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);
        imagerectangle($image, 1, 1, $width - 2, $height - 2, $borderColor);
        
        // Save image
        $extension = pathinfo($imageConfig['filename'], PATHINFO_EXTENSION);
        if ($extension === 'png') {
            imagepng($image, $filepath);
        } else {
            imagejpeg($image, $filepath, 90);
        }
        
        imagedestroy($image);
        
        $status_icon = $imageConfig['should_reject'] ? '❌' : '✅';
        echo "{$status_icon} Created: {$imageConfig['filename']} ({$imageConfig['description']})\n";
        $created++;
        
    } catch (Exception $e) {
        echo "✗ Failed to create {$imageConfig['filename']}: {$e->getMessage()}\n";
        $failed++;
    }
}

echo "\n";
echo "========================================\n";
echo "Summary:\n";
echo "  ✓ Created: {$created} images\n";
echo "  ✗ Failed: {$failed} images\n";
echo "  📁 Location: {$testImagesDir}\n";
echo "========================================\n";
echo "\n";
echo "Next steps:\n";
echo "1. Run tests: php artisan test --filter ImageContentValidationTest\n";
echo "2. Check validation: Upload these images to Laporan Triwulan form\n";
echo "3. Review logs: tail -f storage/logs/laravel.log\n";
echo "\n";
