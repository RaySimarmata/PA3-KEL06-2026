# ⚡ Quick Reference - Class Diagram Indonesia

## 🎯 Pilih Diagram dalam 10 Detik

### Saya butuh untuk...

#### 📄 **Dokumen Skripsi/TA**
→ `CLASS_DIAGRAM_FROM_SQL_INDONESIA.puml`  
✅ Detail lengkap + FK + visibility

#### 🎤 **Presentasi Sidang**
→ `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml`  
✅ Ringkas + color coding + emoji 👈

#### 📊 **Slide PowerPoint**
→ `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml`  
✅ Visual menarik + mudah dibaca

#### 📖 **Dokumentasi Teknis**
→ `CLASS_DIAGRAM_FROM_SQL.puml`  
✅ Standard format

---

## 🔍 Cara Cepat Lihat Diagram

### Windows (VS Code)
```bash
# 1. Buka file
code CLASS_DIAGRAM_INDONESIA_SIMPLE.puml

# 2. Preview
Alt + D
```

### Mac (VS Code)
```bash
# 1. Buka file
code CLASS_DIAGRAM_INDONESIA_SIMPLE.puml

# 2. Preview
Cmd + D
```

### Online (tanpa install)
1. Buka: https://www.plantuml.com/plantuml/
2. Copy isi file → Paste → Submit

---

## 🎨 Cheat Sheet Visual

### Symbol di Diagram Simple:

| Symbol | Arti |
|--------|------|
| `👈` | BelongsTo (milik dari) |
| `🟢 Hijau` | Method BelongsTo |
| `🔵 Biru` | Method HasMany/HasOne |
| `🔴 Merah` | Private attribute |
| `{null}` | Field opsional/nullable |
| `──>` | Relasi wajib |
| `··>` | Relasi opsional |

### Symbol di Diagram Lengkap:

| Symbol | Arti |
|--------|------|
| `+` | Public method/attribute |
| `-` | Private method/attribute |
| `#` | Protected method/attribute |
| `<<FK>>` | Foreign Key |
| `{nullable}` | Field opsional |

---

## 📸 Export untuk Presentasi

### PNG (untuk PowerPoint)
```bash
puml generate CLASS_DIAGRAM_INDONESIA_SIMPLE.puml -o diagram.png
```

### SVG (untuk Word/PDF)
```bash
puml generate CLASS_DIAGRAM_INDONESIA_SIMPLE.puml -o diagram.svg
```

### PDF (langsung)
```bash
puml generate CLASS_DIAGRAM_INDONESIA_SIMPLE.puml -o diagram.pdf
```

---

## 🔥 Tips Cepat

### Presentasi Sidang
1. Gunakan `SIMPLE.puml`
2. Export ke PNG resolusi tinggi
3. Tambahkan ke slide dengan background putih
4. Zoom ke bagian tertentu saat menjelaskan

### Dokumen Skripsi
1. Gunakan `INDONESIA.puml` (lengkap)
2. Export ke SVG (scalable)
3. Insert ke Word sebagai gambar
4. Caption: "Class Diagram Sistem Monitoring Mutu Akademik"

### Quick Review
1. Lihat `RELASI_BELONGSTO_DETAIL.md` untuk detail relasi
2. Total: 28 relasi BelongsTo
3. 10 tabel utama
4. 5 domain bisnis

---

## 📋 Checklist Presentasi

```markdown
□ Diagram sudah di-export ke PNG/SVG
□ Resolusi cukup tinggi (min 1920px width)
□ Background putih bersih
□ Font terbaca dari jarak 2-3 meter
□ Color coding terlihat jelas
□ Legend/keterangan jelas
□ Caption informatif
□ Siap backup file original .puml
```

---

## ⚠️ Common Issues

### Emoji tidak muncul?
→ Update font renderer atau export ke PNG

### Warna tidak terlihat?
→ Pastikan render dengan PlantUML, bukan plain text viewer

### Garis tidak rapi?
→ Gunakan `skinparam linetype ortho`

### Diagram terlalu besar?
→ Gunakan versi SIMPLE atau zoom ke bagian tertentu

---

## 🎓 Contoh Penggunaan

### Skenario 1: Jelaskan Relasi User-Dosen-Prodi
**Gunakan**: `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml`
**Highlight**: 
- `users` → `prodi` (BelongsTo 👈)
- `users` → `dosen` (HasOne)
- `dosen` → `prodi` (BelongsTo 👈)

### Skenario 2: Jelaskan Foreign Key
**Gunakan**: `CLASS_DIAGRAM_FROM_SQL_INDONESIA.puml`
**Highlight**:
- `+prodi_id: BIGINT <<FK>>`
- Relasi solid line `──>`

### Skenario 3: Jelaskan Optional Relation
**Gunakan**: `CLASS_DIAGRAM_INDONESIA_SIMPLE.puml`
**Highlight**:
- `reminder` ··> `dosen` {opsional}
- Garis putus-putus

---

## 📞 Need Help?

| Pertanyaan | Lihat File |
|------------|------------|
| Cara render diagram? | `CLASS_DIAGRAM_SQL_README.md` |
| Detail relasi BelongsTo? | `RELASI_BELONGSTO_DETAIL.md` |
| Perbandingan semua versi? | `INDEX_DIAGRAM_INDONESIA.md` |
| Install PlantUML? | `CLASS_DIAGRAM_SQL_README.md` |

---

**⏱️ Total Reading Time**: 2 menit  
**🎯 Quick Decision**: < 10 detik  
**🚀 Export Time**: < 30 detik
