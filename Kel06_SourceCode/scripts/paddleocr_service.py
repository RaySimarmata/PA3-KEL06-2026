#!/usr/bin/env python3
"""
PaddleOCR Service untuk Laravel
Service ini akan dipanggil dari PHP untuk melakukan OCR processing
"""

import sys
import json
import os
from pathlib import Path
from typing import List, Dict, Any, Optional
import argparse

try:
    from paddleocr import PaddleOCR
    import fitz  # PyMuPDF
    from PIL import Image
    import numpy as np
    import cv2
except ImportError as e:
    print(json.dumps({
        'success': False,
        'error': f'Missing dependency: {str(e)}',
        'message': 'Please run: pip install -r requirements.txt'
    }))
    sys.exit(1)


class PaddleOCRService:
    """Service untuk OCR processing menggunakan PaddleOCR"""
    
    def __init__(self, lang: str = 'id', use_gpu: bool = False):
        """
        Initialize PaddleOCR service
        
        Args:
            lang: Language code (id, en, ch, etc.)
            use_gpu: Whether to use GPU acceleration
        """
        self.lang = lang
        self.use_gpu = use_gpu
        self.ocr = None
        self._initialize_ocr()
    
    def _initialize_ocr(self):
        """Initialize PaddleOCR instance"""
        try:
            self.ocr = PaddleOCR(
                use_textline_orientation=True,
                lang=self.lang,
                det_db_thresh=0.3,
                det_db_box_thresh=0.5,
                rec_batch_num=6
            )
        except Exception as e:
            raise RuntimeError(f"Failed to initialize PaddleOCR: {str(e)}")
    
    def process_image(self, image_path: str) -> Dict[str, Any]:
        """
        Process single image file
        
        Args:
            image_path: Path to image file
            
        Returns:
            Dict containing OCR results
        """
        try:
            if not os.path.exists(image_path):
                return {
                    'success': False,
                    'error': f'Image file not found: {image_path}'
                }
            
            # Run OCR
            result = self.ocr.predict(image_path)
            
            # Extract text and confidence
            extracted_data = self._parse_ocr_result(result)
            
            return {
                'success': True,
                'file': image_path,
                'text': extracted_data['text'],
                'confidence': extracted_data['confidence'],
                'lines': extracted_data['lines'],
                'word_count': extracted_data['word_count']
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': str(e),
                'file': image_path
            }
    
    def process_pdf(self, pdf_path: str, dpi: int = 300) -> Dict[str, Any]:
        """
        Process PDF file
        
        Args:
            pdf_path: Path to PDF file
            dpi: DPI for PDF to image conversion
            
        Returns:
            Dict containing OCR results for all pages
        """
        try:
            if not os.path.exists(pdf_path):
                return {
                    'success': False,
                    'error': f'PDF file not found: {pdf_path}'
                }
            
            # Open PDF
            pdf_document = fitz.open(pdf_path)
            total_pages = len(pdf_document)
            
            all_text = []
            pages_data = []
            total_confidence = 0
            
            # Process each page
            for page_num in range(total_pages):
                page = pdf_document[page_num]
                
                # Convert page to image
                mat = fitz.Matrix(dpi/72, dpi/72)
                pix = page.get_pixmap(matrix=mat)
                img_data = pix.tobytes("png")
                
                # Save temporary image
                temp_image_path = f"/tmp/page_{page_num}.png"
                with open(temp_image_path, "wb") as f:
                    f.write(img_data)
                
                # Run OCR on page
                result = self.ocr.predict(temp_image_path)
                page_data = self._parse_ocr_result(result)
                
                all_text.append(page_data['text'])
                pages_data.append({
                    'page': page_num + 1,
                    'text': page_data['text'],
                    'confidence': page_data['confidence'],
                    'lines': page_data['lines'],
                    'word_count': page_data['word_count']
                })
                
                total_confidence += page_data['confidence']
                
                # Clean up temp file
                if os.path.exists(temp_image_path):
                    os.remove(temp_image_path)
            
            pdf_document.close()
            
            avg_confidence = total_confidence / total_pages if total_pages > 0 else 0
            combined_text = "\n\n".join(all_text)
            
            return {
                'success': True,
                'file': pdf_path,
                'total_pages': total_pages,
                'text': combined_text,
                'average_confidence': round(avg_confidence, 2),
                'pages': pages_data
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': str(e),
                'file': pdf_path
            }
    
    def _parse_ocr_result(self, result: List) -> Dict[str, Any]:
        """
        Parse PaddleOCR result
        
        Args:
            result: Raw OCR result from PaddleOCR
            
        Returns:
            Parsed data with text, confidence, and lines
        """
        if not result:
            return {
                'text': '',
                'confidence': 0,
                'lines': [],
                'word_count': 0
            }
        
        lines = []
        all_text = []
        total_confidence = 0
        line_count = 0
        
        try:
            for res in result:
                # Format baru PaddleOCR 3.x - akses via attribute
                if hasattr(res, 'rec_texts') and hasattr(res, 'rec_scores'):
                    for text, score in zip(res.rec_texts, res.rec_scores):
                        if score > 0.3 and text.strip():
                            lines.append({
                                'text': text.strip(),
                                'confidence': round(score * 100, 2)
                            })
                            all_text.append(text.strip())
                            total_confidence += score
                            line_count += 1
                # Fallback jika format dict
                elif isinstance(res, dict):
                    texts = res.get('rec_texts', [])
                    scores = res.get('rec_scores', [])
                    for text, score in zip(texts, scores):
                        if score > 0.3 and text.strip():
                            lines.append({
                                'text': text.strip(),
                                'confidence': round(score * 100, 2)
                            })
                            all_text.append(text.strip())
                            total_confidence += score
                            line_count += 1
        except Exception as e:
            pass
        
        combined_text = " ".join(all_text)
        avg_confidence = (total_confidence / line_count * 100) if line_count > 0 else 0
        word_count = len(combined_text.split())
        
        return {
            'text': combined_text,
            'confidence': round(avg_confidence, 2),
            'lines': lines,
            'word_count': word_count
        }


def main():
    """Main function untuk CLI usage"""
    parser = argparse.ArgumentParser(description='PaddleOCR Service for Laravel')
    parser.add_argument('file', help='Path to image or PDF file')
    parser.add_argument('--lang', default='id', help='Language code (default: id)')
    parser.add_argument('--gpu', action='store_true', help='Use GPU acceleration')
    parser.add_argument('--dpi', type=int, default=300, help='DPI for PDF conversion (default: 300)')
    
    args = parser.parse_args()
    
    try:
        # Initialize service
        service = PaddleOCRService(lang=args.lang, use_gpu=args.gpu)
        
        # Determine file type
        file_ext = Path(args.file).suffix.lower()
        
        if file_ext == '.pdf':
            result = service.process_pdf(args.file, dpi=args.dpi)
        elif file_ext in ['.png', '.jpg', '.jpeg', '.bmp', '.tiff']:
            result = service.process_image(args.file)
        else:
            result = {
                'success': False,
                'error': f'Unsupported file type: {file_ext}'
            }
        
        # Output JSON result
        print(json.dumps(result, ensure_ascii=False, indent=2))
        
    except Exception as e:
        print(json.dumps({
            'success': False,
            'error': str(e)
        }))
        sys.exit(1)


if __name__ == "__main__":
    main()
