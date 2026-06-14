# Agent Flow Diagram — PA3-KEL06-2026
> Pengganti Sequence Diagram untuk sistem berbasis AI Agent

---

## Gambaran Umum Sistem

Sistem ini adalah **Platform Manajemen Mutu Akademik** Institut Teknologi Del yang dioperasikan oleh dua peran:
- **GKM** (Gugus Kendali Mutu) — per Program Studi
- **GJM** (Gugus Jaminan Mutu) — level Fakultas

Arsitektur terdiri dari 6 layer yang saling terhubung:

```
┌─────────────────────────────────────────────────────────────────┐
│  LAYER 1: User Interface                                        │
│  Laravel Blade (port 8000) + Next.js (port 3000)               │
├─────────────────────────────────────────────────────────────────┤
│  LAYER 2: Controller & Route                                    │
│  Laravel Controllers (GKM/GJM/API)                             │
├─────────────────────────────────────────────────────────────────┤
│  LAYER 3: AI Agent Services                                     │
│  UnifiedAIService → ClaudeAIService → AIAgentService           │
├─────────────────────────────────────────────────────────────────┤
│  LAYER 4: Data & RAG Pipeline                                   │
│  RAGRetrievalService + VectorDatabaseService + AICacheService  │
├─────────────────────────────────────────────────────────────────┤
│  LAYER 5: External AI Providers                                 │
│  OpenAI → Groq → Gemini → OpenRouter → HuggingFace             │
├─────────────────────────────────────────────────────────────────┤
│  LAYER 6: Storage & Processing                                  │
│  MySQL + MongoDB Atlas + Apache Spark + Python OCR             │
└─────────────────────────────────────────────────────────────────┘
```

---

## FLOW 1 — Analisis Kuesioner (GKM)

> Alur pemrosesan data kuesioner mahasiswa menggunakan AI Agent dengan pipeline RAG

```
USER (GKM)
    │
    ▼
[Upload Excel / Ambil dari CIS API]
    │
    ├─── A. Upload Manual ──────────────────────────────────────┐
    │         │                                                  │
    │    POST /gkm/monitoring-kuesioner/store                   │
    │         │                                                  │
    │    MonitoringKuesioneController@store()                   │
    │         │                                                  │
    │    Simpan ke: MySQL → kuesioner_uploads (status=uploaded) │
    │         │                                                  │
    │    Simpan ke: MongoDB → kuesioner_mongos (analyzed=false) │
    │                                                           │
    └─── B. Sinkronisasi API CIS ───────────────────────────────┘
              │
         GET /gkm/monitoring-kuesioner/create-api
              │
         MonitoringKuesioneController@syncSemuaKuesioner()
              │
         Fetch dari: https://cis.del.ac.id/api
              │
              ▼
    ┌─────────────────────────────────────────────────────────┐
    │  SPARK PROCESSING PIPELINE                              │
    │  (Dipicu: tombol "Jalankan Analisis Spark" / Scheduler) │
    │                                                         │
    │  DashboardController@jalankanAnalisisSpark()            │
    │         │                                               │
    │  exec: spark-submit spark/spark_kuesioner.py            │
    │         │                                               │
    │  [1] Read MongoDB: kuesioner_mongos (is_analyzed=false) │
    │  [2] Clean: ekstrak dosen dari judul, normalisasi prodi  │
    │  [3] Transform: explode rekap, konversi skor Likert      │
    │       SS(4) S(3) CS(2) TS(1) → weighted_score           │
    │  [4] Aggregate: groupBy matakuliah, hitung rata-rata     │
    │  [5] Categorize: Sangat Baik / Baik / Cukup / Kurang    │
    │  [6] Sentiment: positive / neutral / negative            │
    │  [7] Write → MongoDB: hasil_analisis_lengkap             │
    │  [8] Write → MongoDB: summary_matkul                     │
    │  [9] Update: is_analyzed = true                          │
    └─────────────────────────────────────────────────────────┘
              │
              ▼
    ┌─────────────────────────────────────────────────────────┐
    │  AI AGENT RAG ANALYSIS (AIAgentService)                 │
    │  Dipicu: User klik "Analisis dengan AI"                  │
    │                                                         │
    │  POST /gkm/monitoring-kuesioner/{id}/reprocess          │
    │         │                                               │
    │  [STEP 1] Extract & Index                               │
    │    extractAndIndexKuesioneData($excelData)              │
    │    → Filter kolom Likert (TS/CS/S/SS ≥ 30%)            │
    │    → Buat metadata: Q1...Qn + distribusi jawaban        │
    │                                                         │
    │  [STEP 2] Retrieve Context                              │
    │    retrieveRelevantContext($indexedData)                │
    │    → overview, statistik_detail, sample_responses       │
    │                                                         │
    │  [STEP 3] Augment Prompt                               │
    │    augmentPromptWithContext($kuesioner, $context)       │
    │    → Tabel distribusi per pertanyaan                    │
    │    → Rumus perhitungan Likert                           │
    │    → Format output JSON yang diharapkan                 │
    │                                                         │
    │  [STEP 4] Generate dengan AI                            │
    │    callAI(systemMessage, augmentedPrompt, 2000 tokens)  │
    │         │                                               │
    │    ┌────▼────────────────────────────────────────────┐  │
    │    │  AI PROVIDER CHAIN                              │  │
    │    │  1. OpenAI gpt-4o-mini (PRIMARY)               │  │
    │    │     ↓ gagal / rate limit                       │  │
    │    │  2. Groq llama-3.3-70b-versatile              │  │
    │    │     ↓ gagal / rate limit                       │  │
    │    │  3. Gemini 1.5 Flash (FALLBACK)               │  │
    │    └────────────────────────────────────────────────┘  │
    │         │                                               │
    │  [STEP 5] Post-process                                  │
    │    → Override statistik dari data REAL (bukan AI)      │
    │    → Hitung index kepuasan: Σrata-rata / jumlah_Q      │
    │    → Identifikasi Q tertinggi & terendah                │
    │    → Generate poin_positif & area_perbaikan             │
    │                                                         │
    │  Output → Simpan ke: kuesioner_uploads.hasil_analisis  │
    └─────────────────────────────────────────────────────────┘
              │
              ▼
    [Dashboard Monitoring Kuesioner]
    Tampilkan: index kepuasan, distribusi jawaban, grafik,
    poin positif, area perbaikan, rekomendasi
```

---

## FLOW 2 — Generate Laporan Triwulan GJM (AI Chat + RAG + RAGAS)

> Alur pembuatan laporan triwulan GJM menggunakan multi-turn AI chat

```
USER (GJM)
    │
    ▼
GET /gjm/buat-laporan/triwulan/create
    │
    ▼
[Pilih template + periode triwulan]
    │
    ▼
POST /gjm/buat-laporan/triwulan/create-draft
    │
    LaporanTriwulanController@createDraft()
    │
    Buat record: MySQL → laporan_gjm (status=draft)
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  AI CHAT ASSISTANT LOOP (Multi-turn Conversation)            │
│                                                              │
│  POST /api/gjm/ai-prompt/triwulan                           │
│   atau                                                       │
│  POST /gjm/buat-laporan/triwulan/ai-prompt                  │
│       │                                                      │
│  LaporanTriwulanController@aiPrompt()                       │
│       │                                                      │
│  ┌────▼─────────────────────────────────────────────────┐   │
│  │  PRE-PROCESSING                                      │   │
│  │  1. Validasi prompt (min 5 karakter)                 │   │
│  │  2. Cek AICacheService (MongoDB) → cache hit?        │   │
│  │     ↓ miss                                           │   │
│  │  3. Ambil GKM monthly reports context               │   │
│  │  4. Proses file upload (jika ada):                   │   │
│  │     • Dokumen → TextExtractionService               │   │
│  │     • Gambar → OCRService (PaddleOCR Python)         │   │
│  │               → ImageContentValidationService       │   │
│  │               → Index ke VectorDatabaseService      │   │
│  │  5. Retrieve RAG context (VectorDB → laporan_gjm_ocr)│   │
│  └──────────────────────────────────────────────────────┘   │
│       │                                                      │
│  ┌────▼─────────────────────────────────────────────────┐   │
│  │  MESSAGE BUILDING                                    │   │
│  │  System: instruksi laporan triwulan + struktur 10 bag│   │
│  │  History: conversation_history (trimmed ~4000 tokens)│   │
│  │  User: file_context + OCR_text + RAG_context + prompt│   │
│  └──────────────────────────────────────────────────────┘   │
│       │                                                      │
│  ┌────▼─────────────────────────────────────────────────┐   │
│  │  AI GENERATION (UnifiedAIService@generateChat)       │   │
│  │                                                      │   │
│  │  messages[] → OpenAI gpt-4o-mini (PRIMARY)           │   │
│  │                  ↓ rate limit (429)                  │   │
│  │               Gemini 1.5 Flash (FALLBACK)            │   │
│  │                  ↓ gagal                             │   │
│  │               Error response ke user                │   │
│  │                                                      │   │
│  │  max_tokens: 8192, temperature: 0.7                  │   │
│  │  retry: 2x dengan delay 1 detik                      │   │
│  └──────────────────────────────────────────────────────┘   │
│       │                                                      │
│  ┌────▼─────────────────────────────────────────────────┐   │
│  │  POST-PROCESSING                                     │   │
│  │  1. Cache response ke AICacheService (MongoDB)       │   │
│  │  2. Log ke AIEvaluationService (tracking)            │   │
│  │  3. Return draft markdown ke frontend                │   │
│  └──────────────────────────────────────────────────────┘   │
│       │                                                      │
│  User bisa: review draft, minta perbaikan (loop ulang),      │
│  atau lanjut ke Generate Word                                │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
POST /gjm/buat-laporan/triwulan/save-preview
    │
    LaporanTriwulanController@savePreview()
    │
    Simpan sections + draft ke: laporan_gjm.ai_preview (DB)
    │
    ▼
POST /gjm/buat-laporan/triwulan (store)
    │
    LaporanTriwulanController@store() → mode=sync
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  WORD DOCUMENT GENERATION                                    │
│                                                              │
│  GenerateLaporanTriwulanJob@handle()                        │
│  → LaporanTriwulanService@generate()                        │
│       │                                                      │
│  [1] Get AI preview dari AIPreviewCacheService               │
│  [2] Parse sections: latar_belakang, dasar, tujuan,         │
│      ruang_lingkup, program_kerja, pelaksanaan,             │
│      hambatan, pemecahan_masalah, evaluasi, saran           │
│  [3] extractPlaceholdersFromAIPreview()                     │
│      → cleanMarkdown() → escape curly braces                │
│  [4] fixSplitPlaceholders() — fix XML word split            │
│      → mergeRunsInParagraphs() per paragraph                │
│  [5] PhpWord TemplateProcessor                              │
│      → setValue({PLACEHOLDER}, value) × 12 fields          │
│      → setImageValue(LAMPIRAN_GAMBAR) jika ada OCR          │
│  [6] saveAs() → storage/laporan_gjm/file.docx              │
│       │                                                      │
│  RAGAS EVALUATION (RAGASEvaluationService)                  │
│  → quickEvaluateVMTS(userMessage, aiDraft, contexts)        │
│  → Hitung: faithfulness, answer_relevancy,                  │
│    context_precision, context_recall, overall_score         │
│  → Simpan ke laporan_gjm (ragas_* columns)                  │
│       │                                                      │
│  Update: laporan_gjm.status = completed                     │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
Response: Download .docx langsung ke browser
```

---

## FLOW 3 — Reminder Agent (GKM → Dosen)

> Alur pengiriman reminder ke dosen menggunakan AI-generated message

```
TRIGGER: Scheduler (setiap 60 detik) atau User manual
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  REMINDER AGENT PIPELINE                                     │
│                                                              │
│  Kernel@schedule → ReminderAgentCommand                     │
│       atau                                                   │
│  User → POST /gkm/monitoring-rps/send-reminder              │
│       atau                                                   │
│  User → POST /gkm/monitoring-perkuliahan/reminder-materi/send│
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  IDENTIFIKASI DOSEN YANG PERLU DIINGATKAN            │   │
│  │  MonitoringRPSController@sendReminder()              │   │
│  │  → Query MySQL: dosen belum upload RPS               │   │
│  │  → Hubungkan dengan matakuliah & prodi               │   │
│  └──────────────────────────────────────────────────────┘   │
│       │                                                      │
│  ┌────▼─────────────────────────────────────────────────┐   │
│  │  AI MESSAGE GENERATION (AIAgentService)              │   │
│  │                                                      │   │
│  │  POST /gkm/monitoring-rps/generate-message           │   │
│  │  → generateReminderMessage($dosenList, $prodi, $type)│   │
│  │       │                                              │   │
│  │  buildReminderPrompt():                              │   │
│  │  → type: 'rps' / 'materi' / 'perwalian' / 'soal'   │   │
│  │  → Format: "Kepada Yth. {nama} - {role}"            │   │
│  │  → Poin: kesiapan pembelajaran, standar akreditasi   │   │
│  │  → 100-120 kata, bahasa formal                       │   │
│  │       │                                              │   │
│  │  callAI() → OpenAI / Groq (max 600 tokens)           │   │
│  │       │                                              │   │
│  │  Post-clean: hapus intro "Baiklah/Berikut..."        │   │
│  └──────────────────────────────────────────────────────┘   │
│       │                                                      │
│  ┌────▼─────────────────────────────────────────────────┐   │
│  │  PENGIRIMAN MULTI-CHANNEL                            │   │
│  │                                                      │   │
│  │  1. Email (Laravel Mail + SMTP Gmail)               │   │
│  │     EmailHelper@sendReminder()                      │   │
│  │                                                      │   │
│  │  2. WhatsApp (WaChat API)                           │   │
│  │     POST https://api.wachat-api.com/...             │   │
│  │     payload: { token, instance_id, number, message }│   │
│  │                                                      │   │
│  │  3. Log ke: MySQL → reminder_logs                    │   │
│  └──────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────┘
```

---

## FLOW 4 — Laporan Kuesioner AI (GKM → Generate Word)

> Alur generate laporan kuesioner menggunakan AI dengan RAG pattern

```
USER (GKM)
    │
    ▼
GET /gkm/laporan-kuesioner/create
    │
    Pilih: periode, jenis (UTS/UAS), template
    │
    ▼
POST /gkm/laporan-kuesioner/create-draft
    │
    LaporanKuesioneController@createDraft()
    │
    ▼
POST /gkm/laporan-kuesioner/ai-prompt
    │
    LaporanKuesioneController@aiPrompt()
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  LaporanKuesioneService (RAG Pipeline)                       │
│                                                              │
│  [STEP 1] collectKuesioneData($periode, $prodiId, $tipe)    │
│  → Query MySQL: kuesioner_uploads WHERE semester + jenis     │
│                                                              │
│  [STEP 2] aggregateStatistik($kuesioneList)                 │
│  → Hitung: total_responden, index_kepuasan_rata_rata        │
│                                                              │
│  [STEP 3] buildContext($aggregatedData, $periode)           │
│  → Tabel per tingkat: | Kode MK | Nama | Dosen | Indeks |   │
│                                                              │
│  [STEP 4] augmentPromptWithTemplate($template, $context)    │
│  → Embed template konten + data kuesioner                    │
│                                                              │
│  [STEP 5] UnifiedAIService@generateChat($messages)          │
│  → Generate JSON: ringkasan, trend, temuan, rekomendasi      │
│                                                              │
│  Output: Simpan ke laporan_kuesioner                        │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
POST /gkm/laporan-kuesioner/generate-word
    │
    PhpWord TemplateProcessor → Download .docx
```

---

## FLOW 5 — OCR Upload + AI Integration

> Alur pemrosesan gambar dengan OCR untuk konteks laporan

```
USER upload gambar (foto kegiatan, daftar hadir, screenshot)
    │
    ▼
POST /gjm/buat-laporan/triwulan/ai-prompt (dengan file_referensi[])
    │
    ▼
┌──────────────────────────────────────────────────────────────┐
│  IMAGE VALIDATION & OCR PIPELINE                             │
│                                                              │
│  [1] Simpan ke temp storage                                  │
│                                                              │
│  [2] ImageContentValidationService@validateImageRelevance()  │
│      → ClaudeAIService@analyzeImageContent() (Claude Vision) │
│      → Cek: apakah gambar relevan untuk laporan triwulan?    │
│      → confidence < 0.5: lolos, confidence ≥ 0.5: validasi  │
│      → Reject jika: selfie, meme, konten tidak relevan       │
│                                                              │
│  [3] OCRService@extractText() → Python OCR Service           │
│      POST http://python-ocr:5000/extract                     │
│      (PaddleOCR container, port 5000)                        │
│      → Response: { success, text, method, confidence }       │
│                                                              │
│  [4] VectorDatabaseService@indexDocument()                   │
│      → Simpan OCR text sebagai chunk                        │
│      → source_type: 'laporan_gjm_ocr', source_id: laporan_id│
│      → EmbeddingService → hash-based vector                  │
│                                                              │
│  [5] RAGRetrievalService@retrieveContext() saat AI prompt    │
│      → cosine similarity search di DocumentChunk             │
│      → top_k=5 chunk paling relevan                          │
│      → Tambahkan ke messages sebagai context                 │
└──────────────────────────────────────────────────────────────┘
    │
    ▼
Context OCR masuk ke AI prompt → AI generate laporan
    yang aware dengan konten gambar
```

---

## FLOW 6 — n8n Async Workflow (Opsional)

> Alur alternatif menggunakan n8n untuk pemrosesan laporan asinkron

```
USER
    │
    ▼
POST /gjm/buat-laporan/triwulan?mode=async
    │
    LaporanTriwulanController@store() → mode=async
    │
    ▼
GenerateLaporanTriwulanJob::dispatch($laporan->id)
→ Queue Worker (pa3_queue container)
    │
    ▼
                [OPSIONAL: via N8nService]
                N8nService@triggerLaporanGeneration()
                │
                POST http://n8n:5678/webhook/generate-laporan-gjm
                payload: { laporan_id, tipe, periode, callback_url }
                │
                ▼
           [n8n Workflow berjalan di background]
                │
                ▼
           POST /api/n8n/callback
           N8nCallbackController@laporanCallback()
           → Update: laporan_gjm.status = completed
           → Simpan: dokumen_hasil_path
```

---

## FLOW 7 — Dashboard Analytics (GJM + GKM)

> Alur pengambilan dan visualisasi data dashboard

```
USER akses Dashboard
    │
    ├── GKM Dashboard ──────────────────────────────────────────┐
    │   GET /gkm/dashboard                                      │
    │   GKMDashboardController@index()                          │
    │   → Query MySQL: monitoring RPS, perkuliahan              │
    │   → Query MongoDB: hasil_analisis_lengkap, summary_matkul │
    │   → Hitung % kepatuhan per prodi                          │
    │                                                           │
    │   AJAX: GET /api/gkm/dashboard                           │
    │   GKMDashboardApiController@index()                       │
    │   → JSON: { stats, charts, recent_activities }            │
    │                                                           │
    └── GJM Dashboard ──────────────────────────────────────────┘
        GET /gjm/dashboard
        GJMDashboardController@index()
        → Query laporan GJM status
        → Query GKM compliance data
        │
        AJAX: GET /api/gjm/dashboard
        GJMDashboardApiController@index()
        → Cache: file-based, TTL configured
        → JSON: { laporan_stats, quality_index, prodi_comparison }
        │
        POST /api/gjm/dashboard/clear-cache
        → Hapus cache dashboard
```

---

## AI Provider Fallback Chain (Global)

```
Request AI Generation
        │
        ▼
┌───────────────────────────────────────────────────────────┐
│  UnifiedAIService (ENTRY POINT)                           │
│                                                           │
│  1. Cek Cache (MongoDB AIResponseCacheMongo)              │
│     ↓ miss                                               │
│  2. OpenAI gpt-4o-mini                                   │
│     LLM_API_KEY + https://api.openai.com/v1              │
│     → retry 3x dengan exponential backoff                 │
│     ↓ rate limit (429) / quota habis                     │
│  3. Gemini 1.5 Flash (FALLBACK)                          │
│     GeminiAIService → googleapis.com                      │
│     ↓ gagal                                              │
│  4. Error: "Semua layanan AI tidak tersedia"             │
│                                                           │
│  [Parallel via ClaudeAIService]                          │
│  1. Anthropic Claude 3.5 Sonnet (jika ANTHROPIC_API_KEY) │
│     ↓ gagal                                              │
│  2. Groq llama-3.3-70b-versatile                        │
│     GROQ_API_KEY + https://api.groq.com/openai/v1        │
│     ↓ rate limit / request too large                     │
│  3. OpenRouter free models (6 model dicoba bergantian)   │
│     meta-llama/llama-3.2-3b-instruct:free                │
│     microsoft/phi-3-mini-128k-instruct:free              │
│     google/gemma-2-9b-it:free, dst.                      │
│     ↓ semua gagal                                        │
│  4. HuggingFace Inference API                            │
│     ↓ gagal                                              │
│  5. Fallback message: "Silakan coba lagi nanti"         │
└───────────────────────────────────────────────────────────┘
        │
        ▼
  Response ke user + Cache ke MongoDB
```

---

## Arsitektur Infrastructure (Docker Compose)

```
Internet
    │
    ▼
┌─────────────────────────────────────────────────────────────────────┐
│  DOCKER NETWORK: pa3_network                                        │
│                                                                     │
│  ┌────────────────┐    ┌────────────────┐    ┌──────────────────┐   │
│  │  pa3_laravel   │    │  pa3_nextjs    │    │ pa3_python_ocr   │   │
│  │  port: 8000    │    │  port: 3000    │    │  port: 5000      │   │
│  │  (PHP + Nginx) │    │  (Frontend)    │    │  (PaddleOCR)     │   │
│  └───────┬────────┘    └────────────────┘    └──────────────────┘   │
│          │                                                          │
│  ┌───────▼────────┐    ┌────────────────┐    ┌──────────────────┐   │
│  │  pa3_mysql     │    │  pa3_queue     │    │  pa3_scheduler   │   │
│  │  port: 3306    │    │  (Queue Worker)│    │  (Cron tiap 60s) │   │
│  │  (MySQL 8.0)   │    │  queue:work   │    │  schedule:run    │   │
│  └────────────────┘    └────────────────┘    └──────────────────┘   │
│                                                                     │
│  ┌────────────────┐                                                 │
│  │  pa3_phpmyadmin│                                                 │
│  │  port: 8080    │                                                 │
│  └────────────────┘                                                 │
└─────────────────────────────────────────────────────────────────────┘
    │                          │
    ▼                          ▼
MongoDB Atlas             External APIs
(gkm_chatbot DB)          - OpenAI API
- kuesioner_mongos        - Groq API
- hasil_analisis_lengkap  - CIS API (cis.del.ac.id)
- summary_matkul          - WaChat API (WhatsApp)
- ai_response_cache       - n8n webhook (opsional)
- document_chunks
```

---

## RAGAS Evaluation Flow

> Evaluasi kualitas output AI menggunakan RAGAS metrics

```
Setelah AI Generate Laporan (Triwulan / Semester / VMTS)
    │
    ▼
RAGASEvaluationService@quickEvaluateVMTS(
    $userMessage,   // pertanyaan user
    $aiDraft,       // jawaban AI
    $contexts[]     // chunk dokumen yang digunakan
)
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│  HEURISTIC EVALUATION (no ground truth needed)             │
│                                                             │
│  1. Faithfulness                                           │
│     = proporsi klaim AI yang ada di contexts               │
│     Range: 0.0 - 1.0                                       │
│                                                             │
│  2. Answer Relevancy                                        │
│     = kemiripan jawaban dengan pertanyaan (TF-IDF style)   │
│     Range: 0.0 - 1.0                                       │
│                                                             │
│  3. Context Precision                                        │
│     = proporsi context yang benar-benar digunakan AI        │
│     Range: 0.0 - 1.0                                        │
│                                                             │
│  4. Context Recall                                          │
│     = cakupan jawaban vs total context                      │
│     Range: 0.0 - 1.0                                        │
│                                                             │
│  5. Context Relevancy                                       │
│     = relevansi context terhadap pertanyaan                 │
│     Range: 0.0 - 1.0                                        │
│                                                             │
│  Overall Score = rata-rata weighted semua metrik            │
└─────────────────────────────────────────────────────────────┘
    │
    ▼
Simpan ke: laporan_gjm
    ragas_faithfulness, ragas_answer_relevancy,
    ragas_context_precision, ragas_context_recall,
    ragas_overall_score, ragas_evaluated_at
    │
    ▼
Tampil di: /gjm/model-evaluation
ModelEvaluationController@index()
→ Grafik tren skor per laporan, perbandingan model
```

---

## Ringkasan Komponen Agent

| Komponen | Peran | Dipanggil Oleh |
|---|---|---|
| `AIAgentService` | Agent analisis kuesioner (RAG 4-step) | MonitoringKuesioneController |
| `UnifiedAIService` | Router AI multi-provider dengan fallback | Semua service yang butuh AI |
| `ClaudeAIService` | Chat AI dengan 5-level fallback + Vision | GJM controllers |
| `GeminiAIService` | Fallback AI (free quota 1500/hari) | UnifiedAIService |
| `RAGRetrievalService` | Semantic search dari vector DB | LaporanTriwulanController |
| `VectorDatabaseService` | Indexing & cosine similarity search | OCR flow, template indexing |
| `AICacheService` | Cache prompt-response (MongoDB) | LaporanTriwulanController |
| `AIPreviewCacheService` | Cache draft AI per laporan | LaporanTriwulanService |
| `RAGASEvaluationService` | Evaluasi kualitas AI (5 metrik) | LaporanTriwulanService |
| `OCRService` | Ekstrak teks dari gambar (PaddleOCR) | LaporanTriwulanController |
| `ImageContentValidationService` | Validasi relevansi gambar | LaporanTriwulanController |
| `N8nService` | Trigger async workflow via webhook | LaporanGJM store (mode=async) |
| `LaporanTriwulanService` | Orchestrasi generate Word + RAGAS | GenerateLaporanTriwulanJob |
| `LaporanKuesioneService` | RAG pipeline laporan kuesioner | LaporanKuesioneController |

---

*Dokumen ini dibuat otomatis berdasarkan analisis kode sumber project PA3-KEL06-2026*
*Last updated: Juni 2026*
