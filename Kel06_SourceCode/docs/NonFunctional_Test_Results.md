# Hasil Pengujian Non-Fungsional

Dokumen ini merangkum hasil pengujian non-fungsional sistem Monitoring dan Pelaporan GKM–GJM.

## 1. Hasil Pengujian Performance Menggunakan Google Lighthouse

Pengujian performance dilakukan menggunakan Google Lighthouse untuk mengukur kecepatan dan kualitas respons halaman berikut:
- Halaman dashboard
- Monitoring RPS
- Monitoring materi
- Pengelolaan laporan
- Proses generate laporan

**Temuan:**
- Halaman dashboard dan monitoring dimuat dengan cepat.
- Respon sistem pada fitur pengelolaan laporan dan generate laporan berjalan efisien.
- Tidak ditemukan kelambatan signifikan saat navigasi antar halaman yang diuji.

**Screenshot:**

![Hasil Pengujian Performance Google Lighthouse](images/lighthouse-performance.png)

> Gambar 5.xx: Hasil Pengujian Performance Menggunakan Google Lighthouse.

---

## 2. Gambar 5.xx Hasil Pengujian Security Menggunakan OWASP ZAP

Pengujian security dilakukan dengan kombinasi OWASP ZAP dan Postman untuk memastikan sistem aman dari akses tidak sah dan serangan umum.

**Aspek yang diuji:**
- Validasi proses login
- Pembatasan hak akses berdasarkan role pengguna
- Validasi file upload
- Deteksi potensi kerentanan SQL Injection
- Deteksi potensi Cross Site Scripting (XSS)

**Temuan:**
- Akses halaman tanpa login ditolak oleh sistem.
- Hak akses role pengguna diterapkan dengan benar.
- Upload file yang tidak valid ditolak.
- Tidak ditemukan celah SQL Injection atau XSS kritis dari pengujian dasar.

**Screenshot:**

![Hasil Pengujian Security OWASP ZAP dan Postman](images/security-zap-postman.png)

> Gambar 5.xx: Hasil Pengujian Security Menggunakan OWASP ZAP dan Postman.

---

## 3. Gambar 5.xx Hasil Pengujian Portability pada Browser

Pengujian portability dilakukan untuk memastikan sistem dapat diakses dan berjalan dengan baik di beberapa browser modern.

**Browser yang diuji:**
- Google Chrome
- Mozilla Firefox
- Microsoft Edge

**Temuan:**
- Tampilan antarmuka konsisten di ketiga browser.
- Fitur navigasi, dashboard, monitoring, dan pengelolaan laporan berjalan dengan normal.
- Tidak diperlukan instalasi tambahan khusus untuk penggunaan browser.

**Screenshot:**

![Hasil Pengujian Portability Browser](images/portability-browser.png)

> Gambar 5.xx: Hasil Pengujian Portability pada Browser.

---

## 4. Gambar 5.xx Hasil Pengujian Data Integrity

Pengujian data integrity dilakukan dengan membandingkan data yang tersimpan di database dengan data yang ditampilkan pada aplikasi.

**Aspek yang diuji:**
- Konsistensi data monitoring
- Kecocokan data laporan
- Validitas hasil kuesioner

**Temuan:**
- Data pada tampilan aplikasi sesuai dengan data yang tersimpan di database.
- Nilai monitoring dan laporan konsisten antara input, penyimpanan, dan output.
- Tidak ditemukan mismatch data saat sampel data diverifikasi.

**Screenshot:**

![Hasil Pengujian Data Integrity](images/data-integrity.png)

> Gambar 5.xx: Hasil Pengujian Data Integrity (database dan tampilan aplikasi).

---

## Kesimpulan

Berdasarkan pengujian non-fungsional, sistem Monitoring dan Pelaporan GKM–GJM terbukti memenuhi kebutuhan kualitas pada aspek performa, keamanan, stabilitas, portability, dan integritas data. 

Pengujian tersebut dilakukan menggunakan tools yang tepat sesuai masing-masing aspek dan menunjukkan hasil "Berhasil" pada semua skenario uji yang dijalankan.
