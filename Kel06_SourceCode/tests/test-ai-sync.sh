#!/bin/bash

# 🧪 Testing Script untuk AI Assistant Sync System
# Untuk verifikasi bahwa sistem synchronisasi berjalan dengan benar

echo "🧪 Testing AI Assistant Synchronization System"
echo "=============================================="
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Check database migration
echo "${YELLOW}[Test 1]${NC} Checking database columns..."
php artisan tinker << 'EOF'
use App\Models\LaporanGKM;
$columns = \DB::getSchemaBuilder()->getColumnListing('laporan_gkm');
$requiredColumns = ['ai_preview_draft', 'ai_sections', 'ai_preview_updated_at', 'ai_preview_used_for_generation'];
$missing = array_diff($requiredColumns, $columns);

if (empty($missing)) {
    echo "✅ All required columns exist\n";
} else {
    echo "❌ Missing columns: " . implode(", ", $missing) . "\n";
}
EOF
echo ""

# Test 2: Check model fillable
echo "${YELLOW}[Test 2]${NC} Checking model fillable attributes..."
php artisan tinker << 'EOF'
use App\Models\LaporanGKM;
$model = new LaporanGKM;
$required = ['ai_preview_draft', 'ai_sections', 'ai_preview_updated_at', 'ai_preview_used_for_generation'];
$fillable = $model->getFillable();

foreach ($required as $attr) {
    if (in_array($attr, $fillable)) {
        echo "✅ $attr is fillable\n";
    } else {
        echo "❌ $attr is NOT fillable\n";
    }
}
EOF
echo ""

# Test 3: Check route
echo "${YELLOW}[Test 3]${NC} Checking routes..."
php artisan route:list | grep "laporan-artefak" | grep "api/get"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ API get route exists${NC}"
else
    echo -e "${RED}❌ API get route not found${NC}"
fi
echo ""

# Test 4: Check casts
echo "${YELLOW}[Test 4]${NC} Checking model casts..."
php artisan tinker << 'EOF'
use App\Models\LaporanGKM;
$model = new LaporanGKM;
$casts = $model->getCasts();

echo (isset($casts['ai_sections']) && $casts['ai_sections'] === 'array' ? "✅" : "❌") . " ai_sections cast\n";
echo (isset($casts['ai_preview_updated_at']) && $casts['ai_preview_updated_at'] === 'datetime' ? "✅" : "❌") . " ai_preview_updated_at cast\n";
echo (isset($casts['ai_preview_used_for_generation']) && $casts['ai_preview_used_for_generation'] === 'boolean' ? "✅" : "❌") . " ai_preview_used_for_generation cast\n";
EOF
echo ""

# Test 5: Create test laporan and verify sync
echo "${YELLOW}[Test 5]${NC} Testing full sync flow..."
php artisan tinker << 'EOF'
use App\Models\LaporanGKM, Carbon\Carbon;

// Create test laporan
$laporan = LaporanGKM::create([
    'jenis_laporan' => 'bulanan',
    'periode' => '2026-06',
    'user_id' => 1,
    'status' => 'pending',
]);

echo "✅ Created test laporan ID: {$laporan->id}\n";

// Test update with AI data
$testData = "## BAB 1 Pendahuluan\nTest content for synchronization testing\n## BAB 2 Hasil\nMore test content";
$testSections = [
    ['title' => 'BAB 1 Pendahuluan', 'content' => 'Test content'],
    ['title' => 'BAB 2 Hasil', 'content' => 'More test content']
];

$laporan->update([
    'ai_preview_draft' => $testData,
    'ai_sections' => $testSections,
    'ai_preview_updated_at' => now(),
    'ai_preview_used_for_generation' => false,
    'status' => 'preview_ready',
]);

echo "✅ Updated AI preview data\n";

// Verify data saved
$refreshed = LaporanGKM::find($laporan->id);

echo "✅ Verified ai_preview_draft saved: " . (strlen($refreshed->ai_preview_draft) > 0 ? "Yes" : "No") . "\n";
echo "✅ Verified ai_sections saved: " . (is_array($refreshed->ai_sections) && count($refreshed->ai_sections) > 0 ? "Yes" : "No") . "\n";
echo "✅ Verified ai_preview_updated_at: " . ($refreshed->ai_preview_updated_at ? "Yes" : "No") . "\n";
echo "✅ Verified status: " . $refreshed->status . "\n";

// Cleanup
$laporan->delete();
echo "✅ Cleaned up test data\n";
EOF
echo ""

echo -e "${GREEN}=============================================="
echo "✅ All tests completed!${NC}"
echo ""
echo "If all tests passed with ✅, the sync system is working correctly."
echo ""
