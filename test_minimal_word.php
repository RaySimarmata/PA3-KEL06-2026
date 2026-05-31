<?php
/**
 * Test minimal Word document generation
 */

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

echo "Creating minimal Word document...\n";

try {
    $phpWord = new PhpWord();
    
    // Set properties
    $properties = $phpWord->getDocInfo();
    $properties->setCreator('Test');
    $properties->setTitle('Test Document');
    
    // Set default font
    $phpWord->setDefaultFontName('Arial');
    $phpWord->setDefaultFontSize(11);
    
    // Add section with MINIMAL settings
    $section = $phpWord->addSection();
    
    // Add simple content
    $section->addText('Test Title', [
        'bold' => true,
        'size' => 16,
        'name' => 'Arial',
    ]);
    
    $section->addTextBreak();
    
    $section->addText('This is a test paragraph with some content.');
    $section->addText('This is another paragraph.');
    
    // Save
    $filename = 'test_minimal_' . time() . '.docx';
    $filepath = __DIR__ . '/storage/app/public/' . $filename;
    
    if (!file_exists(dirname($filepath))) {
        mkdir(dirname($filepath), 0755, true);
    }
    
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($filepath);
    
    echo "✓ SUCCESS! File created: $filepath\n";
    echo "File size: " . filesize($filepath) . " bytes\n";
    echo "Try opening this file in Microsoft Word.\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
