# Script untuk instalasi PaddleOCR di Windows
# Pastikan Anda sudah mengaktifkan conda environment sebelum menjalankan script ini

Write-Host "===================================" -ForegroundColor Cyan
Write-Host "PaddleOCR Installation Script" -ForegroundColor Cyan
Write-Host "===================================" -ForegroundColor Cyan
Write-Host ""

# Cek apakah conda environment aktif
if (-not $env:CONDA_DEFAULT_ENV) {
    Write-Host "⚠️  Warning: Conda environment tidak terdeteksi aktif" -ForegroundColor Yellow
    Write-Host "Silakan aktifkan environment terlebih dahulu dengan:"
    Write-Host "  conda activate paddleocr" -ForegroundColor Green
    Write-Host ""
    $response = Read-Host "Lanjutkan instalasi? (y/n)"
    if ($response -ne "y" -and $response -ne "Y") {
        exit 1
    }
} else {
    Write-Host "✓ Conda environment aktif: $env:CONDA_DEFAULT_ENV" -ForegroundColor Green
}

Write-Host ""
Write-Host "Step 1: Installing PaddlePaddle..." -ForegroundColor Yellow
pip install paddlepaddle==2.5.2 -i https://pypi.tuna.tsinghua.edu.cn/simple

Write-Host ""
Write-Host "Step 2: Installing PaddleOCR..." -ForegroundColor Yellow
pip install paddleocr

Write-Host ""
Write-Host "Step 3: Installing PDF support (PyMuPDF)..." -ForegroundColor Yellow
pip install pymupdf

Write-Host ""
Write-Host "Step 4: Installing image processing libraries..." -ForegroundColor Yellow
pip install pillow numpy opencv-python

Write-Host ""
Write-Host "Step 5: Verifying installation..." -ForegroundColor Yellow
python -c "import paddle; print('PaddlePaddle version:', paddle.__version__)"
python -c "import paddleocr; print('PaddleOCR installed successfully')"
python -c "import fitz; print('PyMuPDF installed successfully')"

Write-Host ""
Write-Host "===================================" -ForegroundColor Cyan
Write-Host "✓ Installation completed!" -ForegroundColor Green
Write-Host "===================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Untuk menggunakan PaddleOCR, jalankan:"
Write-Host "  python scripts/test_paddleocr.py" -ForegroundColor Green
