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

# =========================
# TEMP DIR
# =========================
temp_dir = tempfile.mkdtemp()

# =========================
# MONGO URI
# =========================
MONGO_URI = "mongodb://dangbel:XPOWkcmfJM5IRiww@ac-pvsjuiu-shard-00-00.1k8c4bj.mongodb.net:27017,ac-pvsjuiu-shard-00-01.1k8c4bj.mongodb.net:27017,ac-pvsjuiu-shard-00-02.1k8c4bj.mongodb.net:27017/?ssl=true&replicaSet=atlas-y2p335-shard-0&authSource=admin&appName=Cluster0"

# =========================
# CREATE SPARK
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

spark.sparkContext.setLogLevel("ERROR")

# =========================
# READ DATA MONGO
# =========================
pipeline = """
[
    {
        "$match": {
            "is_analyzed": false
        }
    }
]
"""

df = spark.read \
    .format("mongodb") \
    .option("database", "gkm_chatbot") \
    .option("collection", "kuesioner_mongos") \
    .option("aggregation.pipeline", pipeline) \
    .load()

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

if total_data == 0:
    print("TIDAK ADA DATA BARU")
    spark.stop()
    shutil.rmtree(temp_dir, ignore_errors=True)
    exit()

# =========================
# AMBIL DOSEN DARI JUDUL
# contoh:
# (AMS/D4TRPL)
# =========================
df = df.withColumn(
    "dosen_pengajar",
    regexp_extract(
        col("judul_kuesioner"),
        r"\(([A-Z]{3})\/[^()]*\)$",
        1
    )
)

# =========================
# EXPLODE PRODI
# =========================
df = df.withColumn(
    "prodi_item",
    explode_outer(col("prodi"))
)

df = df.withColumn(
    "prodi",
    col("prodi_item")
)

df = df.withColumn(
    "jenis_kuesioner", 
    col("jenis_kuesioner")
    )

# =========================
# EXPLODE REKAPITULASI
# =========================
rekap = df.select(
    col("kuesioner_id"),
    col("kode_mk"),
    col("judul_kuesioner"),
    col("dosen_pengajar"),
    col("jenis_kuesioner"),
    # col("kode_prodi"),
    col("prodi"),
    col("periode").alias("tahun"),
    col("semester"),
    explode(col("raw_data.statistik.rekapitulasi")).alias("rekap")
)

# =========================
# EXPLODE RINCIAN JAWABAN
# =========================
detail = rekap.select(
    col("kuesioner_id"),
    col("kode_mk"),
    col("judul_kuesioner"),
    col("dosen_pengajar"),
    col("jenis_kuesioner"),
    # col("kode_prodi"),
    col("prodi"),
    col("tahun"),
    col("semester"),

    regexp_replace(
        col("rekap.pertanyaan"),
        "<[^>]+>",
        ""
    ).alias("pertanyaan"),

    col("rekap.total_suara")
        .cast("int")
        .alias("total_suara"),

    explode(
        col("rekap.rincian_jawaban")
    ).alias("jawaban")
)

# =========================
# AMBIL NILAI & JUMLAH
# =========================
hasil = detail.select(
    col("kuesioner_id"),
    col("kode_mk"),
    col("judul_kuesioner"),
    col("dosen_pengajar"),
    col("jenis_kuesioner"),
    # col("kode_prodi"),
    col("prodi"),
    col("tahun"),
    col("semester"),
    col("pertanyaan"),
    col("total_suara"),

    col("jawaban.jawaban")
        .cast("int")
        .alias("jawaban"),

    col("jawaban.jumlah")
        .cast("int")
        .alias("jumlah")
)

# =========================
# KONVERSI SKOR
# 1 = 4
# 2 = 3
# 3 = 2
# 4 = 1
# 5 = 0
# =========================
hasil = hasil.withColumn(
    "skor",

    when(col("jawaban") == 1, 4)
    .when(col("jawaban") == 2, 3)
    .when(col("jawaban") == 3, 2)
    .when(col("jawaban") == 4, 1)
    .when(col("jawaban") == 5, 0)
    .otherwise(0)
)

# =========================
# HITUNG WEIGHTED SCORE
# =========================
hasil = hasil.withColumn(
    "weighted_score",
    col("skor") * col("jumlah")
)

# =========================
# ANALISIS PER PERTANYAAN
# =========================
analisis = hasil.groupBy(
    "kuesioner_id",
    "kode_mk",
    "judul_kuesioner",
    "dosen_pengajar",
    "jenis_kuesioner",
    "prodi",
    # col("kode_prodi"),
    "tahun",
    "semester",
    "pertanyaan",
    "total_suara"
).agg(

    sum("jumlah")
        .alias("total_jawaban"),

    sum("weighted_score")
        .alias("total_skor")
)

# =========================
# HITUNG RATA-RATA
# =========================
analisis = analisis.withColumn(
    "rata_rata",

    round(
        col("total_skor") / col("total_jawaban"),
        2
    )
)

# =========================
# HITUNG PERSENTASE
# =========================
analisis = analisis.withColumn(
    "persentase_kepuasan",

    round(
        (col("rata_rata") / 4) * 100,
        2
    )
)

# =========================
# KATEGORI HASIL
# =========================
analisis = analisis.withColumn(
    "kategori_hasil",

    when(col("rata_rata") >= 3.1, "Sangat Baik")
    .when(col("rata_rata") >= 2.1, "Baik")
    .when(col("rata_rata") >= 1.1, "Cukup")
    .otherwise("Kurang")
)

# =========================
# LABEL SENTIMENT
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

# =========================
# TIMESTAMP
# =========================
analisis = analisis.withColumn(
    "processed_at",
    current_timestamp()
)

# =========================
# TAMPILKAN HASIL
# =========================
print("HASIL ANALISIS:")
analisis.show(
    truncate=False
)

# =========================
# SUMMARY PER MATKUL
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
        sum("total_skor")
        / sum("total_jawaban"),
        2
    ).alias("rata_rata_matkul")
)

print("SUMMARY MATKUL:")
summary_mk.show(
    truncate=False
)

# =========================
# SIMPAN HASIL DETAIL
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

# =========================
# SIMPAN SUMMARY MATKUL
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


# =========================
# UPDATE STATUS ANALISIS
# =========================
client = MongoClient(MONGO_URI)

db = client["gkm_chatbot"]

db.kuesioner_mongos.update_many(
    {
        "kuesioner_id": {
            "$in": kuesioner_ids
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
# =========================
# STOP SPARK
# =========================
spark.stop()

# =========================
# HAPUS TEMP
# =========================
try:

    shutil.rmtree(
        temp_dir,
        ignore_errors=True
    )

    print("TEMP DIR DIHAPUS")

except Exception as e:

    print(
        "GAGAL HAPUS TEMP:",
        e
    )