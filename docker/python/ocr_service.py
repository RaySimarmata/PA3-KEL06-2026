"""
OCR Microservice - Flask API
Digunakan oleh Laravel untuk PaddleOCR processing
"""

from flask import Flask, request, jsonify
import base64
import os
import tempfile
import logging

app = Flask(__name__)
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Lazy load OCR engine (berat, load sekali saja)
_ocr_engine = None

def get_ocr_engine():
    global _ocr_engine
    if _ocr_engine is None:
        try:
            from paddleocr import PaddleOCR
            _ocr_engine = PaddleOCR(use_angle_cls=True, lang='en', use_gpu=False)
            logger.info("PaddleOCR engine loaded successfully")
        except Exception as e:
            logger.error(f"Failed to load PaddleOCR: {e}")
            raise
    return _ocr_engine


@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok', 'service': 'ocr'})


@app.route('/ocr/image', methods=['POST'])
def ocr_image():
    """
    Process image and return extracted text.
    Expects JSON: { "image": "<base64_encoded_image>", "format": "png|jpg|pdf" }
    """
    try:
        data = request.get_json()
        if not data or 'image' not in data:
            return jsonify({'success': False, 'error': 'No image data provided'}), 400

        # Decode base64 image
        image_data = base64.b64decode(data['image'])
        file_format = data.get('format', 'png')

        with tempfile.NamedTemporaryFile(suffix=f'.{file_format}', delete=False) as tmp:
            tmp.write(image_data)
            tmp_path = tmp.name

        try:
            ocr = get_ocr_engine()
            result = ocr.ocr(tmp_path, cls=True)

            # Extract text lines
            extracted_text = []
            if result and result[0]:
                for line in result[0]:
                    if line and len(line) >= 2:
                        text = line[1][0]  # text content
                        confidence = line[1][1]  # confidence score
                        extracted_text.append({
                            'text': text,
                            'confidence': float(confidence)
                        })

            full_text = '\n'.join([item['text'] for item in extracted_text])

            return jsonify({
                'success': True,
                'text': full_text,
                'lines': extracted_text,
                'total_lines': len(extracted_text)
            })

        finally:
            os.unlink(tmp_path)

    except Exception as e:
        logger.error(f"OCR error: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/ocr/pdf', methods=['POST'])
def ocr_pdf():
    """
    Process PDF and return extracted text per page.
    Expects JSON: { "file": "<base64_encoded_pdf>" }
    """
    try:
        data = request.get_json()
        if not data or 'file' not in data:
            return jsonify({'success': False, 'error': 'No file data provided'}), 400

        pdf_data = base64.b64decode(data['file'])

        with tempfile.NamedTemporaryFile(suffix='.pdf', delete=False) as tmp:
            tmp.write(pdf_data)
            tmp_path = tmp.name

        try:
            import fitz  # pymupdf
            doc = fitz.open(tmp_path)
            pages_text = []

            ocr = get_ocr_engine()

            for page_num in range(len(doc)):
                page = doc.load_page(page_num)
                # Render page as image
                mat = fitz.Matrix(2, 2)  # 2x scale for better OCR
                pix = page.get_pixmap(matrix=mat)

                with tempfile.NamedTemporaryFile(suffix='.png', delete=False) as img_tmp:
                    pix.save(img_tmp.name)
                    img_path = img_tmp.name

                try:
                    result = ocr.ocr(img_path, cls=True)
                    page_text = ''
                    if result and result[0]:
                        page_text = '\n'.join([line[1][0] for line in result[0] if line and len(line) >= 2])
                    pages_text.append({'page': page_num + 1, 'text': page_text})
                finally:
                    os.unlink(img_path)

            doc.close()

            full_text = '\n\n'.join([f"=== Halaman {p['page']} ===\n{p['text']}" for p in pages_text])

            return jsonify({
                'success': True,
                'text': full_text,
                'pages': pages_text,
                'total_pages': len(pages_text)
            })

        finally:
            os.unlink(tmp_path)

    except Exception as e:
        logger.error(f"PDF OCR error: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)
