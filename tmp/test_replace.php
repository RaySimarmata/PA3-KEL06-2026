<?php
require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;
$path = __DIR__ . '/../app/temp_template_6a297d3e2932e.docx';
$tpl = new TemplateProcessor($path);
$tpl->setMacroChars('{{','}}');
$tpl->setValue('HASIL_KUESIONER_TINGKAT_I','TEST_REPLACED');
$save = __DIR__ . '/../app/test_replace.docx';
$tpl->saveAs($save);
$zip = new ZipArchive();
$zip->open($save);
$xml = $zip->getFromName('word/document.xml');
$zip->close();
echo strpos($xml,'TEST_REPLACED') !== false ? 'replaced' : 'not replaced';
