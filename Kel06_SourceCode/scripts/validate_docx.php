<?php
$dir = __DIR__ . '/../storage/app/laporan_artefak';
$files = array_filter(glob($dir . '/*.docx'));
if (empty($files)) {
    echo "NO FILES FOUND\n";
    exit(1);
}
usort($files, function($a, $b){ return filemtime($b) - filemtime($a); });
$f = $files[0];
echo "Checking file: " . basename($f) . "\n\n";
$z = new ZipArchive();
if ($z->open($f) !== true) {
    echo "FAILED TO OPEN ZIP\n";
    exit(2);
}
$errors = 0;
for ($i = 0; $i < $z->numFiles; $i++) {
    $name = $z->getNameIndex($i);
    if (!preg_match('/\.xml$/i', $name)) continue;
    $content = $z->getFromName($name);
    if ($content === false) {
        echo "ERRREAD: $name\n";
        $errors++;
        continue;
    }
    libxml_use_internal_errors(true);
    $s = @simplexml_load_string($content);
    if ($s === false) {
        echo "XMLERR: $name\n";
        foreach (libxml_get_errors() as $err) {
            echo trim($err->message) . " on line " . $err->line . "\n";
        }
        libxml_clear_errors();
        $errors++;
    }
}
$z->close();
if ($errors === 0) {
    echo "No XML errors detected.\n";
} else {
    echo "Finished with $errors XML errors.\n";
}
