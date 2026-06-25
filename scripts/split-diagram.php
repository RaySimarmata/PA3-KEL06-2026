#!/usr/bin/env php
<?php

/**
 * Split large PlantUML diagram into smaller parts
 * For PlantUML online viewer that has header size limit
 */

if ($argc < 2) {
    echo "Usage: php split-diagram.php <input.puml>\n";
    echo "Example: php split-diagram.php docs/diagrams/models.puml\n";
    exit(1);
}

$inputFile = $argv[1];
$outputDir = dirname($inputFile);
$baseName = basename($inputFile, '.puml');

if (!file_exists($inputFile)) {
    die("Error: File not found: $inputFile\n");
}

$content = file_get_contents($inputFile);

// Extract classes
preg_match_all('/class\s+(\w+)\s*\{.*?\}/s', $content, $matches, PREG_SET_ORDER);

$classesPerFile = 10; // 10 classes per file
$chunks = array_chunk($matches, $classesPerFile);

echo "Found " . count($matches) . " classes\n";
echo "Splitting into " . count($chunks) . " files...\n\n";

foreach ($chunks as $index => $chunk) {
    $partNumber = $index + 1;
    $outputFile = "{$outputDir}/{$baseName}-part{$partNumber}.puml";
    
    $output = "@startuml\n";
    $output .= "title {$baseName} - Part {$partNumber}\n\n";
    
    foreach ($chunk as $class) {
        $output .= $class[0] . "\n\n";
    }
    
    $output .= "@enduml\n";
    
    file_put_contents($outputFile, $output);
    
    $classCount = count($chunk);
    echo "✓ Created: {$outputFile} ({$classCount} classes)\n";
}

echo "\n✅ Done! Generated " . count($chunks) . " parts\n";
echo "\nYou can now view each part online:\n";
echo "https://www.plantuml.com/plantuml/uml/\n";
