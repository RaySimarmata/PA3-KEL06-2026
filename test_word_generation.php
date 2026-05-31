<?php
/**
 * Test script untuk memverifikasi Word document generation
 * Run: php test_word_generation.php
 */

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

echo "Testing Word Document Generation...\n\n";

try {
    // Create new document
    $phpWord = new PhpWord();
    
    // Set document properties
    $properties = $phpWord->getDocInfo();
    $properties->setCreator('Test');
    $properties->setTitle('Test Document');
    
    // Set default font
    $phpWord->setDefaultFontName('Arial');
    $phpWord->setDefaultFontSize(11);
    
    // Add section with integer twip values
    $sectionStyle = [
        'orientation'  => 'portrait',
        'marginLeft'   => 1701,   // 3 cm
        'marginRight'  => 1701,   // 3 cm
        'marginTop'    => 1701,   // 3 cm
        'marginBottom' => 1701,   // 3 cm
        'pageSizeW'    => 11906,  // A4 width in twips
        'pageSizeH'    => 16838,  // A4 height in twips
    ];
    
    $section = $phpWord->addSection($sectionStyle);
    
    // Add title
    $section->addText('Test Document', [
        'bold' => true,
        'size' => 16,
        'name' => 'Arial',
        'color' => '000000'
    ], [
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
        'spaceAfter' => 200
    ]);
    
    // Add some content
    $section->addText('This is a test paragraph with some content.', [
        'size' => 11,
        'name' => 'Arial',
        'color' => '000000'
    ], ['spaceAfter' => 120]);
    
    // Add a table
    $table = $section->addTable([
        'borderSize'  => 6,
        'borderColor' => '999999',
        'cellMargin'  => 80,
        'width'       => 100 * 50,
        'unit'        => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT,
    ]);
    
    $table->addRow();
    $table->addCell(3000, ['bgColor' => '1F3864'])->addText('Header 1', [
        'name'  => 'Arial',
        'size'  => 10,
        'bold'  => true,
        'color' => 'FFFFFF',
    ]);
    $table->addCell(3000, ['bgColor' => '1F3864'])->addText('Header 2', [
        'name'  => 'Arial',
        'size'  => 10,
        'bold'  => true,
        'color' => 'FFFFFF',
    ]);
    
    $table->addRow();
    $table->addCell(3000)->addText('Data 1', ['name' => 'Arial', 'size' => 10]);
    $table->addCell(3000)->addText('Data 2', ['name' => 'Arial', 'size' => 10]);
    
    // Save document
    $filename = 'test_document_' . time() . '.docx';
    $filepath = __DIR__ . '/storage/app/public/' . $filename;
    
    // Create directory if not exists
    if (!file_exists(dirname($filepath))) {
        mkdir(dirname($filepath), 0755, true);
    }
    
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $tempFilepath = $filepath . '.tmp';
    $objWriter->save($tempFilepath);
    
    echo "Document saved to temp file: $tempFilepath\n";
    
    // Post-process: fix float values
    $zip = new \ZipArchive();
    if ($zip->open($tempFilepath) === true) {
        $docXml = $zip->getFromName('word/document.xml');
        if ($docXml !== false) {
            $fixed = preg_replace_callback(
                '/(w:(?:w|h|top|bottom|left|right|gutter|space|header|footer))="([\d]+\.[\d]+)"/',
                function($m) {
                    return $m[1] . '="' . (string) (int) round((float) $m[2]) . '"';
                },
                $docXml
            );
            
            if ($fixed && $fixed !== $docXml) {
                $zip->deleteName('word/document.xml');
                $zip->addFromString('word/document.xml', $fixed);
                echo "Fixed float values in XML\n";
            }
        }
        $zip->close();
    }
    
    // Move to final location
    rename($tempFilepath, $filepath);
    
    // Validate
    $fileSize = filesize($filepath);
    echo "File size: $fileSize bytes\n";
    
    if ($fileSize < 5120) {
        throw new Exception('File too small!');
    }
    
    // Validate ZIP
    $zip = new \ZipArchive();
    $zipStatus = $zip->open($filepath, \ZipArchive::CHECKCONS);
    if ($zipStatus !== true) {
        throw new Exception('Invalid ZIP: ' . $zipStatus);
    }
    
    // Check required files
    $requiredFiles = ['word/document.xml', '[Content_Types].xml', '_rels/.rels'];
    foreach ($requiredFiles as $requiredFile) {
        if ($zip->locateName($requiredFile) === false) {
            $zip->close();
            throw new Exception('Missing file: ' . $requiredFile);
        }
    }
    $zip->close();
    
    echo "\n✓ SUCCESS! Document created and validated: $filepath\n";
    echo "You can now open this file in Microsoft Word to verify.\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
