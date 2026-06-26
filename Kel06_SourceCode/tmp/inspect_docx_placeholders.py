import zipfile
import os
import re
base = r'd:\New folder\PA3-KEL06-2026\storage\app\public\templates'
file = '1776400136_Laporan GKM Hasil Kuesioner Mahasiswa 2526.docx'
path = os.path.join(base, file)
with zipfile.ZipFile(path, 'r') as z:
    data = z.read('word/document.xml').decode('utf-8', errors='ignore')
    for m in re.finditer(r'(.{0,120}(?:HASIL_KUESIONER_TINGKAT_|MASUKAN_SARAN_TINGKAT_|GABUNGAN_TINGKAT_).{0,120})', data):
        print('---')
        print(m.group(1))
