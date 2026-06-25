<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AIResponseCacheMongo;

try {
    $deleted = AIResponseCacheMongo::where('feature', 'artefak')->delete();
    echo "✅ Deleted {$deleted} artefak cache entries from MongoDB\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
