# Class Diagram Complete - Dokumentasi

## 📋 Daftar Isi
1. [Overview](#overview)
2. [Domain Model](#domain-model)
3. [Relasi Antar Class](#relasi-antar-class)
4. [Pattern & Architecture](#pattern--architecture)
5. [Cara Melihat Diagram](#cara-melihat-diagram)

---

## 🎯 Overview

Class diagram lengkap ini menggambarkan seluruh struktur kelas dalam **Sistem Monitoring Mutu Akademik** yang mencakup:

- **40 Model Classes** (Entity & Value Objects)
- **37 Service Classes** (Business Logic)
- **20+ Controller Classes** (Request Handlers)
- **Helper Classes** (Utility Functions)

### Teknologi Stack
- **Backend**: Laravel 10
- **Database**: MySQL (relational) + MongoDB (analytics/cache)
- **AI Integration**: Claude AI, Google Gemini
- **RAG**: Vector embeddings + semantic search
- **Cache**: Redis + Laravel Cache

---

## 📦 Domain Model

### 1️⃣ Master Data & Authentication

#### User
- **Role**: Authenticatable entity
- **Attributes**: name, email, username, role (GJM/GKM/Dosen)
- **Methods**: 
  - `isGKM()`, `isGJM()`, `isDosen()` - Role checking
  - `getProdiNameAttribute()` - Accessor untuk nama prodi

- **Relationships**:
  - `1:1` dengan Dosen
  - `N:1` dengan Prodi
  - `1:N` dengan Reminder, LaporanGKM, LaporanGJM

#### Prodi (Program Studi)
- **Role**: Academic program entity
- **Attributes**: kode_prodi, nama_prodi, nama_singkat
- **Relationships**:
  - `1:N` dengan Dosen, Matakuliah, LaporanGKM
  - `1:1` dengan Dosen (sebagai kepala prodi)

#### Dosen
- **Role**: Lecturer entity
- **Attributes**: nidn, nama_lengkap, jabatan_akademik
- **Special Fields**:
  - `is_kaprodi`: Boolean - status kepala prodi
  - `is_dosen_wali`: Boolean - status dosen wali
  - `kelas_wali`: String - kelas yang diampu
- **Scopes**: `scopeKaprodi()`, `scopeDosenWali()`
- **Relationships**:
  - `1:1` dengan User
  - `N:1` dengan Prodi
  - `1:N` dengan RPS, Materi, Monitoring
  - `N:N` dengan Matakuliah (many-to-many)

#### Dosenn
- **Role**: Alternative Dosen model (external API sync)
- **Purpose**: Sinkronisasi dengan sistem eksternal SIAKAD
- **Attributes**: pegawai_id, dosen_id, nip, nidn

---

### 2️⃣ Akademik

#### Matakuliah
- **Role**: Course/Subject entity
