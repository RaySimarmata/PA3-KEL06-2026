# 📊 ERD Visual Overview

## Database Structure at a Glance

### Core Entity Groups

```mermaid
graph TB
    subgraph Master["🏢 MASTER DATA"]
        Prodi[Prodi<br/>Program Studi]
        Users[Users<br/>System Users]
        Dosen[Dosen<br/>Lecturers]
        MK[Matakuliah<br/>Courses]
        Ajaran[Ajaran<br/>Academic Period]
    end
    
    subgraph Learn["📚 PEMBELAJARAN"]
        RPS[RPS<br/>Learning Plan]
        Materi[Materi<br/>Materials]
    end
    
    subgraph Survey["📝 KUESIONER"]
        Kuis[Kuisioner<br/>Survey]
        KuisUpload[KuesioneUpload<br/>External Survey]
    end
    
    subgraph Monitor["📊 MONITORING"]
        Mon[Monitoring<br/>Monitoring Data]
        SnapRPS[RpsMonitoringSnapshot<br/>RPS Snapshot]
        SnapKul[PerkuliahanSnapshot<br/>Class Snapshot]
    end
    
    subgraph LapGKM["📄 LAPORAN GKM"]
        LGKM[LaporanGKM<br/>Prodi Report]
        Artefak[EvaluasiArtefak<br/>Artifact Evaluation]
    end
    
    subgraph LapGJM["📊 LAPORAN GJM + AI"]
        Template[TemplateLaporan<br/>Report Template]
        LGJM[LaporanGJM<br/>Institution Report<br/>🤖 AI-Powered]
        Chunk[DocumentChunk<br/>RAG Chunks<br/>🧠 Vectors]
        AICache[AIResponseCache<br/>AI Cache]
    end
    
    subgraph Email["📧 REMINDER & EMAIL"]
        Remind[Reminder<br/>Reminders]
        LogEmail[LogEmail<br/>Email Logs]
    end
    
    Prodi -->|has many| Users
    Prodi -->|has many| Dosen
    Prodi -->|has many| MK
    Prodi -->|has many| Ajaran
    Prodi -->|monitors| Mon
    Prodi -->|reports| LGKM
    
    Users -.->|profile| Dosen
    
    MK -->|has| RPS
    RPS -->|contains| Materi
    Dosen -->|creates| RPS
    Ajaran -->|period for| RPS
    
    Ajaran -->|period for| Kuis
    MK -->|evaluated in| KuisUpload
    
    Prodi -->|snapshot| SnapRPS
    Prodi -->|snapshot| SnapKul
    Ajaran -->|snapshot| SnapRPS
    
    Dosen -->|leads| LGKM
    LGKM -->|includes| Artefak
    
    Template -->|used by| LGJM
    Template -->|chunked to| Chunk
    Ajaran -->|reported in| LGJM
    
    Dosen -->|receives| Remind
    Remind -->|logs to| LogEmail
    
    style Prodi fill:#ff6b6b,stroke:#c92a2a,color:#fff
    style LGJM fill:#4dabf7,stroke:#1971c2,color:#fff
    style Chunk fill:#51cf66,stroke:#2f9e44,color:#fff
    style AICache fill:#ffd43b,stroke:#f59f00,color:#000
```

## Relationship Types

```mermaid
graph LR
    subgraph "One-to-Many"
        A[Prodi] -->|1:N| B[Dosen]
        C[RPS] -->|1:N| D[Materi]
    end
    
    subgraph "One-to-One"
        E[Users] <-->|1:1| F[Dosen]
    end
    
    subgraph "Many-to-Many"
        G[Dosen] -.->|M:N| H[Matakuliah]
        I[dosen_matakuliah<br/>pivot table] 
        G --> I
        I --> H
    end
    
    style A fill:#ff6b6b,color:#fff
    style B fill:#ff6b6b,color:#fff
    style E fill:#4dabf7,color:#fff
    style F fill:#4dabf7,color:#fff
    style G fill:#51cf66,color:#fff
    style H fill:#51cf66,color:#fff
    style I fill:#ffd43b,color:#000
```

## AI & RAG System Flow

```mermaid
graph TB
    subgraph Input["📥 INPUT"]
        Doc[Documents<br/>PDF, DOCX, etc]
        Template[Template<br/>Laporan]
    end
    
    subgraph Processing["⚙️ PROCESSING"]
        Chunk[Document<br/>Chunking]
        Embed[Generate<br/>Embeddings]
        Store[Store in<br/>document_chunks]
    end
    
    subgraph Query["🔍 QUERY"]
        UserQ[User Query<br/>AI Question]
        CheckCache{Cache<br/>Hit?}
        RAG[RAG Retrieval<br/>Vector Search]
        AI[AI Generation<br/>Claude/Gemini]
    end
    
    subgraph Output["📤 OUTPUT"]
        Response[AI Response]
        RAGAS[RAGAS Evaluation<br/>4 Metrics]
        Report[Final Report<br/>PDF/DOCX/PPT]
    end
    
    subgraph Cache["💾 CACHE"]
        EmbedCache[embeddings_cache]
        AICache[ai_response_cache]
    end
    
    Doc --> Chunk
    Template --> Chunk
    Chunk --> Embed
    Embed --> Store
    Embed -.-> EmbedCache
    
    UserQ --> CheckCache
    CheckCache -->|Yes| Response
    CheckCache -->|No| RAG
    RAG --> Store
    RAG --> AI
    AI --> Response
    Response -.-> AICache
    Response --> RAGAS
    RAGAS --> Report
    
    style Doc fill:#e3fafc
    style Response fill:#d0ebff
    style AI fill:#ffd43b,color:#000
    style RAG fill:#b2f2bb
    style RAGAS fill:#ffc9c9
    style EmbedCache fill:#fff3bf
    style AICache fill:#fff3bf
```

## Monitoring & Snapshot Pattern

```mermaid
graph TB
    subgraph Real["📊 REAL-TIME DATA"]
        RPS[rps table<br/>~2,000 records]
        Materi[materi table<br/>~30,000 records]
        Kuis[kuesioner table<br/>~100 records]
    end
    
    subgraph Compute["⚙️ COMPUTE"]
        Calc[Calculate<br/>Metrics]
    end
    
    subgraph Snap["📸 SNAPSHOTS"]
        SnapRPS[rps_monitoring_snapshots<br/>Daily snapshot]
        SnapKul[perkuliahan_monitoring_snapshots<br/>Daily snapshot]
        SnapDetail[perkuliahan_monitoring_detail<br/>Detailed data]
    end
    
    subgraph Report["📈 REPORTING"]
        Fast[Fast Reports<br/>No recompute]
    end
    
    RPS --> Calc
    Materi --> Calc
    Kuis --> Calc
    
    Calc --> SnapRPS
    Calc --> SnapKul
    Calc --> SnapDetail
    
    SnapRPS --> Fast
    SnapKul --> Fast
    SnapDetail --> Fast
    
    style RPS fill:#ffc9c9
    style Materi fill:#ffc9c9
    style Calc fill:#fff3bf,color:#000
    style SnapRPS fill:#b2f2bb
    style Fast fill:#d0ebff
```

## Laporan GKM vs GJM

```mermaid
graph TB
    subgraph GKM["📄 GKM - Program Studi Level"]
        LGKM[LaporanGKM]
        DosenK[Dosen Ketua]
        ProdiGKM[Prodi]
        AjaranGKM[Ajaran]
        
        ProdiGKM --> LGKM
        DosenK --> LGKM
        AjaranGKM --> LGKM
        LGKM --> Artefak[EvaluasiArtefak]
        
        LGKM -.->|contains| RGKM[Results:<br/>- Monitoring RPS<br/>- Monitoring Materi<br/>- Monitoring Kuesioner<br/>- Rencana Perbaikan]
    end
    
    subgraph GJM["📊 GJM - Institution Level"]
        LGJM[LaporanGJM<br/>🤖 AI-Powered]
        TemplateGJM[Template]
        AjaranGJM[Ajaran]
        
        TemplateGJM --> LGJM
        AjaranGJM --> LGJM
        LGJM --> AI[AI Features:<br/>- RAG Retrieval<br/>- RAGAS Evaluation<br/>- OCR Support<br/>- PPT Generation]
        
        TemplateGJM --> Chunks[DocumentChunks<br/>🧠 Vector DB]
        Chunks --> RAGRet[RAG Retrieval]
        RAGRet --> LGJM
    end
    
    style LGKM fill:#ffc9c9
    style LGJM fill:#4dabf7,color:#fff
    style AI fill:#ffd43b,color:#000
    style Chunks fill:#b2f2bb
```

## User Roles & Access

```mermaid
graph TB
    subgraph Roles["👥 USER ROLES"]
        Admin[Admin<br/>🔑 Full Access]
        Kaprodi[Kaprodi<br/>📋 Prodi Head]
        Dosen[Dosen<br/>📚 Lecturer]
        GJMRev[GJM Reviewer<br/>✅ Institution Level]
        GKMRev[GKM Reviewer<br/>✅ Prodi Level]
    end
    
    subgraph Access["🔐 ACCESS"]
        AdminAcc[- All modules<br/>- User management<br/>- System config]
        KaprodiAcc[- Prodi data<br/>- GKM reports<br/>- Monitoring<br/>- Approvals]
        DosenAcc[- Own RPS<br/>- Own materi<br/>- Own laporan<br/>- Kuesioner]
        GJMAcc[- Review GJM reports<br/>- Institution data<br/>- All prodi]
        GKMAcc[- Review GKM reports<br/>- Prodi data<br/>- Specific prodi]
    end
    
    Admin --> AdminAcc
    Kaprodi --> KaprodiAcc
    Dosen --> DosenAcc
    GJMRev --> GJMAcc
    GKMRev --> GKMAcc
    
    style Admin fill:#ff6b6b,color:#fff
    style Kaprodi fill:#ffd43b,color:#000
    style Dosen fill:#b2f2bb
    style GJMRev fill:#d0ebff
    style GKMRev fill:#ffc9c9
```

## Data Flow: Creating GKM Report

```mermaid
sequenceDiagram
    participant D as Dosen/Kaprodi
    participant S as System
    participant DB as Database
    participant M as Monitoring
    participant R as Report
    
    D->>S: Create Laporan GKM
    S->>M: Fetch monitoring data
    M->>DB: Query rps_monitoring_snapshots
    M->>DB: Query perkuliahan_snapshots
    M->>DB: Query kuesioner_uploads
    DB-->>M: Return snapshot data
    M-->>S: Aggregated data
    S->>R: Generate report
    R->>DB: Insert laporan_gkm
    R-->>D: Report created (draft)
    D->>S: Submit for review
    S->>DB: Update status → menunggu_review
    DB-->>D: Status updated
```

## Data Flow: Creating GJM Report with AI

```mermaid
sequenceDiagram
    participant U as User (GJM)
    participant S as System
    participant T as Template
    participant RAG as RAG System
    participant AI as AI Service
    participant DB as Database
    participant E as Evaluator
    
    U->>S: Create Laporan GJM
    S->>T: Select template
    T->>RAG: Prepare context
    RAG->>DB: Retrieve document_chunks
    DB-->>RAG: Vector search results
    RAG->>AI: Generate with context
    AI-->>S: AI response + sources
    S->>DB: Save laporan_gjm
    S->>E: Evaluate with RAGAS
    E->>DB: Save evaluation metrics
    E-->>U: Report generated with scores
    U->>S: Generate PPT
    S->>AI: Create presentation
    AI-->>U: PPT file ready
```

## Technology Stack

```mermaid
graph LR
    subgraph Backend["🔧 BACKEND"]
        Laravel[Laravel 10.x]
        MySQL[MySQL/PostgreSQL]
        Redis[Redis Cache]
    end
    
    subgraph AI["🤖 AI/ML"]
        Claude[Claude AI]
        Gemini[Gemini AI]
        OpenAI[OpenAI Embeddings]
    end
    
    subgraph Vector["🧠 VECTOR DB"]
        PG[pgvector<br/>PostgreSQL]
        Mongo[MongoDB Atlas<br/>Vector Search]
    end
    
    subgraph Queue["⚡ QUEUE"]
        LaravelQ[Laravel Queue]
        Jobs[Background Jobs]
    end
    
    subgraph Storage["💾 STORAGE"]
        Local[Local Storage]
        S3[AWS S3<br/>Optional]
    end
    
    Laravel --> MySQL
    Laravel --> Redis
    Laravel --> LaravelQ
    LaravelQ --> Jobs
    
    Laravel --> Claude
    Laravel --> Gemini
    Laravel --> OpenAI
    
    MySQL --> PG
    Laravel -.-> Mongo
    
    Laravel --> Local
    Laravel -.-> S3
    
    style Laravel fill:#ff6b6b,color:#fff
    style MySQL fill:#4dabf7,color:#fff
    style PG fill:#51cf66,color:#fff
    style Claude fill:#ffd43b,color:#000
```

## Performance Optimizations

```mermaid
mindmap
  root((Performance<br/>Strategies))
    Caching
      AI Response Cache
      Embedding Cache
      Laravel Cache
      Redis Cache
    Snapshots
      RPS Monitoring
      Perkuliahan Monitoring
      Daily Updates
    Indexing
      Primary Keys
      Foreign Keys
      Vector Indexes
      Composite Indexes
    Queue
      Background Jobs
      Email Sending
      Report Generation
      AI Processing
    Optimization
      Eager Loading
      Query Optimization
      Pagination
      Data Archiving
```

## Summary Statistics

| Category | Count | Notes |
|----------|-------|-------|
| **Total Tables** | 43 | Including system tables |
| **Master Data** | 9 | Core entities |
| **Application** | 22 | Business logic |
| **AI/RAG** | 4 | AI system |
| **System** | 3 | Laravel system |
| **Relations** | 5 | Pivot/junction tables |
| **Foreign Keys** | 100+ | Relationships |
| **Indexes** | 50+ | Performance |
| **JSON Fields** | 15+ | Flexible data |
| **Vector Fields** | 2 | Embeddings |

## File Reference

| File | Purpose | View |
|------|---------|------|
| `ERD_DATABASE.puml` | Complete ERD (43 tables) | PlantUML |
| `ERD_SIMPLIFIED.puml` | Simplified overview | PlantUML |
| `ERD_DOCUMENTATION.md` | Full documentation | Markdown |
| `ERD_README.md` | Detailed guide | Markdown |
| `ERD_OVERVIEW.md` | This file (visual) | Mermaid |

## Quick Links

- 🌐 [View PlantUML Online](https://www.planttext.com/)
- 🌐 [View Mermaid Online](https://mermaid.live/)
- 📖 [Quick Guide](../ERD_QUICK_GUIDE.md)
- 📘 [Full Documentation](./ERD_DOCUMENTATION.md)

---

**Last Updated:** 2026-06-16  
**Format:** Mermaid (GitHub-native rendering)  
**Status:** ✅ Complete
