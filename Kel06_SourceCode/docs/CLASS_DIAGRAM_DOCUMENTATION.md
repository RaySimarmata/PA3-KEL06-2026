# CLASS DIAGRAM DOCUMENTATION - PA3 KEL06 2026
# SISTEM PENJAMINAN MUTU AKADEMIK (GKM & GJM)

**Versi:** 1.0  
**Tanggal:** 15 Juni 2026  
**Tim:** Kelompok 06  

---

## DAFTAR ISI

1. [Executive Summary](#executive-summary)
2. [Arsitektur Sistem](#arsitektur-sistem)
3. [Domain Models](#domain-models)
4. [Service Layer](#service-layer)
5. [Controller Layer](#controller-layer)
6. [Background Jobs](#background-jobs)
7. [External Integrations](#external-integrations)
8. [Diagram UML](#diagram-uml)

---

## EXECUTIVE SUMMARY

Sistem ini adalah **Quality Assurance Management System** berbasis Laravel yang mengintegrasikan:

- **RAG (Retrieval Augmented Generation)** untuk analisis cerdas
- **RAGAS Evaluation** untuk quality metrics
- **Multi-provider AI** (Groq, Claude, Gemini, OpenRouter)
- **Vector Database** untuk semantic search
- **OCR Processing** untuk ekstraksi dokumen
- **External API** (Library CIS DEL, WhatsApp)

### Modul Utama
1. **GKM (Gugus Kendali Mutu)**: Quality assurance tingkat program studi
2. **GJM (Gugus Jaminan Mutu)**: Quality assurance tingkat institusi
3. **AI Assistant**: Automated report generation dengan RAG

---

## CATATAN PENTING

### Komponen Tidak Aktif

#### N8N Workflow Automation
File-file terkait N8N masih ada dalam kode namun **TIDAK AKTIF** digunakan dalam sistem:

**File yang ada tapi tidak digunakan:**
- `app/Services/N8nService.php` - Service untuk integrasi N8N
- `app/Http/Controllers/API/N8nCallbackController.php` - Callback handler
- Route: `POST /api/n8n/callback` - Webhook endpoint

**Alasan:**
Fungsionalitas yang sebelumnya direncanakan untuk N8N telah diimplementasikan langsung menggunakan:
- Laravel Queue Jobs untuk asynchronous processing
- Background jobs untuk report generation
- Direct AI service integration tanpa intermediate workflow

**Status:** Deprecated/Legacy code - dapat dihapus di future cleanup

---

## ARSITEKTUR SISTEM

### Arsitektur Tingkat Tinggi

```
┌─────────────────────────────────────────────────────────────┐
│                      PRESENTATION LAYER                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ GKM Module   │  │ GJM Module   │  │ Auth Module  │      │
│  │ Controllers  │  │ Controllers  │  │ Controllers  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                      SERVICE LAYER                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ AI Services  │  │ RAG Services │  │ Report Gen   │      │
│  │              │  │              │  │ Services     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Document Svc │  │ External API │  │ WhatsApp Svc │      │
│  │              │  │ Services     │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                      DATA LAYER                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Eloquent     │  │ MongoDB      │  │ Vector DB    │      │
│  │ Models       │  │ Models       │  │ (Embeddings) │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                  EXTERNAL INTEGRATIONS                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Library API  │  │ LLM Providers│  │ WhatsApp API │      │
│  │ (CIS DEL)    │  │ (Multi)      │  │ (WACHAT)     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│  ┌──────────────┐                                          │
│  │ Tesseract OCR│                                          │
│  └──────────────┘                                          │
└─────────────────────────────────────────────────────────────┘
```

---

## DOMAIN MODELS

### 1. USER & AUTHENTICATION DOMAIN

```plantuml
@startuml User_Authentication_Domain

class User {
  +id: int
  +name: string
  +username: string
  +email: string
  +password: string (hashed)
  +role: enum (GKM, GJM, Dosen)
  +prodi_id: int
  +is_active: boolean
  --
  +dosen(): HasOne
  +prodi(): BelongsTo
  +reminders(): HasMany
  +isGKM(): bool
  +isGJM(): bool
  +isDosen(): bool
}

class Dosen {
  +id: int
  +user_id: int
  +prodi_id: int
  +nama_lengkap: string
  +nidn: string
  +gelar_akademik: string
  +jabatan_akademik: string
  +kontak_email: string
  +bio: text
  +foto_profil: string
  +status: string
  +is_kaprodi: boolean
  +is_dosen_wali: boolean
  +kelas_wali: string
  --
  +user(): BelongsTo
  +prodi(): BelongsTo
  +matakuliah(): BelongsToMany
  +rps(): HasMany
  +materi(): HasMany
  +monitoring(): HasMany
  +perwalian(): HasMany
  +evaluasiArtefak(): HasMany
}

User "1" -- "0..1" Dosen
User "*" -- "1" Prodi

@enduml
```

### 2. ACADEMIC STRUCTURE DOMAIN

```plantuml
@startuml Academic_Structure_Domain

class Prodi {
  +id: int
  +nama_prodi: string
  +kode_prodi: string
  +jenjang: string
  +akreditasi: string
  +kaprodi_id: int
  --
  +dosen(): HasMany
  +matakuliah(): HasMany
  +kelas(): HasMany
  +laporanGKM(): HasMany
  +ajaran(): HasMany
  +kaprodi(): BelongsTo
}

class Matakuliah {
  +id: int
  +prodi_id: int
  +kode_mk: string
  +nama_mk: string
  +sks: int
  +semester: int
  +jenis: enum (Teori, Praktikum)
  --
  +prodi(): BelongsTo
  +dosen(): BelongsToMany
  +rps(): HasMany
  +materi(): HasMany
}

class Kelas {
  +id: int
  +prodi_id: int
  +kode_kelas: string
  +tingkat: int
  +status: enum (aktif, nonaktif)
  --
  +prodi(): BelongsTo
}

class Ajaran {
  +id: int
  +prodi_id: int
  +tahun_ajaran: string
  +semester: enum (Ganjil, Genap)
  +is_active: boolean
  --
  +prodi(): BelongsTo
  +rps(): HasMany
  +monitoring(): HasMany
  +laporanGJM(): HasMany
  +laporanGKM(): HasMany
}

Prodi "1" -- "*" Matakuliah
Prodi "1" -- "*" Kelas
Prodi "1" -- "*" Ajaran
Dosen "*" -- "*" Matakuliah : teaches >

@enduml
```

### 3. ACADEMIC CONTENT DOMAIN

```plantuml
@startuml Academic_Content_Domain

class RPS {
  +id: int
  +matakuliah_id: int
  +ajaran_id: int
  +dosen_id: int
  +status: enum (draft, approved)
  +file_path: string
  +capaian_pembelajaran: text
  +strategi_pembelajaran: text
  +sistem_penilaian: text
  --
  +matakuliah(): BelongsTo
  +ajaran(): BelongsTo
  +dosen(): BelongsTo
  +materi(): HasMany
  +monitoring(): HasMany
  +evaluasiArtefak(): HasOne
}

class Materi {
  +id: int
  +rps_id: int
  +matakuliah_id: int
  +dosen_id: int
  +minggu_ke: int
  +judul_materi: string
  +deskripsi: text
  +file_path: string
  +status_upload: enum
  +tanggal_upload: datetime
  --
  +rps(): BelongsTo
  +matakuliah(): BelongsTo
  +dosen(): BelongsTo
  +monitoring(): HasMany
  +evaluasiArtefak(): HasOne
}

class Monitoring {
  +id: int
  +dosen_id: int
  +matakuliah_id: int
  +ajaran_id: int
  +rps_id: int
  +materi_id: int
  +status_rps: string
  +status_materi: string
  +catatan: text
  +tanggal_monitoring: datetime
  --
  +dosen(): BelongsTo
  +matakuliah(): BelongsTo
  +ajaran(): BelongsTo
  +rps(): BelongsTo
  +materi(): BelongsTo
}

RPS "1" -- "*" Materi
RPS "1" -- "*" Monitoring
Materi "1" -- "*" Monitoring

@enduml
```

### 4. SURVEY & QUESTIONNAIRE DOMAIN

```plantuml
@startuml Survey_Domain

class Kuisioner {
  +id: int
  +nama_kuesioner: string
  +deskripsi: text
  +tanggal_mulai: datetime
  +tanggal_akhir: datetime
  +is_active: boolean
  --
  +pertanyaan(): HasMany
  +jawaban(): HasMany
}

class PertanyaanKuisioner {
  +id: int
  +kuisioner_id: int
  +pertanyaan: text
  +tipe: enum (rating, text, multiple_choice)
  +urutan: int
  +bobot: decimal
  --
  +kuisioner(): BelongsTo
}

class JawabanKuisioner {
  +id: int
  +kuisioner_id: int
  +dosen_id: int
  +pertanyaan_id: int
  +jawaban: text
  +rating: int
  +tanggal_jawab: datetime
  --
  +kuisioner(): BelongsTo
  +dosen(): BelongsTo
}

class KuesioneUpload {
  +id: int
  +user_id: int
  +nama_file: string
  +file_path: string
  +periode: string
  +status: enum (pending, processing, completed)
  +hasil_analisis: json
  +index_kepuasan: decimal
  +persen_kepuasan: decimal
  +tanggal_upload: datetime
  --
  +user(): BelongsTo
  +chunks(): HasMany
}

Kuisioner "1" -- "*" PertanyaanKuisioner
Kuisioner "1" -- "*" JawabanKuisioner
User "1" -- "*" KuesioneUpload

@enduml
```

### 5. REPORT GENERATION DOMAIN

```plantuml
@startuml Report_Domain

class LaporanGJM {
  +id: int
  +ajaran_id: int
  +template_id: int
  +periode_mulai: date
  +periode_akhir: date
  +jenis_laporan: enum (triwulan, semester, tahunan)
  +program_studi: string
  +ringkasan_mutu_institusi: text
  +analisis_kepatuhan: text
  +temuan_utama: text
  +rekomendasi_perbaikan: text
  +file_laporan: string
  +dokumen_path: string
  +ppt_path: string
  +status_laporan: enum (draft, processing, completed)
  +ai_sections: json
  +ocr_data: json
  +ragas_faithfulness: decimal
  +ragas_answer_relevancy: decimal
  +ragas_context_precision: decimal
  +ragas_context_recall: decimal
  +rag_chunks_count: int
  +created_by: int
  +reviewed_by: int
  --
  +ajaran(): BelongsTo
  +template(): BelongsTo
  +createdBy(): BelongsTo
  +reviewedBy(): BelongsTo
}

class LaporanGKM {
  +id: int
  +prodi_id: int
  +user_id: int
  +template_id: int
  +ajaran_id: int
  +jenis_laporan: enum (bulanan, semester)
  +periode: string
  +bulan: string
  +tahun: string
  +file_laporan: string
  +file_word: string
  +file_pdf: string
  +kepatuhan_rps: decimal
  +kepatuhan_materi: decimal
  +hasil_kuisioner: json
  +status: enum (pending, processing, completed)
  +ai_sections: json
  --
  +prodi(): BelongsTo
  +user(): BelongsTo
  +template(): BelongsTo
  +ajaran(): BelongsTo
}

class TemplateLaporan {
  +id: int
  +prodi_id: int
  +nama_template: string
  +jenis_template: enum (GKM, GJM)
  +kategori: enum (bulanan, triwulan, semester)
  +file_path: string
  +deskripsi: text
  +is_active: boolean
  --
  +prodi(): BelongsTo
  +laporanGJM(): HasMany
  +laporanGKM(): HasMany
  +chunks(): HasMany
}

TemplateLaporan "1" -- "*" LaporanGJM
TemplateLaporan "1" -- "*" LaporanGKM
Prodi "1" -- "*" LaporanGKM
Ajaran "1" -- "*" LaporanGJM
Ajaran "1" -- "*" LaporanGKM

@enduml
```

### 6. AI & RAG DOMAIN

```plantuml
@startuml AI_RAG_Domain

class DocumentChunk {
  +id: int
  +source_type: string
  +source_id: int
  +kuesioner_upload_id: int
  +template_laporan_id: int
  +chunk_text: text
  +chunk_index: int
  +embedding: vector (float array)
  +metadata: json
  +created_at: datetime
  --
  +kuesioneUpload(): BelongsTo
  +templateLaporan(): BelongsTo
}

class AIResponseCache {
  +id: int
  +prompt_hash: string (indexed)
  +response_text: text
  +context_data: json
  +token_count: int
  +provider: string
  +model: string
  +cache_hit_count: int
  +expires_at: datetime
  --
}

class EmbeddingsCache {
  +id: int
  +text: text
  +embedding: vector (float array)
  +model: string
  +created_at: datetime
  --
}

class AIEvaluationResult {
  +id: int
  +test_id: int
  +model: string
  +ragas_faithfulness: decimal
  +ragas_answer_relevancy: decimal
  +ragas_context_precision: decimal
  +ragas_context_recall: decimal
  +ragas_context_relevancy: decimal
  +ragas_overall_score: decimal
  +hallucination_rate: decimal
  +quality_score: decimal
  +evaluated_at: datetime
  --
}

KuesioneUpload "1" -- "*" DocumentChunk
TemplateLaporan "1" -- "*" DocumentChunk

@enduml
```

---

## SERVICE LAYER

### 1. AI SERVICES ARCHITECTURE

```plantuml
@startuml AI_Services_Architecture

interface AIServiceInterface {
  +generateText(prompt: string, options: array): array
  +generateChat(messages: array, options: array): array
}

class UnifiedAIService implements AIServiceInterface {
  -provider: string
  -apiKey: string
  -baseUrl: string
  -model: string
  -cacheEnabled: boolean
  -cacheTTL: int
  --
  +__construct()
  +generateText(prompt: string, options: array): array
  +generateChat(messages: array, options: array): array
  +generateReportSections(context: string, sections: array): array
  +getProviderInfo(): array
  +clearCache(): void
  --
  -callOpenAICompatibleAPI(prompt: string, options: array): array
  -callOpenAICompatibleChatAPI(messages: array, options: array): array
  -callClaudeAPI(prompt: string, options: array): array
  -callClaudeChatAPI(messages: array, options: array): array
  -fallbackToGemini(prompt: string, options: array, startTime: float): array
  -fallbackToGeminiChat(messages: array, options: array, startTime: float): array
  -trimMessagesIfNeeded(messages: array, options: array): array
  -getCacheKey(prompt: string, options: array): string
}

class ClaudeAIService implements AIServiceInterface {
  -apiKey: string
  -baseUrl: string
  -model: string
  --
  +generateText(prompt: string, options: array): array
  +streamText(prompt: string, callback: callable): void
}

class GeminiAIService implements AIServiceInterface {
  -apiKey: string
  -model: string
  --
  +generateText(prompt: string, options: array): array
  +generateWithSafetySettings(prompt: string, options: array): array
}

class AIAgentService {
  -unifiedAI: UnifiedAIService
  -ragRetrieval: RAGRetrievalService
  --
  +analyzeKuesioner(kuesioneId: int): array
  +generateReminder(type: string, data: array): string
  +analyzeDataMaster(filters: array): array
  +summarizeMonitoring(monitoring: array): string
}

UnifiedAIService --> ClaudeAIService : fallback
UnifiedAIService --> GeminiAIService : fallback
AIAgentService --> UnifiedAIService : uses

@enduml
```

### 2. RAG SERVICES ARCHITECTURE

```plantuml
@startuml RAG_Services_Architecture

class VectorDatabaseService {
  -embeddingService: EmbeddingService
  -chunkingService: ChunkingService
  --
  +indexDocument(documentData: array): bool
  +indexKuesioner(kuesioner: KuesioneUpload): bool
  +search(query: string, topK: int, filters: array): array
  +deleteKuesioneIndex(kuesioneId: int): bool
  +reindexPeriode(periode: string, prodiId: int): int
  --
  -extractTextFromKuesioner(kuesioner: KuesioneUpload): string
}

class RAGRetrievalService {
  -vectorDbService: VectorDatabaseService
  -topK: int
  -similarityThreshold: float
  --
  +retrieveContext(query: string, filters: array): array
  +retrieveAndRank(query: string, filters: array, rankingWeights: array): array
  --
  -buildContextText(results: array): string
  -extractMetadata(results: array): array
  -rerank(results: array, weights: array): array
  -calculateRecencyBoost(createdAt: string): float
}

class EmbeddingService {
  -apiKey: string
  -model: string
  --
  +generateEmbedding(text: string): array
  +generateBatchEmbeddings(texts: array): array
  +cosineSimilarity(vector1: array, vector2: array): float
  --
  -normalizeVector(vector: array): array
}

class ChunkingService {
  -chunkSize: int
  -overlap: int
  --
  +chunk(text: string): array
  +chunkWithMetadata(text: string, metadata: array): array
  +smartChunk(text: string, preserveContext: bool): array
}

class AdvancedChunkingService extends ChunkingService {
  --
  +semanticChunk(text: string): array
  +sentenceChunk(text: string): array
  +paragraphChunk(text: string): array
  +structuredChunk(document: string, structure: array): array
}

RAGRetrievalService --> VectorDatabaseService : uses
VectorDatabaseService --> EmbeddingService : uses
VectorDatabaseService --> ChunkingService : uses

@enduml
```

### 3. REPORT GENERATION SERVICES

```plantuml
@startuml Report_Generation_Services

abstract class BaseLaporanService {
  #unifiedAI: UnifiedAIService
  #ragRetrieval: RAGRetrievalService
  #templateService: TemplateService
  --
  +generate(laporan: Model): array
  #buildPrompt(data: array): string
  #validateInputData(data: array): bool
  #saveReport(laporan: Model, content: string): bool
  {abstract} #getSectionDefinitions(): array
}

class LaporanTriwulanService extends BaseLaporanService {
  -ocr: OCRService
  -cache: AICacheService
  --
  +generateTriwulan(laporanId: int): array
  +generateWithRAG(laporanId: int, useOCR: bool): array
  +evaluateWithRAGAS(laporanId: int): array
  --
  -buildTriwulanContext(laporan: LaporanGJM): string
  -getSectionDefinitions(): array
  -processOCRDocuments(laporan: LaporanGJM): array
  -retrieveRelevantContext(query: string): array
}

class LaporanSemesterService extends BaseLaporanService {
  -vmtsService: VMTSExcelService
  --
  +generateSemester(laporanId: int): array
  +generateWithVMTS(laporanId: int, excelPath: string): array
  --
  -buildSemesterContext(laporan: LaporanGJM): string
  -getSectionDefinitions(): array
  -integrateVMTSData(data: array): array
}

class LaporanArtefakService extends BaseLaporanService {
  -fileExtraction: EnhancedFileExtractionService
  -validation: FileValidationService
  --
  +generateArtefak(prodi: int, periode: string): array
  +analyzeArtefak(files: array): array
  --
  -extractArtefakData(files: array): array
  -getSectionDefinitions(): array
}

class LaporanKuesioneService extends BaseLaporanService {
  -wordGeneration: KuesioneWordGenerationService
  --
  +generateKuesionerReport(kuesioneIds: array): array
  +generateWordDocument(reportData: array): string
  --
  -aggregateKuesioneData(kuesioneIds: array): array
  -getSectionDefinitions(): array
}

BaseLaporanService --> UnifiedAIService : uses
BaseLaporanService --> RAGRetrievalService : uses

@enduml
```

### 4. DOCUMENT PROCESSING SERVICES

```plantuml
@startuml Document_Processing_Services

class OCRService {
  -tesseractPath: string
  -language: string
  --
  +extractTextFromImage(imagePath: string): string
  +extractTextFromPDF(pdfPath: string): array
  +batchProcess(files: array): array
  --
  -preprocessImage(image: Image): Image
  -cleanOCRText(text: string): string
}

class TextExtractionService {
  --
  +extractFromWord(filePath: string): string
  +extractFromPDF(filePath: string): string
  +extractFromExcel(filePath: string): array
  +detectFileType(filePath: string): string
}

class EnhancedFileExtractionService {
  -textExtraction: TextExtractionService
  -ocr: OCRService
  --
  +extractWithAI(filePath: string): array
  +extractStructured(filePath: string): array
  +extractTablesFromPDF(filePath: string): array
}

class DocumentStructureService {
  --
  +analyzeStructure(document: string): array
  +extractSections(document: string): array
  +extractHeadings(document: string): array
  +buildDocumentTree(document: string): array
}

class FileValidationService {
  --
  +validate(file: UploadedFile): array
  +checkFileType(file: UploadedFile): bool
  +checkFileSize(file: UploadedFile): bool
  +scanForVirus(file: UploadedFile): bool
}

EnhancedFileExtractionService --> TextExtractionService : uses
EnhancedFileExtractionService --> OCRService : uses
EnhancedFileExtractionService --> DocumentStructureService : uses

@enduml
```

### 5. EVALUATION SERVICES

```plantuml
@startuml Evaluation_Services

class RAGASEvaluationService {
  -unifiedAI: UnifiedAIService
  --
  +evaluate(question: string, answer: string, contexts: array, groundTruth: string): array
  +evaluateFaithfulness(answer: string, contexts: array): float
  +evaluateAnswerRelevancy(question: string, answer: string): float
  +evaluateContextPrecision(question: string, contexts: array, groundTruth: string): float
  +evaluateContextRecall(contexts: array, groundTruth: string): float
  +calculateOverallScore(metrics: array): float
  --
  -generateStatements(answer: string): array
  -verifyStatements(statements: array, contexts: array): array
  -calculateSupport(statements: array, verifications: array): float
}

class AIEvaluationService {
  -ragasService: RAGASEvaluationService
  --
  +runEvaluationTest(testCase: AIEvaluationTest): AIEvaluationResult
  +batchEvaluate(testCases: array): array
  +compareModels(models: array, testCases: array): array
  +calculateWER(reference: string, hypothesis: string): float
  --
  -normalizeText(text: string): string
  -tokenize(text: string): array
}

class FileRelevanceValidationService {
  -embeddingService: EmbeddingService
  --
  +validateRelevance(file: string, context: string): array
  +scoreRelevance(fileContent: string, expectedContent: string): float
  +detectHallucination(generated: string, source: string): array
}

RAGASEvaluationService --> UnifiedAIService : uses
AIEvaluationService --> RAGASEvaluationService : uses
FileRelevanceValidationService --> EmbeddingService : uses

@enduml
```

### 6. EXTERNAL API SERVICES

```plantuml
@startuml External_API_Services

class ExternalAPIService {
  -baseUrl: string
  -token: string
  -timeout: int
  --
  +getDosen(): array
  +getFilteredDosen(): array
  +getDosenById(id: int): Dosen
  +getDosenByProdiIds(prodiIds: array): array
  +getMatkulByProdiSemTa(prodiId: int, semTa: int, ta: int): array
  +getMonitoringMateri(kuliahId: int, ta: int, semTa: int): array
  +getMonitoringMateriPraktikum(kuliahId: int, ta: int, semTa: int): array
  +getTahunAjaran(): array
  +getJadwalByDosen(pegawaiId: int, semTa: int, ta: int): array
  +getRekapKuesioner(ta: int, kodeMk: string): array
  +isAvailable(): bool
  +refreshToken(): bool
  --
  -getToken(forceRefresh: bool): string
  -callApi(url: string, params: array, method: string): array
}

class WhatsAppService {
  -baseUrl: string
  -apiKey: string
  --
  +sendMessage(phone: string, message: string): array
  +sendTemplate(phone: string, template: string, params: array): array
  +sendDocument(phone: string, documentUrl: string, caption: string): array
  +checkStatus(messageId: string): array
}

@enduml
```

---

## CONTROLLER LAYER

### 1. GKM CONTROLLERS

```plantuml
@startuml GKM_Controllers

class GKM_DashboardController {
  -externalAPI: ExternalAPIService
  -aiAgent: AIAgentService
  --
  +index(): View
  +getStatistics(): JsonResponse
  +getChartData(type: string): JsonResponse
}

class GKM_MonitoringRPSController {
  -externalAPI: ExternalAPIService
  -whatsapp: WhatsAppService
  -cache: CacheManager
  --
  +index(Request): View
  +getData(Request): JsonResponse
  +sendReminder(Request): JsonResponse
  +exportExcel(Request): Response
  --
  -buildMonitoringData(filters: array): array
  -calculateCompliance(data: array): float
}

class GKM_MonitoringPerkuliahanController {
  -externalAPI: ExternalAPIService
  -whatsapp: WhatsAppService
  --
  +index(Request): View
  +getMateriStatus(Request): JsonResponse
  +sendReminderMateri(Request): JsonResponse
  +getDetailMonitoring(kuliahId: int): JsonResponse
}

class GKM_MonitoringKuesioneController {
  -aiAgent: AIAgentService
  -wordGeneration: KuesioneWordGenerationService
  --
  +index(Request): View
  +analyzeKuesioner(kuesioneId: int): JsonResponse
  +generateReport(Request): Response
  +exportWord(reportId: int): Response
}

class GKM_DataMasterController {
  -externalAPI: ExternalAPIService
  --
  +index(): View
  +getDosen(Request): JsonResponse
  +getMatakuliah(Request): JsonResponse
  +assignDosenMatkul(Request): JsonResponse
}

@enduml
```

### 2. GJM CONTROLLERS

```plantuml
@startuml GJM_Controllers

class GJM_DashboardController {
  -laporanService: BaseLaporanService
  --
  +index(): View
  +getInstitutionalMetrics(): JsonResponse
  +getProdiComparison(): JsonResponse
  +getTrendAnalysis(period: string): JsonResponse
}

class GJM_LaporanTriwulanController {
  -laporanTriwulan: LaporanTriwulanService
  -ragasEval: RAGASEvaluationService
  --
  +index(): View
  +create(): View
  +store(Request): RedirectResponse
  +generate(laporanId: int): JsonResponse
  +preview(laporanId: int): View
  +evaluate(laporanId: int): JsonResponse
  +download(laporanId: int): Response
  --
  -validateLaporanData(data: array): bool
  -prepareAIContext(laporan: LaporanGJM): string
}

class GJM_LaporanSemesterController {
  -laporanSemester: LaporanSemesterService
  -vmtsService: VMTSExcelService
  --
  +index(): View
  +create(): View
  +store(Request): RedirectResponse
  +uploadVMTS(Request): JsonResponse
  +generate(laporanId: int): JsonResponse
  +preview(laporanId: int): View
  +download(laporanId: int): Response
}

class GJM_BuatPPTController {
  -pptGeneration: PPTGenerationService
  --
  +index(laporanId: int): View
  +generate(Request): JsonResponse
  +download(laporanId: int): Response
  --
  -extractKeyPoints(laporan: LaporanGJM): array
  -generateSlides(content: array): string
}

class GJM_OCRUploadController {
  -ocr: OCRService
  -vectorDb: VectorDatabaseService
  --
  +upload(Request): JsonResponse
  +process(documentId: int): JsonResponse
  +getOCRResult(documentId: int): JsonResponse
  --
  -processAndIndex(file: UploadedFile): array
}

@enduml
```

---

## BACKGROUND JOBS

### Job Architecture

```plantuml
@startuml Background_Jobs

abstract class Job {
  +handle(): void
  {abstract} #process(): void
}

class GenerateLaporanTriwulanJob extends Job {
  +laporanId: int
  --
  #process(): void
  --
  -laporanService: LaporanTriwulanService
  -ragRetrieval: RAGRetrievalService
}

class GenerateLaporanSemesterJob extends Job {
  +laporanId: int
  --
  #process(): void
  --
  -laporanService: LaporanSemesterService
}

class GenerateLaporanBulananJob extends Job {
  +laporanId: int
  +prodiId: int
  --
  #process(): void
}

class GenerateLaporanArtefakJob extends Job {
  +prodiId: int
  +periode: string
  --
  #process(): void
  --
  -artefakService: LaporanArtefakService
}

class ProcessOCRAndIndexJob extends Job {
  +documentPath: string
  +sourceType: string
  +sourceId: int
  --
  #process(): void
  --
  -ocr: OCRService
  -vectorDb: VectorDatabaseService
  -chunking: ChunkingService
}

class SendScheduledReminderJob extends Job {
  +reminderId: int
  --
  #process(): void
  --
  -whatsapp: WhatsAppService
  -emailService: EmailHelper
}

class SyncJadwalDosenJob extends Job {
  +prodiId: int
  +semTa: int
  +ta: int
  --
  #process(): void
  --
  -externalAPI: ExternalAPIService
}

@enduml
```

---

## EXTERNAL INTEGRATIONS

### Integration Architecture

```plantuml
@startuml External_Integrations

package "Laravel Application" {
  [ExternalAPIService]
  [UnifiedAIService]
  [WhatsAppService]
  [OCRService]
}

cloud "Library API (CIS DEL)" {
  [Dosen API]
  [Jadwal API]
  [Matakuliah API]
  [Monitoring API]
}

cloud "LLM Providers" {
  [Groq API]
  [OpenRouter API]
  [Claude API]
  [Gemini API]
  [Together AI]
}

cloud "External Services" {
  [WhatsApp WACHAT]
  [Tesseract OCR]
}

database "MongoDB" {
  [KuesionerMongo]
  [HasilAnalisisMongo]
  [AIResponseCacheMongo]
}

database "MySQL" {
  [DocumentChunk]
  [AIResponseCache]
  [EmbeddingsCache]
}

[ExternalAPIService] --> [Dosen API]
[ExternalAPIService] --> [Jadwal API]
[ExternalAPIService] --> [Matakuliah API]
[ExternalAPIService] --> [Monitoring API]

[UnifiedAIService] --> [Groq API]
[UnifiedAIService] --> [OpenRouter API]
[UnifiedAIService] --> [Claude API]
[UnifiedAIService] ..> [Gemini API] : fallback

[WhatsAppService] --> [WhatsApp WACHAT]
[OCRService] --> [Tesseract OCR]

[UnifiedAIService] --> [MySQL]
[UnifiedAIService] --> [MongoDB]

@enduml
```

---

## DIAGRAM UML

### Complete System Class Diagram

```plantuml
@startuml Complete_System_Diagram

' ==========================================
' DOMAIN MODELS
' ==========================================

package "User & Authentication" <<Rectangle>> {
  class User
  class Dosen
  class Prodi
}

package "Academic Structure" <<Rectangle>> {
  class Matakuliah
  class Kelas
  class Ajaran
  class RPS
  class Materi
  class Monitoring
}

package "Survey & Questionnaire" <<Rectangle>> {
  class Kuisioner
  class PertanyaanKuisioner
  class JawabanKuisioner
  class KuesioneUpload
}

package "Report Generation" <<Rectangle>> {
  class LaporanGJM
  class LaporanGKM
  class TemplateLaporan
  class KirimLaporanHistory
}

package "AI & RAG" <<Rectangle>> {
  class DocumentChunk
  class AIResponseCache
  class EmbeddingsCache
  class AIEvaluationResult
}

' ==========================================
' SERVICES
' ==========================================

package "AI Services" <<Rectangle>> {
  class UnifiedAIService
  class ClaudeAIService
  class GeminiAIService
  class AIAgentService
}

package "RAG Services" <<Rectangle>> {
  class VectorDatabaseService
  class RAGRetrievalService
  class EmbeddingService
  class ChunkingService
}

package "Report Services" <<Rectangle>> {
  class LaporanTriwulanService
  class LaporanSemesterService
  class LaporanArtefakService
  class LaporanKuesioneService
}

package "Document Services" <<Rectangle>> {
  class OCRService
  class TextExtractionService
  class FileValidationService
}

package "External Services" <<Rectangle>> {
  class ExternalAPIService
  class WhatsAppService
}

' ==========================================
' CONTROLLERS
' ==========================================

package "GKM Controllers" <<Rectangle>> {
  class GKM_DashboardController
  class GKM_MonitoringRPSController
  class GKM_MonitoringKuesioneController
}

package "GJM Controllers" <<Rectangle>> {
  class GJM_DashboardController
  class GJM_LaporanTriwulanController
  class GJM_LaporanSemesterController
}

' ==========================================
' RELATIONSHIPS
' ==========================================

' Model Relationships
User "1" -- "0..1" Dosen
User "*" -- "1" Prodi
Dosen "*" -- "*" Matakuliah
RPS "1" -- "*" Materi
LaporanGJM "*" -- "1" TemplateLaporan
KuesioneUpload "1" -- "*" DocumentChunk

' Service Dependencies
GKM_MonitoringRPSController --> ExternalAPIService
GKM_MonitoringKuesioneController --> AIAgentService
GJM_LaporanTriwulanController --> LaporanTriwulanService
LaporanTriwulanService --> RAGRetrievalService
RAGRetrievalService --> VectorDatabaseService
AIAgentService --> UnifiedAIService
UnifiedAIService ..> GeminiAIService : fallback

@enduml
```

---

## DETAILED TECHNICAL SPECIFICATIONS

### 1. RAG (Retrieval Augmented Generation) Flow

```
┌─────────────────────────────────────────────────────────────┐
│  1. DOCUMENT INDEXING PHASE                                  │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  Document Upload                                             │
│       ↓                                                      │
│  Text Extraction (OCR/Parser)                               │
│       ↓                                                      │
│  Text Chunking (Semantic/Paragraph/Sentence)                │
│       ↓                                                      │
│  Generate Embeddings (Vector)                               │
│       ↓                                                      │
│  Store in DocumentChunk (MySQL with vector)                 │
│                                                               │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  2. RETRIEVAL PHASE                                          │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  User Query/Prompt                                           │
│       ↓                                                      │
│  Generate Query Embedding                                    │
│       ↓                                                      │
│  Vector Search (Cosine Similarity)                          │
│       ↓                                                      │
│  Rank by Similarity Score                                    │
│       ↓                                                      │
│  Filter by Threshold (0.3)                                   │
│       ↓                                                      │
│  Return Top K Results (10)                                   │
│                                                               │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  3. AUGMENTATION PHASE                                       │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  Retrieved Contexts                                          │
│       ↓                                                      │
│  Build Context Text                                          │
│       ↓                                                      │
│  Inject into AI Prompt                                       │
│       ↓                                                      │
│  Generate Response (LLM)                                     │
│       ↓                                                      │
│  Post-process & Format                                       │
│                                                               │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  4. EVALUATION PHASE (RAGAS)                                 │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  Generated Answer + Contexts + Ground Truth                  │
│       ↓                                                      │
│  Calculate Metrics:                                          │
│    - Faithfulness (no hallucination)                        │
│    - Answer Relevancy (matches question)                    │
│    - Context Precision (relevant contexts)                  │
│    - Context Recall (complete contexts)                     │
│       ↓                                                      │
│  Store in AIEvaluationResult                                │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### 2. AI Provider Fallback Strategy

```
Primary Provider Selection (config: LLM_PROVIDER)
├── Groq (Free Tier)
│   └── Model: llama-3.3-70b-versatile
│   └── Context: 8K tokens
│
├── OpenRouter (Cheap)
│   └── Model: meta-llama/llama-3.1-70b-instruct
│   └── Context: 131K tokens
│
├── Together AI
│   └── Model: Meta-Llama-3.1-70B-Instruct-Turbo
│   └── Context: 131K tokens
│
└── Claude (Premium)
    └── Model: claude-3-5-sonnet/haiku
    └── Context: 200K tokens

                ↓ (on rate limit / 429 error)

Fallback Provider: Gemini
├── Model: gemini-1.5-flash
├── Context: 1M tokens
└── Triggered only on rate limit errors
```

### 3. Caching Strategy

```
┌─────────────────────────────────────────────────────────────┐
│  MULTI-TIER CACHING ARCHITECTURE                             │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  Level 1: Laravel Cache (Redis/Memcached)                   │
│  ├── TTL: 3500 seconds (API tokens)                         │
│  ├── TTL: 86400 seconds (AI responses)                      │
│  └── Key Format: unified_ai_{md5(prompt+options)}           │
│                                                               │
│  Level 2: MySQL (AIResponseCache)                           │
│  ├── Indexed by: prompt_hash                                │
│  ├── Stores: response_text, context_data, token_count      │
│  └── Tracks: cache_hit_count                                │
│                                                               │
│  Level 3: MongoDB (AIResponseCacheMongo)                    │
│  ├── For complex/nested data                                │
│  ├── Stores: full conversation history                      │
│  └── TTL: Configurable per collection                       │
│                                                               │
│  Level 4: EmbeddingsCache (MySQL)                           │
│  ├── Stores: text embeddings (vectors)                      │
│  ├── Indexed by: md5(text + model)                         │
│  └── Prevents redundant embedding generation                │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### 4. Database Schema Overview

#### MySQL Tables

| Table Name | Purpose | Key Columns |
|------------|---------|-------------|
| `users` | User authentication | id, username, email, role, prodi_id |
| `dosen` | Lecturer profiles | id, user_id, prodi_id, nidn, jabatan_akademik |
| `prodi` | Program studi | id, nama_prodi, kode_prodi, kaprodi_id |
| `matakuliah` | Courses | id, prodi_id, kode_mk, nama_mk, sks |
| `rps` | Learning plans | id, matakuliah_id, ajaran_id, dosen_id, status |
| `materi` | Learning materials | id, rps_id, minggu_ke, status_upload |
| `monitoring` | Activity monitoring | id, dosen_id, matakuliah_id, status_rps |
| `kuisioner` | Surveys | id, nama_kuesioner, is_active |
| `kuesione_uploads` | Survey uploads | id, user_id, hasil_analisis, index_kepuasan |
| `laporan_gjm` | GJM reports | id, jenis_laporan, ai_sections, ragas_metrics |
| `laporan_gkm` | GKM reports | id, prodi_id, periode, ai_sections |
| `document_chunks` | Vector database | id, chunk_text, embedding, metadata |
| `ai_response_cache` | AI cache | id, prompt_hash, response_text, token_count |
| `embeddings_cache` | Vector cache | id, text, embedding, model |
| `ai_evaluation_results` | RAGAS metrics | id, ragas_faithfulness, ragas_overall_score |
| `template_laporan` | Report templates | id, prodi_id, jenis_template, file_path |
| `jadwal_reminders` | Scheduled reminders | id, type, schedule_time, status |

#### MongoDB Collections

| Collection Name | Purpose | Key Fields |
|-----------------|---------|------------|
| `kuesioner_mongo` | Raw survey data | kuesioner_id, raw_data, responses |
| `hasil_analisis_mongo` | Analysis results | kuesioner_id, detailed_analysis, statistics |
| `ai_response_cache_mongo` | AI conversation cache | prompt, response, conversation_history |

### 5. API Endpoints

#### Library API (CIS DEL)

```
Base URL: https://cis-dev.del.ac.id/api

Authentication:
POST /jwt-api/do-auth
  → Returns JWT token (valid ~1 hour)

Dosen Endpoints:
GET /library-api/dosen
  → Get all lecturers

GET /library-api/dosen/{id}
  → Get lecturer by ID

Jadwal Endpoints:
GET /library-api/get-jadwal-by-dosen
  params: pegawai_id, sem_ta, ta
  → Get lecturer schedule

Matakuliah Endpoints:
GET /library-api/matkul-by-prodi-sem-ta
  params: prodi_id, sem_ta, ta
  → Get courses by program, semester, year

Monitoring Endpoints:
GET /library-api/get-monitoring-materi-teori
  params: kuliah_id, ta, sem_ta
  → Get RPS and material upload status

GET /library-api/get-monitoring-materi-praktikum
  params: kuliah_id, ta, sem_ta
  → Get practicum material status

Tahun Ajaran Endpoints:
GET /library-api/tahun-ajaran
  → Get academic years

Kuesioner Endpoints:
GET /library-api/get-rekap-kuesioner
  params: ta, kode_mk
  → Get questionnaire summary
```

### 6. Key Design Patterns

#### 1. Service Layer Pattern
```php
// Controllers delegate business logic to Services
class MonitoringRPSController {
    private $externalAPI;
    private $whatsappService;
    
    public function sendReminder(Request $request) {
        // Validate
        $validated = $request->validate([...]);
        
        // Delegate to service
        $result = $this->whatsappService->sendReminder(
            $validated['phone'],
            $validated['message']
        );
        
        return response()->json($result);
    }
}
```

#### 2. Repository Pattern (Implicit via Eloquent)
```php
// Models act as repositories with query scopes
class Dosen extends Model {
    public function scopeKaprodi($query) {
        return $query->where('is_kaprodi', true);
    }
    
    public function scopeDosenWali($query) {
        return $query->where('is_dosen_wali', true);
    }
}

// Usage
$kaprodiList = Dosen::kaprodi()->get();
```

#### 3. Strategy Pattern (AI Provider Selection)
```php
class UnifiedAIService {
    public function generateText($prompt, $options) {
        return match($this->provider) {
            'claude' => $this->callClaudeAPI($prompt, $options),
            'groq' => $this->callOpenAICompatibleAPI($prompt, $options),
            'openrouter' => $this->callOpenAICompatibleAPI($prompt, $options),
            default => $this->callOpenAICompatibleAPI($prompt, $options)
        };
    }
}
```

#### 4. Observer Pattern (Eloquent Events)
```php
// Model events for automatic actions
class KuesioneUpload extends Model {
    protected static function booted() {
        static::created(function ($kuesioner) {
            // Dispatch job to process and index
            ProcessOCRAndIndexJob::dispatch($kuesioner);
        });
    }
}
```

#### 5. Factory Pattern (Job Creation)
```php
class ReportJobFactory {
    public static function create($type, $data) {
        return match($type) {
            'triwulan' => new GenerateLaporanTriwulanJob($data),
            'semester' => new GenerateLaporanSemesterJob($data),
            'bulanan' => new GenerateLaporanBulananJob($data),
            default => throw new \Exception("Unknown report type")
        };
    }
}
```

#### 6. Decorator Pattern (Enhanced Services)
```php
class EnhancedLaporanService extends BaseLaporanService {
    private $ragService;
    
    public function generate($laporan) {
        // Add RAG enhancement
        $contexts = $this->ragService->retrieveContext($laporan->prompt);
        
        // Call parent with enhanced context
        return parent::generate($laporan, $contexts);
    }
}
```

### 7. Security Considerations

#### Authentication & Authorization
```php
// Middleware: CheckRole
class CheckRole {
    public function handle($request, Closure $next, ...$roles) {
        if (!auth()->check()) {
            return redirect('login');
        }
        
        if (!in_array(auth()->user()->role, $roles)) {
            abort(403, 'Unauthorized');
        }
        
        return $next($request);
    }
}

// Route protection
Route::middleware(['auth', 'role:GKM'])->group(function () {
    Route::get('/gkm/dashboard', [GKM_DashboardController::class, 'index']);
});
```

#### Data Validation
```php
// Input sanitization
class FileValidationService {
    public function validate(UploadedFile $file): array {
        // Check MIME type
        $allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new ValidationException('Invalid file type');
        }
        
        // Check file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new ValidationException('File too large');
        }
        
        return ['valid' => true];
    }
}
```

#### SQL Injection Prevention
```php
// Eloquent uses parameterized queries by default
Dosen::where('prodi_id', $prodiId)->get();
// Safe from SQL injection

// Raw queries with bindings
DB::select('SELECT * FROM dosen WHERE prodi_id = ?', [$prodiId]);
```

#### XSS Prevention
```php
// Blade templates auto-escape output
{{ $user->name }} // Escaped

// Raw output (use with caution)
{!! $trustedHtml !!}
```

#### API Token Security
```php
// Token caching with expiration
class APITokenManager {
    public function getToken($forceRefresh = false) {
        if ($forceRefresh) {
            Cache::forget('library_api_token');
        }
        
        return Cache::remember('library_api_token', 3500, function () {
            return $this->fetchNewToken();
        });
    }
}
```

#### Rate Limiting
```php
// API rate limiting
Route::middleware(['throttle:60,1'])->group(function () {
    // Max 60 requests per minute
});
```

### 8. Performance Optimization

#### Database Indexing
```sql
-- Indexes on frequently queried columns
CREATE INDEX idx_dosen_prodi ON dosen(prodi_id);
CREATE INDEX idx_rps_ajaran ON rps(ajaran_id, dosen_id);
CREATE INDEX idx_monitoring_status ON monitoring(status_rps, status_materi);
CREATE INDEX idx_document_chunks_source ON document_chunks(source_type, source_id);
CREATE INDEX idx_ai_cache_hash ON ai_response_cache(prompt_hash);

-- Full-text search index
CREATE FULLTEXT INDEX idx_laporan_content ON laporan_gjm(ringkasan_mutu_institusi);
```

#### Query Optimization
```php
// Eager loading to prevent N+1 queries
$dosen = Dosen::with(['prodi', 'matakuliah', 'rps'])->get();

// Chunking large datasets
Monitoring::chunk(100, function ($monitorings) {
    foreach ($monitorings as $monitoring) {
        // Process in batches
    }
});

// Select specific columns
Dosen::select('id', 'nama_lengkap', 'prodi_id')->get();
```

#### Caching Strategies
```php
// Cache monitoring data (expensive API calls)
$monitoring = Cache::remember(
    "monitoring_rps_{$prodiId}_{$semester}_{$tahun}", 
    3600, 
    function () use ($prodiId, $semester, $tahun) {
        return $this->externalAPI->getMonitoringData($prodiId, $semester, $tahun);
    }
);

// Cache dashboard statistics
$stats = Cache::tags(['dashboard', 'gjm'])
    ->remember('gjm_dashboard_stats', 1800, function () {
        return $this->calculateDashboardStats();
    });
```

#### Queue Management
```php
// Dispatch heavy jobs to queue
GenerateLaporanTriwulanJob::dispatch($laporanId)
    ->onQueue('reports')
    ->delay(now()->addSeconds(5));

// Chain jobs
GenerateLaporanTriwulanJob::withChain([
    new EvaluateWithRAGASJob($laporanId),
    new SendNotificationJob($laporanId)
])->dispatch($laporanId);
```

#### Asset Optimization
```javascript
// Lazy loading in frontend
document.addEventListener('DOMContentLoaded', function() {
    // Load heavy components only when needed
    if (document.querySelector('#monitoring-chart')) {
        import('./charts/monitoring-chart.js');
    }
});
```

### 9. Testing Strategy

#### Unit Tests
```php
// Service testing
class UnifiedAIServiceTest extends TestCase {
    public function test_generate_text_with_cache() {
        $service = new UnifiedAIService();
        
        $result = $service->generateText('Test prompt');
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['text']);
    }
    
    public function test_fallback_to_gemini_on_rate_limit() {
        // Mock rate limit error
        Http::fake([
            'api.groq.com/*' => Http::response(null, 429)
        ]);
        
        $service = new UnifiedAIService();
        $result = $service->generateText('Test prompt');
        
        $this->assertStringContains('gemini', $result['provider']);
    }
}
```

#### Feature Tests
```php
class MonitoringRPSTest extends TestCase {
    public function test_gkm_can_view_monitoring_rps() {
        $user = User::factory()->create(['role' => 'GKM']);
        
        $response = $this->actingAs($user)
            ->get('/gkm/monitoring-rps');
        
        $response->assertStatus(200);
        $response->assertViewHas('monitoring');
    }
    
    public function test_dosen_cannot_access_gkm_monitoring() {
        $user = User::factory()->create(['role' => 'Dosen']);
        
        $response = $this->actingAs($user)
            ->get('/gkm/monitoring-rps');
        
        $response->assertStatus(403);
    }
}
```

#### Integration Tests
```php
class RAGWorkflowTest extends TestCase {
    public function test_complete_rag_workflow() {
        // 1. Index document
        $kuesioner = KuesioneUpload::factory()->create();
        $vectorDb = app(VectorDatabaseService::class);
        $indexed = $vectorDb->indexKuesioner($kuesioner);
        $this->assertTrue($indexed);
        
        // 2. Search
        $results = $vectorDb->search('kepuasan mahasiswa', 5);
        $this->assertNotEmpty($results);
        
        // 3. Generate report
        $ragService = app(RAGRetrievalService::class);
        $context = $ragService->retrieveContext('Analisis kepuasan');
        $this->assertArrayHasKey('context_text', $context);
    }
}
```

---

## DEPLOYMENT ARCHITECTURE

### Docker Infrastructure

```yaml
# docker-compose.yml structure
services:
  laravel:
    - PHP 8.2 FPM
    - Nginx
    - Supervisor (queue workers)
    - Ports: 8000
    
  nextjs:
    - Node.js 20
    - Next.js application
    - Ports: 3000
    
  python:
    - Python 3.11
    - Tesseract OCR
    - Flask API
    - Ports: 5000
    
  mysql:
    - MySQL 8.0
    - Persistent volume
    - Ports: 3306
    
  mongodb:
    - MongoDB 7.0
    - Persistent volume
    - Ports: 27017
    
  redis:
    - Redis 7.2
    - Cache & session storage
    - Ports: 6379
```

### Environment Configuration

```env
# Application
APP_NAME="GKM-GJM System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=gkm_gjm
DB_USERNAME=root
DB_PASSWORD=secret

# MongoDB
MONGODB_URI=mongodb://mongodb:27017/gkm_gjm

# LLM Configuration
LLM_ENABLED=true
LLM_PROVIDER=groq
LLM_API_KEY=your-groq-api-key
LLM_MODEL=llama-3.3-70b-versatile

# Claude Fallback
ANTHROPIC_API_KEY=your-claude-key
ANTHROPIC_MODEL=claude-3-5-sonnet-20241022

# External APIs
LIBRARY_API_URL=https://cis-dev.del.ac.id/api
LIBRARY_API_USERNAME=your-username
LIBRARY_API_PASSWORD=your-password

# WhatsApp
WHATSAPP_API_URL=https://wachat-api.com
WHATSAPP_API_KEY=your-whatsapp-key

# Queue
QUEUE_CONNECTION=redis
```

---

## FUTURE ENHANCEMENTS

### Planned Features

#### 1. Advanced Analytics Dashboard
- Real-time data visualization with Apache Spark
- Predictive analytics for academic trends
- Machine learning models for early warning system

#### 2. Multi-language Support
- i18n implementation for Indonesian and English
- Automatic translation using AI
- Localized report templates

#### 3. Mobile Application
- React Native mobile app
- Push notifications for reminders
- Offline mode with sync

#### 4. Advanced RAG Features
- Multi-modal RAG (text + images + tables)
- Hybrid search (keyword + semantic)
- Graph-based knowledge representation

#### 5. Enhanced AI Capabilities
- Fine-tuned models for academic domain
- Custom LLM training on institutional data
- Multi-agent orchestration

#### 6. Integration Expansion
- Microsoft Teams integration
- Slack notifications
- Google Workspace integration
- Email automation enhancement

#### 7. Blockchain for Report Verification
- Immutable audit trail
- Document authenticity verification
- Timestamping with blockchain

---

## GLOSSARY

| Term | Definition |
|------|------------|
| **GKM** | Gugus Kendali Mutu - Program-level quality assurance |
| **GJM** | Gugus Jaminan Mutu - Institutional-level quality assurance |
| **RAG** | Retrieval Augmented Generation - AI technique for context-aware generation |
| **RAGAS** | RAG Assessment Score - Metrics for evaluating RAG systems |
| **RPS** | Rencana Pembelajaran Semester - Semester learning plan |
| **Prodi** | Program Studi - Academic program |
| **Dosen** | Lecturer/Professor |
| **Matakuliah** | Course/Subject |
| **OCR** | Optical Character Recognition |
| **Vector Database** | Database for storing and querying embeddings |
| **Embedding** | Numerical vector representation of text |
| **Cosine Similarity** | Metric for measuring similarity between vectors |
| **LLM** | Large Language Model |

---

## REFERENCES

### Technologies Used

#### Backend Framework
- **Laravel 11** - PHP web framework
- **PHP 8.2+** - Programming language

#### Frontend
- **Blade Templates** - Laravel templating engine
- **Alpine.js** - Lightweight JavaScript framework
- **Tailwind CSS** - Utility-first CSS framework

#### Databases
- **MySQL 8.0** - Relational database
- **MongoDB 7.0** - NoSQL document database
- **Redis 7.2** - In-memory data structure store

#### AI & ML
- **Groq** - Fast inference for LLMs
- **Claude (Anthropic)** - Advanced language model
- **Gemini (Google)** - Multimodal AI model
- **OpenRouter** - Unified LLM API
- **Together AI** - Open-source model hosting

#### Document Processing
- **Tesseract OCR** - Optical character recognition
- **PhpSpreadsheet** - Excel file processing
- **PhpWord** - Word document generation
- **TCPDF** - PDF generation

#### External Integrations
- **Library API (CIS DEL)** - Academic data provider
- **WACHAT** - WhatsApp business API

#### DevOps
- **Docker** - Containerization
- **Docker Compose** - Multi-container orchestration
- **Nginx** - Web server
- **Supervisor** - Process manager

### Academic References

1. **RAG Implementation**
   - Lewis, P., et al. (2020). "Retrieval-Augmented Generation for Knowledge-Intensive NLP Tasks"
   
2. **RAGAS Metrics**
   - Es, S., et al. (2023). "RAGAS: Automated Evaluation of Retrieval Augmented Generation"
   
3. **Vector Databases**
   - Johnson, J., et al. (2019). "Billion-scale similarity search with GPUs"
   
4. **Educational Quality Assurance**
   - SPMI (Sistem Penjaminan Mutu Internal) Guidelines 2024
   - BAN-PT Accreditation Standards 9.0

---

## CONTACT & SUPPORT

**Kelompok:** 06  
**Mata Kuliah:** Proyek Akhir 3  
**Institusi:** Institut Teknologi Del  
**Tahun:** 2026  

**Repository:** [GitHub Repository URL]  
**Documentation:** [Documentation URL]  
**Issue Tracker:** [Issue Tracker URL]  

---

## CHANGELOG

### Version 1.0 (15 Juni 2026)
- Initial documentation release
- Complete class diagram coverage
- Comprehensive architecture documentation
- RAG & RAGAS implementation details
- Multi-provider AI integration
- External API documentation

---

**End of Documentation**
