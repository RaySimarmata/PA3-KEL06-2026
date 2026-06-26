<?php
require __DIR__ . "/../vendor/autoload.php";
$src = 'storage/app/public/templates/1781059401_Template Laporan Bulanan.docx';
$dst = 'tmp/test_template_replace.docx';
$tp = new \PhpOffice\PhpWord\TemplateProcessor($src);
$tp->setMacroChars('{{','}}');
$tp->setValue('PROGRAM_KERJA', 'TEST_PROGRAM');
$tp->saveAs($dst);
$zip = new ZipArchive();
if ($zip->open($dst) !== true) { echo "SAVE_FAIL"; exit(1); }
$xml = $zip->getFromName('word/document.xml');
$zip->close();
if (strpos($xml, 'TEST_PROGRAM') !== false) {
    echo 'REPLACED';
} else {
    echo 'NOT_REPLACED';
}