# 🎪 Setup Lengkap untuk Pameran

## 🎯 Solusi Terbaik: Local Network + Custom Domain

### ✨ Hasil Akhir:
- URL profesional: `http://sistem-gjm-gkm.local`
- Bisa diakses dari laptop/PC lain via WiFi hotspot
- Gratis 100%
- Semua fitur berjalan sempurna

---

## 📋 Langkah Setup (30 menit)

### **Step 1: Persiapkan Database**

```bash
# 1. Jalankan MySQL (pastikan port 3307 sesuai .env)
# 2. Buat database
mysql -u root -p
CREATE DATABASE gkmgjm;
exit;

# 3. Jalankan migration
php artisan migrate
```

### **Step 2: Setup Custom Domain**

#### A. Edit File Hosts (Windows)

1. Buka **Notepad sebagai Administrator**
2. Buka file: `C:\Windows\System32\drivers\etc\hosts`
3. Tambahkan di akhir:
```
127.0.0.1    sistem-gjm-gkm.local
127.0.0.1    gjm.local
```
4. Save

#### B. Update .env
```env
APP_URL=http://sistem-gjm-gkm.local:8000
APP_ENV=demo
APP_DEBUG=false

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=gkmgjm
DB_USERNAME=root
DB_PASSWORD=

# Queue (untuk background jobs)
QUEUE_CONNECTION=database

# AI (gunakan yang gratis)
LLM_ENABLED=true
LLM_PROVIDER=groq
LLM_API_KEY=gsk_your_free_groq_api_key
LLM_BASE_URL=https://api.groq.com/openai/v1
LLM_MODEL=llama-3.1-8b-instant

# Gemini OCR (gratis 1,500 req/day)
GEMINI_API_KEY=your_gemini_free_api_key
GEMINI_MODEL=gemini-1.5-flash

# Disable email (kalau tidak perlu)
MAIL_MAILER=log
```

### **Step 3: Install Dependencies**

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install JavaScript dependencies
npm install
npm run build

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### **Step 4: Setup Queue Worker**

Buka terminal kedua dan jalankan:
```bash
php artisan queue:work --tries=3 --timeout=0
```

### **Step 5: Jalankan Server**

```bash
# Terminal pertama (server)
php artisan serve --host=0.0.0.0 --port=8000

# Akses dari browser:
# http://sistem-gjm-gkm.local:8000
```

---

## 📱 **Bonus: Akses dari Laptop/HP Lain (untuk Pengunjung Pameran)**

### Setup WiFi Hotspot:

1. **Aktifkan Mobile Hotspot Windows:**
   - Settings → Network & Internet → Mobile hotspot
   - Turn on hotspot
   - Catat password WiFi

2. **Cari IP Address Laptop Anda:**
```bash
ipconfig
# Cari "Wireless LAN adapter Wi-Fi"
# Contoh: 192.168.137.1
```

3. **Update .env untuk akses external:**
```env
APP_URL=http://192.168.137.1:8000
```

4. **Restart Laravel:**
```bash
php artisan config:clear
php artisan serve --host=0.0.0.0 --port=8000
```

5. **Pengunjung bisa akses dari HP/Laptop mereka:**
   - Connect ke WiFi hotspot Anda
   - Buka browser: `http://192.168.137.1:8000`

---

## 🎨 Tampilan Profesional untuk Pameran

### 1. Ganti Favicon dengan Logo Kampus/Proyek

```bash
# Letakkan logo di:
public/favicon.ico
public/images/favicon-32x32.png
public/images/favicon-16x16.png
```

**Generate favicon online:**
- https://favicon.io/
- Upload logo → Download → Extract ke folder `public/`

### 2. Update APP_NAME untuk Branding

```env
APP_NAME="Sistem Monitoring GKM-GJM"
```

### 3. Siapkan Data Demo

Buat seeder untuk data demo:
```bash
php artisan db:seed --class=DemoSeeder
```

---

## ✅ **Checklist Persiapan Pameran**

### Sehari Sebelum:
- [ ] Test semua fitur berfungsi normal
- [ ] Database sudah ada data demo
- [ ] WiFi hotspot tested dengan device lain
- [ ] Laptop fully charged + bawa charger
- [ ] Browser bookmark: `http://sistem-gjm-gkm.local:8000`
- [ ] Screenshot/screen recording demo fitur

### Hari H:
- [ ] Jalankan MySQL service
- [ ] Jalankan queue worker: `php artisan queue:work`
- [ ] Jalankan server: `php artisan serve --host=0.0.0.0 --port=8000`
- [ ] Test akses dari laptop/HP lain
- [ ] Buka browser di fullscreen mode

### Emergency Backup:
- [ ] Export database: `mysqldump -u root gkmgjm > backup.sql`
- [ ] Backup .env file
- [ ] Simpan folder project di USB drive

---

## 🚨 Troubleshooting Cepat

### Server tidak bisa diakses dari device lain?
```bash
# Cek firewall Windows
# Allow port 8000:
netsh advfirewall firewall add rule name="Laravel Server" dir=in action=allow protocol=TCP localport=8000
```

### Queue tidak jalan?
```bash
# Restart queue worker
php artisan queue:restart
php artisan queue:work --tries=3
```

### Database error?
```bash
# Re-migrate
php artisan migrate:fresh --seed
```

### AI/OCR error di demo?
```bash
# Disable temporary
# Edit .env:
LLM_ENABLED=false
OCR_CACHE_ENABLED=true
```

---

## 📊 Alternative: Ngrok (Akses dari Internet)

Jika ingin bisa diakses dari mana saja (tanpa WiFi hotspot):

### 1. Install Ngrok
Download: https://ngrok.com/download

### 2. Jalankan
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Ngrok
ngrok http 8000
```

### 3. Dapatkan URL Public
```
Forwarding: https://abc123.ngrok.io -> http://localhost:8000
```

### 4. Share URL ke pengunjung
- URL bisa diakses dari mana saja
- Gratis tier: 1 concurrent tunnel, expires after 2 hours
- Upgrade (bayar) untuk URL custom dan unlimited time

---

## 🎯 Tips Presentasi Pameran

1. **Siapkan 2-3 Akun Demo:**
   - Admin: untuk demo full fitur
   - Dosen GKM: demo fitur monitoring
   - Dosen GJM: demo fitur laporan

2. **Siapkan Skenario Demo (5 menit):**
   - Login
   - Show dashboard
   - Demo 2-3 fitur utama
   - Show laporan generated

3. **Buat QR Code untuk Akses:**
```
http://192.168.137.1:8000
```
Generate QR: https://www.qr-code-generator.com/

4. **Print/Display:**
   - QR Code besar di stand
   - Username/password demo
   - Flowchart sistem
   - Screenshot fitur utama

---

## 📞 Support

Jika ada masalah saat pameran:
1. Check terminal untuk error messages
2. Check `storage/logs/laravel.log`
3. Restart services (MySQL, Queue, Server)
4. Gunakan backup database jika perlu

**Good luck dengan pameran Anda! 🎉🚀**

