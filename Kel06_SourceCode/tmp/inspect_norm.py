import zipfile
path=r'd:\New Folder\PA3-KEL06-2026\app\temp_template_6a297d3e2932e.docx'
with zipfile.ZipFile(path,'r') as z:
    xml=z.read('word/document.xml').decode('utf-8',errors='ignore')
    pos=xml.find('{{HASIL_KUESIONER')
    print('pos',pos)
    if pos!=-1:
        print(xml[pos:pos+300])
    else:
        print('no placeholder text found')
