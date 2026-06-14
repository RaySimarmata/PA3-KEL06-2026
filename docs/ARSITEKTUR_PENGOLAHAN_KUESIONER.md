# Arsitektur Pengolahan Data Kuesioner

## Overview

Pengolahan data kuesioner pada sistem menggunakan **Apache Spark** sebagai engine pemrosesan data untuk mendukung analisis data kuesioner mahasiswa secara efisien dan terukur. Data kuesioner yang diperoleh dari API Campus Information System (CIS) diproses melalui pipeline yang terdiri dari beberapa komponen yang bekerja secara terintegrasi.

## Pipeline Pengolahan Data

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                     │
│  Data Source (CIS API) → Apache Spark → MongoDB → Dashboard Web    │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

Pipeline pengolahan data kuesioner secara keseluruhan adalah:

**Data Source → Apache Spark → MongoDB → Dashboard Analitik**

Pada pipeline tersebut, Apache Spark bertugas melakukan proses **ingestion**, **cleaning**, **transformasi**, dan **agregasi** data kuesioner sebelum hasil analisis disimpan ke MongoDB. Data yang telah diolah kemudian digunakan oleh dashboard analitik untuk menampilkan informasi, visualisasi, dan insight yang mendukung proses monitoring serta evaluasi kualitas pembelajaran.

---

## Komponen Sistem

### 1. Data Source

Data Source merupakan tahap awal dalam pipeline pengolahan data kuesioner, yaitu proses pengambilan data mentah yang akan dianalisis oleh sistem.

#### Sumber Data

Data diperoleh dari **Campus Information System (CIS)** Institut Teknologi Del melalui API yang disediakan oleh sistem akademik kampus.

#### Jenis Data yang Diambil

1. **Data Kuesioner Mahasiswa**
   - Berisi jawaban dan penilaian mahasiswa terhadap proses pembelajaran
   - Dikumpulkan pada setiap akhir semester (UTS/UAS)
   - Format: JSON dari API atau Excel upload manual

2. **Data Dosen**
   - Informasi dosen pengampu mata kuliah
   - Digunakan untuk analisis performa per dosen
   - Tabel: `dosenn` (MySQL)

3. **Data Matakuliah**
   - Informasi mata kuliah yang dievaluasi
   - Kode mata kuliah, nama, tingkat
   - Tabel: `matakuliah` (MySQL)

4. **Data Program Studi**
   - Informasi program studi
   - Digunakan untuk analisis per prodi
   - Tabel: `prodi` (MySQL)

5. **Data Periode Akademik**
   - Informasi semester dan tahun akademik
   - Digunakan untuk analisis tren temporal
   - Tabel: `periode_akademik` (MySQL)

#### Mekanisme Pengambilan Data

**A. Dari API CIS:**
```php
// MonitoringKuesioneController.php
$apiData = $this->apiService->getRekapKuesioner($ta, $kodeMk);
```

**B. Upload Manual:**
```php
// MonitoringKuesioneController.php -> store()
$file = $request->file('file_excel');
$filePath = $file->storeAs('kuesioner', $fileName, 'public');
```

**C. Penyimpanan ke Staging:**
```php
// Disimpan ke kuesioner_uploads (MySQL) dengan status 'uploaded'
KuesioneUpload::create([
    'nama_file' => $request->nama_file,
    'kode_matakuliah' => $request->kode_matakuliah,
    'periode' => $request->periode,
    'status' => 'uploaded'
]);
```

---

### 2. Apache Spark

Apache Spark digunakan sebagai **engine pemrosesan data** yang bertanggung jawab untuk melakukan pengolahan, transformasi, dan analisis data kuesioner mahasiswa.

#### Mengapa Apache Spark?

Pemilihan Apache Spark didasarkan pada:
- Kemampuan memproses data secara **paralel** dan **terdistribusi**
- Mampu menangani proses analisis data dalam jumlah besar dengan efisien
- Lebih cepat dibandingkan pendekatan query konvensional
- Mendukung transformasi data kompleks dengan API yang ekspresif

#### Lokasi Script

```
spark/spark_kuesioner.py
```

#### Konfigurasi Spark

```python
spark = SparkSession.builder \
    .appName("AnalisisKuesionerLengkap") \
    .config("spark.jars.packages", "org.mongodb.spark:mongo-spark-connector_2.12:10.3.0") \
    .config("spark.local.dir", temp_dir) \
    .config("spark.mongodb.read.connection.uri", MONGO_URI) \
    .config("spark.mongodb.write.connection.uri", MONGO_URI) \
    .getOrCreate()
```

#### Trigger Eksekusi Spark

**A. Manual dari Dashboard:**
```php
// DashboardController.php -> jalankanAnalisisSpark()
public function jalankanAnalisisSpark()
{
    $spark = 'C:\spark\bin\spark-submit.cmd';
    $python = 'C:\Python312\python.exe';
    $pythonFile = base_path('spark/spark_kuesioner.py');
    
    $command = "\"{$spark}\" " .
        "--conf spark.pyspark.python=\"{$python}\" " .
        "--packages org.mongodb.spark:mongo-spark-connector_2.12:10.3.0 " .
        "\"{$pythonFile}\" 2>&1";
    
    exec($command, $output, $returnCode);
}
```

**B. Button di Dashboard:**
```blade
<form method="POST" action="{{ route('gjm.dashboard.run-spark') }}">
    @csrf
    <button type="submit">
        <i class="bi bi-graph-up"></i> Jalankan Analisis Spark
    </button>
</form>
```

#### Fungsi Utama Apache Spark

##### 1. Data Ingestion

**Membaca Data dari MongoDB:**
```python
df = spark.read \
    .format("mongodb") \
    .option("database", "gkm_chatbot") \
    .option("collection", "kuesioner_mongos") \
    .option("aggregation.pipeline", pipeline) \
    .load()
```

**Pipeline Filter:**
```python
pipeline = """
[
    {
        "$match": {
            "is_analyzed": false
        }
    }
]
"""
```

##### 2. Data Cleaning

**Ekstraksi Dosen dari Judul:**
```python
# Contoh: "(AMS/D4TRPL)" → "AMS"
df = df.withColumn(
    "dosen_pengajar",
    regexp_extract(
        col("judul_kuesioner"),
        r"\(([A-Z]{3})\/[^()]*\)$",
        1
    )
)
```

**Normalisasi Prodi:**
```python
df = df.withColumn(
    "prodi_item",
    explode_outer(col("prodi"))
)
```

##### 3. Data Transformation

**A. Explode Rekapitulasi:**
```python
rekap = df.select(
    col("kuesioner_id"),
    col("kode_mk"),
    col("judul_kuesioner"),
    col("dosen_pengajar"),
    col("jenis_kuesioner"),
    col("prodi"),
    col("periode").alias("tahun"),
    col("semester"),
    explode(col("raw_data.statistik.rekapitulasi")).alias("rekap")
)
```

**B. Explode Rincian Jawaban:**
```python
detail = rekap.select(
    # ... kolom lain
    regexp_replace(
        col("rekap.pertanyaan"),
        "<[^>]+>",
        ""
    ).alias("pertanyaan"),  # Hapus HTML tags
    
    explode(
        col("rekap.rincian_jawaban")
    ).alias("jawaban")
)
```

**C. Konversi Skor (Skala Likert):**
```python
# Konversi jawaban 1-5 ke skor 4-0
hasil = hasil.withColumn(
    "skor",
    when(col("jawaban") == 1, 4)  # Sangat Setuju = 4
    .when(col("jawaban") == 2, 3)  # Setuju = 3
    .when(col("jawaban") == 3, 2)  # Cukup Setuju = 2
    .when(col("jawaban") == 4, 1)  # Tidak Setuju = 1
    .when(col("jawaban") == 5, 0)  # Sangat Tidak Setuju = 0
    .otherwise(0)
)
```

**D. Hitung Weighted Score:**
```python
hasil = hasil.withColumn(
    "weighted_score",
    col("skor") * col("jumlah")
)
```

##### 4. Data Aggregation

**Agregasi per Pertanyaan:**
```python
analisis = hasil.groupBy(
    "kuesioner_id",
    "kode_mk",
    "judul_kuesioner",
    "dosen_pengajar",
    "jenis_kuesioner",
    "prodi",
    "tahun",
    "semester",
    "pertanyaan",
    "total_suara"
).agg(
    sum("jumlah").alias("total_jawaban"),
    sum("weighted_score").alias("total_skor")
)
```

**Hitung Rata-rata:**
```python
analisis = analisis.withColumn(
    "rata_rata",
    round(col("total_skor") / col("total_jawaban"), 2)
)
```

**Hitung Persentase Kepuasan:**
```python
analisis = analisis.withColumn(
    "persentase_kepuasan",
    round((col("rata_rata") / 4) * 100, 2)
)
```

##### 5. Statistical Analysis

**Kategori Hasil:**
```python
analisis = analisis.withColumn(
    "kategori_hasil",
    when(col("rata_rata") >= 3.1, "Sangat Baik")
    .when(col("rata_rata") >= 2.1, "Baik")
    .when(col("rata_rata") >= 1.1, "Cukup")
    .otherwise("Kurang")
)
```

**Sentiment Analysis:**
```python
analisis = analisis.withColumn(
    "sentiment",
    when(col("persentase_kepuasan") >= 85, "positive")
    .when(col("persentase_kepuasan") >= 60, "neutral")
    .otherwise("negative")
)
```

**Summary per Mata Kuliah:**
```python
summary_mk = analisis.groupBy(
    "kode_mk",
    "judul_kuesioner",
    "dosen_pengajar",
    "prodi",
    "tahun",
    "semester",
    "jenis_kuesioner"
).agg(
    round(
        sum("total_skor") / sum("total_jawaban"),
        2
    ).alias("rata_rata_matkul")
)
```

##### 6. Data Storage

**Simpan Hasil Detail:**
```python
analisis.write \
    .format("mongodb") \
    .mode("append") \
    .option("spark.mongodb.connection.uri", MONGO_URI) \
    .option("database", "gkm_chatbot") \
    .option("collection", "hasil_analisis_lengkap") \
    .save()
```

**Simpan Summary Matakuliah:**
```python
summary_mk.write \
    .format("mongodb") \
    .mode("append") \
    .option("spark.mongodb.connection.uri", MONGO_URI) \
    .option("database", "gkm_chatbot") \
    .option("collection", "summary_matkul") \
    .save()
```

**Update Status Analisis:**
```python
db.kuesioner_mongos.update_many(
    {"kuesioner_id": {"$in": kuesioner_ids}},
    {"$set": {
        "is_analyzed": True,
        "analyzed_at": datetime.utcnow()
    }}
)
```

---

### 3. MongoDB

MongoDB berfungsi sebagai **database analitik** untuk menyimpan hasil pengolahan Apache Spark.

#### Connection String

```python
MONGO_URI = "mongodb://dangbel:XPOWkcmfJM5IRiww@ac-pvsjuiu-shard-00-00.1k8c4bj.mongodb.net:27017,..."
```

#### Database: `gkm_chatbot`

##### Collection Structure

**A. kuesioner_mongos (Input)**
```javascript
{
    "_id": ObjectId,
    "kuesioner_id": "12345",
    "judul_kuesioner": "Evaluasi Mata Kuliah ...",
    "kode_mk": "TI44101",
    "periode": "2025/2026",
    "semester": "1",
    "prodi": ["D4 TRPL", "D4 TI"],
    "raw_data": {
        "statistik": {
            "total_responden_aktif": 45,
            "rekapitulasi": [
                {
                    "pertanyaan": "Dosen menguasai materi",
                    "total_suara": 45,
                    "rincian_jawaban": [
                        {"jawaban": "1", "jumlah": 30},
                        {"jawaban": "2", "jumlah": 10},
                        {"jawaban": "3", "jumlah": 5}
                    ]
                }
            ]
        }
    },
    "is_analyzed": false,
    "created_at": ISODate
}
```

**B. hasil_analisis_lengkap (Output Detail)**
```javascript
{
    "_id": ObjectId,
    "kuesioner_id": "12345",
    "kode_mk": "TI44101",
    "judul_kuesioner": "Evaluasi Mata Kuliah ...",
    "dosen_pengajar": "AMS",
    "jenis_kuesioner": "UTS",
    "prodi": "D4 TRPL",
    "tahun": "2025/2026",
    "semester": "1",
    "pertanyaan": "Dosen menguasai materi",
    "total_suara": 45,
    "total_jawaban": 45,
    "total_skor": 165,
    "rata_rata": 3.67,
    "persentase_kepuasan": 91.75,
    "kategori_hasil": "Sangat Baik",
    "sentiment": "positive",
    "processed_at": ISODate
}
```

**C. summary_matkul (Output Summary)**
```javascript
{
    "_id": ObjectId,
    "kode_mk": "TI44101",
    "judul_kuesioner": "Evaluasi Mata Kuliah ...",
    "dosen_pengajar": "AMS",
    "prodi": "D4 TRPL",
    "tahun": "2025/2026",
    "semester": "1",
    "jenis_kuesioner": "UTS",
    "rata_rata_matkul": 3.65
}
```

#### Laravel MongoDB Models

**HasilAnalisisMongo.php:**
```php
class HasilAnalisisMongo extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'hasil_analisis_lengkap';
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps = false;
}
```

**KuesionerMongo.php:**
```php
class KuesionerMongo extends Model
{
    protected $connection = 'mongodb';
    protected $table = 'kuesioner_data';
    protected $fillable = [
        'kuesioner_id', 'judul_kuesioner', 'kode_mk',
        'periode', 'raw_data', 'updated_at', 'created_at'
    ];
    protected $casts = ['raw_data' => 'array'];
}
```

---

### 4. Dashboard Analitik

Dashboard web mengambil data dari MongoDB dan menampilkan visualisasi serta insight untuk evaluasi pembelajaran.

#### Service Layer: LaporanKuesioneService

Service ini mengimplementasikan **RAG (Retrieval-Augmented Generation)** pattern untuk menghasilkan laporan.

##### RAG Pipeline

**Step 1: Collect Data (Retrieve)**
```php
public function collectKuesioneData($periode, $prodiId = null, $tipeLaporan = 'UTS')
{
    $query = KuesioneUpload::query();
    
    // Filter by semester
    $periodeContext = $this->resolvePeriodeContext($periode);
    $semester = $periodeContext['semester'];
    
    if (\Schema::hasColumn('kuesioner_uploads', 'semester')) {
        $query->where('semester', $semester);
    }
    
    // Filter by jenis kuesioner (UTS/UAS)
    if (!empty($tipeLaporan)) {
        $query->whereRaw('LOWER(TRIM(jenis_kuesioner)) = ?', 
            [strtolower(trim($tipeLaporan))]);
    }
    
    return $query->orderBy('created_at', 'desc')->get();
}
```

**Step 2: Aggregate Statistics**
```php
public function aggregateStatistik($kuesioneList)
{
    $totalKuesioner = $kuesioneList->count();
    $totalResponden = 0;
    $sumIndexKepuasan = 0;
    
    foreach ($kuesioneList as $kuesioner) {
        $hasilAnalisis = $kuesioner->hasil_analisis;
        
        if (!empty($hasilAnalisis) && isset($hasilAnalisis['statistik'])) {
            $indexKepuasan = $hasilAnalisis['statistik']['index_kepuasan'] ?? 0;
            $responden = $hasilAnalisis['statistik']['total_responden'] ?? 0;
        } else {
            // Fallback ke field model
            $indexKepuasan = $kuesioner->index_kepuasan ?? 0;
            $responden = $kuesioner->total_responden ?? 0;
        }
        
        $totalResponden += $responden;
        $sumIndexKepuasan += $indexKepuasan;
    }
    
    $indexKepuasanRataRata = $totalKuesioner > 0 
        ? $sumIndexKepuasan / $totalKuesioner : 0;
    
    return [
        'total_kuesioner' => $totalKuesioner,
        'total_responden' => $totalResponden,
        'index_kepuasan_rata_rata' => round($indexKepuasanRataRata, 4),
        'persen_kepuasan_rata_rata' => round(($indexKepuasanRataRata / 4) * 100, 2)
    ];
}
```

**Step 3: Build Context**
```php
public function buildContext($aggregatedData, $periode, $prodi = null, $tipeLaporan = 'UTS')
{
    $context = "=== DATA KUESIONER PERIODE {$periode} ===\n\n";
    
    // Group by tingkat
    $kuesioneByTingkat = [];
    foreach ($aggregatedData['kuesioner_data'] as $kuesioner) {
        $tingkat = $kuesioner['tingkat'] ?? 'Unknown';
        $kuesioneByTingkat[$tingkat][] = $kuesioner;
    }
    
    // Build tabel per tingkat
    foreach ($kuesioneByTingkat as $tingkat => $kuesioneList) {
        $context .= "TINGKAT {$tingkat}:\n";
        $context .= "| Kode MK | Nama MK | Dosen | Indeks |\n";
        
        foreach ($kuesioneList as $k) {
            $context .= "| {$k['kode_matakuliah']} | {$k['nama_matakuliah']} | ";
            $context .= "{$k['dosen_pengampu']} | {$k['index_kepuasan']} |\n";
        }
    }
    
    return $context;
}
```

**Step 4: Augment Prompt**
```php
public function augmentPromptWithTemplate($template, $context, $periode, $tipeLaporan = 'UTS')
{
    $prompt = "Anda adalah AI Agent untuk membuat laporan kuesioner.\n\n";
    $prompt .= "JENIS LAPORAN: {$tipeLaporan}\n\n";
    
    if ($template && !empty($template->contoh_konten)) {
        $prompt .= "TEMPLATE LAPORAN:\n";
        $prompt .= $template->contoh_konten . "\n\n";
    }
    
    $prompt .= "DATA KUESIONER:\n";
    $prompt .= $context . "\n\n";
    
    $prompt .= "OUTPUT FORMAT (JSON): ...\n";
    
    return $prompt;
}
```

**Step 5: Generate dengan AI**
```php
public function generateLaporan($periode, $prodiId = null, $templateId = null, $tipeLaporan = 'UTS')
{
    // Step 1-4
    $kuesioneList = $this->collectKuesioneData($periode, $prodiId, $tipeLaporan);
    $aggregatedData = $this->aggregateStatistik($kuesioneList);
    $context = $this->buildContext($aggregatedData, $periode, $prodi, $tipeLaporan);
    $augmentedPrompt = $this->augmentPromptWithTemplate($template, $context, $periode, $tipeLaporan);
    
    // Step 5: Call AI
    $aiService = app(\App\Services\UnifiedAIService::class);
    
    $messages = [
        ['role' => 'system', 'content' => 'Anda adalah AI Agent ahli...'],
        ['role' => 'user', 'content' => $augmentedPrompt]
    ];
    
    $aiResult = $aiService->generateChat($messages, [
        'max_tokens' => 4000,
        'temperature' => 0.7
    ]);
    
    return json_decode($aiResult['text'], true);
}
```

#### Controller: MonitoringKuesioneController

```php
public function index(Request $request)
{
    $kuesionersQuery = KuesioneUpload::with(['user', 'user.prodi'])
        ->whereHas('user', function ($q) use ($user) {
            $q->where('prodi_id', $user->prodi_id);
        });
    
    if ($request->filled('periode')) {
        $kuesionersQuery->where('periode', $request->periode);
    }
    
    if ($request->filled('jenis_kuesioner')) {
        $kuesionersQuery->where('jenis_kuesioner', $request->jenis_kuesioner);
    }
    
    $kuesioners = $kuesionersQuery->paginate(10);
    
    return view('gkm.monitoring-kuesioner.index', compact('kuesioners'));
}
```

---

## Alur Data End-to-End

```
┌─────────────────────────────────────────────────────────────────────┐
│ 1. DATA INGESTION                                                   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   CIS API / Excel Upload                                            │
│         ↓                                                           │
│   MonitoringKuesioneController                                      │
│         ↓                                                           │
│   MySQL: kuesioner_uploads (status='uploaded')                      │
│         ↓                                                           │
│   MongoDB: kuesioner_mongos (is_analyzed=false)                     │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│ 2. SPARK PROCESSING                                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   User clicks "Jalankan Analisis Spark"                             │
│         ↓                                                           │
│   DashboardController->jalankanAnalisisSpark()                      │
│         ↓                                                           │
│   Execute: spark-submit spark_kuesioner.py                          │
│         ↓                                                           │
│   Spark Pipeline:                                                   │
│     • Read from MongoDB (is_analyzed=false)                         │
│     • Clean & Transform data                                        │
│     • Calculate statistics                                          │
│     • Aggregate by matakuliah                                       │
│     • Write to MongoDB (hasil_analisis_lengkap, summary_matkul)     │
│     • Update is_analyzed=true                                       │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│ 3. DASHBOARD VISUALIZATION                                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   User accesses Monitoring Kuesioner page                           │
│         ↓                                                           │
│   MonitoringKuesioneController->index()                             │
│         ↓                                                           │
│   Query MySQL: kuesioner_uploads                                    │
│         ↓                                                           │
│   Display table with pagination                                     │
│                                                                     │
│   User clicks "Generate Laporan"                                    │
│         ↓                                                           │
│   LaporanKuesioneService->generateLaporan()                         │
│         ↓                                                           │
│   RAG Pipeline (5 steps)                                            │
│         ↓                                                           │
│   AI generates report JSON                                          │
│         ↓                                                           │
│   Convert to Word/PDF document                                      │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Konfigurasi Environment

### .env Configuration

```env
# Apache Spark
SPARK_HOME=C:\spark
SPARK_SUBMIT_CMD=spark-submit

# Python
PYTHON_PATH=C:\Python312\python.exe

# MongoDB
MONGODB_URI=mongodb://user:pass@cluster.mongodb.net/gkm_chatbot
MONGODB_DATABASE=gkm_chatbot

# MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gkm_system
DB_USERNAME=root
DB_PASSWORD=

# AI Service
LLM_API_KEY=your_api_key
LLM_BASE_URL=https://api.openai.com/v1
LLM_MODEL=gpt-4
```

### Dependencies

**Python (PySpark):**
```bash
pip install pyspark==3.5.0
pip install pymongo==4.6.0
```

**PHP (Composer):**
```json
{
    "require": {
        "mongodb/laravel-mongodb": "^4.0",
        "phpoffice/phpspreadsheet": "^1.29"
    }
}
```

**Apache Spark:**
- Version: 3.5.0
- Scala: 2.12
- MongoDB Connector: 10.3.0

---

## Performance Metrics

### Spark Processing

| Metric | Value | Notes |
|--------|-------|-------|
| Data Volume | 100-1000 records | Per batch |
| Processing Time | 30-60 seconds | Depends on data size |
| Memory Usage | 2-4 GB | Configurable |
| Parallelism | 4-8 cores | Local mode |

### API Response Time

| Endpoint | Avg Time | Notes |
|----------|----------|-------|
| collectKuesioneData | 50-100ms | MySQL query |
| aggregateStatistik | 100-200ms | In-memory processing |
| generateLaporan | 5-10s | Includes AI call |

---

## Monitoring & Logging

### Spark Logs

```python
spark.sparkContext.setLogLevel("ERROR")
print("TOTAL BELUM DIANALISIS:", total_data)
print("HASIL ANALISIS:", analisis.show(truncate=False))
```

### Laravel Logs

```php
Log::info("=== RAG STEP 1: Collecting Kuesioner Data ===", [
    'periode' => $periode,
    'prodi_id' => $prodiId
]);

Log::info("Final collected kuesioner count: " . $results->count());
```

### Debug Commands

```bash
# Test Spark configuration
php artisan spark:test-config

# Debug dashboard data
php artisan debug:dashboard-gjm

# Check kuesioner collection
php artisan app:check-llmusage
```

---

## Kesimpulan

Sistem pengolahan data kuesioner menggunakan arsitektur **modern data pipeline** dengan Apache Spark sebagai engine utama untuk:

1. ✅ **Skalabilitas**: Dapat menangani volume data yang besar
2. ✅ **Performa**: Processing paralel dan terdistribusi
3. ✅ **Fleksibilitas**: Mudah menambah transformasi baru
4. ✅ **Integrasi**: Seamless dengan MongoDB dan Laravel
5. ✅ **Monitoring**: Dashboard web untuk visualisasi hasil

Pipeline yang terintegrasi antara **CIS API → Spark → MongoDB → Dashboard** memastikan data kuesioner dapat diproses, dianalisis, dan divisualisasikan secara **akurat**, **konsisten**, dan **efisien** untuk mendukung evaluasi kualitas pembelajaran di Institut Teknologi Del.
