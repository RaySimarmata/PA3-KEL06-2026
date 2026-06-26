# 📊 AI Evaluation Tracking - User Guide

## 🎯 Apa itu AI Evaluation Tracking?

AI Evaluation Tracking adalah sistem untuk merekam dan menganalisis performa AI di aplikasi Anda. Setiap kali AI generate laporan, sistem akan:

1. ✅ Menyimpan request & response untuk analisis
2. ✅ Tracking metrics (response time, length, quality)
3. ✅ Memungkinkan evaluasi model over time
4. ✅ Generate insights untuk model improvement

## 🔧 Apa yang Sudah Diimplementasikan?

### Sebelum Update (LAMA)
```
User → Generate Laporan → AI Service → ai_response_cache (MongoDB only)
                                    ❌ TIDAK masuk ke evaluation tables
```

### Setelah Update (BARU)
```
User → Generate Laporan → AI Service → ai_response_cache (MongoDB)
                                    → ✅ ai_evaluation_tests (SQL)
                                    → ✅ Bisa generate ai_evaluation_results
```

## 📋 Tabel yang Terlibat

### 1. `ai_evaluation_tests`
Menyimpan setiap individual AI test/response:
- Input query (prompt user)
- Actual output (response AI)
- Feature (triwulan/semester/vmts/artefak)
- Status (pending/evaluated)
- Metrics (response time, length, etc.)

### 2. `ai_evaluation_results`
Menyimpan agregat evaluation results:
- Average metrics across multiple tests
- Hallucination count
- Quality scores
- Recommendations
- Improvements analysis

## 🚀 Cara Menggunakan

### Step 1: Generate Laporan (Otomatis Tracked)

Setiap kali Anda generate laporan menggunakan AI Assistant:

**Laporan Triwulan:**
```
1. Buka: GJM → Buat Laporan → Laporan Triwulan
2. Klik "AI Assistant"
3. Ketik prompt → Klik "Generate"
→ Data otomatis masuk ke ai_evaluation_tests!
```

**Laporan Semester:**
```
1. Buka: GJM → Buat Laporan → Laporan Semester
2. Gunakan AI Assistant
→ Data otomatis masuk ke ai_evaluation_tests!
```

**Laporan VMTS:**
```
1. Buka: GJM → Buat Laporan → VMTS
2. Gunakan AI Assistant
→ Data otomatis masuk ke ai_evaluation_tests!
```

**Laporan Artefak:**
```
1. Buka: GKM → Laporan Artefak → Create
2. Gunakan AI Assistant (Manual atau Auto-generate)
→ Data otomatis masuk ke ai_evaluation_tests!
```

### Step 2: Verifikasi Data Masuk

Jalankan test script:

```bash
php test_evaluation_tracking.php
```

Output yang diharapkan:
```
✓ Total AI Evaluation Tests: 5
Tests by Feature:
  - triwulan: 2 tests
  - semester: 1 tests
  - vmts: 1 tests
  - artefak: 1 tests
```

### Step 3: Lihat di Model Evaluation Dashboard

```
1. Login sebagai GJM
2. Buka: GJM → Model Evaluation
3. Lihat:
   - Hyperparameter Tuning (sekarang pakai data real!)
   - Performance metrics
   - Quality trends over time
```

## 📊 Memahami Data di Model Evaluation

### Hyperparameter Tuning & Optimization

**Data Sebelumnya (Fallback):**
- Jika `ai_evaluation_tests` kosong → pakai data dari `laporan_gjm`
- Limited metrics
- Tidak bisa track improvement accurately

**Data Sekarang (Real):**
- Data dari `ai_response_cache` (MongoDB)
- Membagi data: 40% awal (before) vs 40% akhir (after)
- Metrics yang tracked:
  - `avg_response_time` - Waktu respons AI
  - `avg_response_length` - Panjang output
  - `cache_hit_rate` - Berapa % pakai cache
  - `quality_score` - Skor kualitas (calculated)
  - `usage_count` - Berapa kali response dipakai ulang

**Improvement Calculation:**
```
Improvement = (After - Before) / Before * 100%

Example:
- Response time: 2.5s → 1.8s = 28% faster ✓
- Cache hit rate: 20% → 45% = 125% increase ✓
- Quality score: 72 → 85 = 18% better ✓
```

## 🔍 Query Database untuk Analisis Manual

### Lihat semua tests
```sql
SELECT 
    id,
    test_name,
    feature,
    status,
    created_at,
    LENGTH(input_query) as query_length,
    LENGTH(actual_output) as response_length
FROM ai_evaluation_tests
ORDER BY created_at DESC
LIMIT 20;
```

### Lihat tests by feature
```sql
SELECT 
    feature,
    COUNT(*) as total_tests,
    AVG(LENGTH(actual_output)) as avg_response_length
FROM ai_evaluation_tests
GROUP BY feature;
```

### Lihat evaluation results
```sql
SELECT 
    evaluation_name,
    feature,
    total_ai_tests,
    avg_relevance,
    avg_accuracy,
    hallucination_count,
    evaluation_date
FROM ai_evaluation_results
ORDER BY evaluation_date DESC;
```

## 🎯 Advanced: Manual Evaluation (Opsional)

Jika Anda ingin meningkatkan quality tracking, Anda bisa evaluate tests secara manual:

### Via Tinker (Command Line)
```php
php artisan tinker

// Get a test
$test = App\Models\AIEvaluationTest::find(1);

// Evaluate it
$evaluationService = app(\App\Services\AIEvaluationService::class);
$evaluationService->evaluateAIResponse(
    testId: $test->id,
    relevance: 4,        // 1-5 scale
    completeness: 5,     // 1-5 scale
    clarity: 4,          // 1-5 scale
    accuracy: 5,         // 1-5 scale
    hasHallucination: false,
    ambiguityScore: 2,   // 1-5 (1=clear, 5=very ambiguous)
    ambiguousParts: null,
    notes: 'Good response',
    evaluatedBy: 'Admin'
);
```

### Generate Aggregate Results
```php
php artisan tinker

// Generate evaluation result for all features
$evaluationService = app(\App\Services\AIEvaluationService::class);
$result = $evaluationService->generateEvaluationResult(
    name: 'Evaluation ' . date('Y-m-d'),
    feature: 'all' // or 'triwulan', 'semester', 'vmts', 'artefak'
);

// View the result
echo "Summary: " . $result->summary . "\n";
echo "Strengths: " . $result->strengths . "\n";
echo "Limitations: " . $result->limitations . "\n";
echo "Recommendations: " . $result->recommendations . "\n";
```

## 🐛 Troubleshooting

### Problem: Data tidak masuk ke `ai_evaluation_tests`

**Check 1: Verify table exists**
```sql
SHOW TABLES LIKE 'ai_evaluation_tests';
```

**Check 2: Run test script**
```bash
php test_evaluation_tracking.php
```

**Check 3: Check Laravel logs**
```bash
tail -f storage/logs/laravel.log
```

Look for:
- `"AI Evaluation test created"` ✓ Success
- `"Failed to create AI evaluation test"` ❌ Error

**Check 4: Verify code is in place**
```bash
grep -r "createAIResponseTest" app/Http/Controllers/
```

Should show files:
- LaporanTriwulanController.php
- LaporanSemesterController.php
- LaporanVMTSController.php
- PromptVMTSController.php
- LaporanArtefakController.php

### Problem: Model Evaluation masih pakai data lama

Pastikan:
1. Minimal 2 entries di `ai_response_cache`
2. Entries punya `response_time` data
3. Feature filter sesuai (triwulan/semester/vmts)

### Problem: Error saat save evaluation test

Check:
1. Database connection
2. Table structure (run migrations)
3. Permissions

## 📈 Best Practices

### 1. Regular Monitoring
- Check test data setiap minggu
- Monitor trends di Model Evaluation
- Identify issues early

### 2. Periodic Evaluation
- Manual evaluate sample tests (10-20% random)
- Generate evaluation results monthly
- Compare before/after improvements

### 3. Data Cleanup
```php
// Remove old pending tests (> 30 days)
AIEvaluationTest::where('status', 'pending')
    ->where('created_at', '<', now()->subDays(30))
    ->delete();
```

### 4. Export for Analysis
```bash
# Export to CSV
php artisan tinker --execute="
\$tests = App\Models\AIEvaluationTest::all();
\$fp = fopen('evaluation_tests_export.csv', 'w');
fputcsv(\$fp, ['ID', 'Feature', 'Query Length', 'Response Length', 'Status', 'Date']);
foreach (\$tests as \$t) {
    fputcsv(\$fp, [\$t->id, \$t->feature, strlen(\$t->input_query), strlen(\$t->actual_output), \$t->status, \$t->created_at]);
}
fclose(\$fp);
echo 'Exported to evaluation_tests_export.csv';
"
```

## 📚 Related Documentation

- `CHANGELOG_AI_EVALUATION_TRACKING.md` - Technical changes
- `test_evaluation_tracking.php` - Testing script
- `app/Services/AIEvaluationService.php` - Service code
- `app/Models/AIEvaluationTest.php` - Model definition

## 👥 Contact

Jika ada pertanyaan atau issues:
1. Check logs: `storage/logs/laravel.log`
2. Run test script: `php test_evaluation_tracking.php`
3. Contact developer

---

**Last Updated:** 3 Juni 2026  
**Version:** 1.0  
**Author:** Kiro AI Assistant
