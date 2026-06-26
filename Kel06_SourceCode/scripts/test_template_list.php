<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TemplateLaporan;

$templates = TemplateLaporan::all();
echo "Templates count: " . $templates->count() . "\n";
foreach ($templates as $t) {
    echo "ID: {$t->id} | name: {$t->nama_template} | path: {$t->file_path}\n";
}
