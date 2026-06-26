# MongoDB Implementation - Database Analitik Sistem Kuesioner

## Overview

MongoDB digunakan sebagai **database analitik** yang menyimpan hasil pengolahan data dari Apache Spark. Pemilihan MongoDB didasarkan pada kemampuannya menyimpan data semi-terstruktur secara fleksibel menggunakan format dokumen JSON, yang sangat cocok untuk menyimpan hasil analisis yang memiliki struktur bervariasi.

---

## 1. Konfigurasi MongoDB

### 1.1 Connection Setup

**File:** `config/database.php`

```php
'connections' => [
    // ... MySQL, PostgreSQL, etc
    
    'mongodb' => [
        'driver' => 'mongodb',
        'dsn' => env('MONGODB_URI'),
        'database' => env('MONGODB_DATABASE'),
    ],
]
```

### 1.2 Environment Variables

**File:** `.env`

```env
# MongoDB Configuration
MONGODB_URI=mongodb://dangbel:XPOWkcmfJM5IRiww@ac-pvsjuiu-shard-00-00.1k8c4bj.mongodb.net:27017,ac-pvsjuiu-shard-00-01.1k8c4bj.mongodb.net:27017,ac-pvsjuiu-shard-00-02.1k8c4bj.mongodb.net:27017/?ssl=true&replicaSet=atlas-y2p335-shard-0&authSource=admin&appName=Cluster0
MONGODB_DATABASE=gkm_chatbot
```

**Keterangan:**
- `MONGODB_URI`: Connection string ke MongoDB Atlas (cluster 3-node replica set)
- `MONGODB_DATABASE`: Database name untuk menyimpan data analitik

### 1.3 Laravel MongoDB Package

```bash
composer require mongodb/laravel-mongodb
```

**Version:** `^4.0` (Laravel 10+ compatible)

---

## 2. Database Structure

### 2.1 Database: `gkm_chatbot`

MongoDB database utama yang menyimpan semua data analitik dari sistem kuesioner.


### 2.2 Collections Overview

| Collection | Purpose | Source | Size Est. |
|------------|---------|--------|-----------|
| `kuesioner_mongos` | Data kuesioner mentah dari API CIS | Apache Spark Input | 100-1000 docs |
| `hasil_analisis_lengkap` | Hasil analisis detail per pertanyaan | Apache Spark Output | 5K-50K docs |
| `summary_matkul` | Summary agregat per mata kuliah | Apache Spark Output | 50-500 docs |
| `ai_response_cache` | Cache response AI untuk performa | Laravel App | 1K-10K docs |
| `kuesioner_data` | Archive data kuesioner | Archive | Variable |

---

## 3. Collection: `kuesioner_mongos` (Input Data)

### 3.1 Deskripsi

Collection ini menyimpan **data mentah kuesioner** yang diperoleh dari Campus Information System (CIS) melalui API. Data ini merupakan input untuk pemrosesan Apache Spark.

### 3.2 Document Structure

```json
{
    "_id": ObjectId("507f1f77bcf86cd799439011"),
    "kuesioner_id": "12345",
    "judul_kuesioner": "Evaluasi Mata Kuliah Pemrograman Web (AMS/D4TRPL) UTS Semester Ganjil 25/26",
    "kode_mk": "TI44101",
    "periode": "2025/2026",
    "semester": "1",
    "prodi": ["D4 TRPL", "D4 TI"],
    "jenis_kuesioner": "UTS",
    "raw_data": {
        "statistik": {
            "total_responden_aktif": 45,
            "total_responden_pasif": 5,
            "persentase_partisipasi": 90.0,
            "rekapitulasi": [
                {
                    "pertanyaan": "<b>Dosen menguasai materi dengan baik</b>",
                    "total_suara": 45,
                    "rincian_jawaban": [
                        {"jawaban": "1", "jumlah": 30},
                        {"jawaban": "2", "jumlah": 10},
                        {"jawaban": "3", "jumlah": 5},
                        {"jawaban": "4", "jumlah": 0},
                        {"jawaban": "5", "jumlah": 0}
                    ]
                },
                {
                    "pertanyaan": "Dosen menyampaikan materi dengan jelas",
                    "total_suara": 45,
                    "rincian_jawaban": [
                        {"jawaban": "1", "jumlah": 25},
                        {"jawaban": "2", "jumlah": 15},
                        {"jawaban": "3", "jumlah": 5}
                    ]
                }
            ]
        },
        "metadata": {
            "source": "CIS_API",
            "api_version": "2.1",
            "retrieved_at": "2026-06-13T10:00:00Z"
        }
    },
    "is_analyzed": false,
    "analyzed_at": null,
    "created_at": ISODate("2026-06-13T09:30:00Z"),
    "updated_at": ISODate("2026-06-13T09:30:00Z")
}
```

### 3.3 Field Descriptions

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `_id` | ObjectId | MongoDB unique identifier | ObjectId("...") |
| `kuesioner_id` | String | ID kuesioner dari CIS | "12345" |
| `judul_kuesioner` | String | Judul lengkap kuesioner | "Evaluasi Mata Kuliah..." |
| `kode_mk` | String | Kode mata kuliah | "TI44101" |
| `periode` | String | Tahun akademik | "2025/2026" |
| `semester` | String | Semester (1=Ganjil, 2=Genap) | "1" |
| `prodi` | Array | Daftar program studi | ["D4 TRPL", "D4 TI"] |
| `jenis_kuesioner` | String | Jenis evaluasi | "UTS" / "UAS" |
| `raw_data` | Object | Data statistik mentah dari API | {...} |
| `is_analyzed` | Boolean | Flag sudah diproses Spark | false |
| `analyzed_at` | ISODate | Timestamp analisis | null / ISODate |

### 3.4 Indexes

```javascript
// Optimasi query untuk Spark
db.kuesioner_mongos.createIndex({ "is_analyzed": 1 });
db.kuesioner_mongos.createIndex({ "kuesioner_id": 1 }, { unique: true });
db.kuesioner_mongos.createIndex({ "periode": 1, "semester": 1 });
db.kuesioner_mongos.createIndex({ "kode_mk": 1 });
```

### 3.5 Laravel Model

**File:** `app/Models/KuesionerMongo.php`

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class KuesionerMongo extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'kuesioner_mongos';  // Note: collection name
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'kuesioner_id',
        'judul_kuesioner',
        'kode_mk',
        'periode',
        'semester',
        'prodi',
        'jenis_kuesioner',
        'raw_data',
        'is_analyzed',
        'analyzed_at',
        'updated_at',
        'created_at'
    ];
    
    protected $casts = [
        'raw_data' => 'array',
        'prodi' => 'array',
        'is_analyzed' => 'boolean',
        'analyzed_at' => 'datetime',
    ];
    
    public $timestamps = true;
}
```

---

## 4. Collection: `hasil_analisis_lengkap` (Detailed Output)

### 4.1 Deskripsi

Collection ini menyimpan **hasil analisis detail** dari Apache Spark untuk setiap pertanyaan dalam kuesioner. Data ini merupakan hasil transformasi, agregasi, dan perhitungan statistik.

### 4.2 Document Structure

```json
{
    "_id": ObjectId("507f1f77bcf86cd799439012"),
    "kuesioner_id": "12345",
    "kode_mk": "TI44101",
    "judul_kuesioner": "Evaluasi Mata Kuliah Pemrograman Web",
    "dosen_pengajar": "AMS",
    "jenis_kuesioner": "UTS",
    "prodi": "D4 TRPL",
    "tahun": "2025/2026",
    "semester": "1",
    "pertanyaan": "Dosen menguasai materi dengan baik",
    "total_suara": 45,
    "total_jawaban": 45,
    "total_skor": 160,
    "rata_rata": 3.56,
    "persentase_kepuasan": 89.00,
    "kategori_hasil": "Sangat Baik",
    "sentiment": "positive",
    "processed_at": ISODate("2026-06-13T10:30:00Z")
}
```

### 4.3 Field Descriptions

| Field | Type | Description | Range |
|-------|------|-------------|-------|
| `kuesioner_id` | String | ID kuesioner sumber | - |
| `kode_mk` | String | Kode mata kuliah | - |
| `dosen_pengajar` | String | Inisial dosen (dari judul) | e.g. "AMS" |
| `pertanyaan` | String | Teks pertanyaan (clean) | - |
| `total_suara` | Integer | Total responden eligible | 0-100 |
| `total_jawaban` | Integer | Total yang menjawab | 0-100 |
| `total_skor` | Integer | Sum weighted score | 0-400 |
| `rata_rata` | Float | Mean score | 0.00-4.00 |
| `persentase_kepuasan` | Float | Percentage | 0.00-100.00 |
| `kategori_hasil` | String | Classification | Sangat Baik/Baik/Cukup/Kurang |
| `sentiment` | String | Sentiment label | positive/neutral/negative |
| `processed_at` | ISODate | Spark processing time | - |

### 4.4 Indexes

```javascript
// Multi-dimensional query optimization
db.hasil_analisis_lengkap.createIndex({ "kode_mk": 1, "tahun": 1, "semester": 1 });
db.hasil_analisis_lengkap.createIndex({ "dosen_pengajar": 1, "tahun": 1 });
db.hasil_analisis_lengkap.createIndex({ "prodi": 1, "semester": 1, "tahun": 1 });
db.hasil_analisis_lengkap.createIndex({ "sentiment": 1 });
db.hasil_analisis_lengkap.createIndex({ "processed_at": -1 });
```

### 4.5 Laravel Model

**File:** `app/Models/HasilAnalisisMongo.php`

```php
<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class HasilAnalisisMongo extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'hasil_analisis_lengkap';
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;
}
```

---

## 5. Collection: `summary_matkul` (Summary Output)

### 5.1 Deskripsi

Collection ini menyimpan **summary agregat** di level mata kuliah. Data ini digunakan untuk dashboard overview dan perbandingan antar mata kuliah.

### 5.2 Document Structure

```json
{
    "_id": ObjectId("507f1f77bcf86cd799439013"),
    "kode_mk": "TI44101",
    "judul_kuesioner": "Evaluasi Mata Kuliah Pemrograman Web",
    "dosen_pengajar": "AMS",
    "prodi": "D4 TRPL",
    "tahun": "2025/2026",
    "semester": "1",
    "jenis_kuesioner": "UTS",
    "rata_rata_matkul": 3.65,
    "persentase_kepuasan_matkul": 91.25,
    "total_pertanyaan": 15,
    "total_responden": 45,
    "distribusi_sentiment": {
        "positive": 12,
        "neutral": 3,
        "negative": 0
    },
    "created_at": ISODate("2026-06-13T10:30:00Z")
}
```

### 5.3 Indexes

```javascript
db.summary_matkul.createIndex({ "kode_mk": 1, "tahun": 1, "semester": 1 }, { unique: true });
db.summary_matkul.createIndex({ "dosen_pengajar": 1 });
db.summary_matkul.createIndex({ "rata_rata_matkul": -1 });
```

---

## 6. Collection: `ai_response_cache` (AI Cache)

### 6.1 Deskripsi

Collection ini menyimpan **cache response AI** untuk meningkatkan performa sistem dengan menghindari request berulang ke AI provider.

### 6.2 Document Structure

```json
{
    "_id": ObjectId("507f1f77bcf86cd799439014"),
    "cache_key": "a3f2b8c9d1e4f5a6b7c8d9e0f1a2b3c4",
    "prompt_hash": "d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9",
    "original_prompt": "Buat analisis mendalam untuk mata kuliah TI44101",
    "context_metadata": {
        "periode": "2025/2026",
        "semester": "1",
        "kode_mk": "TI44101",
        "prodi_id": 1
    },
    "ai_response": "Berdasarkan data kuesioner...",
    "ai_provider": "openai",
    "ai_model": "gpt-4",
    "usage_count": 5,
    "last_used_at": ISODate("2026-06-13T15:20:00Z"),
    "response_length": 2450,
    "similarity_threshold": 0.85,
    "created_at": ISODate("2026-06-13T10:00:00Z"),
    "updated_at": ISODate("2026-06-13T15:20:00Z")
}
```

### 6.3 Field Descriptions

| Field | Type | Description |
|-------|------|-------------|
| `cache_key` | String | SHA-256 hash dari prompt + context |
| `prompt_hash` | String | Hash dari prompt (tanpa stopwords) |
| `original_prompt` | String | Prompt asli dari user |
| `context_metadata` | Object | Context untuk matching |
| `ai_response` | String | Response dari AI |
| `usage_count` | Integer | Berapa kali cache digunakan |
| `last_used_at` | ISODate | Last cache hit timestamp |
| `similarity_threshold` | Float | Threshold untuk matching (0.85) |

### 6.4 Laravel Model

**File:** `app/Models/AIResponseCacheMongo.php`

```php
class AIResponseCacheMongo extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'ai_response_cache';
    
    // Generate cache key from prompt + context
    public static function generateCacheKey(string $prompt, array $context = []): string
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $prompt)));
        ksort($context);
        $combined = $normalized . '|' . json_encode($context);
        return hash('sha256', $combined);
    }
    
    // Find similar cached response
    public static function findSimilar(string $prompt, array $context = [], float $minSimilarity = 0.85): ?self
    {
        // Implementation: similarity matching algorithm
    }
    
    // Increment usage counter
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }
}
```

---

## 7. Data Flow dalam MongoDB

### 7.1 Write Operations (dari Spark)

```
┌──────────────────────────────────────────────────────────────┐
│  APACHE SPARK WRITE TO MONGODB                               │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  1. READ: kuesioner_mongos (is_analyzed = false)             │
│     ↓                                                         │
│  2. PROCESS: Transform, Aggregate, Calculate Statistics       │
│     ↓                                                         │
│  3. WRITE: hasil_analisis_lengkap (append mode)              │
│     ├─ Write detailed results per question                   │
│     └─ Batch write (100-1000 docs)                           │
│     ↓                                                         │
│  4. WRITE: summary_matkul (append mode)                      │
│     ├─ Write aggregated results per course                   │
│     └─ Batch write (50-500 docs)                             │
│     ↓                                                         │
│  5. UPDATE: kuesioner_mongos                                 │
│     └─ Set is_analyzed = true, analyzed_at = NOW()           │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

**Python Code (Spark):**

```python
# Write hasil analisis
analisis.write \
    .format("mongodb") \
    .mode("append") \
    .option("database", "gkm_chatbot") \
    .option("collection", "hasil_analisis_lengkap") \
    .save()

# Write summary
summary_mk.write \
    .format("mongodb") \
    .mode("append") \
    .option("database", "gkm_chatbot") \
    .option("collection", "summary_matkul") \
    .save()

# Update flags
db.kuesioner_mongos.update_many(
    {"kuesioner_id": {"$in": kuesioner_ids}},
    {"$set": {"is_analyzed": True, "analyzed_at": datetime.utcnow()}}
)
```

### 7.2 Read Operations (dari Laravel)

```
┌──────────────────────────────────────────────────────────────┐
│  LARAVEL READ FROM MONGODB                                    │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  DASHBOARD QUERY                                              │
│    ↓                                                          │
│    HasilAnalisisMongo::where('tahun', '2025/2026')           │
│                      ->where('prodi', 'D4 TRPL')             │
│                      ->get()                                 │
│    ↓                                                          │
│    Display charts, tables, KPIs                              │
│                                                               │
│  REPORT GENERATION                                            │
│    ↓                                                          │
│    summary_matkul aggregation pipeline                       │
│    ↓                                                          │
│    Generate Word/PDF document                                │
│                                                               │
│  AI ASSISTANT                                                 │
│    ↓                                                          │
│    Check AIResponseCacheMongo for cache hit                  │
│    ↓                                                          │
│    If miss: Call AI + Save to cache                          │
│    If hit: Return cached response + increment usage          │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

---

## 8. Keunggulan Penggunaan MongoDB

### 8.1 Schema Flexibility

**Masalah dengan SQL:**
- Struktur data kuesioner bervariasi per mata kuliah
- Jumlah pertanyaan tidak tetap
- Metadata tambahan yang dinamis

**Solusi MongoDB:**
```json
{
    "pertanyaan": [...],  // Array dengan size dinamis
    "metadata": {         // Nested object fleksibel
        "custom_field_1": "value",
        "custom_field_2": [...]
    }
}
```

### 8.2 Performance untuk Nested Data

**Query Nested Structure:**
```javascript
// MongoDB: Native nested query (fast)
db.hasil_analisis_lengkap.find({
    "raw_data.statistik.rekapitulasi.pertanyaan": {
        $regex: "Dosen menguasai"
    }
})

// SQL: Requires JOIN atau JSON extract (slow)
SELECT * FROM hasil_analisis 
WHERE JSON_EXTRACT(raw_data, '$.statistik.rekapitulasi[*].pertanyaan') 
LIKE '%Dosen menguasai%'
```

### 8.3 Integration dengan Spark

**MongoDB Spark Connector:**
- Native read/write dari Spark DataFrame
- Aggregation pushdown untuk optimasi
- Parallel read/write untuk performance

```python
# Direct read dari MongoDB ke Spark DataFrame
df = spark.read.format("mongodb") \
    .option("uri", MONGO_URI) \
    .option("database", "gkm_chatbot") \
    .option("collection", "kuesioner_mongos") \
    .load()

# Aggregation pushdown
pipeline = '[{"$match": {"is_analyzed": false}}]'
df = spark.read.format("mongodb") \
    .option("aggregation.pipeline", pipeline) \
    .load()
```

### 8.4 Hierarchical Data Structure

**Struktur Organisasi Akademik:**
```
Institusi
  └─ Fakultas Vokasi
      └─ Program Studi (D4 TRPL, D4 TI, D3 NM)
          └─ Mata Kuliah (Tingkat 1-4)
              └─ Kelas
                  └─ Kuesioner
                      └─ Pertanyaan
```

**MongoDB Document:**
```json
{
    "prodi": "D4 TRPL",
    "tingkat": 3,
    "kuesioner": [
        {
            "kode_mk": "TI43101",
            "pertanyaan": [...]
        }
    ]
}
```

### 8.5 Comparison Table

| Aspek | MongoDB | MySQL (JSON) | PostgreSQL (JSONB) |
|-------|---------|--------------|---------------------|
| Schema Flexibility | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| Nested Query Performance | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ |
| Spark Integration | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐ |
| Write Performance | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| Horizontal Scaling | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐ |

---

## 9. Performance Optimization

### 9.1 Indexing Strategy

```javascript
// Compound indexes untuk query kompleks
db.hasil_analisis_lengkap.createIndex({
    "prodi": 1,
    "semester": 1,
    "tahun": 1,
    "rata_rata": -1
});

// Text index untuk full-text search
db.hasil_analisis_lengkap.createIndex({
    "pertanyaan": "text",
    "judul_kuesioner": "text"
});
```

### 9.2 Aggregation Pipeline

**Efficient Aggregation:**
```javascript
db.hasil_analisis_lengkap.aggregate([
    // Stage 1: Filter early
    { $match: { 
        "tahun": "2025/2026",
        "semester": "1"
    }},
    
    // Stage 2: Group by mata kuliah
    { $group: {
        _id: "$kode_mk",
        rata_rata: { $avg: "$rata_rata" },
        total_responden: { $sum: "$total_jawaban" },
        sentiment_counts: {
            $push: "$sentiment"
        }
    }},
    
    // Stage 3: Sort by rata-rata desc
    { $sort: { rata_rata: -1 }},
    
    // Stage 4: Limit top 10
    { $limit: 10 }
]);
```

### 9.3 Caching Strategy

**Application-Level Cache:**
```php
// Laravel query with cache
$results = Cache::remember('dashboard_stats_2025_1', 3600, function() {
    return HasilAnalisisMongo::raw(function($collection) {
        return $collection->aggregate([...])->toArray();
    });
});
```

**MongoDB-Level Cache:**
- MongoDB automatically caches frequently accessed documents in RAM
- Working set should fit in RAM for optimal performance

---

## 10. Data Lifecycle Management

### 10.1 Data Retention Policy

```javascript
// Archive data lebih dari 2 tahun
db.hasil_analisis_lengkap.aggregate([
    { $match: {
        "processed_at": {
            $lt: new Date(new Date().setFullYear(new Date().getFullYear() - 2))
        }
    }},
    { $out: "hasil_analisis_archive" }
]);

// Delete dari collection aktif
db.hasil_analisis_lengkap.deleteMany({
    "processed_at": {
        $lt: new Date(new Date().setFullYear(new Date().getFullYear() - 2))
    }
});
```

### 10.2 Backup Strategy

```bash
# Daily backup
mongodump --uri="$MONGODB_URI" \
          --db=gkm_chatbot \
          --out=/backup/$(date +%Y%m%d)

# Restore
mongorestore --uri="$MONGODB_URI" \
             /backup/20260613/gkm_chatbot
```

---

## 11. Monitoring & Maintenance

### 11.1 Collection Statistics

```javascript
// Check collection size
db.hasil_analisis_lengkap.stats();

// Output:
{
    "count": 15234,
    "size": 45678912,
    "avgObjSize": 2998,
    "storageSize": 12345678,
    "indexes": 5,
    "totalIndexSize": 1234567
}
```

### 11.2 Query Performance

```javascript
// Explain query execution
db.hasil_analisis_lengkap.find({
    "prodi": "D4 TRPL",
    "tahun": "2025/2026"
}).explain("executionStats");

// Check for slow queries
db.system.profile.find().sort({millis: -1}).limit(10);
```

### 11.3 Laravel Monitoring

```php
// app/Services/MongoDBMonitorService.php
public function getCollectionStats(): array
{
    $connection = DB::connection('mongodb');
    
    $stats = [
        'kuesioner_mongos' => $connection->command([
            'collStats' => 'kuesioner_mongos'
        ]),
        'hasil_analisis_lengkap' => $connection->command([
            'collStats' => 'hasil_analisis_lengkap'
        ]),
        'summary_matkul' => $connection->command([
            'collStats' => 'summary_matkul'
        ]),
    ];
    
    return $stats;
}
```

---

## 12. Security Best Practices

### 12.1 Connection Security

```env
# Use SSL/TLS
MONGODB_URI=mongodb://user:pass@cluster.mongodb.net/?ssl=true&authSource=admin

# Restrict IP whitelist
# MongoDB Atlas → Network Access → Add IP Address
```

### 12.2 Authentication

```javascript
// Create read-only user untuk reporting
db.createUser({
    user: "report_reader",
    pwd: "secure_password",
    roles: [
        { role: "read", db: "gkm_chatbot" }
    ]
});

// Create write user untuk Spark
db.createUser({
    user: "spark_writer",
    pwd: "secure_password",
    roles: [
        { role: "readWrite", db: "gkm_chatbot" }
    ]
});
```

### 12.3 Data Privacy

```php
// Mask sensitive data
public function toArray()
{
    return [
        'kode_mk' => $this->kode_mk,
        'rata_rata' => $this->rata_rata,
        // Don't expose raw response data
    ];
}
```

---

## 13. Kesimpulan

### 13.1 Summary

MongoDB dipilih sebagai database analitik karena:

✅ **Flexibility**: Schema-less untuk data bervariasi  
✅ **Performance**: Native nested query dan indexing  
✅ **Integration**: Seamless dengan Apache Spark  
✅ **Scalability**: Horizontal scaling untuk growth  
✅ **Developer Experience**: Intuitive document model

### 13.2 Key Metrics

| Metric | Value |
|--------|-------|
| Total Collections | 5 |
| Avg Document Size | 2-3 KB |
| Query Response Time | < 100ms |
| Spark Write Throughput | 100-200 docs/sec |
| Storage Growth | ~1 GB/semester |

### 13.3 Future Enhancements

- **Time-Series Collection**: Untuk data longitudinal
- **Change Streams**: Real-time notification
- **Atlas Search**: Full-text search dengan relevance
- **Analytics Node**: Dedicated node untuk reporting queries

---

## 14. Referensi

### 14.1 Documentation Links

- MongoDB Manual: https://docs.mongodb.com/
- MongoDB Spark Connector: https://docs.mongodb.com/spark-connector/
- Laravel MongoDB: https://github.com/mongodb/laravel-mongodb

### 14.2 Related Files

| File | Description |
|------|-------------|
| `config/database.php` | MongoDB connection config |
| `app/Models/*Mongo.php` | MongoDB Eloquent models |
| `spark/spark_kuesioner.py` | Spark write operations |
| `app/Services/LaporanKuesioneService.php` | MongoDB query service |

---

**Dokumentasi dibuat:** 13 Juni 2026  
**Versi:** 1.0  
**Last Updated:** 13 Juni 2026
