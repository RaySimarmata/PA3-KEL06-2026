<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\LaporanKuesioneService;
use App\Models\User;
use App\Models\TemplateLaporan;

$user = User::first();
$prodiId = $user->prodi_id ?? null;
$template = TemplateLaporan::where('nama_template','like','%Kusioner%')->first();
$tplId = $template ? $template->id : null;

$service = app(LaporanKuesioneService::class);
$periode = date('Y-m');
try {
    $result = $service->generateLaporanWithPlaceholders($periode, $prodiId, $tplId, 'UTS');
    echo "AI generate succeeded. Keys: " . implode(',', array_keys($result['hasil_laporan'] ?? [])) . "\n";
    // Print brief preview
    $preview = $result['hasil_laporan'];
    foreach (['PENDAHULUAN_TUJUAN','HASIL_KUESIONER_TINGKAT_I','KESIMPULAN'] as $k) {
        echo "--- $k ---\n";
        echo substr($preview[$k] ?? '(missing)', 0, 300) . "\n\n";
    }
} catch (Exception $e) {
    echo "AI generate failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(2);
}
