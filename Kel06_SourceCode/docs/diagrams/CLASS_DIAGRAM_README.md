# CLASS DIAGRAM - SISTEM PENJAMINAN MUTU AKADEMIK

Dokumentasi Design Class Diagram untuk Skripsi D4 Teknologi Informasi

---

## 📂 STRUKTUR FILE

```
docs/
├── diagrams/
│   ├── CLASS_DIAGRAM_BUSINESS.puml         ⭐ FILE UTAMA (PlantUML)
│   └── CLASS_DIAGRAM_README.md             📖 File ini
│
├── CLASS_DIAGRAM_BUSINESS_DOCUMENTATION.md  📚 Dokumentasi Lengkap
└── CLASS_DIAGRAM_EXECUTIVE_SUMMARY.md       📄 Ringkasan Eksekutif
```

---

## 🎯 QUICK START

### **Untuk Melihat Diagram:**

**Option 1: Online (Tercepat)**
1. Buka http://www.plantuml.com/plantuml/uml/
2. Copy-paste isi `CLASS_DIAGRAM_BUSINESS.puml`
3. Klik "Submit"

**Option 2: VS Code**
1. Install extension "PlantUML"
2. Buka file `CLASS_DIAGRAM_BUSINESS.puml`
3. Tekan `Alt+D` untuk preview

**Option 3: Draw.io (Untuk Edit)**
1. Buka https://app.diagrams.net/
2. File → Import from → PlantUML
3. Upload file `.puml`

---

## 📖 FILE DOCUMENTATION

### **1. CLASS_DIAGRAM_BUSINESS.puml**
- **Tipe**: PlantUML source code
- **Ukuran**: ~450 lines
- **Isi**: Full class diagram dengan 32 class
- **Gunakan untuk**: Render diagram, import ke tools

### **2. CLASS_DIAGRAM_BUSINESS_DOCUMENTATION.md**
- **Tipe**: Dokumentasi lengkap
- **Ukuran**: ~1000 lines
- **Isi**: 
  - Ringkasan sistem
  - Penjelasan setiap class (32 class)
  - Relasi antar class (45+ relasi)
  - Alasan pemilihan class & relasi
  - Design decisions & trade-offs
- **Gunakan untuk**: Referensi detail, penjelasan skripsi BAB 3


### **3. CLASS_DIAGRAM_EXECUTIVE_SUMMARY.md**
- **Tipe**: Ringkasan eksekutif
- **Ukuran**: ~150 lines
- **Isi**:
  - Overview 32 class dalam 6 kategori
  - Statistik diagram
  - Business rules utama
  - Tips presentasi sidang
- **Gunakan untuk**: Quick reference, persiapan presentasi

---

## 🎓 UNTUK SKRIPSI

### **BAB yang Ter-cover:**
- ✅ **BAB 3.2.2 Class Diagram**: File PUML utama
- ✅ **BAB 3.2 Perancangan Sistem**: Dokumentasi lengkap
- ✅ **Penjelasan Design**: Design decisions di dokumentasi

### **Cara Insert ke Skripsi:**

1. **Render diagram ke gambar:**
   - Buka PlantUML online / VS Code
   - Export ke PNG/SVG (300 DPI minimum)
   - Simpan dengan nama: `class_diagram_business.png`

2. **Insert ke dokumen Word:**
   ```
   Gambar 3.X Class Diagram Sistem Penjaminan Mutu Akademik
   ```

3. **Tulis penjelasan:**
   - Copy dari `CLASS_DIAGRAM_BUSINESS_DOCUMENTATION.md`
   - Section "Penjelasan Detail Setiap Class"
   - Sesuaikan dengan kebutuhan

---

## 📊 ISI DIAGRAM

### **Aktor Sistem (4 Role):**
- Dosen
- GKM (Gugus Kendali Mutu)
- GJM (Gugus Jaminan Mutu)
- Admin

### **Class Diagram (32 Class):**

#### **A. User Management (4)**
- User, RoleEnum, Dosen, ProgramStudi

#### **B. Academic Structure (4)**
- PeriodeAkademik, MataKuliah, JadwalDosen, Kelas

#### **C. RPS & Materials (4)**
- RPS, Materi, StatusEnum, StatusReviewEnum

#### **D. Monitoring & Evaluation (3)**
- MonitoringRPS, MonitoringPerkuliahan, EvaluasiArtefak

#### **E. Questionnaire System (6)**
- Kuesioner, Pertanyaan, Jawaban, HasilAnalisis
- StatusKuisionerEnum, TipePertanyaanEnum

#### **F. Reminder & Notification (5)**
- JadwalReminder, Reminder, LogEmail
- TipeReminderEnum, StatusPengirimanEnum

#### **G. Reporting System (6)**
- TemplateLaporan, LaporanGKM, LaporanGJM
- KirimLaporanHistory, JenisLaporanEnum, StatusLaporanEnum


---

## 🔍 FITUR UNGGULAN

1. **✅ Domain-Focused**: Fokus bisnis akademik, bukan teknis kode
2. **✅ UML Standard**: Mengikuti UML 2.5 specification
3. **✅ Complete**: Semua fitur sistem ter-cover
4. **✅ Readable**: Mudah dipahami untuk presentasi
5. **✅ Production-Ready**: Sudah diimplementasi & validated

---

## ❌ YANG TIDAK DIMASUKKAN

Class teknis yang dihilangkan (untuk simplicity):
- RpsMonitoringSnapshot (teknis caching)
- AIResponseCache (teknis AI optimization)
- DocumentChunk, Embeddings (teknis RAG)
- LaporanBulanan (redundant)
- Junction tables (teknis database)

**Alasan**: Diagram fokus pada domain bisnis, bukan detail implementasi

---

## 💡 TIPS & BEST PRACTICE

### **Untuk Presentasi Sidang:**

**DO:**
- ✅ Highlight 3-4 class utama (RPS, Monitoring, Laporan)
- ✅ Jelaskan business value
- ✅ Fokus pada proses (Dosen → Monitoring → Laporan)
- ✅ Print dalam A3 landscape untuk visibility

**DON'T:**
- ❌ Jangan jelaskan setiap attribute detail
- ❌ Jangan bahas technical implementation
- ❌ Jangan stuck di 1 class terlalu lama
- ❌ Jangan lupa backup digital

### **Untuk Menulis Penjelasan:**

**Struktur yang Baik:**
```
3.2.2 Class Diagram

Class diagram sistem terdiri dari 32 class yang dikelompokkan 
dalam 6 kategori utama...

[Insert Gambar Diagram]

Penjelasan class utama:

A. User Management
   - User: Abstract class yang merepresentasikan...
   - Dosen: Spesialisasi dari User dengan...
   
B. Academic Structure
   ...
```


---

## 🛠️ TOOLS YANG DIBUTUHKAN

### **Untuk Viewing:**
- PlantUML Online (tidak perlu install)
- VS Code + PlantUML Extension (recommended)
- IntelliJ IDEA + PlantUML Plugin

### **Untuk Editing:**
- Draw.io / diagrams.net (recommended)
- PlantUML GUI
- Any text editor (untuk edit .puml)

### **Untuk Export:**
- PNG Export: PlantUML online / VS Code
- SVG Export: PlantUML (vector, best quality)
- PDF Export: Via Draw.io

---

## 📞 SUPPORT

### **Jika Mengalami Masalah:**

**1. Diagram tidak ter-render:**
- Cek syntax PlantUML
- Pastikan semua tag (@startuml/@enduml) ada
- Coba di online viewer dulu

**2. Export hasil blur:**
- Gunakan SVG format (vector)
- Atau PNG dengan 300 DPI minimum
- Hindari screenshot

**3. Diagram terlalu besar:**
- Print dalam A3/A2 landscape
- Atau split menjadi beberapa diagram per kategori

**4. Butuh modifikasi:**
- Edit file `.puml` dengan text editor
- Atau import ke Draw.io untuk visual editing
- Ikuti standar UML notation

---

## 📚 REFERENSI

### **Dokumentasi:**
- PlantUML: https://plantuml.com/class-diagram
- UML 2.5: https://www.omg.org/spec/UML/
- Draw.io: https://app.diagrams.net/

### **Learning Resources:**
- UML Class Diagram Tutorial: https://www.visual-paradigm.com/guide/uml-unified-modeling-language/uml-class-diagram-tutorial/
- Domain-Driven Design by Eric Evans

---

## ✅ CHECKLIST SEBELUM SIDANG

- [ ] Diagram sudah di-render ke PNG/SVG high quality
- [ ] Gambar sudah diinsert ke dokumen skripsi
- [ ] Penjelasan class sudah ditulis di BAB 3
- [ ] Print A3 landscape sudah ready
- [ ] Backup digital (USB + cloud)
- [ ] Sudah latihan presentasi (< 5 menit untuk class diagram)

---

**Version**: 1.0  
**Date**: June 2026  
**Status**: ✅ Ready for Thesis Submission

**Good luck dengan skripsi! 🎓**

