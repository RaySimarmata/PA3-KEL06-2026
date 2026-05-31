#!/usr/bin/env python3
"""
PaddleOCR Bridge Script for Laravel
Supports: JPG, PNG, JPEG, GIF, WEBP, PDF (scanned)
"""
import sys
import json
import argparse
from pathlib import Path

try:
    from paddleocr import PaddleOCR
except ImportError as e:
    print(json.dumps({
        'success': False,
        'error': f'PaddleOCR not installed: {str(e)}. Run: pip install paddleocr'
    }))
    sys.exit(1)

try:
    import fitz  # PyMuPDF untuk konversi PDF ke gambar
except ImportError:
    fitz = None

try:
    from PIL import Image
    import numpy as np
except ImportError as e:
    print(json.dumps({
        'success': False,
        'error': f'Required package missing: {str(e)}'
    }))
    sys.exit(1)

# Inisialisasi OCR engine (load sekali, reuse)
ocr_engine = None

def get_ocr_engine(lang='en'):
    """Get or initialize OCR engine"""
    global ocr_engine
    if ocr_engine is None:
        # Disable advanced features to avoid compatibility issues
        import os
        os.environ['FLAGS_use_mkldnn'] = '0'
        
        ocr_engine = PaddleOCR(
            use_textline_orientation=False,
            use_doc_orientation_classify=False,
            use_doc_unwarping=False,
            lang=lang
        )
    return ocr_engine

def extract_from_image(image_path, lang='en'):
    """Extract text dari file gambar"""
    ocr = get_ocr_engine(lang)
    result = ocr.predict(image_path)
    
    lines = []
    try:
        if result:
            for res in result:
                # Format baru PaddleOCR 3.x - akses via attribute
                if hasattr(res, 'rec_texts') and hasattr(res, 'rec_scores'):
                    for text, score in zip(res.rec_texts, res.rec_scores):
                        if score > 0.3 and text.strip():
                            lines.append(text.strip())
                # Fallback jika format dict
                elif isinstance(res, dict):
                    texts = res.get('rec_texts', [])
                    scores = res.get('rec_scores', [])
                    for text, score in zip(texts, scores):
                        if score > 0.3 and text.strip():
                            lines.append(text.strip())
    except Exception as e:
        pass
    
    return '\n'.join(lines)

def extract_from_pdf(pdf_path, lang='en'):
    """Extract text dari PDF scan (konversi per halaman ke gambar)"""
    if fitz is None:
        return {
            'success': False,
            'error': 'PyMuPDF tidak terinstall. Jalankan: pip install pymupdf'
        }
    
    import tempfile
    import os
    
    doc = fitz.open(pdf_path)
    all_text = []
    temp_files = []
    
    try:
        for page_num in range(len(doc)):
            page = doc[page_num]
            
            # Render halaman ke gambar dengan DPI tinggi
            mat = fitz.Matrix(2.0, 2.0)  # 2x zoom = ~144 DPI
            pix = page.get_pixmap(matrix=mat)
            
            # Simpan ke temp file
            temp_file = tempfile.mktemp(suffix=f'_page_{page_num}.png')
            pix.save(temp_file)
            temp_files.append(temp_file)
            
            # OCR halaman ini
            try:
                page_text = extract_from_image(temp_file, lang)
                if page_text.strip():
                    all_text.append(f"=== Halaman {page_num + 1} ===\n{page_text}")
            except Exception as e:
                all_text.append(f"=== Halaman {page_num + 1} === [Gagal: {str(e)}]")
    
    finally:
        # Cleanup temp files
        for temp_file in temp_files:
            try:
                if os.path.exists(temp_file):
                    os.remove(temp_file)
            except:
                pass
        
        doc.close()
    
    return '\n\n'.join(all_text)

def check_installation():
    """Cek apakah PaddleOCR terinstall dengan benar"""
    try:
        ocr = get_ocr_engine()
        pymupdf_ok = fitz is not None
        
        return {
            'success': True,
            'message': 'PaddleOCR berhasil diinisialisasi',
            'pymupdf_available': pymupdf_ok,
            'pdf_support': pymupdf_ok,
            'method': 'paddleocr'
        }
    except Exception as e:
        return {
            'success': False,
            'error': f'PaddleOCR gagal: {str(e)}'
        }

def main():
    parser = argparse.ArgumentParser(description='PaddleOCR Bridge for Laravel')
    parser.add_argument('file_path', nargs='?', help='Path ke file gambar atau PDF')
    parser.add_argument('--lang', default='en', help='Bahasa (en/ch/id, default: en)')
    parser.add_argument('--check', action='store_true', help='Cek instalasi')
    
    args = parser.parse_args()
    
    # Mode cek instalasi
    if args.check:
        result = check_installation()
        print(json.dumps(result, ensure_ascii=False, indent=2))
        sys.exit(0 if result['success'] else 1)
    
    # Validasi file path
    if not args.file_path:
        print(json.dumps({
            'success': False,
            'error': 'File path diperlukan'
        }))
        sys.exit(1)
    
    path = Path(args.file_path)
    if not path.exists():
        print(json.dumps({
            'success': False,
            'error': f'File tidak ditemukan: {args.file_path}'
        }))
        sys.exit(1)
    
    # Proses file
    try:
        extension = path.suffix.lower()
        
        if extension == '.pdf':
            text = extract_from_pdf(str(path), args.lang)
        elif extension in ['.jpg', '.jpeg', '.png', '.gif', '.webp']:
            text = extract_from_image(str(path), args.lang)
        else:
            print(json.dumps({
                'success': False,
                'error': f'Format tidak didukung: {extension}'
            }))
            sys.exit(1)
        
        print(json.dumps({
            'success': True,
            'text': text.strip(),
            'method': 'paddleocr',
            'confidence': 85,
            'boxes_count': len(text.split('\n')) if text else 0,
            'language': args.lang,
            'file_type': extension.replace('.', '')
        }, ensure_ascii=False))
        
    except Exception as e:
        import traceback
        print(json.dumps({
            'success': False,
            'error': f'OCR gagal: {str(e)}',
            'traceback': traceback.format_exc()
        }))
        sys.exit(1)

if __name__ == '__main__':
    main()
