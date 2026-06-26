import zipfile
path=r'd:\New Folder\PA3-KEL06-2026\storage\app\public\templates\1776400136_Laporan GKM Hasil Kuesioner Mahasiswa 2526.docx'
with zipfile.ZipFile(path,'r') as z:
    xml=z.read('word/document.xml').decode('utf-8',errors='ignore')
for key in ['GABUNGAN_TINGKAT_I','GABUNGAN_TINGKAT_II','GABUNGAN_TINGKAT_III','GABUNGAN_TINGKAT_IV']:
    pos=xml.find(key)
    print(key, pos)
    if pos!=-1:
        print(xml[max(0,pos-100):pos+100])
