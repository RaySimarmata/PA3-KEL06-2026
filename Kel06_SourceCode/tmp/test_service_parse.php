<?php
require __DIR__ . "/../vendor/autoload.php";
use App\Services\LaporanArtefakService;
use App\Services\TextExtractionService;
use App\Services\DocumentStructureService;
use App\Services\AdvancedChunkingService;

function instantiateWithoutConstructor($class) {
    $ref = new ReflectionClass($class);
    return $ref->newInstanceWithoutConstructor();
}

$deps = [
    instantiateWithoutConstructor(TextExtractionService::class),
    instantiateWithoutConstructor(DocumentStructureService::class),
    instantiateWithoutConstructor(AdvancedChunkingService::class),
];
$service = new LaporanArtefakService($deps[0], $deps[1], $deps[2]);
$text = "## BAB 1 PENDAHULUAN\n### 1.1 Latar Belakang\nContoh latar belakang...\n### 1.2 Dasar Acuan\nContoh dasar...\n## BAB 5 EVALUASI\nHasil pemeriksaan...\n## ANALISIS KETERCAPAIAN\nAnalisis...\n## TINDAK LANJUT\nTindak lanjut...\n## BAB 6 PENUTUP\nKesimpulan...";
$sections = $service->parseAIPreviewSections($text);
print_r($sections);
$extracted = $service->extractNarasiFromAIPreview($text, []);
print_r($extracted);