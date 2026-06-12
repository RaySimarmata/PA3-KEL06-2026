# Panduan Deploy PA3 KEL06 ke VPS Niagahoster

## Informasi VPS
- **IP**: 187.77.121.239
- **OS**: Ubuntu 24.04
- **Akses**: SSH root

---

## TAHAP 1 — Persiapan Lokal (di komputer kamu)

### 1.1 Pastikan project ada di GitHub/GitLab
```bash
# Di komputer lokal
git init                          # kalau belum
git add .
git commit -m "ready for deploy"
git remote add origin https://github.com/USERNAME/REPO.git
git push -u origin main
```

> **Penting**: Pastikan `.env` ada di `.gitignore` (sudah ada). Jangan push `.env`!

---

## TAHAP 2 — Setup VPS (SSH ke server)

### 2.1 Login ke VPS
```bash
ssh root@187.77.121.239
```

### 2.2 Update sistem
```bash
apt update && apt upgrade -y
```

### 2.3 Install Git (biasanya sudah ada)
```bash
apt install -y git curl
```

### 2.4 Cek Docker sudah terinstall
```bash
docker --version
docker compose version
```
> Docker sudah ada di VPS (terlihat dari dashboard Niagahoster).

---

## TAHAP 3 — Upload Project ke VPS

### Opsi A: Clone dari GitHub (direkomendasikan)
```bash
cd /var/www
git clone https://github.com/USERNAME/REPO.git pa3-kel06
cd pa3-kel06
```

### Opsi B: Upload manual via SCP (kalau repo private tanpa SSH key)
```bash
# Di komputer lokal, jalankan ini:
scp -r "D:\New folder\PA3-KEL06-2026" root@187.77.121.239:/var/www/pa3-kel06
```

---

## TAHAP 4 — Setup Environment di VPS

### 4.1 Buat file .env dari template
```bash
cd /var/www/pa3-kel06
cp .env.production .env
nano .env
```

### 4.2 Isi nilai-nilai berikut di .env:

| Key | Yang perlu diisi |
|-----|-----------------|
| `APP_KEY` | Generate dulu (lihat step 4.3) |
| `APP_URL` | `http://187.77.121.239:8000` |
| `FRONTEND_URL` | `http://187.77.121.239:3000` |
| `DB_ROOT_PASSWORD` | Password MySQL root (bebas, contoh: `RootPass123!`) |
| `DB_PASSWORD` | Password MySQL user (bebas, contoh: `Pa3Pass123!`) |
| `LLM_API_KEY` | OpenAI API key dari `.env` lokal |
| `OPENAI_API_KEY` | OpenAI API key dari `.env` lokal |
| `GROQ_API_KEY` | Groq API key dari `.env` lokal |

### 4.3 Generate APP_KEY
```bash
# Jalankan ini dan copy hasilnya ke APP_KEY di .env
docker run --rm php:8.2-cli php -r "echo 'base64:'.base64_encode(random_bytes(32));"
```

---

## TAHAP 5 — Whitelist IP VPS di MongoDB Atlas

1. Buka https://cloud.mongodb.com
2. Login → pilih cluster
3. **Network Access** → **Add IP Address**
4. Masukkan IP VPS: `187.77.121.239`
5. Klik **Confirm**

---

## TAHAP 6 — Deploy!

```bash
cd /var/www/pa3-kel06
chmod +x docker/deploy.sh
bash docker/deploy.sh
```

Script ini akan:
1. Build semua Docker image
2. Start MySQL, Laravel, Queue Worker, Scheduler, OCR Service, Next.js
3. Jalankan database migrations
4. Optimize cache Laravel

> Proses build pertama kali sekitar **10-15 menit** (terutama PaddleOCR yang besar).

---

## TAHAP 7 — Verifikasi

### Cek semua container jalan
```bash
docker compose ps
```
Output yang diharapkan:
```
NAME               STATUS
pa3_mysql          running (healthy)
pa3_laravel        running
pa3_queue          running
pa3_scheduler      running
pa3_python_ocr     running (healthy)
pa3_nextjs         running
```

### Test akses
```bash
# Laravel
curl http://localhost:8000

# Next.js
curl http://localhost:3000

# OCR Service
curl http://localhost:5000/health
```

### Buka di browser
- **Laravel App**: http://187.77.121.239:8000
- **Next.js App**: http://187.77.121.239:3000

---

## Perintah Berguna Setelah Deploy

### Lihat logs realtime
```bash
docker compose logs -f laravel      # Laravel logs
docker compose logs -f queue        # Queue worker logs
docker compose logs -f python-ocr   # OCR logs
docker compose logs -f nextjs       # Next.js logs
```

### Restart service tertentu
```bash
docker compose restart laravel
docker compose restart queue
```

### Update code (setelah git push)
```bash
cd /var/www/pa3-kel06
git pull origin main
docker compose build laravel nextjs --no-cache
docker compose up -d laravel queue scheduler nextjs
docker compose exec -T laravel php artisan migrate --force
docker compose exec -T laravel php artisan config:cache
docker compose exec -T laravel php artisan route:cache
```

### Masuk ke container Laravel
```bash
docker compose exec laravel bash
```

### Jalankan artisan command
```bash
docker compose exec laravel php artisan tinker
docker compose exec laravel php artisan cache:clear
```

---

## Troubleshooting

### Laravel error 500
```bash
docker compose exec laravel php artisan config:clear
docker compose exec laravel php artisan cache:clear
docker compose logs laravel
```

### MySQL tidak bisa connect
```bash
docker compose logs mysql
# Pastikan DB_HOST=mysql (nama container), bukan 127.0.0.1
```

### OCR tidak jalan
```bash
docker compose logs python-ocr
# OCR service perlu waktu lebih lama untuk start (download model PaddleOCR)
```

### Storage permission error
```bash
docker compose exec laravel chown -R www-data:www-data /var/www/html/storage
docker compose exec laravel chmod -R 775 /var/www/html/storage
```

---

## Setup Domain (Opsional)

Kalau kamu punya domain (misal `pa3kel06.del.ac.id`):

### 1. Arahkan DNS ke IP VPS
Di panel domain, tambahkan A record:
```
@   A   187.77.121.239
www A   187.77.121.239
```

### 2. Update .env
```env
APP_URL=https://api.pa3kel06.del.ac.id
FRONTEND_URL=https://pa3kel06.del.ac.id
```

### 3. Setup SSL dengan Certbot
```bash
apt install -y certbot
certbot certonly --standalone -d pa3kel06.del.ac.id -d api.pa3kel06.del.ac.id
```

### 4. Update Traefik (sudah ada di VPS)
Traefik di VPS Niagahoster bisa handle SSL otomatis. Buka Docker Manager di dashboard Niagahoster dan tambahkan label Traefik ke service.
