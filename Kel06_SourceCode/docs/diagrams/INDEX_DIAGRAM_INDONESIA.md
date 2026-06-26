# 📚 Indeks Diagram Class - Versi Indonesia

## 🎯 Ringkasan

Dokumentasi ini berisi **5 versi Class Diagram** dengan fokus berbeda untuk kebutuhan dokumentasi yang beragam. Versi terbaru menambahkan **2 diagram Bahasa Indonesia** dengan fitur-fitur khusus.

---

## 📁 Daftar File Diagram

### 🇬🇧 **Versi Bahasa Inggris**

| No | File | Deskripsi | Best For |
|----|------|-----------|----------|
| 1 | `CLASS_DIAGRAM_FROM_SQL.puml` | Standard orthogonal layout | Technical documentation |
| 2 | `CLASS_DIAGRAM_FROM_SQL_CLEAN.puml` | Package-based dengan warna | Stakeholder presentation |
| 3 | `CLASS_DIAGRAM_FROM_SQL_HORIZONTAL.puml` | Flow horizontal (LTR) | Architecture workshop |

### 🇮🇩 **Versi Bahasa Indonesia** 🆕

| No | File | Deskripsi | Best For |
|----|------|-----------|----------|
| 4 | `CLASS_DIAGRAM_FROM_SQL_INDONESIA.puml` | Lengkap dengan visibility & FK | Skripsi/Thesis Indonesia |
| 5 | `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml` | Ringkas dengan color coding | Presentasi sidang |

---

## ✨ Fitur Khusus Versi Indonesia

### 🎨 **Versi Lengkap** (`CLASS_DIAGRAM_FROM_SQL_INDONESIA.puml`)

**Keunggulan:**
- ✅ Pembedaan visibility method: `+public`, `-private`, `#protected`
- ✅ Foreign Key ditandai: `<<FK>>`
- ✅ Field nullable ditandai: `{nullable}`
- ✅ Grouping method berdasarkan kategori
- ✅ Label relasi dalam Bahasa Indonesia
- ✅ Package dengan warna berbeda per domain
- ✅ Legend lengkap dengan penjelasan

**Contoh Class:**
```plantuml
class users <<Entitas>> {
    .. Atribut ..
    +id: BIGINT
    +email: VARCHAR(255)
    -password: VARCHAR(255)
    +prodi_id: BIGINT <<FK>>
    ====
    .. Method Relasi ..
    +prodi(): BelongsTo
    +dosen(): HasOne
    .. Method Helper (Public) ..
    +isGKM(): boolean
    .. Method Internal (Protected) ..
    #casts(): array
}
```

**Preview Relasi:**
```
dosen "*" --> "1" prodi : << milik dari >>
```

---

### 🚀 **Versi Simple** (`CLASS_DIAGRAM_INDONESIA_SIMPLE.puml`)

**Keunggulan:**
- ✅ BelongsTo ditandai emoji: `👈`
- ✅ Color coding otomatis:
  - <span style="color:green">🟢 Hijau = BelongsTo method</span>
  - <span style="color:blue">🔵 Biru = HasMany/HasOne method</span>
  - <span style="color:red">🔴 Merah = Private attribute</span>
- ✅ Nullable field: `{null}`
- ✅ Relasi opsional dengan garis putus-putus: `··>`
- ✅ Lebih ringkas dan mudah dibaca
- ✅ Cocok untuk slide presentasi

**Contoh Class:**
```plantuml
class users {
    +id: BIGINT
    +email: VARCHAR(255)
    <color:red>-password: VARCHAR(255)</color>
    +prodi_id: BIGINT
    --
    <color:green>+prodi() BelongsTo</color>
    <color:blue>+dosen()</color>
    +isGKM(): bool
}
```

**Preview Relasi:**
```
users "*" --> "1" prodi : milik >
reminder "*" .right.> "0..1" dosen : < tentang\n{opsional}
```

---

## 📊 Perbandingan Fitur

| Fitur | Default | Clean | Horizontal | ID Lengkap | ID Simple |
|-------|---------|-------|------------|------------|-----------|
| **Bahasa** | EN | EN | EN | 🇮🇩 ID | 🇮🇩 ID |
| **Visibility Symbol** | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Color Coding** | ❌ | ✅ | ❌ | ✅ | ✅✅ |
| **BelongsTo Emoji** | ❌ | ❌ | ❌ | ❌ | ✅ 👈 |
| **FK Annotation** | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Nullable Mark** | ❌ | ❌ | ❌ | ✅ | ✅ |
| **Method Grouping** | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Package Color** | ❌ | ✅ | ✅ | ✅ | ❌ |
| **Label Indonesia** | ❌ | ❌ | ❌ | ✅ | ✅ |
| **Complexity** | Medium | Low | High | High | **Low** |
| **File Size** | Medium | Large | Large | Large | **Small** |

---

## 🎯 Matrix Rekomendasi

### Berdasarkan Tujuan:

| Tujuan | Diagram Rekomendasi | Alasan |
|--------|---------------------|--------|
| Skripsi Bab 3 (Bahasa Indonesia) | `CLASS_DIAGRAM_FROM_SQL_INDONESIA.puml` | Detail, formal, FK jelas |
| Slide Presentasi Sidang | `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml` | Ringkas, visual menarik, color coding |
| Laporan TA (Bahasa Inggris) | `CLASS_DIAGRAM_FROM_SQL_CLEAN.puml` | Professional, clean |
| Dokumentasi Teknis | `CLASS_DIAGRAM_FROM_SQL.puml` | Standard, orthogonal |
| Workshop Arsitektur | `CLASS_DIAGRAM_FROM_SQL_HORIZONTAL.puml` | Flow jelas, layered |

### Berdasarkan Audiens:

| Audiens | Diagram Rekomendasi |
|---------|---------------------|
| Dosen Pembimbing (Indonesia) | `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml` |
| Penguji Sidang | `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml` |
| Tim Developer | `CLASS_DIAGRAM_FROM_SQL.puml` |
| Stakeholder Non-Technical | `CLASS_DIAGRAM_FROM_SQL_CLEAN.puml` |
| Database Administrator | `CLASS_DIAGRAM_FROM_SQL_INDONESIA.puml` |

### Berdasarkan Media:

| Media | Diagram Rekomendasi |
|-------|---------------------|
| PowerPoint | `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml` |
| PDF Report | `CLASS_DIAGRAM_FROM_SQL_INDONESIA.puml` |
| Word Document | `CLASS_DIAGRAM_FROM_SQL_CLEAN.puml` |
| Poster A3/A2 | `CLASS_DIAGRAM_FROM_SQL_HORIZONTAL.puml` |
| Online Documentation | `CLASS_DIAGRAM_FROM_SQL.puml` |

---

## 📖 Dokumentasi Pendukung

| File | Deskripsi |
|------|-----------|
| `CLASS_DIAGRAM_SQL_README.md` | Panduan lengkap semua diagram |
| `RELASI_BELONGSTO_DETAIL.md` | Detail 28 relasi BelongsTo 🆕 |
| `ERD_DATABASE.puml` | ERD komplementer |

---

## 🚀 Quick Start

### 1. Lihat Diagram Simple (Recommended)
```bash
code CLASS_DIAGRAM_INDONESIA_SIMPLE.puml
# Tekan Alt+D untuk preview (VS Code + PlantUML extension)
```

### 2. Export ke PNG untuk Presentasi
```bash
# Install node-plantuml
npm install -g node-plantuml

# Generate PNG
puml generate CLASS_DIAGRAM_INDONESIA_SIMPLE.puml -o slide_class_diagram.png
```

### 3. Export ke SVG untuk Dokumen
```bash
puml generate CLASS_DIAGRAM_FROM_SQL_INDONESIA.puml -o class_diagram.svg
```

### 4. View Online
1. Buka https://www.plantuml.com/plantuml/
2. Copy-paste isi file `.puml`
3. Submit

---

## 🔧 Tools yang Dibutuhkan

### Option 1: VS Code (Recommended)
```bash
# Install extension
ext install jebbs.plantuml

# Install Graphviz
choco install graphviz  # Windows
brew install graphviz   # Mac
```

### Option 2: IntelliJ IDEA
- Install plugin: **PlantUML Integration**

### Option 3: Online
- https://www.plantuml.com/plantuml/
- https://plantuml-editor.kkeisuke.com/

---

## 📝 Catatan Penting

### ✅ **Kelebihan Versi Indonesia:**
1. Mudah dipahami audiens lokal
2. Label relasi natural dalam Bahasa Indonesia
3. Color coding mempercepat pemahaman
4. Emoji sebagai visual indicator
5. Cocok untuk dokumentasi akademik

### ⚠️ **Perhatian:**
1. File `.puml` harus di-render untuk melihat hasilnya
2. Warna hanya terlihat di output (PNG/SVG), bukan di source code
3. Emoji `👈` mungkin tidak muncul di beberapa font
4. Untuk presentasi, export ke PNG/SVG dulu

---

## 🎨 Contoh Visual Indicator

### Public Method:
```
+isGKM(): boolean
```

### Private Attribute:
```
-password: VARCHAR(255)
```

### Protected Method:
```
#casts(): array
```

### BelongsTo Relation:
```
+prodi() BelongsTo
```

### Foreign Key:
```
+prodi_id: BIGINT <<FK>>
```

### Nullable Field:
```
+dosen_id: BIGINT {nullable}
```

---

## 📧 Support

Untuk pertanyaan atau request diagram khusus:
- Lihat: `CLASS_DIAGRAM_SQL_README.md`
- Detail relasi: `RELASI_BELONGSTO_DETAIL.md`

---

**Last Updated**: 2026-06-16  
**Total Diagrams**: 5 versions  
**New Indonesian Versions**: 2 🆕  
**Total BelongsTo Relations**: 28
