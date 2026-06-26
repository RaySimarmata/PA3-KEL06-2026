@echo off
REM Script untuk instalasi PaddleOCR di Windows
REM Pastikan Anda sudah mengaktifkan conda environment sebelum menjalankan script ini

echo ===================================
echo PaddleOCR Installation Script
echo ===================================
echo.

REM Cek apakah conda environment aktif
if "%CONDA_DEFAULT_ENV%"=="" (
    echo WARNING: Conda environment tidak terdeteksi aktif
    echo Silakan aktifkan environment terlebih dahulu dengan:
    echo   conda activate paddleocr
    echo.
    set /p continue="Lanjutkan instalasi? (y/n): "
    if /i not "%continue%"=="y" exit /b 1
) else (
    echo [OK] Conda environment aktif: %CONDA_DEFAULT_ENV%
)

echo.
echo Step 1: Installing PaddlePaddle...
pip install paddlepaddle==2.5.2 -i https://pypi.tuna.tsinghua.edu.cn/simple

echo.
echo Step 2: Installing PaddleOCR...
pip install paddleocr

echo.
echo Step 3: Installing PDF support (PyMuPDF)...
pip install pymupdf

echo.
echo Step 4: Installing image processing libraries...
pip install pillow numpy opencv-python

echo.
echo Step 5: Verifying installation...
python -c "import paddle; print('PaddlePaddle version:', paddle.__version__)"
python -c "import paddleocr; print('PaddleOCR installed successfully')"
python -c "import fitz; print('PyMuPDF installed successfully')"

echo.
echo ===================================
echo [OK] Installation completed!
echo ===================================
echo.
echo Untuk menggunakan PaddleOCR, jalankan:
echo   python scripts/test_paddleocr.py
echo.

pause
