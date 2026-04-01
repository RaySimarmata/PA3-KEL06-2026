# Setup RAG System untuk Laporan Kuisioner (AI)

## 📋 Gambaran Besar (High Level Architecture)

Sistem ini menggunakan **RAG (Retrieval-Augmented Generation)** dengan **LLM lokal/free model** untuk generate laporan bulanan otomatis.

### Alur Kerja:
```
Front-End → Backend (Laravel) → RAG Processing → LLM → Laporan Jadi
```

### Komponen Utama:

1. **File Processor Service** - Mengolah file yang diupload
2. **Text Extraction Layer** - Mengambil isi teks dari kuesioner
3. **Chunking Service** - Memecah teks menjadi potongan kecil
4. **Embedding Service** - Mengubah chunks menjadi vector embeddings
5. **Vector Database** - Menyimpan embeddings (menggunakan MySQL/PostgreSQL)
6. **RAG Retrieval Engine** - Mencari chunks paling relevan
7. **LLM Generator** - Generate laporan menggunakan context dari RAG

---

## 🚀 Quick Start

### Option 1: Menggunakan Ollama (RECOMMENDED - 100% Gratis & Lokal)

#### Step 1: Install Ollama

**Windows:**
```powershell
# Download dari https://ollama.ai/download
# Atau gunakan winget
winget install Ollama.Ollama
```

**Linux/Mac:**
```bash
curl -fsSL https://ollama.ai/install.sh | sh
```

#### Step 2: Pull Model yang Diperlukan

```bash
# LLM untuk generate laporan (pilih salah satu)
ollama pull llama3:8b-instruct      # Recommended (4.7GB)
ollama pull mistral:7b-instruct     # Alternative (4.1GB)
ollama pull deepseek-coder:7b       # Alternative (3.8GB)

# Embedding model untuk RAG (pilih salah satu)
ollama pull nomic-embed-text        # Recommended (274MB)
ollama pull bge-small-en            # Alternative (134MB)
```

#### Step 3: Verifikasi Ollama Berjalan

```bash
# Test LLM
ollama run llama3:8b-instruct "Hello, how are you?"

# Test Embedding
curl http://localhost:11434/api/embeddings -d '{
  "model": "nomic-embed-text",
  "prompt": "Test embedding"
}'
```

#### Step 4: Konfigurasi .env

```env
# LLM Configuration
LLM_PROVIDER=ollama
OLLAMA_BASE_URL=http://localhost:11434/v1
OLLAMA_MODEL=llama3:8b-instruct

# RAG Configuration
VECTOR_DB_ENABLED=true
EMBEDDING_PROVIDER=local
EMBEDDING_MODEL=nomic-embed-text
EMBEDDING_BASE_URL=http://localhost:11434

# RAG Parameters
CHUNK_SIZE=500
CHUNK_OVERLAP=50
RAG_TOP_K=10
RAG_SIMILARITY_THRESHOLD=0.3
```

---

### Option 2: Menggunakan GROQ API (Cloud - Gratis dengan Limit)

#### Step 1: Dapatkan API Key

1. Daftar di https://console.groq.com
2. Buat API Key baru
3. Copy API Key

#### Step 2: Konfigurasi .env

```env
# LLM Configuration
LLM_PROVIDER=groq
GROQ_API_KEY=your_groq_api_key_here
GROQ_BASE_URL=https://api.groq.com/openai/v1
GROQ_MODEL=llama-3.3-70b-versatile

# RAG Configuration (Simple Mode - No Vector DB)
VECTOR_DB_ENABLED=false
EMBEDDING_PROVIDER=simple
```

---

## 📊 Struktur Laporan yang Dihasilkan

LLM akan mengikuti format tetap:

1. **Ringkasan Eksekutif**
   - Overview singkat
   - Highlight positif & negatif
   - Trend

2. **Statistik Utama**
   - Total kuesioner
   - Total responden
   - Index kepuasan rata-rata
   - Persen kepuasan

3. **Analisis Per Kuesioner**
   - Tabel ringkasan
   - Kategori: Sangat Baik, Baik, Cukup, Kurang

4. **Top 5 Kuesioner Tertinggi**
   - List dengan penjelasan

5. **Top 5 Kuesioner Terendah**
   - List dengan analisis masalah

6. **Insight Utama**
   - 5-7 insight penting
   - Pola yang terlihat
   - Temuan menarik

7. **Rekomendasi Strategis**
   - 5-7 rekomendasi actionable
   - Prioritas: High, Medium, Low
   - Timeline implementasi

---

## 🔧 Cara Kerja RAG System

### Mode 1: Simple RAG (VECTOR_DB_ENABLED=false)

```
1. Collect Data → Ambil semua kuesioner completed
2. Aggregate → Hitung statistik agregat
3. Build Context → Gabungkan semua data jadi text
4. Augment Prompt → Tambahkan template & context
5. Generate → Call LLM untuk generate laporan
```

**Kelebihan:**
- Setup mudah
- Tidak perlu embedding model
- Cepat untuk dataset kecil

**Kekurangan:**
- Tidak ada semantic search
- Context terbatas oleh token limit
- Kurang akurat untuk dataset besar

### Mode 2: Advanced RAG (VECTOR_DB_ENABLED=true)

```
1. Index Phase:
   - Extract text dari kuesioner
   - Chunk text (500 chars dengan overlap 50)
   - Generate embeddings untuk setiap chunk
   - Store di Vector Database (document_chunks table)

2. Retrieval Phase:
   - User request laporan
   - Generate query embedding
   - Search top-K similar chunks (cosine similarity)
   - Filter by similarity threshold (>0.3)

3. Generation Phase:
   - Build enriched context dari retrieved chunks
   - Augment prompt dengan template
   - Call LLM dengan context yang relevan
   - Generate laporan
```

**Kelebihan:**
- Semantic search yang akurat
- Bisa handle dataset besar
- Context lebih relevan
- Deep insights

**Kekurangan:**
- Setup lebih kompleks
- Butuh embedding model
- Proses indexing memakan waktu

---

## 🎯 Cara Menggunakan

### 1. Upload Kuesioner & Proses dengan AI

```
Menu: GKM → Monitoring Kuesioner → Upload Kuesioner
- Upload file Excel kuesioner
- Sistem akan otomatis proses dengan AI
- Hasil analisis tersimpan di database
```

### 2. Generate Laporan Bulanan

```
Menu: GKM → Laporan Kuisioner (AI) → Buat Laporan Baru
- Pilih periode (bulan & tahun)
- Pilih template (opsional)
- Klik "Generate Laporan"
- Sistem akan proses di background (queue)
- Refresh halaman untuk lihat progress
```

### 3. Download Laporan

```
- Setelah status "Selesai"
- Klik tombol "Download Word"
- File .docx akan terdownload
```

---

## 🔍 Troubleshooting

### Ollama tidak bisa connect

```bash
# Check apakah Ollama running
curl http://localhost:11434/api/tags

# Restart Ollama
# Windows: Restart dari System Tray
# Linux/Mac:
systemctl restart ollama
```

### Model tidak ditemukan

```bash
# List model yang terinstall
ollama list

# Pull model yang diperlukan
ollama pull llama3:8b-instruct
ollama pull nomic-embed-text
```

### Embedding generation gagal

```env
# Fallback ke simple embedding
EMBEDDING_PROVIDER=simple
```

### LLM response tidak valid JSON

- Cek log di `storage/logs/laravel.log`
- Pastikan model yang digunakan support instruction following
- Coba model lain (mistral, deepseek)

### Queue tidak jalan

```bash
# Jalankan queue worker
php artisan queue:work --tries=3

# Atau gunakan Task Scheduler (Windows)
# Lihat file: setup_task_scheduler.ps1
```

---

## 📈 Performance Tips

### 1. Optimasi Chunking

```env
# Untuk dokumen panjang
CHUNK_SIZE=800
CHUNK_OVERLAP=100

# Untuk dokumen pendek
CHUNK_SIZE=300
CHUNK_OVERLAP=30
```

### 2. Optimasi Retrieval

```env
# Lebih banyak context (lebih lambat tapi lebih akurat)
RAG_TOP_K=15
RAG_SIMILARITY_THRESHOLD=0.2

# Lebih sedikit context (lebih cepat tapi kurang akurat)
RAG_TOP_K=5
RAG_SIMILARITY_THRESHOLD=0.4
```

### 3. Optimasi LLM

```bash
# Gunakan model yang lebih kecil untuk speed
ollama pull llama3:8b-instruct  # Fast

# Atau model yang lebih besar untuk quality
ollama pull llama3:70b-instruct # Slow but accurate
```

---

## 🎓 Rekomendasi Model

### Untuk LLM (Generate Laporan):

| Model | Size | Speed | Quality | Rekomendasi |
|-------|------|-------|---------|-------------|
| llama3:8b-instruct | 4.7GB | ⚡⚡⚡ | ⭐⭐⭐⭐ | ✅ Best Balance |
| mistral:7b-instruct | 4.1GB | ⚡⚡⚡ | ⭐⭐⭐ | Good Alternative |
| deepseek-coder:7b | 3.8GB | ⚡⚡⚡ | ⭐⭐⭐ | For Technical |
| llama3:70b-instruct | 40GB | ⚡ | ⭐⭐⭐⭐⭐ | Best Quality |

### Untuk Embeddings (RAG):

| Model | Size | Speed | Quality | Rekomendasi |
|-------|------|-------|---------|-------------|
| nomic-embed-text | 274MB | ⚡⚡⚡ | ⭐⭐⭐⭐ | ✅ Best Balance |
| bge-small-en | 134MB | ⚡⚡⚡⚡ | ⭐⭐⭐ | Fastest |
| intfloat/e5-base | 438MB | ⚡⚡ | ⭐⭐⭐⭐ | Most Accurate |

---

## 📝 Kesimpulan

Sistem RAG ini memberikan:

✅ **Data-driven**: Laporan berdasarkan data nyata, bukan halusinasi  
✅ **Modular**: Mudah ganti model/DB/embedding  
✅ **Gratis**: Bisa pakai model lokal (Ollama)  
✅ **Akurat**: RAG memastikan context yang relevan  
✅ **Scalable**: Bisa handle banyak kuesioner  

Untuk pertanyaan lebih lanjut, silakan hubungi tim development.
