#!/bin/bash
# ============================================================
# DEPLOY SCRIPT - PA3 KEL06
# Jalankan di VPS setelah upload project
# Usage: bash docker/deploy.sh
# ============================================================

set -e  # Stop on error

echo "============================================"
echo "  PA3 KEL06 - Deploy Script"
echo "============================================"

# ─── 1. Cek Docker ───
echo "[1/7] Checking Docker..."
if ! command -v docker &> /dev/null; then
    echo "ERROR: Docker tidak ditemukan!"
    exit 1
fi
if ! command -v docker compose &> /dev/null; then
    echo "ERROR: Docker Compose tidak ditemukan!"
    exit 1
fi
echo "✓ Docker OK"

# ─── 2. Cek .env ───
echo "[2/7] Checking .env..."
if [ ! -f ".env" ]; then
    echo "ERROR: File .env tidak ada!"
    echo "Copy .env.production ke .env dan isi nilainya:"
    echo "  cp .env.production .env && nano .env"
    exit 1
fi

# Cek APP_KEY
APP_KEY=$(grep "^APP_KEY=" .env | cut -d'=' -f2)
if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY kosong di .env!"
    echo "Generate dengan: docker run --rm php:8.2-cli php -r \"echo 'base64:'.base64_encode(random_bytes(32));\""
    exit 1
fi
echo "✓ .env OK"

# ─── 3. Stop containers lama ───
echo "[3/7] Stopping old containers..."
docker compose down --remove-orphans 2>/dev/null || true
echo "✓ Old containers stopped"

# ─── 4. Build images ───
echo "[4/7] Building Docker images (this may take 5-10 minutes)..."
docker compose build --no-cache
echo "✓ Images built"

# ─── 5. Start services ───
echo "[5/7] Starting services..."
docker compose up -d
echo "✓ Services started"

# ─── 6. Wait for MySQL ───
echo "[6/7] Waiting for MySQL to be ready..."
MAX_TRIES=30
COUNT=0
until docker compose exec -T mysql mysqladmin ping -h localhost --silent; do
    COUNT=$((COUNT+1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "ERROR: MySQL tidak ready setelah $MAX_TRIES percobaan"
        docker compose logs mysql
        exit 1
    fi
    echo "  Waiting... ($COUNT/$MAX_TRIES)"
    sleep 5
done
echo "✓ MySQL ready"

# ─── 7. Laravel setup ───
echo "[7/7] Running Laravel setup..."

# Storage link
docker compose exec -T laravel php artisan storage:link 2>/dev/null || true

# Run migrations
docker compose exec -T laravel php artisan migrate --force
echo "✓ Migrations done"

# Cache config & routes
docker compose exec -T laravel php artisan config:cache
docker compose exec -T laravel php artisan route:cache
docker compose exec -T laravel php artisan view:cache
echo "✓ Cache optimized"

# Set permissions
docker compose exec -T laravel chown -R www-data:www-data /var/www/html/storage
docker compose exec -T laravel chmod -R 775 /var/www/html/storage
echo "✓ Permissions set"

# ─── Done ───
echo ""
echo "============================================"
echo "  ✓ DEPLOY BERHASIL!"
echo "============================================"
echo ""
echo "  Laravel Backend : http://$(hostname -I | awk '{print $1}'):8000"
echo "  Next.js Frontend: http://$(hostname -I | awk '{print $1}'):3000"
echo "  OCR Service     : http://$(hostname -I | awk '{print $1}'):5000/health"
echo ""
echo "  Cek status: docker compose ps"
echo "  Lihat logs : docker compose logs -f laravel"
echo ""
