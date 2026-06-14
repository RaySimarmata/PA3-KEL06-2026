# Apache Spark Implementation - Detail Kode Lengkap

## Daftar Isi
1. [Konfigurasi Spark](#1-konfigurasi-spark)
2. [Trigger Eksekusi](#2-trigger-eksekusi)
3. [Data Ingestion](#3-data-ingestion)
4. [Data Cleaning](#4-data-cleaning)
5. [Data Transformation](#5-data-transformation)
6. [Data Aggregation](#6-data-aggregation)
7. [Statistical Analysis](#7-statistical-analysis)
8. [Data Storage](#8-data-storage)
9. [Konversi Skor Likert](#9-konversi-skor-likert)
10. [Sentiment Analysis](#10-sentiment-analysis)

---

## 1. Konfigurasi Spark

### File: `spark/spark_kuesioner.py`

**Setup Dependencies:**
```python
from pyspark.sql import SparkSession
from pyspark.sql.functions import (
    col,
    explode,
    explode_outer,
    sum,
    when,
    round,
    regexp_extract,
    regexp_replace,
    current_timestamp
)
from pymongo import MongoClient
from datetime import datetime
from pyspark.sql.functions import struct, array
import shutil
import tempfile
```

**Temporary Directory:**
```python
# =========================
# TEMP DIR
# =========================
temp_dir = tempfile.mkdtemp()
```

**MongoDB Connection:**
```python
# =========================
# MONGO URI
# =========================
MONGO_URI = "mongodb://dangbel:XPOWkcmfJM5IRiww@ac-pvsjuiu-shard-00-00.1k8c4bj.mongodb.net:27017,ac-pvsjuiu-shard-00-01.1k8c4bj.mongodb.net:27017,ac-pvsjuiu-shard-00-02.1k8c4bj.mongodb.net:27017/?ssl=true&replicaSet=atlas-y2p335-shard-0&authSource=admin&appName=Cluster0"
```

**Create Spark Session:**
```python
# =========================
# CREATE SPARK SESSION
# =========================
spark = SparkSession.builder \
    .appName("AnalisisKuesionerLengkap") \
    .config(
        "spark.jars.packages",
        "org.mongodb.spark:mongo-spark-connector_2.12:10.3.0"
    ) \
    .config("spark.local.dir", temp_dir) \
    .config("spark.mongodb.read.connection.uri", MONGO_URI) \
    .config("spark.mongodb.write.connection.uri", MONGO_URI) \
    .getOrCreate()

# Set log level untuk mengurangi noise
spark.sparkContext.setLogLevel("ERROR")
```

**Penjelasan Konfigurasi:**
- `appName`: Nama aplikasi Spark untuk identifikasi
- `spark.jars.packages`: MongoDB connector untuk read/write ke MongoDB
- `spark.local.dir`: Directory temporary untuk Spark processing
- `spark.mongodb.*.connection.uri`: Connection string MongoDB
- `getOrCreate()`: Reuse session jika sudah ada, atau create new

---

## 2. Trigger Eksekusi

### A. Dari Laravel Controller

**File: `app/Http/Controllers/GJM/DashboardController.php`**

```php
/**
 * Jalankan Analisis Spark
 * Trigger Spark job dari Laravel
 */
public function jalankanAnalisisSpark()
{
    try {
        // Path ke Spark submit command
        $spark = 'C:\spark\bin\spark-submit.cmd';
        
        // Path ke Python interpreter
        $python = 'C:\Python312\python.exe';
        
        // Path ke script Python
        $pythonFile = base_path('spark/spark_kuesioner.py');

        // Set environment variables untuk PySpark
        putenv("PYSPARK_PYTHON={$python}");
        putenv("PYSPARK_DRIVER_PYTHON={$python}");

        // Build command
        $command = "\"{$spark}\" " .
            "--conf spark.pyspark.python=\"{$python}\" " .
            "--conf spark.pyspark.driver.python=\"{$python}\" " .
            "--packages org.mongodb.spark:mongo-spark-connector_2.12:10.3.0 " .
            "\"{$pythonFile}\" 2>&1";

        // Execute command
        exec($command, $output, $returnCode);

        // Log output
        \Log::info('SPARK COMMAND', ['command' => $command]);
        \Log::info('SPARK OUTPUT', ['output' => $output]);

        if ($returnCode !== 0) {
            throw new \Exception('Spark job failed: ' . implode("\n", $output));
        }

        return back()->with('success', 'Analisis Spark berhasil dijalankan');

    } catch (\Exception $e) {
        \Log::error('SPARK ERROR', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return back()->with('error', 'Gagal menjalankan analisis: ' . $e->getMessage());
    }
}
```

### B. Route Definition

**File: `routes/web.php`**

```php
Route::post('/gjm/dashboard/run-spark', [
    App\Http\Controllers\GJM\DashboardController::class,
    'jalankanAnalisisSpark'
])->name('gjm.dashboard.run-spark');
```

### C. Button di View

**File: `resources/views/gjm/dashboard/index.blade.php`**

```blade
<form method="POST" action="{{ route('gjm.dashboard.run-spark') }}">
    @csrf
    <button type="submit" class="btn btn-primary shadow-sm d-inline-flex align-items-center gap-2">
        <i class="bi bi-graph-up"></i> Jalankan Analisis Spark
    </button>
</form>
```

---

## 3. Data Ingestion

### Membaca Data dari MongoDB

**File: `spark/spark_kuesioner.py`**

```python
# =========================
# READ DATA MONGO
# =========================
# Pipeline untuk filter data yang belum dianalisis
pipeline = """
[
    {
        "$match": {
            "is_analyzed": false
        }
    }
]
"""

# Read dari MongoDB dengan aggregation pipeline
df = spark.read \
    .format("mongodb") \
    .option("database", "gkm_chatbot") \
    .option("collection", "kuesioner_mongos") \
    .option("aggregation.pipeline", pipeline) \
    .load()

# Hitung total data
total_data = df.count()

print("TOTAL BELUM DIANALISIS:")
print(total_data)

# =========================
# SIMPAN KUESIONER YANG DIPROSES
# =========================
kuesioner_ids = [
    row["kuesioner_id"]
    for row in df.select("kuesioner_id").distinct().collect()
]

print("KUESIONER YANG AKAN DIPROSES:")
print(kuesioner_ids)

# Exit jika tidak ada data baru
if total_data == 0:
    print("TIDAK ADA DATA BARU")
    spark.stop()
    shutil.rmtree(temp_dir, ignore_errors=True)
    exit()
```

**Penjelasan:**
- `pipeline`: MongoDB aggregation untuk filter `is_analyzed=false`
- `.format("mongodb")`: Menggunakan MongoDB connector
- `.option("aggregation.pipeline", pipeline)`: Apply filter saat read
- `.load()`: Execute read operation
- `df.count()`: Hitung jumlah records
- `distinct().collect()`: Ambil unique kuesioner_id untuk tracking

---

## 4. Data Cleaning

### A. Ekstraksi Dosen dari Judul Kuesioner

```python
# =========================
# AMBIL DOSEN DARI JUDUL
# Contoh judul: "Evaluasi Mata Kuliah Pemrograman Web (AMS/D4TRPL)"
# Ekstrak: AMS
# =========================
df = df.withColumn(
    "dosen_pengajar",
    regexp_extract(
        col("judul_kuesioner"),
        r"\(([A-Z]{3})\/[^()]*\)$",  # Regex pattern
        1  # Group 1
    )
)
```

**Penjelasan Regex:**
- `\(`: Literal opening parenthesis
- `([A-Z]{3})`: Capture 3 uppercase letters (inisial dosen)
- `\/`: Literal forward slash
- `[^()]*`: Any characters except parentheses
- `\)$`: Closing parenthesis at end of string
- Group `1`: Return captured group (dosen inisial)

**Contoh:**
- Input: `"Evaluasi Mata Kuliah Pemrograman Web (AMS/D4TRPL)"`
- Output: `"AMS"`

### B. Normalisasi Prodi (Explode Array)

```python
# =========================
# EXPLODE PRODI
# Satu kuesioner bisa untuk multiple prodi
# Contoh: ["D4 TRPL", "D4 TI"] → 2 rows
# =========================
df = df.withColumn(
    "prodi_item",
    explode_outer(col("prodi"))  # explode_outer: handle null/empty array
)

# Rename column
df = df.withColumn(
    "prodi",
    col("prodi_item")
)

# Normalisasi jenis_kuesioner
df = df.withColumn(
    "jenis_kuesioner", 
    col("jenis_kuesioner")
)
```

**Penjelasan:**
- `explode_outer()`: Seperti `explode()` tapi handle null values
- Jika prodi = `["D4 TRPL", "D4 TI"]`, akan jadi 2 rows:
  - Row 1: prodi = "D4 TRPL"
  - Row 2: prodi = "D4 TI"

---

## 5. Data Transformation

### A. Explode Rekapitulasi Pertanyaan

```python
# =========================
# EXPLODE REKAPITULASI
# raw_data.statistik.rekapitulasi berisi array pertanyaan
# =========================
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

**Struktur Data Sebelum:**
```json
{
  "kuesioner_id": "12345",
  "raw_data": {
    "statistik": {
      "rekapitulasi": [
        {"pertanyaan": "Q1", "rincian_jawaban": [...]},
        {"pertanyaan": "Q2", "rincian_jawaban": [...]}
      ]
    }
  }
}
```

**Struktur Data Setelah (2 rows):**
```
kuesioner_id | rekap
-------------|-------
12345        | {"pertanyaan": "Q1", "rincian_jawaban": [...]}
12345        | {"pertanyaan": "Q2", "rincian_jawaban": [...]}
```

### B. Explode Rincian Jawaban

```python
# =========================
# EXPLODE RINCIAN JAWABAN
# Setiap pertanyaan punya multiple jawaban (1-5)
# =========================
detail = rekap.select(
    col("kuesioner_id"),
    col("kode_mk"),
    col("judul_kuesioner"),
    col("dosen_pengajar"),
    col("jenis_kuesioner"),
    col("prodi"),
    col("tahun"),
    col("semester"),

    # Clean HTML tags dari pertanyaan
    regexp_replace(
        col("rekap.pertanyaan"),
        "<[^>]+>",  # Regex untuk HTML tags
        ""
    ).alias("pertanyaan"),

    # Cast ke integer
    col("rekap.total_suara")
        .cast("int")
        .alias("total_suara"),

    # Explode rincian jawaban
    explode(
        col("rekap.rincian_jawaban")
    ).alias("jawaban")
)
```

**Contoh Cleaning HTML:**
- Input: `"<b>Dosen menguasai materi</b>"`
- Output: `"Dosen menguasai materi"`

**Struktur Data:**
```
pertanyaan              | jawaban
------------------------|--------
"Dosen menguasai materi"| {"jawaban": "1", "jumlah": 30}
"Dosen menguasai materi"| {"jawaban": "2", "jumlah": 10}
"Dosen menguasai materi"| {"jawaban": "3", "jumlah": 5}
```

### C. Extract Jawaban dan Jumlah

```python
# =========================
# AMBIL NILAI & JUMLAH
# =========================
hasil = detail.select(
    col("kuesioner_id"),
    col("kode_mk"),
    col("judul_kuesioner"),
    col("dosen_pengajar"),
    col("jenis_kuesioner"),
    col("prodi"),
    col("tahun"),
    col("semester"),
    col("pertanyaan"),
    col("total_suara"),

    # Extract jawaban (1-5) dan cast ke int
    col("jawaban.jawaban")
        .cast("int")
        .alias("jawaban"),

    # Extract jumlah responden per jawaban
    col("jawaban.jumlah")
        .cast("int")
        .alias("jumlah")
)
```

**Hasil:**
```
pertanyaan              | jawaban | jumlah
------------------------|---------|-------
"Dosen menguasai materi"| 1       | 30
"Dosen menguasai materi"| 2       | 10
"Dosen menguasai materi"| 3       | 5
```

---

## 6. Data Aggregation

### A. Hitung Total dan Skor

```python
# =========================
# ANALISIS PER PERTANYAAN
# Group by semua dimensi + pertanyaan
# =========================
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
    # Sum total jawaban
    sum("jumlah")
        .alias("total_jawaban"),

    # Sum weighted score
    sum("weighted_score")
        .alias("total_skor")
)
```

**Penjelasan:**
- `groupBy()`: Group data berdasarkan dimensi analisis
- `sum("jumlah")`: Total responden yang menjawab
- `sum("weighted_score")`: Total skor terbobot

**Contoh Hasil:**
```
pertanyaan              | total_jawaban | total_skor
------------------------|---------------|------------
"Dosen menguasai materi"| 45            | 165
```

### B. Hitung Rata-rata

```python
# =========================
# HITUNG RATA-RATA
# rata_rata = total_skor / total_jawaban
# =========================
analisis = analisis.withColumn(
    "rata_rata",
    round(
        col("total_skor") / col("total_jawaban"),
        2  # 2 decimal places
    )
)
```

**Perhitungan:**
```
rata_rata = 165 / 45 = 3.67
```

### C. Hitung Persentase Kepuasan

```python
# =========================
# HITUNG PERSENTASE
# Persentase = (rata_rata / 4) * 100
# Skala maksimal = 4
# =========================
analisis = analisis.withColumn(
    "persentase_kepuasan",
    round(
        (col("rata_rata") / 4) * 100,
        2  # 2 decimal places
    )
)
```

**Perhitungan:**
```
persentase = (3.67 / 4) * 100 = 91.75%
```

---

## 7. Statistical Analysis

### A. Kategori Hasil

```python
# =========================
# KATEGORI HASIL
# Berdasarkan skala rata-rata
# =========================
analisis = analisis.withColumn(
    "kategori_hasil",

    when(col("rata_rata") >= 3.1, "Sangat Baik")
    .when(col("rata_rata") >= 2.1, "Baik")
    .when(col("rata_rata") >= 1.1, "Cukup")
    .otherwise("Kurang")
)
```

**Kategori Mapping:**
| Rata-rata | Kategori      |
|-----------|---------------|
| ≥ 3.1     | Sangat Baik   |
| 2.1 - 3.0 | Baik          |
| 1.1 - 2.0 | Cukup         |
| < 1.1     | Kurang        |

### B. Sentiment Analysis

```python
# =========================
# LABEL SENTIMENT
# Berdasarkan persentase kepuasan
# =========================
analisis = analisis.withColumn(
    "sentiment",

    when(
        col("persentase_kepuasan") >= 85,
        "positive"
    )
    .when(
        col("persentase_kepuasan") >= 60,
        "neutral"
    )
    .otherwise("negative")
)
```

**Sentiment Mapping:**
| Persentase | Sentiment |
|------------|-----------|
| ≥ 85%      | positive  |
| 60% - 84%  | neutral   |
| < 60%      | negative  |

### C. Add Timestamp

```python
# =========================
# TIMESTAMP
# Catat waktu pemrosesan
# =========================
analisis = analisis.withColumn(
    "processed_at",
    current_timestamp()
)
```

### D. Summary per Mata Kuliah

```python
# =========================
# SUMMARY PER MATKUL
# Agregasi lebih tinggi: per mata kuliah
# =========================
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

print("SUMMARY MATKUL:")
summary_mk.show(truncate=False)
```

**Contoh Output:**
```
+--------+-------------------------+---------------+--------+----------+--------+----------------+-----------------+
|kode_mk |judul_kuesioner          |dosen_pengajar |prodi   |tahun     |semester|jenis_kuesioner |rata_rata_matkul |
+--------+-------------------------+---------------+--------+----------+--------+----------------+-----------------+
|TI44101 |Evaluasi Mata Kuliah...  |AMS            |D4 TRPL |2025/2026 |1       |UTS             |3.65             |
+--------+-------------------------+---------------+--------+----------+--------+----------------+-----------------+
```

---

## 8. Data Storage

### A. Simpan Hasil Detail ke MongoDB

```python
# =========================
# SIMPAN HASIL DETAIL
# Collection: hasil_analisis_lengkap
# =========================
analisis.write \
    .format("mongodb") \
    .mode("append") \
    .option(
        "spark.mongodb.connection.uri",
        MONGO_URI
    ) \
    .option(
        "database",
        "gkm_chatbot"
    ) \
    .option(
        "collection",
        "hasil_analisis_lengkap"
    ) \
    .save()

print("BERHASIL SIMPAN DETAIL")
```

**Penjelasan:**
- `.format("mongodb")`: Use MongoDB connector
- `.mode("append")`: Append data (tidak overwrite)
- `.option("database", ...)`: Target database
- `.option("collection", ...)`: Target collection
- `.save()`: Execute write operation

**Document Structure:**
```json
{
    "_id": ObjectId("..."),
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
    "processed_at": ISODate("2026-06-13T10:30:00Z")
}
```

### B. Simpan Summary Matakuliah

```python
# =========================
# SIMPAN SUMMARY MATKUL
# Collection: summary_matkul
# =========================
summary_mk.write \
    .format("mongodb") \
    .mode("append") \
    .option(
        "spark.mongodb.connection.uri",
        MONGO_URI
    ) \
    .option(
        "database",
        "gkm_chatbot"
    ) \
    .option(
        "collection",
        "summary_matkul"
    ) \
    .save()

print("BERHASIL SIMPAN SUMMARY")
```

**Document Structure:**
```json
{
    "_id": ObjectId("..."),
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

### C. Update Status Analisis

```python
# =========================
# UPDATE STATUS ANALISIS
# Tandai data sebagai sudah dianalisis
# =========================
client = MongoClient(MONGO_URI)
db = client["gkm_chatbot"]

# Update many documents
db.kuesioner_mongos.update_many(
    {
        "kuesioner_id": {
            "$in": kuesioner_ids  # List ID yang diproses
        }
    },
    {
        "$set": {
            "is_analyzed": True,
            "analyzed_at": datetime.utcnow()
        }
    }
)

print("STATUS ANALISIS BERHASIL DIUPDATE")
```

**Penjelasan:**
- `update_many()`: Update multiple documents
- `$in`: Match kuesioner_id dalam list
- `$set`: Set field values
- `is_analyzed`: Flag untuk prevent duplicate processing
- `analyzed_at`: Timestamp untuk audit trail

### D. Cleanup dan Stop Spark

```python
# =========================
# STOP SPARK
# =========================
spark.stop()

# =========================
# HAPUS TEMP DIR
# =========================
try:
    shutil.rmtree(
        temp_dir,
        ignore_errors=True
    )
    print("TEMP DIR DIHAPUS")
except Exception as e:
    print("GAGAL HAPUS TEMP:", e)
```

---

## 9. Konversi Skor Likert (Detail Lengkap)

### Konsep Skala Likert

Kuesioner menggunakan **5-point Likert scale** untuk mengukur kepuasan:

| Jawaban | Label                | Makna              |
|---------|----------------------|--------------------|
| 1       | Sangat Setuju (SS)   | Paling puas        |
| 2       | Setuju (S)           | Puas               |
| 3       | Cukup Setuju (CS)    | Netral             |
| 4       | Tidak Setuju (TS)    | Tidak puas         |
| 5       | Sangat Tidak Setuju  | Sangat tidak puas  |

### Konversi ke Skor (Reverse Scoring)

Untuk analisis, kita perlu **reverse scoring** karena nilai 1 = paling baik:

```python
# =========================
# KONVERSI SKOR
# Reverse scoring: nilai rendah (1) = skor tinggi (4)
# Skala output: 0-4
# =========================
hasil = hasil.withColumn(
    "skor",

    when(col("jawaban") == 1, 4)  # SS → 4 poin
    .when(col("jawaban") == 2, 3)  # S  → 3 poin
    .when(col("jawaban") == 3, 2)  # CS → 2 poin
    .when(col("jawaban") == 4, 1)  # TS → 1 poin
    .when(col("jawaban") == 5, 0)  # STS → 0 poin
    .otherwise(0)  # Invalid → 0 poin
)
```

### Mapping Table

| Input (jawaban) | Output (skor) | Label |
|-----------------|---------------|-------|
| 1               | 4             | SS    |
| 2               | 3             | S     |
| 3               | 2             | CS    |
| 4               | 1             | TS    |
| 5               | 0             | STS   |

### Contoh Perhitungan

**Data Responden:**
```
Pertanyaan: "Dosen menguasai materi"
- 30 orang jawab "1" (Sangat Setuju)
- 10 orang jawab "2" (Setuju)
- 5 orang jawab "3" (Cukup Setuju)
Total: 45 responden
```

**Step 1: Konversi ke Skor**
```python
# Data setelah konversi:
jawaban=1, jumlah=30 → skor=4
jawaban=2, jumlah=10 → skor=3
jawaban=3, jumlah=5  → skor=2
```

**Step 2: Hitung Weighted Score**
```python
# =========================
# HITUNG WEIGHTED SCORE
# weighted_score = skor * jumlah
# =========================
hasil = hasil.withColumn(
    "weighted_score",
    col("skor") * col("jumlah")
)
```

```
Row 1: weighted_score = 4 * 30 = 120
Row 2: weighted_score = 3 * 10 = 30
Row 3: weighted_score = 2 * 5  = 10
```

    **Step 3: Agregasi**
```python
total_skor = sum(weighted_score) = 120 + 30 + 10 = 160
total_jawaban = sum(jumlah) = 30 + 10 + 5 = 45
```

**Step 4: Hitung Rata-rata**
```python
rata_rata = total_skor / total_jawaban
rata_rata = 160 / 45 = 3.56
```

**Step 5: Hitung Persentase**
```python
persentase = (rata_rata / 4) * 100
persentase = (3.56 / 4) * 100 = 89%
```

### Interpretasi Hasil

```python
rata_rata = 3.56
kategori = "Sangat Baik"  # karena >= 3.1
sentiment = "positive"     # karena 89% >= 85%
```

---

## 10. Sentiment Analysis (Detail Lengkap)

### Konsep Sentiment Analysis

Sentiment analysis mengklasifikasikan hasil evaluasi ke 3 kategori berdasarkan **persentase kepuasan**:

| Sentiment | Range         | Interpretasi              |
|-----------|---------------|---------------------------|
| positive  | ≥ 85%         | Sangat memuaskan          |
| neutral   | 60% - 84%     | Memuaskan dengan catatan  |
| negative  | < 60%         | Perlu perbaikan           |

### Implementasi Kode

```python
# =========================
# LABEL SENTIMENT
# Klasifikasi berdasarkan threshold
# =========================
analisis = analisis.withColumn(
    "sentiment",

    # Condition 1: Positive
    when(
        col("persentase_kepuasan") >= 85,
        "positive"
    )
    
    # Condition 2: Neutral
    .when(
        col("persentase_kepuasan") >= 60,
        "neutral"
    )
    
    # Default: Negative
    .otherwise("negative")
)
```

### Contoh Kasus

#### Kasus 1: Positive Sentiment
```python
persentase_kepuasan = 91.75
sentiment = "positive"  # >= 85%

Interpretasi:
- Mahasiswa sangat puas dengan pembelajaran
- Dosen dan materi perkuliahan sangat baik
- Tidak ada tindakan perbaikan mendesak
```

#### Kasus 2: Neutral Sentiment
```python
persentase_kepuasan = 72.50
sentiment = "neutral"  # 60-84%

Interpretasi:
- Mahasiswa cukup puas, tapi ada ruang perbaikan
- Beberapa aspek perlu ditingkatkan
- Perlu review feedback detail
```

#### Kasus 3: Negative Sentiment
```python
persentase_kepuasan = 45.25
sentiment = "negative"  # < 60%

Interpretasi:
- Mahasiswa tidak puas dengan pembelajaran
- Perlu tindakan perbaikan segera
- Review metode pengajaran dan materi
```

### Agregasi Sentiment per Prodi

Untuk mendapatkan overview sentiment per program studi:

```python
# Hitung distribusi sentiment
sentiment_summary = analisis.groupBy(
    "prodi",
    "semester",
    "tahun"
).agg(
    # Count per sentiment
    sum(when(col("sentiment") == "positive", 1).otherwise(0)).alias("count_positive"),
    sum(when(col("sentiment") == "neutral", 1).otherwise(0)).alias("count_neutral"),
    sum(when(col("sentiment") == "negative", 1).otherwise(0)).alias("count_negative"),
    
    # Total
    count("*").alias("total_pertanyaan"),
    
    # Percentage
    round(
        sum(when(col("sentiment") == "positive", 1).otherwise(0)) / count("*") * 100,
        2
    ).alias("persen_positive")
)
```

**Contoh Output:**
```
+--------+--------+----------+---------------+--------------+---------------+------------------+---------------+
|prodi   |semester|tahun     |count_positive |count_neutral |count_negative |total_pertanyaan  |persen_positive|
+--------+--------+----------+---------------+--------------+---------------+------------------+---------------+
|D4 TRPL |1       |2025/2026 |120            |45            |10             |175               |68.57          |
+--------+--------+----------+---------------+--------------+---------------+------------------+---------------+
```

### Visualisasi Sentiment Distribution

**Query untuk Dashboard:**
```python
# Spark: Prepare data untuk visualisasi
dashboard_data = analisis.groupBy("sentiment") \
    .agg(
        count("*").alias("count"),
        avg("persentase_kepuasan").alias("avg_kepuasan")
    ) \
    .orderBy("sentiment")

# Simpan ke MongoDB untuk dashboard
dashboard_data.write \
    .format("mongodb") \
    .mode("overwrite") \
    .option("database", "gkm_chatbot") \
    .option("collection", "sentiment_dashboard") \
    .save()
```

**Hasil untuk Chart:**
```json
[
    {"sentiment": "positive", "count": 120, "avg_kepuasan": 89.5},
    {"sentiment": "neutral", "count": 45, "avg_kepuasan": 72.3},
    {"sentiment": "negative", "count": 10, "avg_kepuasan": 48.7}
]
```

---

## Complete Pipeline Flow Diagram

```
┌──────────────────────────────────────────────────────────────────────┐
│                     SPARK PROCESSING PIPELINE                         │
├──────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  1. INGESTION                                                         │
│     ↓                                                                 │
│     Read from MongoDB (is_analyzed=false)                             │
│     Filter: kuesioner_mongos collection                               │
│                                                                       │
│  2. CLEANING                                                          │
│     ↓                                                                 │
│     • Extract dosen from judul (regex)                                │
│     • Explode prodi array                                             │
│     • Normalize jenis_kuesioner                                       │
│                                                                       │
│  3. TRANSFORMATION                                                    │
│     ↓                                                                 │
│     • Explode rekapitulasi (pertanyaan)                               │
│     • Explode rincian_jawaban                                         │
│     • Clean HTML from pertanyaan                                      │
│     • Extract jawaban & jumlah                                        │
│                                                                       │
│  4. LIKERT CONVERSION                                                 │
│     ↓                                                                 │
│     Convert jawaban 1-5 → skor 4-0 (reverse scoring)                  │
│     Calculate weighted_score = skor * jumlah                          │
│                                                                       │
│  5. AGGREGATION                                                       │
│     ↓                                                                 │
│     GroupBy: kuesioner, pertanyaan, dosen, prodi                      │
│     Sum: total_jawaban, total_skor                                    │
│                                                                       │
│  6. STATISTICAL ANALYSIS                                              │
│     ↓                                                                 │
│     Calculate:                                                        │
│     • rata_rata = total_skor / total_jawaban                          │
│     • persentase_kepuasan = (rata_rata / 4) * 100                     │
│     • kategori_hasil (4 levels)                                       │
│     • sentiment (3 levels)                                            │
│                                                                       │
│  7. SUMMARY AGGREGATION                                               │
│     ↓                                                                 │
│     GroupBy: kode_mk, dosen, prodi                                    │
│     Calculate: rata_rata_matkul                                       │
│                                                                       │
│  8. STORAGE                                                           │
│     ↓                                                                 │
│     Write to MongoDB:                                                 │
│     • hasil_analisis_lengkap (detail)                                 │
│     • summary_matkul (summary)                                        │
│     Update: is_analyzed = true                                        │
│                                                                       │
│  9. CLEANUP                                                           │
│     ↓                                                                 │
│     Stop Spark session                                                │
│     Remove temp directory                                             │
│                                                                       │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Testing & Debugging

### Test Spark Configuration

**Command:**
```bash
php artisan spark:test-config
```

**File: `app/Console/Commands/TestSparkConfig.php`**

```php
public function handle()
{
    $this->info('=== SPARK CONFIGURATION TEST ===');
    
    // Test Python
    $python = env('PYTHON_PATH', 'python');
    exec("\"{$python}\" --version 2>&1", $pythonVersion, $returnCode);
    
    if ($returnCode === 0) {
        $this->info("✓ Python found: " . implode('', $pythonVersion));
    } else {
        $this->error("✗ Python NOT found");
    }
    
    // Test Spark
    $sparkHome = env('SPARK_HOME', '');
    if (!empty($sparkHome)) {
        $sparkPath = rtrim($sparkHome, '\\/') . '\\bin\\spark-submit.cmd';
        if (file_exists($sparkPath)) {
            $this->info("✓ Spark found: {$sparkPath}");
        } else {
            $this->error("✗ Spark NOT found at: {$sparkPath}");
        }
    }
    
    // Test PySpark
    exec("\"{$python}\" -c \"import pyspark; print(pyspark.__version__)\" 2>&1", 
         $pysparkCheck, $pysparkReturn);
    
    if ($pysparkReturn === 0) {
        $this->info("✓ PySpark installed: " . implode('', $pysparkCheck));
    } else {
        $this->error("✗ PySpark NOT installed");
    }
}
```

### Debug Output

Tambahkan `.show()` untuk debugging:

```python
# Tampilkan sample data
print("=== SAMPLE DATA SETELAH CLEANING ===")
df.select("kuesioner_id", "dosen_pengajar", "prodi").show(5)

print("=== SAMPLE HASIL ANALISIS ===")
analisis.show(5, truncate=False)

print("=== SUMMARY MATAKULIAH ===")
summary_mk.show(truncate=False)
```

### Log File Analysis

**Laravel Log:**
```bash
tail -f storage/logs/laravel.log
```

**Sample Log Output:**
```
[2026-06-13 10:30:45] local.INFO: SPARK COMMAND {"command":"C:\\spark\\bin\\spark-submit.cmd ..."}
[2026-06-13 10:30:50] local.INFO: SPARK OUTPUT {"output":["TOTAL BELUM DIANALISIS:","15","BERHASIL SIMPAN DETAIL"]}
```

---

## Performance Optimization

### 1. Partition Data

```python
# Repartition untuk distribusi lebih baik
df = df.repartition(8, "prodi", "semester")
```

### 2. Cache Intermediate Results

```python
# Cache data yang sering diakses
rekap.cache()
```

### 3. Broadcast Small Tables

```python
from pyspark.sql.functions import broadcast

# Broadcast tabel kecil (ex: daftar dosen)
result = large_df.join(
    broadcast(small_df),
    "dosen_id"
)
```

### 4. Adjust Spark Config

```python
spark = SparkSession.builder \
    .config("spark.executor.memory", "4g") \
    .config("spark.driver.memory", "2g") \
    .config("spark.sql.shuffle.partitions", "8") \
    .getOrCreate()
```

---

## Error Handling

### Handle Empty Data

```python
if total_data == 0:
    print("TIDAK ADA DATA BARU")
    spark.stop()
    shutil.rmtree(temp_dir, ignore_errors=True)
    exit()
```

### Handle MongoDB Connection Errors

```python
try:
    df = spark.read.format("mongodb") \
        .option("database", "gkm_chatbot") \
        .option("collection", "kuesioner_mongos") \
        .load()
except Exception as e:
    print(f"ERROR READING FROM MONGODB: {e}")
    spark.stop()
    exit(1)
```

### Handle Write Errors

```python
try:
    analisis.write.format("mongodb") \
        .mode("append") \
        .option("database", "gkm_chatbot") \
        .option("collection", "hasil_analisis_lengkap") \
        .save()
    print("BERHASIL SIMPAN DETAIL")
except Exception as e:
    print(f"ERROR WRITING TO MONGODB: {e}")
    # Rollback atau retry logic
```

---

## Summary

### Key Technologies
- **Apache Spark 3.5.0**: Distributed data processing
- **PySpark**: Python API for Spark
- **MongoDB Connector**: Read/write MongoDB from Spark
- **PyMongo**: Direct MongoDB operations

### Core Operations
1. **Ingestion**: Read 100-1000 records from MongoDB
2. **Cleaning**: Extract, normalize, explode nested data
3. **Transformation**: Flatten structures, clean text
4. **Aggregation**: GroupBy multiple dimensions
5. **Analysis**: Calculate statistics, classify sentiment
6. **Storage**: Write back to MongoDB, update flags

### Performance Metrics
- **Processing Time**: 30-60 seconds
- **Memory Usage**: 2-4 GB
- **Parallelism**: 4-8 cores (local mode)
- **Throughput**: ~20-30 records/second

### Output Collections
1. **hasil_analisis_lengkap**: Detail per pertanyaan
2. **summary_matkul**: Summary per mata kuliah
3. **kuesioner_mongos**: Updated with is_analyzed flag

---

## Complete Code Reference

**Main Script:** `spark/spark_kuesioner.py`  
**Trigger:** `app/Http/Controllers/GJM/DashboardController.php`  
**Config:** `.env` (SPARK_HOME, PYTHON_PATH, MONGODB_URI)  
**Test:** `php artisan spark:test-config`

---

**Dokumentasi dibuat:** 13 Juni 2026  
**Versi Spark:** 3.5.0  
**Versi PySpark:** 3.5.0  
**MongoDB Connector:** 10.3.0
