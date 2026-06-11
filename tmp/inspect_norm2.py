import zipfile
path=r'd:\New Folder\PA3-KEL06-2026\app\temp_template_6a297d3e2932e.docx'
with zipfile.ZipFile(path,'r') as z:
    xml=z.read('word/document.xml').decode('utf-8',errors='ignore')
    for key in ['HASIL_KUESIONER_TINGKAT', 'MASUKAN_SARAN_TINGKAT', 'GABUNGAN_TINGKAT']:
        pos=xml.find(key)
        print('===', key, pos)
        if pos!=-1:
            print(xml[max(0,pos-150):pos+450])
        else:
            print('not found')
