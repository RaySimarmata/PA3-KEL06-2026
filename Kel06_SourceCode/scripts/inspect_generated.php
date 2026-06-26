<?php
$dir = __DIR__ . '/../storage/app/laporan_artefak';
$files = array_filter(glob($dir.'/*.docx'));
usort($files, function($a,$b){ return filemtime($b) - filemtime($a); });
$f = $files[0] ?? null;
if (!$f) { echo "NOFILE\n"; exit(1); }
$z = new ZipArchive();
if ($z->open($f) !== true) { echo "OPENFAIL\n"; exit(2); }
$found=false;
for ($i=0;$i<$z->numFiles;$i++){
    $name = $z->getNameIndex($i);
    if (!preg_match('/\.xml$/i',$name)) continue;
    $c = $z->getFromName($name);
    if ($c === false) continue;
    if (strpos($c,'{{') !== false) {
        echo "FILE: $name\n";
        $pos = strpos($c,'{{');
        $start = max(0, $pos-100);
        echo substr($c,$start,260) . "\n\n";
        $found=true;
    }
}
if (!$found) echo "No {{ found in generated docx\n";
$z->close();
