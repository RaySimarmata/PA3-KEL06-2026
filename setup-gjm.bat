@echo off
REM GJM Module Setup Script for Windows
REM This script will setup the GJM module with dummy data

echo ==========================================
echo GJM Module Setup
echo ==========================================
echo.

REM Check if .env exists
if not exist .env (
    echo Error: .env file not found!
    echo Please copy .env.example to .env and configure your database settings.
    exit /b 1
)

echo Step 1: Running migrations...
php artisan migrate

if %errorlevel% neq 0 (
    echo Error: Migration failed!
    exit /b 1
)

echo.
echo Step 2: Running seeders...
php artisan db:seed

if %errorlevel% neq 0 (
    echo Error: Seeder failed!
    exit /b 1
)

echo.
echo Step 3: Clearing cache...
php artisan cache:clear
php artisan config:clear
php artisan view:clear

echo.
echo Step 4: Verifying data...
php artisan tinker --execute="echo 'Laporan GJM Count: ' . \App\Models\LaporanGJM::count() . PHP_EOL;"

echo.
echo ==========================================
echo Setup Complete!
echo ==========================================
echo.
echo You can now login with:
echo   Email: gjm@example.com
echo   Password: password
echo.
echo Available pages:
echo   - Dashboard: /gjm/dashboard
echo   - Buat Laporan: /gjm/buat-laporan
echo   - Arsip Laporan: /gjm/laporan-gjm
echo   - Buat PPT: /gjm/buat-ppt
echo   - Arsip PPT: /gjm/buat-ppt/archive
echo   - Kirim Laporan: /gjm/kirim-laporan
echo.
pause
