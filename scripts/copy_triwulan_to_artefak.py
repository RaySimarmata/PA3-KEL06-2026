#!/usr/bin/env python3
"""
Script untuk copy struktur triwulan-create ke artefak-create
dengan replacement otomatis
"""

import re

source_file = r"c:\Semester 6\PA3\resources\views\gjm\buat-laporan\triwulan-create.blade.php"
dest_file = r"c:\Semester 6\PA3\resources\views\gkm\laporan-artefak\create.blade.php"

print("Reading source file: triwulan-create.blade.php")

with open(source_file, 'r', encoding='utf-8') as f:
    content = f.read()

print("Performing replacements for artefak...")

# Replace all triwulan references with artefak
content = content.replace('triwulan', 'artefak')
content = content.replace('Triwulan', 'Artefak')
content = content.replace('TRIWULAN', 'ARTEFAK')

# Replace periode options
old_periode = '''                                        <option value="">Pilih Periode Artefak</option>
                                        <option value="1">Artefak I (Januari - Maret)</option>
                                        <option value="2">Artefak II (April - Juni)</option>
                                        <option value="3">Artefak III (Juli - September)</option>
                                        <option value="4">Artefak IV (Oktober - Desember)</option>'''

new_periode = '''                                        <option value="">-- Pilih Periode --</option>
                                        @foreach ($periodes as $p)
                                            <option value="{{ $p['value'] }}" {{ old('periode') == $p['value'] ? 'selected' : '' }}>
                                                {{ $p['label'] }}
                                            </option>
                                        @endforeach'''

content = content.replace(old_periode, new_periode)

# Replace field names
content = content.replace('periode_artefak', 'periode')

# Replace routes
content = content.replace("route('gjm.buat-laporan.artefak", "route('gkm.laporan-artefak")

# Replace descriptions
content = content.replace(
    'AI Agent akan menganalisis data kegiatan dan monitoring mutu dalam periode artefak yang dipilih',
    'AI Agent akan menganalisis data RPS dan Materi dalam periode yang dipilih'
)
content = content.replace(
    'Data laporan bulanan otomatis digunakan sebagai konteks',
    'Data monitoring RPS dan Materi otomatis digunakan sebagai konteks'
)

# Replace AI header text  
content = content.replace(
    'Siap membantu Anda membuat laporan',
    'Siap membantu Anda membuat laporan RPS dan Materi'
)

# Replace placeholder
content = content.replace(
    'Deskripsikan website yang ingin Anda buat...',
    'Deskripsikan laporan artefak yang ingin Anda buat...'
)

print("Writing to destination: artefak-create.blade.php")

with open(dest_file, 'w', encoding='utf-8') as f:
    f.write(content)

print("")
print("✓ File successfully copied and modified!")
print("")
print("Summary of changes:")
print("- Replaced 'triwulan' with 'artefak'")
print("- Updated periode options to use dynamic $periodes")
print("- Updated routes to GKM paths")
print("- Updated descriptions for RPS and Materi context")
print("")
print("Please manually verify:")
print("1. Route references")
print("2. Field names match controller expectations")
print("3. JavaScript functionality")
