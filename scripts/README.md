# Scripts Directory

Kumpulan script untuk PaddleOCR installation dan testing.

## 📁 File Structure

```
scripts/
├── install_paddleocr.sh      # Installation script untuk Linux/Mac
├── install_paddleocr.ps1     # Installation script untuk Windows PowerShell
├── install_paddleocr.bat     # Installation script untuk Windows CMD
├── test_paddleocr.py         # Script untuk test instalasi
├── paddleocr_service.py      # PaddleOCR service untuk Laravel
├── create_test_images.py     # Script untuk membuat test images
└── README.md                 # File ini
```

## 🚀 Quick Start

### 1. Installation

**Windows (CMD):**
```cmd
conda activate paddleocr
scripts\install_paddleocr.bat
```

**Windows (PowerShell):**
```powershell
conda activate paddleocr
powershell -ExecutionPolicy Bypass -File scripts/install_paddleocr.ps1
```

**Linux/Mac:**
```bash
conda activate paddleocr
chmod +x scripts/install_paddleocr.sh
./scripts/install_paddleocr.sh
```

### 2. Testing

```bash
# Test instalasi
python scripts/test_paddleocr.py

# Test dengan file
python scripts/paddleocr_service.py path/to/file.pdf
```

## 📝 Script Details

### install_paddleocr.sh / .ps1 / .bat

Script instalasi otomatis yang akan:
- Check conda environment
- Install PaddlePaddle 2.5.2
- Install PaddleOCR
- Install PyMuPDF untuk PDF support
- Install dependencies (Pillow, NumPy, OpenCV)
- Verify instalasi

**Usage:**
```bash
# Linux/Mac
./scripts/install_paddleocr.sh

# Windows PowerShell
powershell -ExecutionPolicy Bypass -File scripts/install_paddleocr.ps1

# Windows CMD
scripts\install_paddleocr.bat
```

### test_paddleocr.py

Script untuk testing dan verifikasi instalasi PaddleOCR.

**Features:**
- Test semua imports (PaddlePaddle, PaddleOCR, PyMuPDF, dll)
- Test basic functionality
- Display version information
- Comprehensive error reporting

**Usage:**
```bash
python scripts/test_paddleocr.py
```

**Expected Output:**
```
==================================================
Testing PaddleOCR Installation
==================================================

1. Testing PaddlePaddle import...
   ✓ PaddlePaddle version: 2.5.2
2. Testing PaddleOCR import...
   ✓ PaddleOCR imported successfully
...
✓ All tests PASSED!
```

### paddleocr_service.py

Service utama untuk OCR processing yang dapat dipanggil dari PHP/Laravel.

**Features:**
- Process image files (PNG, JPG, JPEG, BMP, TIFF)
- Process PDF files (multi-page support)
- JSON output untuk easy integration
- Configurable language dan GPU support
- Confidence scoring
- Line-by-line text extraction

**Usage:**

```bash
# Basic usage
python scripts/paddleocr_service.py path/to/file.pdf

# With options
python scripts/paddleocr_service.py path/to/file.pdf --lang id --dpi 300

# With GPU
python scripts/paddleocr_service.py path/to/file.jpg --gpu

# Help
python scripts/paddleocr_service.py --help
```

**Arguments:**
- `file` - Path to image or PDF file (required)
- `--lang` - Language code (default: id)
- `--gpu` - Use GPU acceleration
- `--dpi` - DPI for PDF conversion (default: 300)

**Output Format:**

For images:
```json
{
  "success": true,
  "file": "path/to/image.jpg",
  "text": "Extracted text...",
  "confidence": 95.5,
  "lines": [
    {"text": "Line 1", "confidence": 96.2},
    {"text": "Line 2", "confidence": 94.8}
  ],
  "word_count": 150
}
```

For PDFs:
```json
{
  "success": true,
  "file": "path/to/document.pdf",
  "total_pages": 5,
  "text": "Combined text from all pages...",
  "average_confidence": 93.5,
  "pages": [
    {
      "page": 1,
      "text": "Page 1 text...",
      "confidence": 95.0,
      "lines": [...],
      "word_count": 200
    }
  ]
}
```

### create_test_images.py

Script untuk membuat test images dengan teks Indonesia.

**Usage:**
```bash
python scripts/create_test_images.py
```

## 🔧 Integration with Laravel

### Calling from PHP

```php
use Symfony\Component\Process\Process;

$process = new Process([
    'python',
    base_path('scripts/paddleocr_service.py'),
    $filePath,
    '--lang', 'id'
]);

$process->run();

if ($process->isSuccessful()) {
    $result = json_decode($process->getOutput(), true);
    
    if ($result['success']) {
        $text = $result['text'];
        $confidence = $result['confidence'];
        // Process hasil OCR...
    }
}
```

### Using OCRService.php

OCRService.php sudah diupdate untuk support PaddleOCR:

```php
use App\Services\OCRService;

$ocrService = new OCRService();
$result = $ocrService->extractTextFromPDF($pdfPath, 'paddleocr');

if ($result['success']) {
    $text = $result['text'];
    $confidence = $result['confidence'];
}
```

## 🐛 Troubleshooting

### Script tidak executable (Linux/Mac)

```bash
chmod +x scripts/install_paddleocr.sh
```

### PowerShell execution policy error

```powershell
powershell -ExecutionPolicy Bypass -File scripts/install_paddleocr.ps1
```

### Python not found

Pastikan Python dan Conda sudah terinstall dan ada di PATH:
```bash
python --version
conda --version
```

### Import errors

Jalankan test script untuk diagnosa:
```bash
python scripts/test_paddleocr.py
```

## 📚 Documentation

- [Quick Start Guide](../PADDLEOCR_QUICKSTART.md)
- [Full Installation Guide](../PADDLEOCR_INSTALLATION.md)
- [OCR Implementation Summary](../OCR_IMPLEMENTATION_SUMMARY.md)

## 🆘 Support

Jika mengalami masalah:
1. Jalankan `python scripts/test_paddleocr.py` untuk diagnosa
2. Check log error di console
3. Baca troubleshooting guide di dokumentasi
4. Hubungi tim development

---

**Happy OCR-ing!** 🚀
