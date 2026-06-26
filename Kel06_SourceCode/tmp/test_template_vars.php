<?php
require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;
$path = __DIR__ . '/../app/temp_template_6a297d3e2932e.docx';
$tpl = new TemplateProcessor($path);
$tpl->setMacroChars('{{','}}');
print_r($tpl->getVariables());
