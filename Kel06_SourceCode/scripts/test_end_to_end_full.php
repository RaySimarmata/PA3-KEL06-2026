<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaporanBulanan;
use App\Models\TemplateLaporan;
use App\Models\User;
use App\Services\LaporanKuesioneService;

$user = User::first();
if (!$user) { echo "No user found\n"; exit(1); }
$template = TemplateLaporan::where('nama_template','like','%Kusioner%')->first();
if (!$template) { echo "No template found\n"; exit(1); }

$laporan = LaporanBulanan::create([
    'periode' => date('Y-m'),
    'bulan' => date('F'),
    'tahun' => date('Y'),
    'user_id' => $user->id,
    'periode_akademik_id' => null,
    'template_id' => $template->id,
    'judul_laporan' => 'E2E TEST - Laporan Kuesioner',
    'status' => 'pending',
    'tipe_laporan' => 'UTS'
]);

$service = app(LaporanKuesioneService::class);
try {
    $result = $service->generateLaporanWithPlaceholders($laporan->periode, $user->prodi_id ?? null, $template->id, 'UTS');
    echo "AI generation OK, saving to laporan...\n";
    $laporan->hasil_laporan = $result['hasil_laporan'] ?? [];
    $laporan->save();

    $wordService = app(\App\Services\KuesioneWordGenerationService::class);
    $ok = $wordService->generateWordDocument($laporan);
    if ($ok) {
        $laporan = $laporan->fresh();
        echo "Word generated: " . ($laporan->file_word ?? 'no-path') . "\n";
        echo "Full path: " . storage_path('app/' . ($laporan->file_word ?? '')) . "\n";
        exit(0);
    } else {
        echo "Word generation returned falsy\n";
        exit(2);
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(3);
}
