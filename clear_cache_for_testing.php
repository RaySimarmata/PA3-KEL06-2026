<?php

/**
 * Clear Cache for Testing - Simple Version
 * Quick script to clear old AI cache and RAGAS data
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  Hapus Cache Lama untuk Testing Faithfulness Improvements    ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

try {
    // Count current data
    $mongoCount = DB::connection('mongodb')->table('ai_response_cache')->count();
    $ragasCount = DB::table('ragas_evaluation_tests')->count();
    
    echo "📊 Data saat ini:\n";
    echo "   - MongoDB ai_response_cache: {$mongoCount} entries\n";
    echo "   - MySQL ragas_evaluation_tests: {$ragasCount} entries\n";
    echo "\n";
    
    if ($mongoCount === 0 && $ragasCount === 0) {
        echo "✅ Cache sudah kosong! Silakan generate laporan baru.\n\n";
        exit(0);
    }
    
    // Clear MongoDB cache
    echo "🗑️  Menghapus MongoDB cache...\n";
    $deletedMongo = DB::connection('mongodb')->table('ai_response_cache')->delete();
    echo "   ✅ Berhasil hapus {$deletedMongo} entries\n";
    
    // Clear RAGAS data
    echo "🗑️  Menghapus RAGAS evaluation data...\n";
    $deletedRagas = DB::table('ragas_evaluation_tests')->delete();
    echo "   ✅ Berhasil hapus {$deletedRagas} entries\n";
    
    echo "\n";
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ SELESAI - Cache berhasil dihapus                         ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    
    // Verify
    $mongoAfter = DB::connection('mongodb')->table('ai_response_cache')->count();
    $ragasAfter = DB::table('ragas_evaluation_tests')->count();
    
    if ($mongoAfter === 0 && $ragasAfter === 0) {
        echo "✅ Verifikasi: Semua data berhasil dihapus\n\n";
        
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "                 SIAP UNTUK TESTING!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        echo "Langkah selanjutnya:\n\n";
        
        echo "1️⃣  GENERATE LAPORAN BULANAN (5-10 laporan)\n";
        echo "    Login → GKM → Laporan Artefak → Buat Laporan\n";
        echo "    Pilih periode yang berbeda-beda\n\n";
        
        echo "2️⃣  SYNC KE RAGAS\n";
        echo "    php artisan ragas:sync\n\n";
        
        echo "3️⃣  CEK HASIL\n";
        echo "    http://127.0.0.1:8000/gjm/evaluasi/ragas\n";
        echo "    Filter: Laporan Bulanan\n\n";
        
        echo "📊 Target Metrik:\n";
        echo "    • Faithfulness: >80% (sebelumnya: 69.76%)\n";
        echo "    • Hallucination: <15% (sebelumnya: 30.24%)\n";
        echo "    • RAGAS Score: >85% (sebelumnya: 76.88%)\n\n";
        
        echo "💡 Perbaikan yang diterapkan:\n";
        echo "    ✅ Temperature: 0.7 → 0.3\n";
        echo "    ✅ Anti-hallucination system prompt (detailed)\n";
        echo "    ✅ Enhanced user prompt dengan data faktual\n";
        echo "    ✅ Contoh benar/salah + larangan eksplisit\n\n";
        
        echo "📖 Dokumentasi lengkap:\n";
        echo "    - LAPORAN_BULANAN_FIX.md\n";
        echo "    - FAITHFULNESS_FIX_COMPLETE.md\n\n";
        
    } else {
        echo "⚠️  Peringatan: Masih ada data tersisa\n";
        echo "   - MongoDB: {$mongoAfter} entries\n";
        echo "   - RAGAS: {$ragasAfter} entries\n\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}
