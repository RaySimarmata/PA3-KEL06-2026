#!/usr/bin/env python3
"""
Script untuk testing PaddleOCR installation
"""

import sys
import os

def test_imports():
    """Test semua import yang diperlukan"""
    print("=" * 50)
    print("Testing PaddleOCR Installation")
    print("=" * 50)
    print()
    
    try:
        print("1. Testing PaddlePaddle import...")
        import paddle
        print(f"   ✓ PaddlePaddle version: {paddle.__version__}")
    except ImportError as e:
        print(f"   ✗ Failed to import PaddlePaddle: {e}")
        return False
    
    try:
        print("2. Testing PaddleOCR import...")
        from paddleocr import PaddleOCR
        print("   ✓ PaddleOCR imported successfully")
    except ImportError as e:
        print(f"   ✗ Failed to import PaddleOCR: {e}")
        return False
    
    try:
        print("3. Testing PyMuPDF import...")
        import fitz
        print(f"   ✓ PyMuPDF version: {fitz.version}")
    except ImportError as e:
        print(f"   ✗ Failed to import PyMuPDF: {e}")
        return False
    
    try:
        print("4. Testing PIL import...")
        from PIL import Image
        print(f"   ✓ Pillow version: {Image.__version__}")
    except ImportError as e:
        print(f"   ✗ Failed to import Pillow: {e}")
        return False
    
    try:
        print("5. Testing NumPy import...")
        import numpy as np
        print(f"   ✓ NumPy version: {np.__version__}")
    except ImportError as e:
        print(f"   ✗ Failed to import NumPy: {e}")
        return False
    
    try:
        print("6. Testing OpenCV import...")
        import cv2
        print(f"   ✓ OpenCV version: {cv2.__version__}")
    except ImportError as e:
        print(f"   ✗ Failed to import OpenCV: {e}")
        return False
    
    return True

def test_paddleocr_basic():
    """Test basic PaddleOCR functionality"""
    print()
    print("=" * 50)
    print("Testing PaddleOCR Basic Functionality")
    print("=" * 50)
    print()
    
    try:
        from paddleocr import PaddleOCR
        
        print("Initializing PaddleOCR (this may take a moment)...")
        # Initialize dengan bahasa Indonesia dan Inggris
        ocr = PaddleOCR(
            use_textline_orientation=True,
            lang='id'  # Indonesian
        )
        print("✓ PaddleOCR initialized successfully")
        print()
        print("PaddleOCR is ready to use!")
        print("Supported features:")
        print("  - Text detection")
        print("  - Text recognition")
        print("  - Angle classification")
        print("  - Multi-language support (ID, EN, etc.)")
        
        return True
    except Exception as e:
        print(f"✗ Failed to initialize PaddleOCR: {e}")
        return False

def main():
    """Main test function"""
    print()
    
    # Test imports
    if not test_imports():
        print()
        print("=" * 50)
        print("✗ Installation test FAILED")
        print("=" * 50)
        sys.exit(1)
    
    # Test basic functionality
    if not test_paddleocr_basic():
        print()
        print("=" * 50)
        print("✗ Functionality test FAILED")
        print("=" * 50)
        sys.exit(1)
    
    print()
    print("=" * 50)
    print("✓ All tests PASSED!")
    print("=" * 50)
    print()
    print("Next steps:")
    print("1. Integrate PaddleOCR dengan OCRService.php")
    print("2. Test dengan file PDF/gambar real")
    print("3. Compare hasil dengan Tesseract OCR")
    print()

if __name__ == "__main__":
    main()
