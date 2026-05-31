# Template Excel VMTS - Panduan Penggunaan

## Format Excel yang Direkomendasikan

### Struktur File Excel

File Excel untuk VMTS harus memiliki struktur sebagai berikut:

```
| Kolom A          | Kolom B                    | Kolom C                |
|------------------|----------------------------|------------------------|
| VISI             |                            |                        |
| Visi Institut    | Menjadi institut teknologi | terkemuka di Asia      |
|                  |                            |                        |
| MISI             |                            |                        |
| 1                | Menyelenggarakan pendidikan| berkualitas tinggi     |
| 2                | Mengembangkan penelitian   | yang inovatif          |
| 3                | Melakukan pengabdian       | kepada masyarakat      |
|                  |                            |                        |
| TUJUAN           |                            |                        |
| 1                | Menghasilkan lulusan       | yang kompeten          |
| 2                | Menghasilkan penelitian    | yang berkualitas       |
| 3                | Memberikan kontribusi      | kepada masyarakat      |
|                  |                            |                        |
| SASARAN          |                            |                        |
| 1                | Meningkatkan kualitas      | pembelajaran           |
| 2                | Meningkatkan publikasi     | ilmiah                 |
| 3                | Meningkatkan kerjasama     | dengan industri        |
```

## Aturan Penting

1. **Header Section**: Gunakan kata kunci "VISI", "MISI", "TUJUAN", "SASARAN" di kolom pertama untuk menandai section
2. **Konten**: Isi konten di baris-baris berikutnya setelah header
3. **Numbering**: Untuk Misi, Tujuan, dan Sasaran, gunakan numbering (1, 2, 3, dst) di kolom pertama
4. **Multiple Columns**: Sistem akan menggabungkan konten dari multiple kolom dengan separator " | "
5. **Baris Kosong**: Baris kosong akan diabaikan

## Contoh Pengisian

### Sheet 1: VMTS Institut

| A        | B                                                          | C                    |
|----------|------------------------------------------------------------|----------------------|
| VISI     |                                                            |                      |
|          | Menjadi institut teknologi terkemuka di Asia Tenggara     |                      |
|          |                                                            |                      |
| MISI     |                                                            |                      |
| 1        | Menyelenggarakan pendidikan tinggi berkualitas            |                      |
| 2        | Mengembangkan penelitian dan inovasi teknologi            |                      |
| 3        | Melakukan pengabdian kepada masyarakat                    |                      |
| 4        | Membangun kerjasama dengan industri dan institusi lain    |                      |
|          |                                                            |                      |
| TUJUAN   |                                                            |                      |
| 1        | Menghasilkan lulusan yang kompeten dan berintegritas      |                      |
| 2        | Menghasilkan penelitian yang berkontribusi pada ilmu      |                      |
| 3        | Memberikan solusi bagi permasalahan masyarakat            |                      |
|          |                                                            |                      |
| SASARAN  |                                                            |                      |
| 1        | Akreditasi A untuk semua program studi                    |                      |
| 2        | Publikasi internasional minimal 50 paper per tahun        |                      |
| 3        | Kerjasama dengan minimal 20 industri                      |                      |

## Tips

1. **Konsistensi**: Gunakan format yang konsisten untuk semua section
2. **Detail**: Berikan detail yang cukup untuk setiap item
3. **Bahasa**: Gunakan Bahasa Indonesia yang baik dan benar
4. **Review**: Review data sebelum upload untuk memastikan tidak ada kesalahan

## Proses di Sistem

1. Upload file Excel
2. Sistem akan mengekstrak data berdasarkan section (VISI, MISI, TUJUAN, SASARAN)
3. Preview data akan ditampilkan
4. Klik "Generate Laporan" untuk membuat laporan dengan bantuan AI
5. AI akan memperkaya konten dengan bahasa yang lebih formal dan profesional
6. Edit konten jika diperlukan
7. Simpan laporan

## Troubleshooting

**Q: Data tidak terdeteksi dengan benar?**
A: Pastikan menggunakan kata kunci yang tepat (VISI, MISI, TUJUAN, SASARAN) di kolom pertama

**Q: Konten terpotong?**
A: Sistem membaca semua kolom, pastikan tidak ada kolom yang terlewat

**Q: Format tidak sesuai?**
A: Gunakan format .xlsx atau .xls, hindari format lain

**Q: File terlalu besar?**
A: Maksimal ukuran file adalah 10MB

## Support

Jika mengalami kesulitan, hubungi tim IT atau GJM untuk bantuan.
