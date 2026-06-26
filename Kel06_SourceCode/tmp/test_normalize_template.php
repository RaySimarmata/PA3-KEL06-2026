<?php
if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return realpath(__DIR__ . '/../') . '/' . ltrim($path, '/');
    }
}
require __DIR__ . '/../vendor/autoload.php';

use App\Services\KuesioneWordGenerationService;

$service = new KuesioneWordGenerationService();
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('normalizeTemplatePath');
$method->setAccessible(true);
$templatePath = storage_path('storage/app/public/templates/1776400136_Laporan GKM Hasil Kuesioner Mahasiswa 2526.docx');
$normalized = $method->invoke($service, $templatePath);
if ($normalized === $templatePath) {
    echo "No normalization path produced\n";
}
print_r(array_slice(scandir(dirname($normalized)), 0, 20));

require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;
$tpl = new TemplateProcessor($normalized);
print_r($tpl->getVariables());
