<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LaporanGJM;
use App\Models\Ajaran;
use Carbon\Carbon;

class LaporanGJMSeeder extends Seeder
{
    public function run()
    {
        // Get the first ajaran record or create one if none exists
        $ajaran = Ajaran::first();
        if (!$ajaran) {
            $ajaran = Ajaran::create([
                'tahun_ajaran' => 2024,
                'semester' => 'ganjil',
                'status' => 'aktif',
            ]);
        }

        $laporan = [
            [
                'ajaran_id' => $ajaran->id,
                'periode_mulai' => Carbon::create(2024, 1, 1),
                'periode_akhir' => Carbon::create(2024, 1, 31),
                'jenis_laporan' => 'bulanan',
                'ringkasan_mutu_institusi' => 'Laporan Bulanan Januari 2024',
                'analisis_kepatuhan' => 'Kepatuhan upload materi mencapai 83%, dengan 1 prodi yang masih perlu perbaikan.',
                'temuan_utama' => 'Keterlambatan upload RPS pada 2 mata kuliah',
                'rekomendasi_perbaikan' => 'Perlu peningkatan monitoring dan reminder otomatis',
                'status_laporan' => 'approved',
                'tanggal_submit' => Carbon::create(2024, 2, 1),
                'jumlah_prodi_terlibat' => 5,
                'jumlah_laporan_gkm_diterima' => 4,
                'file_laporan' => 'laporan_bulanan_januari_2024.pdf',
            ],
            [
                'ajaran_id' => $ajaran->id,
                'periode_mulai' => Carbon::create(2023, 10, 1),
                'periode_akhir' => Carbon::create(2023, 12, 31),
                'jenis_laporan' => 'semester',
                'ringkasan_mutu_institusi' => 'Analisis Strategis Q4 2023',
                'analisis_kepatuhan' => 'Evaluasi strategis untuk kuarter keempat tahun 2023.',
                'temuan_utama' => 'Peningkatan kualitas pembelajaran secara keseluruhan',
                'rekomendasi_perbaikan' => 'Pertahankan momentum positif dan tingkatkan inovasi',
                'status_laporan' => 'approved',
                'tanggal_submit' => Carbon::create(2023, 12, 15),
                'jumlah_prodi_terlibat' => 5,
                'jumlah_laporan_gkm_diterima' => 5,
                'file_laporan' => 'analisis_strategis_q4_2023.pdf',
            ],
            [
                'ajaran_id' => $ajaran->id,
                'periode_mulai' => Carbon::create(2024, 2, 1),
                'periode_akhir' => Carbon::create(2024, 2, 7),
                'jenis_laporan' => 'bulanan',
                'ringkasan_mutu_institusi' => 'Draft Laporan Mingguan W1 Feb',
                'analisis_kepatuhan' => 'Draft laporan operasional minggu pertama Februari.',
                'temuan_utama' => 'Masih dalam tahap pengumpulan data',
                'rekomendasi_perbaikan' => 'Percepat proses pengumpulan dan verifikasi data',
                'status_laporan' => 'draft',
                'tanggal_submit' => null,
                'jumlah_prodi_terlibat' => 5,
                'jumlah_laporan_gkm_diterima' => 3,
                'file_laporan' => null,
            ],
            [
                'ajaran_id' => $ajaran->id,
                'periode_mulai' => Carbon::create(2024, 1, 1),
                'periode_akhir' => Carbon::create(2024, 1, 31),
                'jenis_laporan' => 'bulanan',
                'ringkasan_mutu_institusi' => 'Laporan Inventaris Gudang',
                'analisis_kepatuhan' => 'Inventarisasi peralatan dan fasilitas gudang.',
                'temuan_utama' => 'Semua peralatan dalam kondisi baik',
                'rekomendasi_perbaikan' => 'Lakukan maintenance rutin sesuai jadwal',
                'status_laporan' => 'approved',
                'tanggal_submit' => Carbon::create(2024, 1, 15),
                'jumlah_prodi_terlibat' => 5,
                'jumlah_laporan_gkm_diterima' => 5,
                'file_laporan' => 'laporan_inventaris_gudang.pdf',
            ],
        ];

        foreach ($laporan as $data) {
            LaporanGJM::create($data);
        }
    }
}