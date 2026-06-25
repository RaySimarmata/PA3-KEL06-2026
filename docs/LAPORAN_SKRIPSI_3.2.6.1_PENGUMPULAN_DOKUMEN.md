# 3.2.6.1 Pengumpulan Dokumen (Document Collection)

## Definisi dan Tujuan

Pengumpulan dokumen merupakan tahap pertama dan fundamental dalam implementasi sistem Agent-Based Report Assistant berbasis Retrieval-Augmented Generation (RAG). Pada tahap ini, sistem bertugas mengumpulkan, menerima, dan mengorganisir berbagai jenis dokumen akademik yang akan menjadi sumber pengetahuan (knowledge base) bagi sistem AI untuk menghasilkan laporan yang akurat dan kontekstual.

Tujuan utama dari proses pengumpulan dokumen adalah:

1. **Membentuk Basis Pengetahuan Komprehensif**: Mengumpulkan data akademik dari berbagai sumber untuk menciptakan repository pengetahuan yang luas dan mendalam tentang kegiatan akademik institusi.

2. **Memastikan Kelengkapan Informasi**: Menjamin bahwa semua dokumen relevan yang diperlukan untuk pembuatan laporan telah tersedia dalam sistem.

3. **Mengorganisir Sumber Data**: Mengelola dan mengkategorikan dokumen berdasarkan jenis, sumber, dan periode waktu untuk memudahkan proses retrieval di tahap selanjutnya.

## Kategori Dokumen yang Dikumpulkan

Sistem PA3 (Program Aplikasi Ketiga) dirancang untuk menerima dan memproses berbagai kategori dokumen akademik, yang dapat dibagi menjadi lima kelompok utama:

### 3.2.6.1.1 Dokumen Evaluasi Pembelajaran

**Kuesioner Kepuasan Mahasiswa**

Kuesioner kepuasan mahasiswa merupakan instrumen evaluasi yang mengukur tingkat kepuasan dan persepsi mahasiswa terhadap kualitas pembelajaran. Dokumen ini biasanya berbentuk spreadsheet Excel (.xlsx) atau file Comma-Separated Values (.csv) yang berisi:

- **Identitas Responden**: Nama mahasiswa, NIM, program studi, semester
- **Informasi Matakuliah**: Kode matakuliah, nama matakuliah, nama dosen pengampu, kelas
- **Skala Penilaian**: Respons mahasiswa terhadap 20 pertanyaan evaluasi menggunakan skala Likert (1-5)
- **Komentar Tambahan**: Saran dan masukan mahasiswa dalam bentuk teks bebas

Contoh struktur data kuesioner:
```
| NIM        | Nama Mahasiswa | Prodi | Matakuliah           | Dosen        | Q1 | Q2 | ... | Q20 | Komentar |
|------------|----------------|-------|----------------------|--------------|----|----|-----|-----|----------|
| 2024001001 | Ahmad Rizki    | TRPL  | Pemrograman Web      | Dr. Sari     | 4  | 5  | ... | 4   | Bagus    |
| 2024001002 | Siti Nurhaliza | TRPL  | Basis Data           | Prof. Budi   | 3  | 4  | ... | 5   | Perlu... |
```

**Data Monitoring Perkuliahan**

Data monitoring perkuliahan mencakup informasi tentang pelaksanaan kegiatan belajar mengajar, yang meliputi:

- **Kehadiran Dosen**: Rekam jejak kehadiran dosen dalam setiap pertemuan
- **Materi yang Disampaikan**: Topik pembelajaran yang telah dibahas
- **Metode Pembelajaran**: Pendekatan pedagogis yang digunakan (ceramah, diskusi, praktikum)
- **Kendala dan Solusi**: Hambatan yang dihadapi dan cara penyelesaiannya

### 3.2.6.1.2 Dokumen Kurikulum dan Pembelajaran

**Rencana Pembelajaran Semester (RPS)**

RPS merupakan dokumen perencanaan pembelajaran yang berisi rancangan kegiatan pembelajaran untuk satu semester. Dokumen ini biasanya dalam format PDF atau Microsoft Word (.docx) dan mencakup:

- **Identitas Matakuliah**: Nama, kode, SKS, semester, prasyarat
- **Capaian Pembelajaran**: Learning outcomes yang diharapkan
- **Rencana Kegiatan Mingguan**: Jadwal pembelajaran selama 16 pertemuan
- **Metode Penilaian**: Bobot dan jenis evaluasi (UTS, UAS, tugas, praktikum)
- **Referensi**: Daftar buku dan sumber belajar yang digunakan

**Materi Perkuliahan**

Materi perkuliahan berupa dokumen pembelajaran yang dibuat atau digunakan oleh dosen dalam proses belajar mengajar, meliputi:

- **Slide Presentasi**: File PowerPoint (.pptx) berisi materi pembelajaran
- **Modul Pembelajaran**: Dokumen PDF atau Word berisi penjelasan mendalam
- **Handout**: Ringkasan materi dalam format yang ringkas dan praktis
- **Contoh Soal dan Solusi**: Bank soal untuk latihan dan evaluasi

### 3.2.6.1.3 Dokumen Administratif

**Data Penugasan Dosen**

Informasi tentang pembagian tugas mengajar dosen dalam satu periode akademik, yang mencakup:

- **Identitas Dosen**: NIP, nama lengkap, jabatan akademik, program studi
- **Beban Mengajar**: Jumlah SKS dan matakuliah yang diampu
- **Jadwal Mengajar**: Waktu dan tempat pelaksanaan kuliah
- **Kelas yang Diampu**: Daftar kelas dan jumlah mahasiswa per kelas

Data ini dapat diperoleh melalui dua cara:
1. **Integrasi API CIS (Computer and Information System)**: Sinkronisasi otomatis dengan sistem informasi akademik
2. **Input Manual**: Unggah file Excel oleh administrator sistem

**Dokumen Monitoring dan Evaluasi**

Dokumen yang berisi hasil monitoring dan evaluasi kegiatan akademik, seperti:

- **Laporan Semester Sebelumnya**: Hasil evaluasi periode sebelumnya sebagai referensi
- **Data Statistik**: Agregasi data akademik dalam bentuk tabel dan grafik
- **Dokumentasi Kegiatan**: Screenshot dashboard monitoring, foto kegiatan akademik

### 3.2.6.1.4 Dokumen Multimedia

**Gambar dan Screenshot**

Sistem juga menerima dokumen visual berupa:

- **Screenshot Dashboard**: Tangkapan layar dari sistem monitoring GKM (Gugus Kendali Mutu)
- **Foto Dokumentasi**: Gambar kegiatan pembelajaran, seminar, atau workshop
- **Grafik dan Chart**: Visualisasi data dalam format gambar (.jpg, .png, .gif)

Dokumen multimedia ini akan diproses menggunakan teknologi Optical Character Recognition (OCR) untuk mengekstrak teks yang terkandung di dalamnya.

## Sumber Dokumen

Dokumen-dokumen tersebut berasal dari berbagai sumber di lingkungan akademik:

### 3.2.6.1.5 Sumber Internal

**Gugus Kendali Mutu (GKM)**

GKM berperan sebagai unit yang bertanggung jawab dalam pengumpulan dan pengelolaan data kualitas pembelajaran. Dokumen dari GKM meliputi:

- Hasil kuesioner mahasiswa yang telah dikompilasi
- Data monitoring kehadiran dosen dan mahasiswa
- Laporan evaluasi pembelajaran per semester
- Rekomendasi perbaikan kualitas pembelajaran

**Gugus Jaminan Mutu (GJM)**

GJM berfungsi sebagai unit yang menggunakan data dari GKM untuk membuat laporan dan analisis. Dokumen dari GJM mencakup:

- Template laporan triwulan dan semester
- Standar operating procedure (SOP) pelaporan
- Dokumen kebijakan mutu akademik
- Hasil analisis tren kualitas pembelajaran

**Dosen dan Tenaga Kependidikan**

Kontribusi langsung dari para akademisi berupa:

- RPS yang telah disusun dan disetujui
- Materi pembelajaran hasil pengembangan mandiri
- Dokumentasi inovasi pembelajaran
- Laporan pelaksanaan kegiatan akademik

### 3.2.6.1.6 Sumber Eksternal

**Sistem Informasi Akademik (CIS)**

Integrasi dengan Computer and Information System (CIS) memungkinkan sistem memperoleh:

- Data penugasan dosen secara real-time
- Informasi jadwal kuliah dan ruang kelas
- Data mahasiswa dan program studi
- Kalender akademik dan periode perkuliahan

**Sistem Monitoring Nasional**

Data referensi dari sistem monitoring tingkat nasional seperti:

- Standar nasional pendidikan tinggi
- Indikator kinerja perguruan tinggi
- Benchmark kualitas pembelajaran
- Regulasi dan kebijakan pendidikan terbaru

## Metode Pengumpulan Dokumen

Sistem PA3 mengimplementasikan beberapa metode pengumpulan dokumen untuk memastikan kelengkapan dan keakuratan data:

### 3.2.6.1.7 Upload Manual

**Antarmuka Web Upload**

Sistem menyediakan antarmuka web yang memungkinkan pengguna untuk mengunggah dokumen secara manual. Fitur ini mencakup:

- **Drag and Drop Interface**: Kemudahan mengunggah file dengan cara menyeret dan menjatuhkan
- **Multiple File Selection**: Kemampuan mengunggah beberapa file sekaligus
- **Progress Indicator**: Indikator kemajuan unggah untuk file berukuran besar
- **Format Validation**: Validasi otomatis format file yang diizinkan

**Validasi File**

Setiap file yang diunggah melalui proses validasi:

- **Format Check**: Memastikan file sesuai dengan format yang didukung (.pdf, .docx, .xlsx, .jpg, .png)
- **Size Limitation**: Membatasi ukuran file maksimal 50MB per file
- **Content Scanning**: Pemindaian dasar untuk memastikan file tidak berisi malware
- **Metadata Extraction**: Ekstraksi informasi metadata seperti tanggal pembuatan, author, dan properties lainnya

### 3.2.6.1.8 Sinkronisasi Otomatis

**API Integration dengan CIS**

Sistem melakukan sinkronisasi otomatis dengan CIS melalui Application Programming Interface (API):

- **Scheduled Synchronization**: Sinkronisasi terjadwal setiap 6 jam sekali
- **Real-time Updates**: Update data secara real-time untuk perubahan kritis
- **Error Handling**: Mekanisme penanganan error dengan retry logic
- **Data Validation**: Validasi data yang diterima dari API eksternal

**Webhook Integration**

Implementasi webhook untuk menerima notifikasi perubahan data:

- **Event-driven Updates**: Update data berdasarkan event tertentu
- **Payload Processing**: Pemrosesan data yang diterima melalui webhook
- **Security Authentication**: Autentikasi webhook untuk memastikan keamanan
- **Logging and Monitoring**: Pencatatan semua aktivitas webhook untuk audit

## Organisasi dan Penyimpanan Dokumen

### 3.2.6.1.9 Struktur Direktori

Dokumen yang terkumpul diorganisir dalam struktur direktori yang sistematis:

```
storage/documents/
├── kuesioner/
│   ├── 2026/
│   │   ├── semester_1/
│   │   │   ├── trpl/
│   │   │   ├── ti/
│   │   │   └── sib/
│   │   └── semester_2/
│   └── 2025/
├── rps/
│   ├── 2026_ganjil/
│   ├── 2026_genap/
│   └── templates/
├── materi_kuliah/
│   ├── by_matakuliah/
│   ├── by_dosen/
│   └── by_semester/
├── penugasan/
│   ├── current/
│   ├── history/
│   └── sync_logs/
└── multimedia/
    ├── screenshots/
    ├── photos/
    └── processed/
```

### 3.2.6.1.10 Metadata Management

Setiap dokumen yang dikumpulkan dilengkapi dengan metadata komprehensif:

**Metadata Dasar**
- **File Information**: Nama file, ukuran, format, checksum
- **Upload Information**: Tanggal upload, user yang mengunggah, IP address
- **Source Information**: Asal dokumen (manual upload, API sync, webhook)
- **Processing Status**: Status pemrosesan dokumen (pending, processed, error)

**Metadata Akademik**
- **Academic Period**: Tahun akademik, semester, periode
- **Subject Matter**: Program studi, matakuliah, kelas yang terkait
- **Content Type**: Jenis konten (evaluasi, kurikulum, administrasi, multimedia)
- **Relevance Score**: Skor relevansi dokumen untuk proses retrieval

**Metadata Teknis**
- **Processing History**: Riwayat pemrosesan dokumen
- **Version Control**: Informasi versi jika dokumen diperbarui
- **Access Rights**: Hak akses dan pembatasan penggunaan
- **Retention Policy**: Kebijakan retensi dan siklus hidup dokumen

## Quality Assurance dan Kontrol Kualitas

### 3.2.6.1.11 Validasi Konten

Sistem mengimplementasikan mekanisme quality assurance untuk memastikan kualitas dokumen yang dikumpulkan:

**Content Validation**
- **Completeness Check**: Memastikan dokumen lengkap dan tidak corrupt
- **Relevance Assessment**: Penilaian relevansi dokumen dengan tujuan sistem
- **Duplicate Detection**: Deteksi dan penanganan dokumen duplikat
- **Content Quality Score**: Pemberian skor kualitas berdasarkan kelengkapan dan relevansi

**Data Integrity**
- **Consistency Check**: Memastikan konsistensi data antar dokumen
- **Cross-reference Validation**: Validasi silang dengan data referensi
- **Temporal Validation**: Validasi kesesuaian periode dan waktu
- **Business Rule Compliance**: Kepatuhan terhadap aturan bisnis institusi

### 3.2.6.1.12 Monitoring dan Audit Trail

**Collection Monitoring**
- **Volume Tracking**: Monitoring volume dokumen yang dikumpulkan
- **Source Analysis**: Analisis distribusi dokumen berdasarkan sumber
- **Performance Metrics**: Metrik kinerja proses pengumpulan dokumen
- **Error Rate Monitoring**: Monitoring tingkat error dan kegagalan

**Audit Trail**
- **Activity Logging**: Pencatatan semua aktivitas pengumpulan dokumen
- **User Action Tracking**: Tracking aksi user dalam proses upload
- **System Event Logging**: Log event sistem terkait pengumpulan dokumen
- **Compliance Reporting**: Laporan kepatuhan untuk audit internal

## Tantangan dan Solusi

### 3.2.6.1.13 Tantangan Implementasi

**Volume Data**
Penanganan volume data yang besar memerlukan strategi khusus:
- **Storage Scalability**: Penyediaan storage yang dapat berkembang sesuai kebutuhan
- **Processing Capacity**: Kapasitas pemrosesan yang memadai untuk volume tinggi
- **Network Bandwidth**: Bandwidth yang cukup untuk transfer file besar

**Keragaman Format**
Beragamnya format dokumen menimbulkan kompleksitas:
- **Format Compatibility**: Kompatibilitas dengan berbagai format file
- **Version Differences**: Perbedaan versi aplikasi pembuat dokumen
- **Encoding Issues**: Masalah encoding karakter dalam berbagai bahasa

### 3.2.6.1.14 Solusi yang Diimplementasikan

**Teknologi Adaptif**
- **Multi-format Parser**: Parser yang mendukung berbagai format dokumen
- **Automatic Format Detection**: Deteksi otomatis format file
- **Fallback Mechanisms**: Mekanisme fallback untuk format yang tidak didukung
- **Cloud Storage Integration**: Integrasi dengan cloud storage untuk skalabilitas

**Quality Control Automation**
- **Automated Validation**: Validasi otomatis menggunakan rule engine
- **Machine Learning Quality Assessment**: Penilaian kualitas menggunakan ML
- **Smart Duplicate Detection**: Deteksi duplikat menggunakan algoritma advanced
- **Content Enrichment**: Pengayaan konten otomatis dengan metadata tambahan

## Keluaran Proses Pengumpulan Dokumen

Hasil dari proses pengumpulan dokumen adalah terbentuknya repository dokumen yang terorganisir dan siap untuk diproses pada tahap selanjutnya. Keluaran ini meliputi:

### 3.2.6.1.15 Document Repository

**Organized Document Structure**
- Kumpulan dokumen yang terstruktur berdasarkan kategori, sumber, dan periode
- Metadata lengkap untuk setiap dokumen
- Index dokumen untuk mempercepat pencarian dan retrieval

**Quality-assured Content**
- Dokumen yang telah melalui proses validasi dan quality control
- Dokumen bebas duplikasi dan dengan tingkat relevansi tinggi
- Content yang siap untuk diproses pada tahap data ingestion

### 3.2.6.1.16 Statistik dan Metrics

**Collection Statistics**
- Jumlah dokumen berdasarkan kategori dan periode
- Distribusi ukuran file dan format dokumen
- Tingkat keberhasilan pengumpulan dari berbagai sumber
- Tren volume dokumen dari waktu ke waktu

**Quality Metrics**
- Skor kualitas rata-rata dokumen yang dikumpulkan
- Tingkat duplikasi dan cara penanganannya
- Distribusi relevansi dokumen terhadap tujuan sistem
- Metrics kinerja proses pengumpulan dokumen

## Kesimpulan

Pengumpulan dokumen merupakan fondasi penting dalam implementasi sistem Agent-Based Report Assistant berbasis RAG. Proses ini tidak hanya sekadar mengumpulkan file, tetapi juga melibatkan organisasi, validasi, dan quality assurance yang komprehensif. Dengan implementasi yang tepat, tahap pengumpulan dokumen akan menghasilkan knowledge base yang berkualitas tinggi dan menjadi dasar yang kuat untuk tahapan-tahapan selanjutnya dalam pipeline RAG.

Keberhasilan tahap ini sangat menentukan kualitas output sistem secara keseluruhan, karena sistem AI hanya dapat menghasilkan laporan yang sebaik sumber pengetahuan yang tersedia. Oleh karena itu, investasi dalam desain dan implementasi proses pengumpulan dokumen yang robust merupakan kunci sukses implementasi sistem Agent-Based Report Assistant.

---

**Catatan**: Dokumen ini merupakan bagian dari laporan implementasi sistem PA3-KEL06-2026 dan menjelaskan secara detail tahap pertama dalam workflow Agent-Based Report Assistant berbasis Retrieval-Augmented Generation (RAG).