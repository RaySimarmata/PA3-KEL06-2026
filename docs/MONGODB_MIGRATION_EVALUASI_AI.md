# Migrasi Evaluasi AI Assistant ke MongoDB

## Overview

Halaman "Evaluasi AI Assistant" telah diupdate untuk menggunakan **MongoDB** sebagai sumber data utama untuk cache AI responses. Sebelumnya, sistem menggunakan MySQL dengan query builder `DB::table()`, sekarang menggunakan model Eloquent `AIResponseCacheMongo` yang terhubung ke MongoDB.

## Konfigurasi MongoDB

### Environment Variables (.env)

```env
MONGODB_URI="mongodb://dangbel:XPOWkcmfJM5IRiww@ac-pvsjuiu-shard-00-00.1k8c4bj.mongodb.net:27017,ac-pvsjuiu-shard-00-01.1k8c4bj.mongodb.net:27017,ac-pvsjuiu-shard-00-02.1k8c4bj.mongodb.net:27017/gkm_chatbot?ssl=true&replicaSet=atlas-y2p335-shard-0&authSource=admin&appName=Cluster0"
MONGODB_DATABASE=gkm_chatbot
MONGODB_OPTIONS="serverSelectionTimeoutMS=5000&connectTimeoutMS=10000&socketTimeoutMS=10000"
```

### Database Configuration (config/database.php)

```php
'mongodb' => [
    'driver' => 'mongodb',
    'dsn' => env('MONGODB_URI'),
    'database' => env('MONGODB_DATABASE'),
],
```

## Model: AIResponseCacheMongo

**Location:** `app/Models/AIResponseCacheMongo.php`

Model ini menggunakan:
- **Connection:** `mongodb`
- **Collection:** `ai_response_cache`
- **Package:** `mongodb/laravel-mongodb` v5.7

### Key Features:

1. **Cache Key Generation** - Generate unique cache keys dari prompt dan context
2. **Prompt Hash** - Hash prompt dengan normalisasi dan stop word removal
3. **Similarity Calculation** - Hitung similarity antara prompts (Jaccard similarity)
4. **Find Similar** - Cari cached response yang mirip dengan threshold (default 0.85)
5. **Usage Tracking** - Track penggunaan cache dengan increment counter
6. **Statistics** - Dapatkan statistik cache usage

## Perubahan pada Controller

**File:** `app/Http/Controllers/GJM/ModelEvaluationController.php`

### Methods yang Diperbarui:

#### 1. `getOverviewStats()`
✅ Sudah menggunakan `AIResponseCacheMongo::query()`

**Query Pattern:**
```php
$query = AIResponseCacheMongo::query();
if ($dateFilter) {
    $query->where('created_at', '>=', $dateFilter);
}
if ($feature !== 'all') {
    $query->where('context_metadata.feature', $feature);
}
$totalRequests = $query->count();
```

#### 2. `getPerformanceMetrics()`
✅ Diperbarui dari `DB::table()` ke `AIResponseCacheMongo::query()`

**Sebelum:**
```php
$requests = DB::table('ai_response_cache')
    ->whereJsonContains('context_metadata->feature', $feat)
    ->get();
```

**Sesudah:**
```php
$query = AIResponseCacheMongo::query();
if ($dateFilter) {
    $query->where('created_at', '>=', $dateFilter);
}
$requests = $query->where('context_metadata.feature', $feat)->get();
```

#### 3. `getUsageStats()`
✅ Diperbarui untuk manual grouping (MongoDB aggregation)

**Sebelum:**
```php
$usage = DB::table('ai_response_cache')
    ->select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('COUNT(*) as requests'),
        DB::raw('SUM(usage_count) as total_usage')
    )
    ->groupBy('date')
    ->get();
```

**Sesudah:**
```php
$data = $query->orderBy('created_at', 'asc')->get();

$usage = [];
foreach ($data as $entry) {
    $date = $entry->created_at->format('Y-m-d');
    
    if (!isset($usage[$date])) {
        $usage[$date] = [
            'date' => $date,
            'requests' => 0,
            'total_usage' => 0,
        ];
    }
    
    $usage[$date]['requests']++;
    $usage[$date]['total_usage'] += $entry->usage_count ?? 1;
}

return array_values($usage);
```

#### 4. `getCacheStats()`
✅ Diperbarui ke `AIResponseCacheMongo::query()`

#### 5. `getTimelineData()`
✅ Sudah menggunakan `AIResponseCacheMongo::query()` dengan manual grouping

#### 6. `getFeatureStatus()`
✅ Sudah menggunakan `AIResponseCacheMongo::query()`

#### 7. `getHyperparameterTuning()`
✅ Diperbarui ke `AIResponseCacheMongo::query()`

#### 8. `getBeforeAfterComparison()`
✅ Diperbarui ke `AIResponseCacheMongo::query()`

### Methods yang TIDAK Berubah:

- `getAmbiguityMetrics()` - Sudah menggunakan `AIEvaluationTest` model (MySQL)
- `getRAGASMetrics()` - Sudah menggunakan `AIEvaluationTest` model (MySQL)

> **Note:** Metrics untuk Ambiguity dan RAGAS tetap menggunakan MySQL karena data tersebut disimpan di tabel `ai_evaluation_tests` dan `laporan_gjm`.

## Query Pattern Differences

### MySQL (Lama)

```php
// WhereJsonContains
DB::table('ai_response_cache')
    ->whereJsonContains('context_metadata->feature', 'triwulan')
    ->get();
```

### MongoDB (Baru)

```php
// Dot notation
AIResponseCacheMongo::where('context_metadata.feature', 'triwulan')->get();
```

## Fallback Mechanism

Sistem memiliki fallback mechanism ke `laporan_gjm` table (MySQL) jika tidak ada data di MongoDB:

```php
// If no cache data, get data from laporan_gjm
if ($totalRequests === 0) {
    return $this->getOverviewStatsFromLaporanGJM($period, $feature);
}
```

## Testing

### Script Test
Gunakan script test untuk memverifikasi koneksi MongoDB:

```bash
php test_mongodb_connection.php
```

**Output yang diharapkan:**
```
Test 1: Checking MongoDB Configuration
✓ MongoDB URI: Configured
✓ MongoDB Database: gkm_chatbot

Test 2: Testing MongoDB Connection
✓ MongoDB connection successful!

Test 3: Counting Documents in ai_response_cache Collection
✓ Total documents in ai_response_cache: X

Test 4: Counting Documents by Feature
  triwulan: X documents
  semester: X documents
  vmts: X documents
```

### Manual Testing

1. **Access halaman Evaluasi AI Assistant:**
   ```
   /gjm/model-evaluation
   ```

2. **Test filter periode:**
   - 7 Days
   - 30 Days
   - 90 Days
   - All Time

3. **Test filter fitur:**
   - All
   - Triwulan
   - Semester
   - VMTS

4. **Verifikasi data source:**
   Di response JSON, check field `data_source`:
   - `ai_response_cache_mongodb` - Data dari MongoDB ✅
   - `laporan_gjm` - Fallback ke MySQL (jika MongoDB kosong)

## Troubleshooting

### Error: "Class 'MongoDB\Laravel\Eloquent\Model' not found"

**Solution:**
```bash
composer require mongodb/laravel-mongodb
```

### Error: "Connection refused" atau "Connection timeout"

**Check:**
1. MongoDB URI di `.env` benar
2. Network bisa akses MongoDB cluster
3. IP whitelist di MongoDB Atlas (jika menggunakan Atlas)

**Test connection:**
```bash
php artisan tinker
>>> DB::connection('mongodb')->getPdo();
```

### Error: "Collection not found"

MongoDB akan otomatis membuat collection `ai_response_cache` saat data pertama disimpan. Jika belum ada data, halaman akan menampilkan fallback dari `laporan_gjm`.

## Data Source Priority

1. **Primary:** MongoDB (`ai_response_cache` collection)
   - AI response cache
   - Usage statistics
   - Performance metrics

2. **Fallback:** MySQL (`laporan_gjm` table)
   - Jika MongoDB belum memiliki data
   - Untuk historical data sebelum migrasi

3. **Evaluation Metrics:** MySQL (`ai_evaluation_tests` table)
   - Ambiguity scores
   - RAGAS metrics

## Benefits of MongoDB

1. **Performance** - Faster query untuk nested JSON data
2. **Scalability** - Better untuk large dataset
3. **Flexibility** - Schema-less untuk AI response metadata
4. **Native JSON** - No need for JSON extraction functions
5. **Cloud Native** - Sudah di MongoDB Atlas dengan replica set

## Migration Checklist

- [x] Install `mongodb/laravel-mongodb` package
- [x] Configure MongoDB connection di `config/database.php`
- [x] Add MongoDB credentials di `.env`
- [x] Create `AIResponseCacheMongo` model
- [x] Update `ModelEvaluationController` methods:
  - [x] `getOverviewStats()`
  - [x] `getPerformanceMetrics()`
  - [x] `getUsageStats()`
  - [x] `getCacheStats()`
  - [x] `getTimelineData()`
  - [x] `getFeatureStatus()`
  - [x] `getHyperparameterTuning()`
  - [x] `getBeforeAfterComparison()`
- [x] Create test script (`test_mongodb_connection.php`)
- [x] Create documentation (this file)
- [ ] Test di environment staging
- [ ] Verify data accuracy
- [ ] Deploy to production

## Next Steps

1. **Populate MongoDB** - Ensure AI services write to MongoDB collection
2. **Data Migration** (Optional) - Migrate existing MySQL cache to MongoDB
3. **Monitoring** - Monitor MongoDB query performance
4. **Optimization** - Add indexes if needed:
   ```javascript
   db.ai_response_cache.createIndex({ "context_metadata.feature": 1 })
   db.ai_response_cache.createIndex({ "created_at": -1 })
   db.ai_response_cache.createIndex({ "cache_key": 1 })
   ```

## Support

Jika ada masalah atau pertanyaan, hubungi:
- Backend Team
- Database Team
- Check MongoDB Atlas dashboard untuk monitoring
