#!/usr/bin/env python3
"""
Create test images for OCR testing
Generates sample images with Indonesian text
"""

import os
from PIL import Image, ImageDraw, ImageFont

def create_test_images():
    """Create sample test images with Indonesian text"""
    
    # Create test-images directory
    test_dir = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'public', 'test-images')
    os.makedirs(test_dir, exist_ok=True)
    
    # Sample texts in Indonesian
    texts = [
        {
            'filename': 'sample1.jpg',
            'text': 'Laporan Kegiatan Triwulan I\nTahun Akademik 2024/2025\n\nProgram Studi Teknologi Rekayasa Perangkat Lunak\nInstitut Teknologi Del',
            'size': (800, 400)
        },
        {
            'filename': 'sample2.jpg',
            'text': 'DAFTAR HADIR RAPAT\n\nTanggal: 15 Januari 2024\nTempat: Ruang Rapat GKM\n\n1. Dr. John Doe\n2. Prof. Jane Smith\n3. Dr. Ahmad Rahman',
            'size': (800, 500)
        },
        {
            'filename': 'sample3.jpg',
            'text': 'HASIL EVALUASI PEMBELAJARAN\n\nMata Kuliah: Pemrograman Web\nDosen: Dr. Budi Santoso\n\nNilai Rata-rata: 85.5\nJumlah Mahasiswa: 45\nTingkat Kelulusan: 95%',
            'size': (800, 450)
        },
        {
            'filename': 'sample4.jpg',
            'text': 'DOKUMENTASI KEGIATAN\n\nWorkshop Pengembangan Kurikulum\n20-21 Februari 2024\n\nPeserta: 30 Dosen\nNarasumber: Prof. Dr. Siti Aminah\nLokasi: Aula Utama IT Del',
            'size': (800, 400)
        },
        {
            'filename': 'sample5.jpg',
            'text': 'MONITORING RPS\n\nSemester: Genap 2023/2024\nProdi: Teknik Informatika\n\nTotal RPS: 45\nRPS Lengkap: 42 (93%)\nRPS Belum Lengkap: 3 (7%)',
            'size': (800, 350)
        }
    ]
    
    print("Creating test images...")
    print(f"Output directory: {test_dir}")
    print()
    
    for item in texts:
        # Create image
        img = Image.new('RGB', item['size'], color='white')
        draw = ImageDraw.Draw(img)
        
        # Try to use a nice font, fallback to default if not available
        try:
            # Try different font paths
            font_paths = [
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/System/Library/Fonts/Helvetica.ttc',
                'C:\\Windows\\Fonts\\arial.ttf',
            ]
            
            font = None
            for font_path in font_paths:
                if os.path.exists(font_path):
                    font = ImageFont.truetype(font_path, 24)
                    break
            
            if font is None:
                font = ImageFont.load_default()
        except:
            font = ImageFont.load_default()
        
        # Draw text
        draw.text((50, 50), item['text'], fill='black', font=font)
        
        # Add border
        draw.rectangle([(0, 0), (item['size'][0]-1, item['size'][1]-1)], outline='gray', width=2)
        
        # Save image
        output_path = os.path.join(test_dir, item['filename'])
        img.save(output_path, 'JPEG', quality=95)
        
        print(f"✓ Created: {item['filename']}")
    
    print()
    print(f"Successfully created {len(texts)} test images!")
    print(f"Location: {test_dir}")
    print()
    print("You can now test OCR with:")
    print(f"  php artisan ocr:test {os.path.join(test_dir, 'sample1.jpg')}")
    print(f"  php artisan ocr:test --batch")

if __name__ == '__main__':
    try:
        create_test_images()
    except Exception as e:
        print(f"Error: {e}")
        print()
        print("Make sure you have Pillow installed:")
        print("  pip install Pillow")
