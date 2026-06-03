<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Finding Specific Mata Kuliah ===\n\n";

// Check what the API returns for these mata kuliah
$apiService = new \App\Services\ExternalAPIService();

// Try to find these matkul from API
$prodiId = 4; // TRPL
$tahunAjaran = '2025';

// Try Ganjil first
echo "1. Fetching mata kuliah from API for TRPL, Ganjil 2025...\n";
$matkulDataGanjil = $apiService->getMatkulByProdiSemTa($prodiId, '1', $tahunAjaran);
echo "Total mata kuliah from API (Ganjil): " . count($matkulDataGanjil) . "\n";

// Try Genap 
echo "Fetching mata kuliah from API for TRPL, Genap 2025...\n";
$matkulDataGenap = $apiService->getMatkulByProdiSemTa($prodiId, '2', $tahunAjaran);
echo "Total mata kuliah from API (Genap): " . count($matkulDataGenap) . "\n\n";

$matkulData = array_merge($matkulDataGanjil, $matkulDataGenap);

// Search for the specific ones
$keywords = ['Tata Kelola', 'Algoritma', 'Automata'];

echo "2. Searching for specific mata kuliah:\n";
foreach ($keywords as $keyword) {
    echo "\n--- Searching for: $keyword ---\n";
    
    foreach ($matkulData as $mk) {
        $namaMatkul = $mk['nama_matkul'] ?? '';
        if (stripos($namaMatkul, $keyword) !== false) {
            $kodeMk = $mk['kode_mk'] ?? '';
            echo "Found: {$kodeMk} - {$namaMatkul}\n";
            
            // Check if this kode_mk exists in database
            $dosenCount = DB::table('jadwal_dosen')
                ->where('kode_mk', $kodeMk)
                ->where('semester', '2')
                ->where('tahun_ajaran', '2025')
                ->count();
            
            echo "  → Dosen in database for '{$kodeMk}': {$dosenCount}\n";
            
            // Try variations
            if ($dosenCount == 0) {
                // Try removing 'KU' prefix if exists
                if (strpos($kodeMk, 'KU') === 0) {
                    $altKode = substr($kodeMk, 2);
                    $altCount = DB::table('jadwal_dosen')
                        ->where('kode_mk', 'LIKE', "%{$altKode}%")
                        ->where('semester', '2')
                        ->where('tahun_ajaran', '2025')
                        ->count();
                    
                    if ($altCount > 0) {
                        $matches = DB::table('jadwal_dosen')
                            ->where('kode_mk', 'LIKE', "%{$altKode}%")
                            ->where('semester', '2')
                            ->where('tahun_ajaran', '2025')
                            ->distinct()
                            ->pluck('kode_mk');
                        
                        echo "  → Alternative codes found: " . $matches->implode(', ') . " ({$altCount} dosen)\n";
                    }
                }
            }
        }
    }
}

echo "\nDone!\n";
