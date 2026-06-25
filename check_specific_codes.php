<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$codes = ['4143107', '4143101', '4143103'];

echo "Checking dosen data for specific mata kuliah codes:\n\n";

foreach ($codes as $code) {
    echo "Kode MK: $code\n";
    
    // Check Ganjil
    $countGanjil = DB::table('jadwal_dosen')
        ->where('kode_mk', $code)
        ->where('semester', '1')
        ->where('tahun_ajaran', '2025')
        ->count();
    echo "  Semester Ganjil (1): $countGanjil dosen\n";
    
    if ($countGanjil > 0) {
        $dosen = DB::table('jadwal_dosen as jd')
            ->leftJoin('dosenn as d', 'jd.pegawai_id', '=', 'd.pegawai_id')
            ->where('jd.kode_mk', $code)
            ->where('jd.semester', '1')
            ->where('jd.tahun_ajaran', '2025')
            ->select('d.nama')
            ->distinct()
            ->pluck('nama');
        echo "    Dosen: " . $dosen->implode(', ') . "\n";
    }
    
    // Check Genap
    $countGenap = DB::table('jadwal_dosen')
        ->where('kode_mk', $code)
        ->where('semester', '2')
        ->where('tahun_ajaran', '2025')
        ->count();
    echo "  Semester Genap (2): $countGenap dosen\n";
    
    if ($countGenap > 0) {
        $dosen = DB::table('jadwal_dosen as jd')
            ->leftJoin('dosenn as d', 'jd.pegawai_id', '=', 'd.pegawai_id')
            ->where('jd.kode_mk', $code)
            ->where('jd.semester', '2')
            ->where('jd.tahun_ajaran', '2025')
            ->select('d.nama')
            ->distinct()
            ->pluck('nama');
        echo "    Dosen: " . $dosen->implode(', ') . "\n";
    }
    
    echo "\n";
}
