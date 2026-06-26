import re
frag = '''<w:r w:rsidRPr="000135C7"><w:rPr><w:rFonts w:ascii="Times New Roman" w:eastAsia="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b w:val="0"/><w:bCs w:val="0"/><w:color w:val="000000"/></w:rPr><w:t>{{HASIL_KUESIONER_TINGKAT_</w:t></w:r><w:r w:rsidRPr="000135C7"><w:rPr><w:rFonts w:ascii="Times New Roman" w:eastAsia="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b w:val="0"/><w:bCs w:val="0"/><w:color w:val="000000"/></w:rPr><w:t>I</w:t></w:r><w:r w:rsidRPr="0005489B"><w:rPr><w:rFonts w:ascii="Times New Roman" w:eastAsia="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b w:val="0"/><w:bCs w:val="0"/><w:color w:val="000000"/></w:rPr><w:t xml:space="preserve">}} </w:t></w:r>'''
pattern = re.compile(r'(<w:r[^>]*>\s*<w:t[^>]*>\s*\{\{(?:HASIL_KUESIONER_TINGKAT_|MASUKAN_SARAN_TINGKAT_|GABUNGAN_TINGKAT_).*?</w:t></w:r>(?:\s*<w:r[^>]*>\s*<w:t[^>]*>.*?</w:t></w:r>)*?\s*<w:r[^>]*>\s*<w:t[^>]*>.*?\}\}.*?</w:t></w:r>)', re.S)
match = pattern.search(frag)
print('match', bool(match))
if match:
    print(match.group(1))
