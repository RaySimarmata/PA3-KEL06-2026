# Folder Favicon

## Cara Mengganti Favicon (Icon di Tab Browser)

### 1. Buat Favicon Online
Kunjungi salah satu website berikut:
- https://favicon.io/ (Recommended)
- https://www.favicon-generator.org/
- https://realfavicongenerator.net/

### 2. Upload Logo atau Buat Icon
- Upload logo kampus/sistem Anda
- Atau buat icon dari text/emoji
- Download hasil generate

### 3. Copy File ke Folder Ini
Setelah download, extract dan copy file berikut:
- `favicon-32x32.png` → Copy ke folder ini
- `favicon-16x16.png` → Copy ke folder ini
- `favicon.ico` → Copy ke folder `public/` (folder parent)

### 4. Struktur File yang Benar
```
public/
├── favicon.ico
└── images/
    ├── favicon-32x32.png
    └── favicon-16x16.png
```

### 5. Test Favicon Baru
1. Refresh browser dengan `Ctrl + F5` (hard refresh)
2. Atau clear browser cache: `Ctrl + Shift + Del`
3. Favicon baru akan muncul di tab browser

---

## Tips untuk Pameran
- Gunakan logo kampus atau logo sistem sebagai favicon
- Ukuran icon yang bagus: 512x512px (akan otomatis diresize)
- Format terbaik: PNG dengan background transparan
- Warna yang kontras agar terlihat jelas di tab browser

Good luck! 🎉
