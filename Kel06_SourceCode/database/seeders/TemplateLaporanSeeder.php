<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TemplateLaporan;
use App\Models\Prodi;

class TemplateLaporanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all prodi
        $prodis = Prodi::all();

        foreach ($prodis as $prodi) {
            // Check if template already exists for this prodi
            $existingTemplate = TemplateLaporan::where('prodi_id', $prodi->id)
                ->where('jenis_template', 'laporan_bulanan')
                ->where('nama_template', 'Template Laporan Kepuasan Mahasiswa (Final)')
                ->first();

            if (!$existingTemplate) {
                $templateFile = storage_path('app/public/templates/template_laporan_kepuasan_mahasiswa_final.docx');
                $fileSize = file_exists($templateFile) ? filesize($templateFile) : 0;
                
                TemplateLaporan::create([
                    'prodi_id' => $prodi->id,
                    'nama_template' => 'Template Laporan Kepuasan Mahasiswa (Final)',
                    'nama_file' => 'template_laporan_kepuasan_mahasiswa_final.docx',
                    'jenis_file' => 'docx',
                    'file_path' => 'templates/template_laporan_kepuasan_mahasiswa_final.docx',
                    'ukuran_file' => $fileSize,
                    'deskripsi' => 'Template standar dengan cover page, daftar isi, dan format Times New Roman',
                    'uploaded_by' => null,
                    'jenis_template' => 'laporan_bulanan',
                    'contoh_konten' => $this->getContohKonten(),
                    'is_active' => true,
                    'is_indexed' => false,
                ]);
            }
        }
    }

    private function getContohKonten()
    {
        return "# LAPORAN HASIL KEPUASAN MAHASISWA
## Mata Kuliah Semester GENAP 2025/2026

**GUGUS KENDALI MUTU (GKM) Prodi D3 Teknologi Informasi**  
**Institut Teknologi Del**  
**2025/2026**

## I. PENDAHULUAN

### a. Tujuan
Survei ini bertujuan untuk mengevaluasi mata kuliah pada semester GENAP 2025/2026 di lingkungan program studi D3 Teknologi Informasi Fakultas Vokasi Institut Teknologi Del.

### b. Waktu pelaksanaan
Penyebaran kuesioner evaluasi mata kuliah dilaksanakan pada bulan Maret 2026. Penyebaran kuesioner dibagi menjadi 2 tahap yaitu pembagian pertama dilakukan pada minggu ke-14 dan pembagian kedua dilakukan pada minggu ke-15.

### c. Ruang Lingkup
Kuesioner evaluasi menggunakan 17 pertanyaan dengan 4 kriteria penilaian berdasarkan skala Likert.

| Skala | Keterangan | Nilai |
|-------|------------|-------|
| 4 | Sangat Baik | 3.26 - 4.00 |
| 3 | Baik | 2.51 - 3.25 |
| 2 | Cukup | 1.76 - 2.50 |
| 1 | Kurang | 1.00 - 1.75 |

## II. HASIL KUESIONER

### I. Tingkat I (Semester 2)
| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Indeks Kepuasan |
|-----------------|-----------------|----------------|------------------|
| TI101 | Pemrograman Dasar | Dr. John Doe | 3.45 |

### Masukan dan Saran
| Kode Matakuliah | Nama Matakuliah | Masukan/Saran |
|-----------------|-----------------|---------------|
| TI101 | Pemrograman Dasar | Tingkatkan praktikum dengan tools modern |

## III. KESIMPULAN DAN SARAN

### Kesimpulan
- Adanya matakuliah yang dihitung berdasarkan kode matakuliah, sebaiknya tetap dipisahkan sesuai prodi
- Indeks Kepuasan semua matakuliah di prodi adalah 3.2. Nilai kepuasan ini telah melampaui indikator minimum yaitu 2.8

### Saran dan Rekomendasi
Perlu peningkatan kualitas pembelajaran melalui inovasi metode pengajaran dan penggunaan teknologi terkini.

Laguboti, 25 Juni 2025

(GKM PRODI D3 TEKNOLOGI INFORMASI)";
    }
}