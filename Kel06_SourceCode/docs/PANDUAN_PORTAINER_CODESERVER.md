# Panduan Instalasi Portainer & Code-Server di VPS Hostinger

> VPS IP: `187.77.121.239`  
> OS: Ubuntu 24.04  
> Setup: Docker + Docker Compose

---

## Ringkasan Akses

| Service      | URL                              | Fungsi                     |
|--------------|----------------------------------|----------------------------|
| Laravel App  | http://187.77.121.239:8000       | Aplikasi utama             |
| Next.js      | http://187.77.121.239:3000       | Frontend                   |
| phpMyAdmin   | http://187.77.121.239:8080       | Manage database MySQL      |
| Portainer    | http://187.77.121.239:9000       | Manage Docker via UI       |
| Code-Server  | http://187.77.121.239:8888       | Edit file (VS Code browser)|

---

## Langkah 1 — Upload/Update docker-compose.yml ke VPS

Jika menggunakan Git:
```bash
cd /root/PA3-KEL06-2026
git pull origin main
```

Jika upload manual (via SCP dari lokal):
```bash
scp docker-compose.yml root@187.77.121.239:/root/PA3-KEL06-2026/
```

---

## Langkah 2 — Set Password di File .env VPS

SSH ke VPS dulu:
```bash
ssh root@187.77.121.239
```

Lalu edit `.env` di VPS dan pastikan ada baris ini:
```bash
nano /root/PA3-KEL06-2026/.env
```

Tambahkan atau ubah:
```env
CODE_SERVER_PASSWORD=passwordkamu_ganti_ini
PROJECT_PATH=/root/PA3-KEL06-2026
```

> **Penting:** Ganti `passwordkamu_ganti_ini` dengan password yang kuat sebelum deploy!

Simpan: `Ctrl+O` → `Enter` → `Ctrl+X`

---

## Langkah 3 — Buka Port di Firewall Hostinger

Masuk ke **hPanel Hostinger → VPS → Keamanan → Firewall Rule**  
Tambahkan rule berikut:

| Port | Protocol | Keterangan         |
|------|----------|--------------------|
| 9000 | TCP      | Portainer          |
| 8888 | TCP      | Code-Server        |

Atau lewat terminal VPS dengan UFW:
```bash
ufw allow 9000/tcp
ufw allow 8888/tcp
ufw reload
```

---

## Langkah 4 — Jalankan Container Baru

Di terminal VPS, masuk ke folder project:
```bash
cd /root/PA3-KEL06-2026
```

Jalankan hanya service baru (tanpa restart yang sudah running):
```bash
docker compose up -d portainer code-server
```

Atau kalau mau restart semua sekalian:
```bash
docker compose up -d
```

Cek apakah container sudah jalan:
```bash
docker compose ps
```

Output yang diharapkan:
```
NAME                STATUS
pa3_portainer       Up
pa3_codeserver      Up
pa3_laravel         Up
pa3_mysql           Up
...
```

---

## Langkah 5 — Akses Portainer

1. Buka browser → `http://187.77.121.239:9000`
2. Pertama kali akses, akan diminta **buat akun admin**
3. Isi username dan password (minimal 12 karakter)
4. Pilih **"Get Started"** → klik environment **"local"**
5. Selesai — kamu bisa lihat semua container, logs, dan exec terminal

### Yang bisa dilakukan di Portainer:
- Lihat status semua container
- Start / Stop / Restart container
- Lihat **logs** real-time tiap container
- Masuk ke **terminal** container (Exec Console)
- Monitor CPU & Memory usage

---

## Langkah 6 — Akses Code-Server (VS Code di Browser)

1. Buka browser → `http://187.77.121.239:8888`
2. Masukkan password yang sudah di-set di `.env` (`CODE_SERVER_PASSWORD`)
3. Klik **"Open Folder"** → pilih `/home/coder/project`
4. Semua file project sudah bisa diedit langsung

### Yang bisa dilakukan di Code-Server:
- Edit semua file PHP, Blade, JS, .env, config, dll
- Terminal terintegrasi (bisa jalankan `php artisan`, `composer`, dll)
- Install extension VS Code
- Search & replace across files
- Git integration

---

## Tips Keamanan

Karena Portainer dan Code-Server tidak pakai HTTPS by default, sebaiknya:

**Opsi A — Batasi akses hanya dari IP tertentu (rekomendasi):**
```bash
# Hanya izinkan IP kamu sendiri
ufw allow from IP_KAMU to any port 9000
ufw allow from IP_KAMU to any port 8888
ufw deny 9000
ufw deny 8888
```

**Opsi B — Ganti port ke yang tidak umum:**
Di `docker-compose.yml`, ubah port host:
```yaml
ports:
  - "19000:9000"   # Portainer di port 19000
  - "18888:8080"   # Code-Server di port 18888
```

---

## Troubleshooting

**Container tidak mau start:**
```bash
docker compose logs portainer
docker compose logs code-server
```

**Port sudah dipakai:**
```bash
# Cek port yang sedang digunakan
ss -tlnp | grep -E '9000|8888'
```

**Reset password Code-Server:**
```bash
# Edit .env lalu restart container
nano /root/PA3-KEL06-2026/.env
docker compose restart code-server
```

**Portainer lupa password admin:**
```bash
# Hapus volume dan buat ulang
docker compose stop portainer
docker volume rm pa3_portainer_data
docker compose up -d portainer
# Buka browser dan buat akun baru
```
