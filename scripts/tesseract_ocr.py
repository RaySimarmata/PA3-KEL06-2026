#!/usr/bin/env python3
"""
Tesseract OCR Bridge Script for Laravel
Extracts text from images using Tesseract OCR
"""

import sys
import json
import argparse
from pathlib import Path

try:
    import pytesseract
    from PIL import Image
    import cv2
    import numpy as np
except ImportError as e:
    print(json.dumps({
        'success': False,
        'error': f'Required package not installed: {str(e)}. Please run: pip install pytesseract pillow opencv-python'
    }))
    sys.exit(1)


class TesseractOCRBridge:
    """Bridge between Laravel and Tesseract OCR"""
    
    def __init__(self, lang='eng+ind'):
        """
        Initialize Tesseract OCR
        
        Args:
            lang (str): Language code (eng, ind, eng+ind for English+Indonesian)
        """
        self.lang = lang
        
        # Try to find tesseract executable
        self.tesseract_cmd = self.find_tesseract()
        if self.tesseract_cmd:
            pytesseract.pytesseract.tesseract_cmd = self.tesseract_cmd
    
    def find_tesseract(self):
        """Find Tesseract executable in common locations"""
        import os
        import platform
        
        # Common Windows paths
        if platform.system() == 'Windows':
            common_paths = [
                r'C:\Program Files\Tesseract-OCR\tesseract.exe',
                r'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe',
                r'C:\Tesseract-OCR\tesseract.exe',
            ]
            
            for path in common_paths:
                if os.path.exists(path):
                    return path
        
        # For Linux/Mac, tesseract should be in PATH
        return None
    
    def preprocess_image(self, image_path):
        """
        Preprocess image for better OCR results
        
        Args:
            image_path (str): Path to image file
            
        Returns:
            PIL.Image: Preprocessed image
        """
        # Read image with OpenCV
        img = cv2.imread(image_path)
        
        # Convert to grayscale
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        
        # Apply thresholding to preprocess the image
        gray = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY | cv2.THRESH_OTSU)[1]
        
        # Noise removal
        gray = cv2.medianBlur(gray, 3)
        
        # Convert back to PIL Image
        return Image.fromarray(gray)
    
    def extract_text(self, image_path):
        """
        Extract text from image
        
        Args:
            image_path (str): Path to image file
            
        Returns:
            dict: Extraction results
        """
        try:
            # Validate image path
            if not Path(image_path).exists():
                return {
                    'success': False,
                    'error': f'Image file not found: {image_path}'
                }
            
            # Preprocess image
            try:
                image = self.preprocess_image(image_path)
            except Exception as e:
                # Fallback to original image if preprocessing fails
                image = Image.open(image_path)
            
            # Extract text with Tesseract
            custom_config = r'--oem 3 --psm 6'
            text = pytesseract.image_to_string(image, lang=self.lang, config=custom_config)
            
            # Get confidence data
            try:
                data = pytesseract.image_to_data(image, lang=self.lang, output_type=pytesseract.Output.DICT)
                confidences = [int(conf) for conf in data['conf'] if int(conf) > 0]
                avg_confidence = sum(confidences) / len(confidences) if confidences else 0
                boxes_count = len(confidences)
            except:
                avg_confidence = 0
                boxes_count = 0
            
            return {
                'success': True,
                'text': text.strip(),
                'confidence': round(avg_confidence, 2),
                'boxes_count': boxes_count,
                'language': self.lang,
                'method': 'tesseract'
            }
            
        except pytesseract.TesseractNotFoundError:
            return {
                'success': False,
                'error': 'Tesseract is not installed or not found in PATH. Please install Tesseract OCR.'
            }
        except Exception as e:
            import traceback
            return {
                'success': False,
                'error': f'OCR extraction failed: {str(e)}',
                'traceback': traceback.format_exc()
            }
    
    def check_installation(self):
        """Check if Tesseract is properly installed"""
        try:
            version = pytesseract.get_tesseract_version()
            return {
                'success': True,
                'message': f'Tesseract OCR is properly installed (version {version})',
                'version': str(version),
                'language': self.lang
            }
        except pytesseract.TesseractNotFoundError:
            return {
                'success': False,
                'error': 'Tesseract is not installed. Please install from: https://github.com/UB-Mannheim/tesseract/wiki'
            }
        except Exception as e:
            return {
                'success': False,
                'error': f'Tesseract check failed: {str(e)}'
            }


def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(description='Tesseract OCR Bridge for Laravel')
    parser.add_argument('image_path', nargs='?', help='Path to image file')
    parser.add_argument('--lang', default='eng+ind', help='Language code (default: eng+ind)')
    parser.add_argument('--check', action='store_true', help='Check installation')
    
    args = parser.parse_args()
    
    # Initialize bridge
    bridge = TesseractOCRBridge(lang=args.lang)
    
    # Check installation mode
    if args.check:
        result = bridge.check_installation()
        print(json.dumps(result, ensure_ascii=False, indent=2))
        sys.exit(0 if result['success'] else 1)
    
    # Extract text mode
    if not args.image_path:
        print(json.dumps({
            'success': False,
            'error': 'Image path is required'
        }))
        sys.exit(1)
    
    # Extract text
    result = bridge.extract_text(args.image_path)
    
    # Output JSON result
    print(json.dumps(result, ensure_ascii=False, indent=2))
    
    # Exit with appropriate code
    sys.exit(0 if result['success'] else 1)


if __name__ == '__main__':
    main()
