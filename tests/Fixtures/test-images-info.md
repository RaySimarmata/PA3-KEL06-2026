# Test Images for Image Validation

## Test Cases

### 1. Valid Images (Should be ACCEPTED)

#### A. Academic Document
- **Filename**: `valid_academic_document.jpg`
- **Content**: Laporan monitoring perkuliahan dengan data mahasiswa, dosen, kehadiran
- **Expected**: ✅ ACCEPTED (High relevance score)

#### B. Attendance List
- **Filename**: `valid_attendance_list.jpg`
- **Content**: Daftar hadir perkuliahan dengan nama mahasiswa, NIM, tanda tangan
- **Expected**: ✅ ACCEPTED (High relevance score)

#### C. Chart/Graph
- **Filename**: `valid_chart_monitoring.jpg`
- **Content**: Grafik monitoring mutu dengan data persentase capaian
- **Expected**: ✅ ACCEPTED (Medium-High relevance score)

#### D. System Screenshot
- **Filename**: `valid_system_screenshot.jpg`
- **Content**: Screenshot sistem akademik dengan data RPS/monitoring
- **Expected**: ✅ ACCEPTED (Medium relevance score)

#### E. Official Letter
- **Filename**: `valid_official_letter.jpg`
- **Content**: Surat resmi kampus tentang kegiatan akademik
- **Expected**: ✅ ACCEPTED (Medium relevance score)

### 2. Invalid Images (Should be REJECTED)

#### A. Apple Logo
- **Filename**: `invalid_apple_logo.jpg`
- **Content**: Logo Apple dengan teks "Apple Inc."
- **Expected**: ❌ REJECTED (Logo detection, high confidence)

#### B. Shopping Advertisement
- **Filename**: `invalid_shopping_ad.jpg`
- **Content**: Iklan belanja online dengan teks "SALE 50% OFF"
- **Expected**: ❌ REJECTED (Irrelevant keywords, high confidence)

#### C. Social Media Screenshot
- **Filename**: `invalid_social_media.jpg`
- **Content**: Screenshot Instagram/Facebook
- **Expected**: ❌ REJECTED (Irrelevant keywords, high confidence)

#### D. Food/Recipe
- **Filename**: `invalid_food_recipe.jpg`
- **Content**: Resep makanan atau foto makanan
- **Expected**: ❌ REJECTED (Irrelevant keywords, medium-high confidence)

#### E. Game Screenshot
- **Filename**: `invalid_game_screenshot.jpg`
- **Content**: Screenshot game atau gaming content
- **Expected**: ❌ REJECTED (Irrelevant keywords, high confidence)

### 3. Borderline Cases (Uncertain)

#### A. Short Text Document
- **Filename**: `borderline_short_text.jpg`
- **Content**: Dokumen dengan teks sangat sedikit (< 20 karakter)
- **Expected**: ⚠️ ACCEPTED with LOW CONFIDENCE

#### B. Mixed Content
- **Filename**: `borderline_mixed_content.jpg`
- **Content**: Dokumen dengan campuran relevant dan irrelevant keywords
- **Expected**: ⚠️ ACCEPTED or REJECTED (depends on score)

#### C. Poor Quality Image
- **Filename**: `borderline_poor_quality.jpg`
- **Content**: Gambar blur atau kualitas rendah, OCR sulit membaca
- **Expected**: ⚠️ ACCEPTED with LOW CONFIDENCE (OCR failure)

## How to Create Test Images

### Using Python (PaddleOCR)
```python
from PIL import Image, ImageDraw, ImageFont

def create_test_image(text, filename, size=(800, 600)):
    # Create white background
    img = Image.new('RGB', size, color='white')
    draw = ImageDraw.Draw(img)
    
    # Use default font
    try:
        font = ImageFont.truetype("arial.ttf", 24)
    except:
        font = ImageFont.load_default()
    
    # Draw text
    draw.text((50, 50), text, fill='black', font=font)
    
    # Save image
    img.save(filename)
    print(f"Created: {filename}")

# Create valid academic document
create_test_image("""
LAPORAN MONITORING PERKULIAHAN
SEMESTER GANJIL 2025/2026

Mata Kuliah: Pemrograman Web
Dosen: Dr. Ahmad Santoso
Jumlah Mahasiswa: 45 orang
Persentase Kehadiran: 85%

Evaluasi:
- Materi sesuai RPS
- Metode pembelajaran efektif
- Capaian pembelajaran baik
""", "valid_academic_document.jpg")

# Create invalid logo
create_test_image("Apple Inc.", "invalid_apple_logo.jpg", size=(400, 400))

# Create invalid shopping ad
create_test_image("""
SALE! 50% OFF
Buy iPhone 15 Pro Max
Limited Time Offer
Shop Now!
""", "invalid_shopping_ad.jpg")
```

### Using Online Tools
1. **Canva**: Create document-style images with text
2. **Google Docs**: Create document, screenshot it
3. **Excel/Sheets**: Create table, screenshot it

## Testing Workflow

### 1. Manual Testing
```bash
# 1. Start Laravel server
php artisan serve

# 2. Open browser: http://localhost:8000/gjm/laporan-triwulan/create

# 3. Upload test images one by one

# 4. Verify results:
#    - Valid images: Should be accepted
#    - Invalid images: Should be rejected with clear message
#    - Borderline: Should be accepted with warning
```

### 2. Automated Testing
```bash
# Run unit tests
php artisan test --filter ImageContentValidationTest

# Run with coverage
php artisan test --filter ImageContentValidationTest --coverage

# Run specific test
php artisan test --filter test_valid_academic_document_is_accepted
```

### 3. API Testing (Postman/cURL)
```bash
# Test upload endpoint
curl -X POST http://localhost:8000/gjm/ocr/upload \
  -H "Content-Type: multipart/form-data" \
  -F "images[]=@valid_academic_document.jpg" \
  -F "laporan_id=1" \
  -F "report_type=laporan_triwulan"

# Expected response for valid image:
# {
#   "success": true,
#   "message": "Images uploaded and integrated...",
#   "stats": {
#     "total_uploaded": 1,
#     "accepted": 1,
#     "rejected": 0
#   }
# }

# Test with invalid image
curl -X POST http://localhost:8000/gjm/ocr/upload \
  -H "Content-Type: multipart/form-data" \
  -F "images[]=@invalid_apple_logo.jpg" \
  -F "laporan_id=1" \
  -F "report_type=laporan_triwulan"

# Expected response for invalid image:
# {
#   "success": false,
#   "message": "❌ Semua gambar ditolak...",
#   "rejected_images": [...]
# }
```

## Expected Validation Results

| Image Type | Relevance Score | Confidence | Result |
|------------|----------------|------------|--------|
| Academic Document | 0.7-1.0 | 0.8-1.0 | ✅ ACCEPTED |
| Attendance List | 0.6-0.9 | 0.7-0.9 | ✅ ACCEPTED |
| Chart/Graph | 0.5-0.8 | 0.6-0.8 | ✅ ACCEPTED |
| System Screenshot | 0.4-0.7 | 0.5-0.7 | ✅ ACCEPTED |
| Official Letter | 0.4-0.7 | 0.5-0.7 | ✅ ACCEPTED |
| Apple Logo | 0.0-0.1 | 0.8-0.9 | ❌ REJECTED |
| Shopping Ad | 0.0-0.2 | 0.7-0.9 | ❌ REJECTED |
| Social Media | 0.0-0.2 | 0.7-0.8 | ❌ REJECTED |
| Food/Recipe | 0.0-0.1 | 0.6-0.8 | ❌ REJECTED |
| Game Screenshot | 0.0-0.1 | 0.7-0.8 | ❌ REJECTED |
| Short Text | 0.2-0.4 | 0.3-0.5 | ⚠️ ACCEPTED (Low Conf) |
| Mixed Content | 0.2-0.5 | 0.4-0.6 | ⚠️ Varies |
| Poor Quality | N/A | 0.2-0.4 | ⚠️ ACCEPTED (OCR Fail) |

## Troubleshooting

### Issue: All images rejected
**Cause**: OCR not working or keywords too strict
**Solution**: 
- Check Tesseract installation
- Review keyword lists
- Lower threshold

### Issue: Invalid images accepted
**Cause**: Keywords not comprehensive enough
**Solution**:
- Add more irrelevant keywords
- Increase confidence threshold
- Improve OCR preprocessing

### Issue: OCR extraction fails
**Cause**: Tesseract not installed or image quality poor
**Solution**:
- Install Tesseract: `sudo apt-get install tesseract-ocr`
- Preprocess images (contrast, denoise)
- Use higher quality images

## Notes

- Test images should be realistic (actual documents, not synthetic)
- Use various image qualities (good, medium, poor)
- Test with different file formats (JPG, PNG)
- Test with different image sizes
- Test batch upload (multiple images at once)
