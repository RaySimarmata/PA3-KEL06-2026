# Script Setup Custom Domain untuk Pameran
# Jalankan sebagai Administrator!

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "  Setup Custom Domain - Sistem GJM dan GKM      " -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: Script ini harus dijalankan sebagai Administrator!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Cara menjalankan sebagai Administrator:" -ForegroundColor Yellow
    Write-Host "1. Klik kanan pada file ini" -ForegroundColor Yellow
    Write-Host "2. Pilih 'Run with PowerShell' atau 'Run as Administrator'" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Tekan Enter untuk keluar"
    exit
}

Write-Host "Script berjalan sebagai Administrator ✓" -ForegroundColor Green
Write-Host ""

# Pilihan nama domain
Write-Host "Pilih nama domain custom yang Anda inginkan:" -ForegroundColor Yellow
Write-Host "1. sistem-gjm-gkm.local" -ForegroundColor White
Write-Host "2. gjm-gkm.local" -ForegroundColor White
Write-Host "3. sistem-kampus.local" -ForegroundColor White
Write-Host "4. kampus-gkm.local" -ForegroundColor White
Write-Host "5. Custom (input sendiri)" -ForegroundColor White
Write-Host ""

$choice = Read-Host "Masukkan pilihan (1-5)"

switch ($choice) {
    "1" { $domain = "sistem-gjm-gkm.local" }
    "2" { $domain = "gjm-gkm.local" }
    "3" { $domain = "sistem-kampus.local" }
    "4" { $domain = "kampus-gkm.local" }
    "5" { 
        $domain = Read-Host "Masukkan nama domain custom (tanpa http://)"
        if ($domain -notlike "*.local") {
            $domain = "$domain.local"
        }
    }
    default { 
        Write-Host "Pilihan tidak valid! Menggunakan default: sistem-gjm-gkm.local" -ForegroundColor Yellow
        $domain = "sistem-gjm-gkm.local" 
    }
}

Write-Host ""
Write-Host "Domain yang dipilih: $domain" -ForegroundColor Cyan
Write-Host ""

# Path ke file hosts
$hostsPath = "C:\Windows\System32\drivers\etc\hosts"

# Backup file hosts
$backupPath = "C:\Windows\System32\drivers\etc\hosts.backup." + (Get-Date -Format "yyyyMMdd_HHmmss")
Copy-Item $hostsPath $backupPath
Write-Host "Backup file hosts dibuat: $backupPath" -ForegroundColor Green

# Baca file hosts
$hostsContent = Get-Content $hostsPath

# Cek apakah domain sudah ada
$domainExists = $hostsContent | Select-String -Pattern $domain

if ($domainExists) {
    Write-Host "Domain $domain sudah ada di file hosts!" -ForegroundColor Yellow
} else {
    # Tambahkan entry baru
    $newEntry = "127.0.0.1    $domain"
    Add-Content -Path $hostsPath -Value ""
    Add-Content -Path $hostsPath -Value "# Custom domain untuk Sistem GJM dan GKM - Pameran"
    Add-Content -Path $hostsPath -Value $newEntry
    Write-Host "Domain $domain berhasil ditambahkan ke file hosts!" -ForegroundColor Green
}

Write-Host ""
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "  Konfigurasi Selesai!                          " -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Langkah selanjutnya:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Update file .env dengan APP_URL yang baru:" -ForegroundColor White
Write-Host "   APP_URL=http://$domain:8000" -ForegroundColor Cyan
Write-Host ""
Write-Host "2. Clear cache Laravel:" -ForegroundColor White
Write-Host "   php artisan config:clear" -ForegroundColor Cyan
Write-Host "   php artisan cache:clear" -ForegroundColor Cyan
Write-Host ""
Write-Host "3. Jalankan server Laravel:" -ForegroundColor White
Write-Host "   php artisan serve --host=0.0.0.0 --port=8000" -ForegroundColor Cyan
Write-Host ""
Write-Host "4. Akses website di browser:" -ForegroundColor White
Write-Host "   http://$domain:8000/login" -ForegroundColor Cyan
Write-Host ""
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

$autoUpdate = Read-Host "Apakah Anda ingin otomatis update file .env? (y/n)"

if ($autoUpdate -eq "y" -or $autoUpdate -eq "Y") {
    $envPath = Join-Path $PSScriptRoot ".env"
    
    if (Test-Path $envPath) {
        # Backup .env
        $envBackup = Join-Path $PSScriptRoot ".env.backup." + (Get-Date -Format "yyyyMMdd_HHmmss")
        Copy-Item $envPath $envBackup
        Write-Host "Backup .env dibuat: $envBackup" -ForegroundColor Green
        
        # Update APP_URL di .env
        $envContent = Get-Content $envPath
        $envContent = $envContent -replace "APP_URL=.*", "APP_URL=http://$domain:8000"
        $envContent | Set-Content $envPath
        
        Write-Host "File .env berhasil diupdate!" -ForegroundColor Green
        Write-Host ""
        
        Write-Host "Menjalankan php artisan config:clear..." -ForegroundColor Yellow
        & php artisan config:clear
        
        Write-Host "Menjalankan php artisan cache:clear..." -ForegroundColor Yellow
        & php artisan cache:clear
        
        Write-Host ""
        Write-Host "Setup selesai! Silakan jalankan server dengan:" -ForegroundColor Green
        Write-Host "php artisan serve --host=0.0.0.0 --port=8000" -ForegroundColor Cyan
    } else {
        Write-Host "File .env tidak ditemukan!" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Tekan Enter untuk keluar..." -ForegroundColor Gray
Read-Host
