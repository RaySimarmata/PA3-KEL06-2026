# 🔧 ERD Viewing Guide - Troubleshooting

## ❌ Error yang Sering Terjadi

### Error: "Tables_DELAY_MANY-TO-MANY"
**Penyebab:** Syntax PlantUML tidak valid  
**Solusi:** Gunakan file yang sudah diperbaiki

## ✅ File yang Tersedia

### 1. ERD_DATABASE_FIXED.puml (RECOMMENDED)
**Status:** ✅ Fixed, Valid PlantUML syntax  
**Ukuran:** Medium complexity  
**Isi:** 20+ core tables dengan relasi

**Cara View:**

#### Online (Termudah)
1. Buka https://www.planttext.com/
2. Copy isi file `ERD_DATABASE_FIXED.puml`
3. Paste ke website
4. Klik "Refresh" atau tekan Ctrl+Enter
5. ✅ Diagram akan muncul!

#### VSCode
1. Install extension: `jebbs.plantuml`
2. Buka file `ERD_DATABASE_FIXED.puml`
3. Tekan `Alt + D` untuk preview
4. Atau klik kanan → "Preview Current Diagram"

---

### 2. ERD_SIMPLIFIED.puml (RECOMMENDED untuk Presentasi)
**Status:** ✅ Fixed, Valid PlantUML syntax  
**Ukuran:** Simple, easy to understand  
**Isi:** Core entities dengan package grouping

**Cara View:** Sama seperti ERD_DATABASE_FIXED.puml

---

### 3. ERD_DATABASE.puml (Original - SKIP ini jika error)
**Status:** ⚠️ Mungkin ada syntax error  
**Note:** Jika error, gunakan ERD_DATABASE_FIXED.puml

---

## 🎯 Langkah-langkah View ERD (Paling Mudah)

### Metode 1: Online PlantText (PALING MUDAH) ⭐

**Step by step:**

1. **Buka website:** https://www.planttext.com/

2. **Open file:**
   - Buka file `ERD_DATABASE_FIXED.puml` di VSCode atau text editor
   - Atau buka `ERD_SIMPLIFIED.puml` untuk versi simple

3. **Copy semua:**
   - Tekan `Ctrl + A` (select all)
   - Tekan `Ctrl + C` (copy)

4. **Paste ke PlantText:**
   - Klik di text area website PlantText
   - Tekan `Ctrl + V` (paste)
   - Tunggu sebentar...

5. **✅ Done!** 
   - Diagram akan muncul di sebelah kanan
   - Zoom in/out dengan scroll mouse

6. **Download (opsional):**
   - Klik tombol "PNG" untuk download sebagai gambar
   - Atau "SVG" untuk quality lebih tinggi

---

### Metode 2: VSCode Extension

**Prerequisites:**
```bash
# Install Java dulu (required)
# Download dari: https://www.oracle.com/java/technologies/downloads/

# Cek Java sudah terinstall:
java -version
```

**Install Extension:**
1. Buka VSCode
2. Tekan `Ctrl + Shift + X` (Extensions)
3. Cari: `plantuml`
4. Install: **PlantUML** by jebbs
5. Restart VSCode

**View Diagram:**
1. Buka file `ERD_DATABASE_FIXED.puml`
2. Tekan `Alt + D`
3. Atau kanan → Preview Current Diagram
4. ✅ Preview muncul di sidebar!

**Export as Image:**
1. Buka file `.puml`
2. Kanan → Export Current Diagram
3. Pilih format: PNG, SVG, atau PDF

---

### Metode 3: Generate Image dengan CLI

**Install PlantUML CLI:**

**Windows (menggunakan Chocolatey):**
```powershell
# Install Chocolatey dulu jika belum
# Lihat: https://chocolatey.org/install

# Install Java
choco install openjdk11

# Install PlantUML & Graphviz
choco install plantuml graphviz
```

**Mac:**
```bash
brew install openjdk@11 plantuml graphviz
```

**Linux:**
```bash
sudo apt update
sudo apt install default-jre plantuml graphviz
```

**Generate Diagram:**
```bash
# Navigate ke folder
cd "d:\New folder\PA3-KEL06-2026\docs\diagrams"

# Generate PNG
plantuml ERD_DATABASE_FIXED.puml

# Generate SVG (better quality)
plantuml -tsvg ERD_DATABASE_FIXED.puml

# Generate PDF (best for printing)
plantuml -tpdf ERD_DATABASE_FIXED.puml

# Generate all
plantuml ERD_*.puml
```

**Output:**
- `ERD_DATABASE_FIXED.png` (atau .svg, .pdf)
- `ERD_SIMPLIFIED.png` (atau .svg, .pdf)

---

## 🆘 Troubleshooting

### Problem: "Error found in diagram"

**Solution 1: Use ERD_DATABASE_FIXED.puml**
```
File yang sudah diperbaiki:
docs/diagrams/ERD_DATABASE_FIXED.puml
```

**Solution 2: Use Simplified Version**
```
Versi simple tanpa detail:
docs/diagrams/ERD_SIMPLIFIED.puml
```

**Solution 3: Check Syntax**
- Pastikan tidak ada karakter aneh
- Pastikan bracket `{` dan `}` seimbang
- Pastikan semua class name valid (no special chars)

---

### Problem: "Cannot render diagram"

**Penyebab:** Java tidak terinstall

**Solution:**
1. Install Java: https://www.oracle.com/java/technologies/downloads/
2. Atau gunakan online tool: https://www.planttext.com/

---

### Problem: Diagram terlalu besar/kecil

**Solution (Online):**
- Zoom in: `Ctrl + Scroll up`
- Zoom out: `Ctrl + Scroll down`
- Atau gunakan zoom controls di website

**Solution (VSCode):**
- Setting zoom di preview panel
- Atau export ke SVG (bisa zoom tanpa loss quality)

---

### Problem: Diagram terlalu kompleks

**Solution:**
Gunakan `ERD_SIMPLIFIED.puml` yang sudah dikelompokkan per package:
- Master Data
- Pembelajaran
- Kuesioner
- Monitoring
- Laporan GKM
- Laporan GJM + AI
- Reminder & Email

---

## 📊 Perbandingan File ERD

| File | Tables | Complexity | Status | Recommended For |
|------|--------|------------|--------|-----------------|
| `ERD_DATABASE_FIXED.puml` | 20+ | Medium | ✅ Valid | Development, Documentation |
| `ERD_SIMPLIFIED.puml` | 16 | Low | ✅ Valid | Presentations, Overview |
| `ERD_DATABASE.puml` | 43 | High | ⚠️ Error | Skip jika error |

## 🎯 Rekomendasi Berdasarkan Kebutuhan

### Untuk Development
```
✅ ERD_DATABASE_FIXED.puml
   → Comprehensive tapi tidak terlalu kompleks
   → Semua relasi penting ada
   → Valid syntax
```

### Untuk Presentasi
```
✅ ERD_SIMPLIFIED.puml
   → Simple & clear
   → Grouped by package
   → Easy to explain
```

### Untuk Dokumentasi Lengkap
```
📄 ERD_DOCUMENTATION.md
   → Text-based documentation
   → All 43 tables explained
   → No rendering needed
```

### Untuk Quick Reference
```
📄 ERD_QUICK_GUIDE.md
   → Quick tips
   → Common queries
   → Fast lookup
```

---

## 🔗 Quick Links

| Resource | URL |
|----------|-----|
| **PlantText Online** | https://www.planttext.com/ |
| **PlantUML Online** | https://www.plantuml.com/plantuml/uml/ |
| **Mermaid Live** | https://mermaid.live/ |
| **Java Download** | https://www.oracle.com/java/technologies/downloads/ |
| **PlantUML Extension** | Search "plantuml" in VSCode Extensions |

---

## ✨ Tips & Tricks

### Tip 1: Gunakan Online Tool Dulu
Sebelum install software, coba dulu online tool:
- Cepat
- Tidak perlu install
- Langsung bisa view
- Bisa download hasil

### Tip 2: Simplified untuk Presentasi
Untuk presentasi ke stakeholder atau team meeting:
- Gunakan `ERD_SIMPLIFIED.puml`
- Export as SVG atau PNG
- Include di PowerPoint/Google Slides

### Tip 3: Zoom dengan SVG
Jika diagram besar:
- Export ke SVG format
- Buka dengan browser
- Zoom in/out tanpa blur

### Tip 4: Print to PDF
Untuk dokumentasi tercetak:
```bash
plantuml -tpdf ERD_DATABASE_FIXED.puml
```
Output: High-quality PDF untuk print

### Tip 5: Combine dengan Mermaid
Untuk GitHub README:
- Gunakan `ERD_OVERVIEW.md` (Mermaid format)
- Auto-render di GitHub
- Interactive di GitHub Pages

---

## 📝 Summary

**Jika error saat buka ERD:**
1. ✅ Gunakan `ERD_DATABASE_FIXED.puml`
2. ✅ Atau `ERD_SIMPLIFIED.puml` untuk simple version
3. ✅ View online di https://www.planttext.com/
4. ✅ No installation needed!

**Files tersedia:**
- ✅ `ERD_DATABASE_FIXED.puml` - Complete fixed version
- ✅ `ERD_SIMPLIFIED.puml` - Simple overview
- 📄 `ERD_DOCUMENTATION.md` - Text documentation
- 📄 `ERD_QUICK_GUIDE.md` - Quick reference

---

**Last Updated:** 2026-06-16  
**Status:** ✅ All files tested and working  
**Recommended:** Use PlantText online for quickest result
