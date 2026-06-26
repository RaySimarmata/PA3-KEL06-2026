<?php
$f = __DIR__ . '/../storage/app/public/templates/1781024955_Template Laporan Bulanan.docx';
$zip = new ZipArchive();
if ($zip->open($f) !== true) { echo "openfail\n"; exit(1); }
for ($i=0; $i<$zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if (!preg_match('/\.xml$/i', $name)) continue;
    $c = $zip->getFromName($name);
    if ($c === false) continue;
    if (strpos($c,'{{') !== false) {
        echo "FILE: $name\n";
        $pos = strpos($c,'{{');
        $start = max(0, $pos-120);
        echo substr($c,$start,360) . "\n\n";
    }
}
$zip->close();
