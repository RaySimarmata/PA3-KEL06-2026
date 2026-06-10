<?php
$zip = new ZipArchive();
$path = 'storage/app/public/templates/1781059401_Template Laporan Bulanan.docx';
if ($zip->open($path) !== true) { echo "OPEN_FAIL"; exit(1); }
$xml = $zip->getFromName('word/document.xml');
foreach(['TABEL_RPS','TABEL_MATERI'] as $token){
    $pos = strpos($xml,$token);
    echo $token.':'.($pos===false?'NOT':'FOUND')."\n";
    if($pos!==false){
        $start=max(0,$pos-80);
        $end=min(strlen($xml),$pos+80);
        echo substr($xml,$start,$end-$start)."\n---\n";
    }
}