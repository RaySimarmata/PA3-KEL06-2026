# Apache Spark - Quick Reference

## 📍 File Locations

| Component | Path |
|-----------|------|
| Main Spark Script | `spark/spark_kuesioner.py` |
| Laravel Trigger | `app/Http/Controllers/GJM/DashboardController.php` |
| Test Command | `app/Console/Commands/TestSparkConfig.php` |
| Full Documentation | `docs/SPARK_IMPLEMENTATION_DETAIL.md` |
| Architecture Doc | `docs/ARSITEKTUR_PENGOLAHAN_KUESIONER.md` |

---

## 🚀 Quick Start

### 1. Run Spark Analysis

**Via Web Dashboard:**
```
Login → Dashboard GJM → Click "Jalankan Analisis Spark"
```

**Via Command Line:**
```bash
cd d:\New folder\PA3-KEL06-2026
spark-submit --packages org.mongodb.spark:mongo-spark-connector_2.12:10.3.0 spark/spark_kuesioner.py
```

**Via Laravel (Programmatic):**
```php
app(DashboardController::class)->jalankanAnalisisSpark();
```

### 2. Test Configuration

```bash
php artisan spark:test-config
```

---

## 📊 Data Flow

```
CIS API / Excel Upload
    ↓
MySQL: kuesioner_uploads
    ↓
MongoDB: kuesioner_mongos (is_analyzed=false)
    ↓
Apache Spark Processing
    ↓
MongoDB: hasil_analisis_lengkap + summary_matkul
    ↓
Dashboard Web (Charts & Reports)
```

---

## 🔧 6 Core Functions

### 1. Data Ingestion
```python
df = spark.read.format("mongodb") \
    .option("database", "gkm_chatbot") \
    .option("collection", "kuesioner_mongos") \
    .load()
```

### 2. Data Cleaning
```python
df = df.withColumn("dosen_pengajar", 
    regexp_extract(col("judul_kuesioner"), r"\(([A-Z]{3})\/[^()]*\)$", 1))
```

### 3. Data Transformation
```python
detail = rekap.select(
    explode(col("rekap.rincian_jawaban")).alias("jawaban")
)
```

### 4. Data Aggregation
```python
analisis = hasil.groupBy(...).agg(
    sum("jumlah").alias("total_jawaban"),
    sum("weighted_score").alias("total_skor")
)
```

### 5. Statistical Analysis
```python
analisis = analisis.withColumn("rata_rata",
    round(col("total_skor") / col("total_jawaban"), 2))
```

### 6. Data Storage
```python
analisis.write.format("mongodb") \
    .mode("append") \
    .option("collection", "hasil_analisis_lengkap") \
    .save()
```

---

## 📈 Likert Score Conversion

| Jawaban | Skor | Label |
|---------|------|-------|
| 1 (SS)  | 4    | Sangat Setuju |
| 2 (S)   | 3    | Setuju |
| 3 (CS)  | 2    | Cukup Setuju |
| 4 (TS)  | 1    | Tidak Setuju |
| 5 (STS) | 0    | Sangat Tidak Setuju |

**Code:**
```python
hasil = hasil.withColumn("skor",
    when(col("jawaban") == 1, 4)
    .when(col("jawaban") == 2, 3)
    .when(col("jawaban") == 3, 2)
    .when(col("jawaban") == 4, 1)
    .when(col("jawaban") == 5, 0)
)
```

---

## 😊 Sentiment Analysis

| Persentase | Sentiment | Kategori |
|------------|-----------|----------|
| ≥ 85%      | positive  | Sangat Baik |
| 60-84%     | neutral   | Baik |
| < 60%      | negative  | Perlu Perbaikan |

**Code:**
```python
analisis = analisis.withColumn("sentiment",
    when(col("persentase_kepuasan") >= 85, "positive")
    .when(col("persentase_kepuasan") >= 60, "neutral")
    .otherwise("negative")
)
```

---

## ⚙️ Configuration

### .env Setup
```env
SPARK_HOME=C:\spark
PYTHON_PATH=C:\Python312\python.exe
MONGODB_URI=mongodb://user:pass@cluster.mongodb.net/gkm_chatbot
```

### Dependencies
```bash
# Python
pip install pyspark==3.5.0 pymongo==4.6.0

# PHP
composer require mongodb/laravel-mongodb
```

---

## 📊 Performance

| Metric | Value |
|--------|-------|
| Processing Time | 30-60 seconds |
| Memory Usage | 2-4 GB |
| Records/Batch | 100-1000 |
| Throughput | 20-30 records/sec |

---

## 🐛 Common Issues

### Issue 1: Spark Not Found
```bash
✗ Spark NOT found at: C:\spark\bin\spark-submit.cmd
```
**Solution:**
1. Download Spark from https://spark.apache.org/downloads.html
2. Extract to `C:\spark`
3. Set `SPARK_HOME=C:\spark` in `.env`

### Issue 2: PySpark Not Installed
```bash
✗ PySpark NOT installed
```
**Solution:**
```bash
pip install pyspark==3.5.0
```

### Issue 3: MongoDB Connection Failed
```
ERROR READING FROM MONGODB: Connection refused
```
**Solution:**
1. Check `MONGODB_URI` in `.env`
2. Verify MongoDB Atlas IP whitelist
3. Test connection: `mongosh "mongodb://..."`

### Issue 4: No Data to Process
```
TIDAK ADA DATA BARU
```
**Solution:**
- Pastikan ada data dengan `is_analyzed=false` di MongoDB
- Check collection: `db.kuesioner_mongos.find({is_analyzed: false})`

---

## 📖 Full Documentation

For complete implementation details, see:
- **SPARK_IMPLEMENTATION_DETAIL.md** - All code with explanations
- **ARSITEKTUR_PENGOLAHAN_KUESIONER.md** - System architecture

---

**Last Updated:** 13 Juni 2026
