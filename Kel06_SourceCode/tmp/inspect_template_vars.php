<?php
require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;

$path = __DIR__ . '/../storage/app/public/templates/1776400136_Laporan GKM Hasil Kuesioner Mahasiswa 2526.docx';
$tpl = new TemplateProcessor($path);
print_r($tpl->getVariables());
