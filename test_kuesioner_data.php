<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Kuesioner Data ===\n\n";

// Test query
$uploads = \App\Models\KuesioneUpload::where('semester', 2)
    ->take(5)
    ->get(['id', 'kode_matakuliah', 'nama_matakuliah', 'tingkat', 'index_kepuasan']);

echo "Total records: " . $uploads->count() . "\n\n";

foreach ($uploads as $u) {
    echo "ID: {$u->id}\n";
    echo "Kode: {$u->kode_matakuliah}\n";
    echo "Tingkat: " . ($u->tingkat ?? 'NULL') . " (type: " . gettype($u->tingkat) . ")\n";
    echo "Index: {$u->index_kepuasan}\n";
    echo "---\n";
}

// Test grouping
echo "\n=== Testing Grouping ===\n\n";

$grouped = $uploads->groupBy(function($item) {
    $tingkat = $item->tingkat;
    if (is_numeric($tingkat)) {
        return (int)$tingkat;
    }
    return $tingkat ?? 'unknown';
});

echo "Groups: " . implode(', ', $grouped->keys()->toArray()) . "\n";
foreach ($grouped as $key => $items) {
    echo "Tingkat {$key}: " . $items->count() . " items\n";
}
