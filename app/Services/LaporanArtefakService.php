<?php

namespace App\Services;

use App\Models\LaporanGKM;
use App\Models\TemplateLaporan;
use App\Models\RpsMonitoringSnapshot;
use App\Models\PerkuliahanMonitoringSnapshot;
use App\Models\DocumentChunk;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Carbon\Carbon;

class LaporanArtefakService
{
    private $apiKey;
    private $baseUrl;
    private $model;
    private $textExtractionService;
    private $structureService;
    private $advancedChunkingService;

    public function __construct(
        TextExtractionService $textExtractionService,
        DocumentStructureService $structureService,
        AdvancedChunkingService $advancedChunkingService
    ) {
        $this->apiKey = config('services.llm.api_key');
        $this->baseUrl = config('services.llm.base_url');
        $this->model = config('services.llm.model');

        $this->textExtractionService = $textExtractionService;
        $this->structureService = $structureService;
        $this->advancedChunkingService = $advancedChunkingService;
    }

    /**
     * Generate laporan artefak (dengan placeholder-based template jika ada template)
     */
    public function generateLaporan($laporanId)
    {
        $laporan = LaporanGKM::with(['prodi', 'template'])->findOrFail($laporanId);

        Log::info('Starting laporan artefak generation', [
            'laporan_id' => $laporanId,
            'periode' => $laporan->periode,
            'prodi_id' => $laporan->prodi_id,
            'template_id' => $laporan->template_id,
        ]);

        try {
            $laporan->update(['status' => 'processing']);

            Log::info('Starting Word generation process', [
                'laporan_id' => $laporanId,
                'has_ai_preview_draft' => !empty($laporan->ai_preview_draft),
                'ai_preview_length' => !empty($laporan->ai_preview_draft) ? strlen($laporan->ai_preview_draft) : 0,
                'has_ai_sections' => !empty($laporan->ai_sections),
                'ai_sections_count' => is_array($laporan->ai_sections) ? count($laporan->ai_sections) : 0,
            ]);

            // 1. Kumpulkan data artefak dari DB
            $dataArtefak = $this->collectArtefakData($laporan);

            // 2. Generate narasi AI atau ekstrak dari preview
            if (!empty($laporan->ai_preview_draft)) {
                Log::info('Using AI preview draft from user chat', [
                    'laporan_id' => $laporanId,
                    'draft_length' => strlen($laporan->ai_preview_draft),
                    'has_sections' => !empty($laporan->ai_sections)
                ]);

                $narasiAI = $this->extractNarasiFromAIPreview($laporan->ai_preview_draft, $laporan->ai_sections);
            } else {
                Log::warning('No AI preview draft found, generating new narasi', [
                    'laporan_id' => $laporanId,
                    'reason' => 'ai_preview_draft is empty'
                ]);

                $narasiAI = $this->generateNarasiWithAI($dataArtefak);
            }

            // Pastikan semua field yang dibutuhkan ada
            $narasiAI = $this->ensureCompleteNarasi($narasiAI, $dataArtefak);

            // 3. Tentukan metode: template-based atau full-AI
            if ($laporan->template_id) {
                $wordPath = $this->generateFromTemplate($laporan, $dataArtefak, $narasiAI);
                $content = $this->buildFallbackContent($dataArtefak, $narasiAI);
            } else {
                $content = $this->buildFallbackContent($dataArtefak, $narasiAI);
                $wordPath = $this->createWordDocument($content, $laporan);
            }

            $laporan->update([
                'file_word' => $wordPath,
                'konten_laporan' => $content,
                'total_rps' => $dataArtefak['total_rps'],
                'total_materi' => $dataArtefak['total_materi'],
                'status' => 'completed',
                'generated_at' => now(),
            ]);

            Log::info('Laporan artefak generation completed', ['laporan_id' => $laporanId]);

            return $laporan;

        } catch (\Exception $e) {
            Log::error('Laporan artefak generation failed', [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $laporan->update(['status' => 'error', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Pastikan semua field narasi tersedia (jika kosong, isi dengan default)
     */
    protected function ensureCompleteNarasi($narasiAI, $dataArtefak)
    {
        $defaults = [
            'latar_belakang' => "Program Studi {$dataArtefak['prodi']} melaksanakan monitoring artefak perkuliahan pada semester {$dataArtefak['semester']} Tahun Akademik {$dataArtefak['tahun_ajaran']}. Kegiatan ini bertujuan untuk memastikan ketersediaan dan kelengkapan dokumen RPS dan materi perkuliahan sebagai bagian dari penjaminan mutu internal.",

            'dasar_acuan' => "1. Standar Nasional Pendidikan Tinggi (Permenristekdikti No. 44 Tahun 2015)\n2. Standar Mutu Program Studi {$dataArtefak['prodi']}\n3. Kalender Akademik Semester {$dataArtefak['semester']} TA {$dataArtefak['tahun_ajaran']}",

            'tujuan' => "1. Memeriksa kelengkapan dokumen RPS yang diunggah oleh dosen pengampu\n2. Memeriksa kelengkapan materi perkuliahan (teori dan praktikum) per minggu\n3. Memberikan rekomendasi perbaikan untuk peningkatan kepatuhan dosen",

            'sasaran' => "Seluruh dosen pengampu mata kuliah di lingkungan Program Studi {$dataArtefak['prodi']} semester {$dataArtefak['semester']} TA {$dataArtefak['tahun_ajaran']}.",

            'waktu_pelaksanaan' => "Kegiatan monitoring dilaksanakan pada minggu pertama perkuliahan semester {$dataArtefak['semester']} TA {$dataArtefak['tahun_ajaran']}.",

            'ruang' => "Monitoring dilaksanakan secara daring melalui sistem informasi akademik (CIS) dan secara luring melalui koordinasi dengan dosen pengampu.",

            'instrumen_pengukuran' => "1. Ceklist kelengkapan RPS\n2. Ceklist kelengkapan materi perkuliahan per minggu\n3. Form laporan monitoring GKM",

            'hasil_pemeriksaan' => $narasiAI['hasil_pemeriksaan'] ?? "Berdasarkan hasil pemeriksaan, dari {$dataArtefak['total_rps']} mata kuliah, sebanyak {$dataArtefak['rps_uploaded']} mata kuliah ({$dataArtefak['rps_percentage']}%) telah mengunggah RPS. Untuk kelengkapan materi perkuliahan, dari {$dataArtefak['total_materi']} mata kuliah, sebanyak {$dataArtefak['materi_uploaded']} mata kuliah ({$dataArtefak['materi_percentage']}%) telah mengunggah materi lengkap per minggu.",

            'analisis_ketercapaian' => $narasiAI['analisis_ketercapaian'] ?? "Tingkat kepatuhan dosen dalam mengunggah RPS mencapai {$dataArtefak['rps_percentage']}%, sedangkan untuk kelengkapan materi mencapai {$dataArtefak['materi_percentage']}%. Capaian ini menunjukkan bahwa masih terdapat ruang peningkatan, terutama pada kelengkapan materi perkuliahan.",

            'tindak_lanjut' => $narasiAI['tindak_lanjut'] ?? "1. Mengirimkan reminder kepada dosen yang belum mengunggah RPS\n2. Koordinasi dengan Ketua Program Studi untuk memberikan teguran tertulis\n3. Menjadwalkan monitoring ulang pada pertengahan semester\n4. Memberikan insentif bagi dosen dengan kepatuhan 100%",

            'program_kerja' => "1. Pemeriksaan kelengkapan RPS di CIS\n2. Pemeriksaan materi perkuliahan Week 1-16\n3. Konfirmasi kelengkapan artefak kepada dosen\n4. Pelaporan hasil monitoring ke Prodi",

            'pelaksanaan' => "Monitoring dilaksanakan oleh tim GKM dengan melakukan pengecekan satu per satu pada sistem CIS. Dosen yang belum lengkap dihubungi melalui email dan WhatsApp.",

            // HAMBATAN_PENJELASAN tanpa tanda ** (bold markdown)
            'hambatan_penjelasan' => "Beberapa kendala yang dihadapi:\n\n" .
                   "1. Keterlambatan Unggah Dokumen: Beberapa dosen belum mengunggah RPS dan materi perkuliahan tepat waktu sesuai jadwal yang ditentukan.\n\n" .
                   "2. Kurangnya Respons Dosen: Tidak semua dosen merespons konfirmasi kelengkapan artefak melalui email atau WhatsApp dengan cepat.\n\n" .
                   "3. Kelengkapan Materi Praktikum: Untuk mata kuliah praktikum, kelengkapan modul dan jobsheet masih rendah dibandingkan teori.\n\n" .
                   "4. Keterbatasan Waktu Tim GKM: Waktu tim GKM terbatas karena harus mengecek satu per satu mata kuliah secara manual.\n\n" .
                   "5. Sosialisasi Kurang Maksimal: Beberapa dosen mengaku belum mendapatkan informasi tentang batas waktu unggah artefak.",

            'tabel_hambatan' => [
                [
                    'program_kerja' => 'Pemeriksaan RPS dan artefak perkuliahan minggu ke-1',
                    'catatan' => 'Dilaksanakan sesuai jadwal pada minggu pertama perkuliahan',
                    'hambatan' => 'Beberapa dosen belum mengunggah RPS tepat waktu',
                    'saran' => 'Mengirimkan reminder H-7 sebelum batas unggah'
                ],
                [
                    'program_kerja' => 'Konfirmasi kelengkapan artefak kepada dosen pengampu',
                    'catatan' => 'Dilaksanakan melalui email dan grup WhatsApp dosen',
                    'hambatan' => 'Tidak semua dosen merespons konfirmasi dengan cepat',
                    'saran' => 'Koordinasi melalui Ketua Program Studi untuk peneguran langsung'
                ],
                [
                    'program_kerja' => 'Pemeriksaan materi perkuliahan per minggu',
                    'catatan' => 'Dilaksanakan secara berkala setiap akhir minggu',
                    'hambatan' => 'Materi praktikum banyak yang belum lengkap',
                    'saran' => 'Dilakukan pendampingan khusus untuk dosen praktikum'
                ],
            ],

            'kesimpulan_penutup' => $narasiAI['kesimpulan_penutup'] ?? "Monitoring artefak perkuliahan semester {$dataArtefak['semester']} TA {$dataArtefak['tahun_ajaran']} telah dilaksanakan. Secara umum, kepatuhan dosen dalam mengunggah RPS cukup baik, namun masih perlu peningkatan pada kelengkapan materi per minggu. Rekomendasi tindak lanjut akan dilaksanakan oleh tim GKM untuk periode berikutnya.",
        ];

        return array_merge($defaults, $narasiAI);
    }

    protected function collectArtefakData($laporan)
    {
        $periode = $laporan->periode;

        $periodeObj   = Carbon::createFromFormat('Y-m', $periode);
        $tahun        = $periodeObj->year;
        $bulan        = $periodeObj->month;
        $semester     = ($bulan >= 8) ? 1 : 2;
        $tahunAjaranSlash = $semester == 1
            ? $tahun . '/' . ($tahun + 1)
            : ($tahun - 1) . '/' . $tahun;

        $rpsData = RpsMonitoringSnapshot::with('dosen')
            ->orderByRaw("SUBSTRING(kode_mk, 4, 1)")
            ->orderBy('kode_mk')
            ->get();

        $materiData = PerkuliahanMonitoringSnapshot::with('dosen')
            ->orderByRaw("SUBSTRING(kode_mk, 4, 1)")
            ->orderBy('kode_mk')
            ->orderBy('jenis_materi')
            ->get();

        Log::info('collectArtefakData (ALL data)', [
            'rps_found' => $rpsData->count(),
            'mat_found' => $materiData->count(),
        ]);

        $totalRPS    = $rpsData->count();
        $rpsUploaded = $rpsData->where('status_rps', 'SUDAH UPLOAD')->count();

        $totalMateri    = $materiData->unique('kode_mk')->count();
        $materiUploaded = $materiData->where('status_upload', 'SUDAH UPLOAD')
            ->unique('kode_mk')->count();

        return [
            'periode'            => $periode,
            'tahun_ajaran'       => $tahunAjaranSlash,
            'semester'           => $semester == 1 ? 'Ganjil' : 'Genap',
            'prodi'              => $laporan->prodi->nama_prodi ?? 'D4 TRPL',
            'total_rps'          => $totalRPS,
            'rps_uploaded'       => $rpsUploaded,
            'rps_not_uploaded'   => $totalRPS - $rpsUploaded,
            'rps_percentage'     => $totalRPS > 0 ? round(($rpsUploaded / $totalRPS) * 100, 1) : 0,
            'total_materi'       => $totalMateri,
            'materi_uploaded'    => $materiUploaded,
            'materi_not_uploaded'=> $totalMateri - $materiUploaded,
            'materi_percentage'  => $totalMateri > 0 ? round(($materiUploaded / $totalMateri) * 100, 1) : 0,
            'rps_details'        => $rpsData,
            'materi_details'     => $materiData,
        ];
    }

    protected function extractNarasiFromAIPreview($aiPreviewDraft, $aiSections)
    {
        Log::info('Extracting narasi from AI preview draft', [
            'draft_length' => strlen($aiPreviewDraft),
            'has_sections' => !empty($aiSections),
            'sections_count' => is_array($aiSections) ? count($aiSections) : 0
        ]);

        $extracted = [];

        if (is_array($aiSections) && !empty($aiSections)) {
            Log::info('Using parsed ai_sections from database', [
                'sections_count' => count($aiSections),
                'sections_keys' => array_keys($aiSections)
            ]);

            foreach ($aiSections as $key => $content) {
                $keyLower = strtolower($key);

                if (str_contains($keyLower, 'latar belakang') || str_contains($keyLower, '1.1')) {
                    $extracted['latar_belakang'] = $content;
                } elseif (str_contains($keyLower, 'dasar acuan') || str_contains($keyLower, '1.2')) {
                    $extracted['dasar_acuan'] = $content;
                } elseif (str_contains($keyLower, 'tujuan') || str_contains($keyLower, '1.3')) {
                    $extracted['tujuan'] = $content;
                } elseif (str_contains($keyLower, 'sasaran') || str_contains($keyLower, '1.4')) {
                    $extracted['sasaran'] = $content;
                } elseif (str_contains($keyLower, 'waktu') || str_contains($keyLower, '1.5')) {
                    $extracted['waktu_pelaksanaan'] = $content;
                } elseif (str_contains($keyLower, 'ruang lingkup') || str_contains($keyLower, 'ruang') || str_contains($keyLower, '1.6')) {
                    $extracted['ruang'] = $content;
                } elseif (str_contains($keyLower, 'instrumen') || str_contains($keyLower, '1.7')) {
                    $extracted['instrumen_pengukuran'] = $content;
                } elseif (str_contains($keyLower, 'program kerja') || str_contains($keyLower, 'bab 2')) {
                    $extracted['program_kerja'] = $content;
                } elseif (str_contains($keyLower, 'pelaksanaan') || str_contains($keyLower, 'bab 3')) {
                    $extracted['pelaksanaan'] = $content;
                } elseif ((str_contains($keyLower, 'hambatan') || str_contains($keyLower, 'kendala')) && (str_contains($keyLower, 'bab 4') || str_contains($keyLower, 'penjelasan'))) {
                    $extracted['hambatan_penjelasan'] = $content;
                } elseif (str_contains($keyLower, 'hasil') && str_contains($keyLower, 'pemeriksaan')) {
                    $extracted['hasil_pemeriksaan'] = $content;
                } elseif (str_contains($keyLower, 'analisis') && str_contains($keyLower, 'ketercapaian')) {
                    $extracted['analisis_ketercapaian'] = $content;
                } elseif (str_contains($keyLower, 'tindak lanjut')) {
                    $extracted['tindak_lanjut'] = $content;
                } elseif (str_contains($keyLower, 'kesimpulan') || str_contains($keyLower, 'penutup')) {
                    $extracted['kesimpulan_penutup'] = $content;
                }
            }
        }

        return $extracted;
    }

    protected function generateNarasiWithAI($dataArtefak)
    {
        $mkBelumUploadRPS = $dataArtefak['rps_details']
            ->where('status_rps', 'BELUM UPLOAD')
            ->map(fn($i) => ($i->nama_matkul ?? '-') . ' (' . ($i->dosen->inisial_nama ?? $i->pegawai_id) . ')')
            ->implode(', ');

        $mkBelumUploadMateri = $dataArtefak['materi_details']
            ->where('status_upload', 'BELUM UPLOAD')
            ->unique('kode_mk')
            ->map(fn($i) => ($i->nama_matkul ?? '-') . ' (' . ($i->dosen->inisial_nama ?? $i->pegawai_id) . ')')
            ->implode(', ');

        $prompt = "Anda adalah AI Agent GKM yang membuat narasi laporan monitoring artefak perkuliahan.\n\n";
        $prompt .= "DATA:\n";
        $prompt .= "- Semester: {$dataArtefak['semester']} TA {$dataArtefak['tahun_ajaran']}\n";
        $prompt .= "- Prodi: {$dataArtefak['prodi']}\n";
        $prompt .= "- Total MK: {$dataArtefak['total_rps']}\n";
        $prompt .= "- RPS Upload: {$dataArtefak['rps_uploaded']}/{$dataArtefak['total_rps']} ({$dataArtefak['rps_percentage']}%)\n";
        $prompt .= "- Materi Upload: {$dataArtefak['materi_uploaded']}/{$dataArtefak['total_materi']} ({$dataArtefak['materi_percentage']}%)\n";
        if ($mkBelumUploadRPS) {
            $prompt .= "- MK belum upload RPS: {$mkBelumUploadRPS}\n";
        }
        if ($mkBelumUploadMateri) {
            $prompt .= "- MK belum upload Materi: {$mkBelumUploadMateri}\n";
        }
        $prompt .= "\n";

        $prompt .= "Buat JSON dengan field berikut (semua dalam Bahasa Indonesia formal):\n";
        $prompt .= "{\n";
        $prompt .= '  "hasil_pemeriksaan": "narasi 2-3 paragraf hasil pemeriksaan",' . "\n";
        $prompt .= '  "analisis_ketercapaian": "narasi 1-2 paragraf analisis ketercapaian target",' . "\n";
        $prompt .= '  "tindak_lanjut": "narasi 1-2 paragraf rekomendasi tindak lanjut",' . "\n";
        $prompt .= '  "kesimpulan_penutup": "narasi 1 paragraf penutup"' . "\n";
        $prompt .= "}\n";

        $raw = $this->callAI([
            'role' => 'system',
            'content' => 'Anda adalah AI Agent GKM ahli laporan akademik. Balas HANYA JSON valid.',
        ], $prompt, 2500);

        if (!$raw) {
            return [];
        }

        $clean = trim($raw);
        $clean = preg_replace('/```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/```\s*/i', '', $clean);
        $start = strpos($clean, '{');
        $end = strrpos($clean, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $clean = substr($clean, $start, $end - $start + 1);
        }

        $result = json_decode($clean, true);
        return $result ?: [];
    }

    /**
     * Build placeholder map - mendukung placeholder yang sudah diubah di template
     * {{PROGRAM_KERJA}}, {{PELAKSANAAN}}, {{HAMBATAN_PENJELASAN}}
     */
    protected function buildPlaceholderMap($dataArtefak, $laporan, $narasiAI)
    {
        $now = Carbon::now()->locale('id');
        $namaKetua = $laporan->user->name ?? 'Ketua GKM D4 TRPL';
        $tanggalLaporan = $now->translatedFormat('d F Y');

        $periodeObj = Carbon::createFromFormat('Y-m', $laporan->periode);
        $rentangBulan = $periodeObj->locale('id')->translatedFormat('F') . ' s/d ' .
            $periodeObj->addMonths(1)->locale('id')->translatedFormat('F Y');

        // TABEL_HAMBATAN tidak diisi sebagai teks biasa karena akan diinjeksikan
        // sebagai tabel Word yang rapi melalui injectHambatanTable().

        // Mapping nilai untuk setiap placeholder
        $values = [
            // Data statis
            'SEMESTER' => $dataArtefak['semester'],
            'TAHUN_AJARAN' => $dataArtefak['tahun_ajaran'],
            'NAMA_PRODI' => $dataArtefak['prodi'],
            'TANGGAL_LAPORAN' => 'Sitoluama, ' . $tanggalLaporan,
            'NAMA_KETUA_GKM' => $namaKetua,
            'RENTANG_BULAN_PROGRAM_KERJA' => $rentangBulan,
            'TANGGAL_REMINDER' => $tanggalLaporan,
            'TANGGAL_PEMERIKSAAN' => $tanggalLaporan,
            'TOTAL_MK' => (string) $dataArtefak['total_rps'],
            'TOTAL_RPS_UPLOAD' => (string) $dataArtefak['rps_uploaded'],
            'TOTAL_RPS_KURIKULUM_UPLOAD' => (string) $dataArtefak['rps_uploaded'],
            'STATISTIK_RPS_PERSEN' => $dataArtefak['rps_percentage'] . '%',
            'STATISTIK_MATERI_PERSEN' => $dataArtefak['materi_percentage'] . '%',

            // BAB 1 PENDAHULUAN
            'LATAR_BELAKANG' => $narasiAI['latar_belakang'] ?? '',
            'DASAR_ACUAN' => $narasiAI['dasar_acuan'] ?? '',
            'TUJUAN' => $narasiAI['tujuan'] ?? '',
            'SASARAN' => $narasiAI['sasaran'] ?? '',
            'WAKTU_PELAKSANAAN' => $narasiAI['waktu_pelaksanaan'] ?? '',
            'RUANG' => $narasiAI['ruang'] ?? '',
            'RUANG_LINGKUP' => $narasiAI['ruang'] ?? '',
            'INSTRUMEN_PENGUKURAN' => $narasiAI['instrumen_pengukuran'] ?? '',

            // BAB 2 PROGRAM KERJA - sesuai perubahan template
            'PROGRAM_KERJA' => $narasiAI['program_kerja'] ?? $this->getDefaultProgramKerja(),

            // BAB 3 PELAKSANAAN - sesuai perubahan template
            'PELAKSANAAN' => $narasiAI['pelaksanaan'] ?? $this->getDefaultPelaksanaan(),

            // BAB 4 HAMBATAN PENJELASAN - sesuai perubahan template (tanpa **)
            'HAMBATAN_PENJELASAN' => $narasiAI['hambatan_penjelasan'] ?? $this->getDefaultHambatanPenjelasan(),

            // BAB 5-6
            'HASIL_PEMERIKSAAN' => $narasiAI['hasil_pemeriksaan'] ?? '',
            'ANALISIS_KETERCAPAIAN' => $narasiAI['analisis_ketercapaian'] ?? '',
            'TINDAK_LANJUT' => $narasiAI['tindak_lanjut'] ?? '',
            'KESIMPULAN_PENUTUP' => $narasiAI['kesimpulan_penutup'] ?? '',
        ];

        // Buat array final dengan format {{PLACEHOLDER}} => value
        $finalMap = [];
        foreach ($values as $key => $value) {
            // Format standar {{PLACEHOLDER}}
            $finalMap['{{' . $key . '}}'] = $value;

            // Format dengan spasi {{ PLACEHOLDER }} - untuk mengatasi spasi di template
            $finalMap['{{ ' . $key . ' }}'] = $value;

            // Format dengan spasi di dalam {{ HAMBATAN_PENJELASAN }}
            if ($key === 'HAMBATAN_PENJELASAN') {
                $finalMap['{{ HAMBATAN_PENJELASAN}}'] = $value;
                $finalMap['{{HAMBATAN_PENJELASAN }}'] = $value;
                $finalMap['{{ HAMBATAN_PENJELASAN }}'] = $value;
            }
        }

        Log::info('Placeholder map built', [
            'total_placeholders' => count($finalMap),
            'key_placeholders' => array_keys($values)
        ]);

        return $finalMap;
    }

    /**
     * Build tabel hambatan dalam format teks yang rapi untuk placeholder {{TABEL_HAMBATAN}}
     */
    protected function buildTabelHambatanText($rows)
    {
        if (!is_array($rows) || empty($rows)) {
            $rows = [
                [
                    'program_kerja' => 'Pemeriksaan RPS dan artefak perkuliahan minggu ke-1',
                    'catatan' => 'Dilaksanakan sesuai jadwal pada minggu pertama perkuliahan',
                    'hambatan' => 'Beberapa dosen belum mengunggah RPS tepat waktu',
                    'saran' => 'Mengirimkan reminder H-7 sebelum batas unggah'
                ],
                [
                    'program_kerja' => 'Konfirmasi kelengkapan artefak kepada dosen pengampu',
                    'catatan' => 'Dilaksanakan melalui email dan grup WhatsApp dosen',
                    'hambatan' => 'Tidak semua dosen merespons konfirmasi dengan cepat',
                    'saran' => 'Koordinasi melalui Ketua Program Studi untuk peneguran langsung'
                ],
                [
                    'program_kerja' => 'Pemeriksaan materi perkuliahan per minggu',
                    'catatan' => 'Dilaksanakan secara berkala setiap akhir minggu',
                    'hambatan' => 'Materi praktikum banyak yang belum lengkap',
                    'saran' => 'Dilakukan pendampingan khusus untuk dosen praktikum'
                ],
            ];
        }

        $lines = [];
        $lines[] = "+----+------------------------------------------+------------------------------------------+------------------------------------------+----------------------------------------------------+";
        $lines[] = "| No | Program Kerja                            | Catatan                                  | Hambatan                                 | Saran atau Pemecahan Masalah                       |";
        $lines[] = "+----+------------------------------------------+------------------------------------------+------------------------------------------+----------------------------------------------------+";

        foreach ($rows as $i => $row) {
            $no = ($i + 1) . '.';
            $programKerja = $row['program_kerja'] ?? '-';
            $catatan = $row['catatan'] ?? '-';
            $hambatan = $row['hambatan'] ?? '-';
            $saran = $row['saran'] ?? '-';

            $lines[] = sprintf("| %-2s | %-40s | %-40s | %-40s | %-50s |",
                $no,
                substr($programKerja, 0, 40),
                substr($catatan, 0, 40),
                substr($hambatan, 0, 40),
                substr($saran, 0, 50)
            );
            $lines[] = "+----+------------------------------------------+------------------------------------------+------------------------------------------+----------------------------------------------------+";
        }

        return implode("\n", $lines);
    }

    /**
     * Inject tabel hambatan sebagai OOXML table yang rapi
     */
    protected function injectHambatanTable(\PhpOffice\PhpWord\TemplateProcessor $tp, $rows)
    {
        $headers = ['No.', 'Program Kerja', 'Catatan', 'Hambatan', 'Saran atau Pemecahan Masalah'];
        $widths = [700, 2600, 2200, 2500, 1500];

        $dataRows = [];
        if (is_array($rows) && !empty($rows)) {
            foreach ($rows as $i => $row) {
                $dataRows[] = [
                    (string) ($i + 1) . '.',
                    $row['program_kerja'] ?? '-',
                    $row['catatan'] ?? '-',
                    $row['hambatan'] ?? '-',
                    $row['saran'] ?? '-',
                ];
            }
        } else {
            $dataRows = [
                ['1.', 'Pemeriksaan RPS dan artefak perkuliahan minggu ke-1', 'Dilaksanakan sesuai jadwal pada minggu pertama perkuliahan', 'Beberapa dosen belum mengunggah RPS tepat waktu', 'Mengirimkan reminder H-7 sebelum batas unggah'],
                ['2.', 'Konfirmasi kelengkapan artefak kepada dosen pengampu', 'Dilaksanakan melalui email dan grup WhatsApp dosen', 'Tidak semua dosen merespons konfirmasi dengan cepat', 'Koordinasi melalui Ketua Program Studi untuk peneguran langsung'],
                ['3.', 'Pemeriksaan materi perkuliahan per minggu', 'Dilaksanakan secara berkala setiap akhir minggu', 'Materi praktikum banyak yang belum lengkap', 'Dilakukan pendampingan khusus untuk dosen praktikum'],
            ];
        }

        $xml = $this->buildWordTableXML($headers, $dataRows, $widths);

        $placeholders = ['TABEL_HAMBATAN', 'tabel_hambatan', 'TabelHambatan'];
        foreach ($placeholders as $ph) {
            try {
                $this->replaceWithTable($tp, $ph, $xml);
                Log::info("Hambatan table injected with placeholder: {$ph}");
                return;
            } catch (\Exception $e) {
                // Lanjut ke placeholder berikutnya
            }
        }

        Log::warning('Hambatan table injection failed for all placeholder formats');
    }

    /**
     * Default values untuk BAB 2 PROGRAM KERJA
     */
    protected function getDefaultProgramKerja()
    {
        return "1. Pemeriksaan kelengkapan dokumen RPS seluruh mata kuliah di sistem CIS\n" .
               "2. Pemeriksaan kelengkapan materi perkuliahan (teori dan praktikum) per minggu (Week 1-16)\n" .
               "3. Konfirmasi kelengkapan artefak kepada dosen pengampu melalui email dan WhatsApp\n" .
               "4. Pelaporan hasil monitoring kepada Ketua Program Studi\n" .
               "5. Tindak lanjut berupa reminder dan pembinaan kepada dosen yang belum lengkap";
    }

    /**
     * Default values untuk BAB 3 PELAKSANAAN
     */
    protected function getDefaultPelaksanaan()
    {
        return "Pelaksanaan monitoring artefak perkuliahan dilakukan oleh tim GKM dengan prosedur sebagai berikut:\n\n" .
               "1. Tim GKM mengakses sistem CIS untuk memeriksa kelengkapan RPS setiap mata kuliah\n" .
               "2. Data RPS yang sudah/belum diupload dicatat dan direkap per program studi\n" .
               "3. Pemeriksaan materi perkuliahan dilakukan per minggu (Week 1 s.d Week 16)\n" .
               "4. Untuk mata kuliah praktikum, pemeriksaan difokuskan pada modul dan jobsheet praktikum\n" .
               "5. Dosen yang belum melengkapi artefak dihubungi melalui email dan WhatsApp\n" .
               "6. Hasil monitoring direkap dan dilaporkan ke Ketua Program Studi untuk ditindaklanjuti";
    }

    /**
     * Default values untuk BAB 4 HAMBATAN (tanpa format bold **)
     */
    protected function getDefaultHambatanPenjelasan()
    {
        return "Beberapa kendala yang dihadapi:\n\n" .
               "1. Keterlambatan Unggah Dokumen: Beberapa dosen belum mengunggah RPS dan materi perkuliahan tepat waktu sesuai jadwal yang ditentukan.\n\n" .
               "2. Kurangnya Respons Dosen: Tidak semua dosen merespons konfirmasi kelengkapan artefak melalui email atau WhatsApp dengan cepat.\n\n" .
               "3. Kelengkapan Materi Praktikum: Untuk mata kuliah praktikum, kelengkapan modul dan jobsheet masih rendah dibandingkan teori.\n\n" .
               "4. Keterbatasan Waktu Tim GKM: Waktu tim GKM terbatas karena harus mengecek satu per satu mata kuliah secara manual.\n\n" .
               "5. Sosialisasi Kurang Maksimal: Beberapa dosen mengaku belum mendapatkan informasi tentang batas waktu unggah artefak.";
    }

    protected function buildTabelRPS($dataArtefak)
    {
        $rows = "Tingkat\tKode MK\tNama MK\tInisial Dosen\tRPS (0=Tidak, 1=Ya)\n";
        $rows .= str_repeat("-", 80) . "\n";

        $grouped = [];
        foreach ($dataArtefak['rps_details'] as $item) {
            $kodeMK = $item->kode_mk ?? '-';
            $tingkat = $item->raw_data['tingkat'] ?? $item->raw_data['semester'] ?? '-';

            if (!isset($grouped[$kodeMK])) {
                $grouped[$kodeMK] = [
                    'tingkat' => $tingkat,
                    'kode_mk' => $kodeMK,
                    'nama_matkul' => $item->nama_matkul ?? '-',
                    'dosen' => [],
                    'status_rps' => $item->status_rps,
                ];
            }

            $inisial = $item->dosen->inisial_nama ?? (string) $item->pegawai_id;
            if (!in_array($inisial, $grouped[$kodeMK]['dosen'])) {
                $grouped[$kodeMK]['dosen'][] = $inisial;
            }
        }

        foreach ($grouped as $item) {
            $rps = ($item['status_rps'] === 'SUDAH UPLOAD') ? '1' : '0';
            $dosenGabung = implode(', ', $item['dosen']);
            $rows .= implode("\t", [
                $item['tingkat'],
                $item['kode_mk'],
                $item['nama_matkul'],
                $dosenGabung,
                $rps,
            ]) . "\n";
        }

        $rows .= str_repeat("-", 80) . "\n";
        $rows .= "Jumlah\t{$dataArtefak['total_rps']}\t\t\t{$dataArtefak['rps_uploaded']}\n";

        return $rows;
    }

    protected function buildTabelMateri($dataArtefak)
    {
        $rows = "Tingkat\tKode MK\tNama MK\tInisial Dosen\tTeori (Slide/Video/Catatan)\n";
        $rows .= str_repeat("-", 80) . "\n";

        foreach ($dataArtefak['materi_details'] as $item) {
            $materi = ($item->status_materi === 'Sudah Upload') ? 'Ada' : '-';
            $rows .= implode("\t", [
                $item->tingkat ?? '-',
                $item->kode_mk ?? '-',
                $item->nama_matkul ?? '-',
                $item->dosen_pengampu ?? '-',
                $materi,
            ]) . "\n";
        }

        return $rows;
    }

    protected function generateFromTemplate($laporan, $dataArtefak, $narasiAI)
    {
        $template = TemplateLaporan::find($laporan->template_id);
        if (!$template) {
            Log::warning('Template not found, fallback to full-AI mode');
            return $this->createWordDocument($this->buildFallbackContent($dataArtefak, $narasiAI), $laporan);
        }

        $possiblePaths = [
            storage_path('app/public/' . $template->file_path),
            storage_path('app/' . $template->file_path),
        ];
        $templateFilePath = null;
        foreach ($possiblePaths as $p) {
            if (file_exists($p)) {
                $templateFilePath = $p;
                break;
            }
        }

        if (!$templateFilePath) {
            Log::warning('Template file not found on disk, fallback to full-AI mode');
            return $this->createWordDocument($this->buildFallbackContent($dataArtefak, $narasiAI), $laporan);
        }

        Log::info('Using template for Word generation', [
            'template_path' => $templateFilePath,
            'template_id' => $laporan->template_id,
        ]);

        $preprocessedPath = $this->preprocessTemplate($templateFilePath);
        $tp = new \PhpOffice\PhpWord\TemplateProcessor($preprocessedPath);
        $tp->setMacroChars('{{', '}}');

        $map = $this->buildPlaceholderMap($dataArtefak, $laporan, $narasiAI);

        $replacedCount = 0;
        $failedPlaceholders = [];

        foreach ($map as $placeholder => $value) {
            $key = trim($placeholder, '{}');
            $key = trim($key);
            try {
                $tp->setValue($key, htmlspecialchars((string) $value, ENT_XML1, 'UTF-8'));
                $replacedCount++;
            } catch (\Exception $e) {
                $failedPlaceholders[] = $key;
                Log::warning('Failed to replace placeholder', [
                    'placeholder' => $key,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Placeholder replacement summary', [
            'total_placeholders' => count($map),
            'replaced_count' => $replacedCount,
            'failed_count' => count($failedPlaceholders),
        ]);

        try {
            $this->injectRPSTable($tp, $dataArtefak);
        } catch (\Exception $e) {
            Log::warning('RPS table injection failed', ['error' => $e->getMessage()]);
        }

        try {
            $this->injectMateriTable($tp, $dataArtefak);
        } catch (\Exception $e) {
            Log::warning('Materi table injection failed', ['error' => $e->getMessage()]);
        }

        // Inject tabel hambatan
        try {
            $this->injectHambatanTable($tp, $narasiAI['tabel_hambatan'] ?? []);
        } catch (\Exception $e) {
            Log::warning('Hambatan table injection failed', ['error' => $e->getMessage()]);
        }

        $tmpOut = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lap_out_' . uniqid() . '.docx';
        $tp->saveAs($tmpOut);

        $this->cleanupGeneratedDocPlaceholders($tmpOut, $map);

        $fileName = 'laporan_artefak_' . $laporan->periode . '_' . time() . '.docx';
        $destRel = 'laporan_artefak/' . $fileName;
        $destFull = storage_path('app/' . $destRel);

        if (!file_exists(dirname($destFull))) {
            mkdir(dirname($destFull), 0755, true);
        }

        copy($tmpOut, $destFull);
        unlink($tmpOut);
        unlink($preprocessedPath);

        return $destRel;
    }

    protected function convertDoubleBracePlaceholders(string $xml): string
    {
        $xml = $this->mergeRunsInParagraphs($xml);

        $xml = preg_replace_callback(
            '/\{\{([^}]*(?:\}(?!\})[^}]*)*)\}\}/s',
            function ($m) {
                $content = $m[1];
                $cleaned = preg_replace('/<[^>]*>/', '', $content);
                $cleaned = preg_replace('/[\s\x{00A0}\p{Z}]+/u', '_', $cleaned);
                $cleaned = preg_replace('/[^\p{L}\p{N}_]+/u', '_', $cleaned);
                $cleaned = preg_replace('/_+/', '_', $cleaned);
                $cleaned = trim($cleaned, '_');
                $cleaned = strtoupper($cleaned);

                if (preg_match('/^[A-Z0-9_]+$/', $cleaned)) {
                    return '{{' . $cleaned . '}}';
                }
                return $m[0];
            },
            $xml
        );

        $xml = preg_replace_callback(
            '/\$\s*\{\s*([A-Za-z0-9_]+)\s*\}/s',
            function ($m) {
                return '{{' . strtoupper($m[1]) . '}}';
            },
            $xml
        );

        return $xml;
    }

    protected function normalizePlaceholderName(string $content): ?string
    {
        $cleaned = preg_replace('/<[^>]*>/', '', $content);
        $cleaned = preg_replace('/[\s\x{00A0}\p{Z}]+/u', '_', $cleaned);
        $cleaned = preg_replace('/[^\p{L}\p{N}_]+/u', '_', $cleaned);
        $cleaned = preg_replace('/_+/', '_', $cleaned);
        $cleaned = trim($cleaned, '_');
        $cleaned = strtoupper($cleaned);

        return preg_match('/^[A-Z0-9_]+$/', $cleaned) ? $cleaned : null;
    }

    protected function cleanupGeneratedDocPlaceholders(string $docxPath, array $map): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            return;
        }

        $placeholders = [];
        foreach ($map as $placeholder => $value) {
            $key = trim($placeholder, '{}');
            $placeholders[$key] = htmlspecialchars((string) $value, ENT_XML1, 'UTF-8');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('/\.xml$/i', $name)) {
                continue;
            }
            $content = $zip->getFromName($name);
            if ($content === false) {
                continue;
            }

            $fixed = $this->convertDoubleBracePlaceholders($content);

            foreach ($placeholders as $key => $value) {
                $fixed = str_replace(['{{' . $key . '}}', '${' . $key . '}'], $value, $fixed);
            }

            $fixed = preg_replace('/\{\{\s*[A-Z0-9_]+\s*\}\}/i', '', $fixed);
            $fixed = preg_replace('/\$\{\s*[A-Z0-9_]+\s*\}/i', '', $fixed);

            if ($fixed !== $content) {
                $zip->addFromString($name, $fixed);
            }
        }

        $zip->close();
    }

    protected function preprocessTemplate(string $sourcePath): string
    {
        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tpl_' . uniqid() . '.docx';

        copy($sourcePath, $tmpPath);

        $zip = new \ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            unlink($tmpPath);
            throw new \RuntimeException('Cannot open template docx for preprocessing');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('/\.xml$/i', $name)) continue;

            $content = $zip->getFromName($name);
            if ($content === false) continue;

            $converted = $this->convertDoubleBracePlaceholders($content);
            $converted = $this->mergeRunsInParagraphs($converted);

            if ($converted !== $content) {
                libxml_use_internal_errors(true);
                $valid = @simplexml_load_string($converted);
                if ($valid !== false) {
                    $zip->addFromString($name, $converted);
                }
                libxml_clear_errors();
            }
        }

        $zip->close();
        return $tmpPath;
    }

    protected function mergeRunsInParagraphs(string $xml): string
    {
        return preg_replace_callback(
            '/<w:p[ >].*?<\/w:p>/s',
            function ($matches) {
                $paragraph = $matches[0];
                if (!preg_match_all('/<w:r(?:\s[^>]*)?>.*?<\/w:r>/s', $paragraph, $runMatches)) {
                    return $paragraph;
                }

                $runs = $runMatches[0];
                $texts = [];
                foreach ($runs as $run) {
                    preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $run, $textMatches);
                    $texts[] = implode('', $textMatches[1]);
                }

                $origCombined = implode('', $texts);
                $origTexts = $texts;

                preg_match_all('/\{\{([^}]*(?:\}(?!\})[^}]*)*)\}\}/s', $origCombined, $matches, PREG_OFFSET_CAPTURE);
                if (empty($matches[0])) {
                    return $paragraph;
                }

                $tokens = [];
                foreach ($matches[0] as $match) {
                    $placeholderName = $this->normalizePlaceholderName($match[0]);
                    if ($placeholderName !== null) {
                        $tokens[] = [
                            'token' => '{{' . $placeholderName . '}}',
                            'start' => $match[1],
                            'length' => mb_strlen($match[0], 'UTF-8'),
                        ];
                    }
                }

                if (empty($tokens)) {
                    return $paragraph;
                }

                foreach ($tokens as $tokenData) {
                    $token = $tokenData['token'];
                    $tokenName = substr($token, 2, -2);
                    $origPattern = '/\{\{?\s*' . preg_quote($tokenName, '/') . '\s*\}?\}/';
                    if (!preg_match($origPattern, $origCombined, $match, PREG_OFFSET_CAPTURE)) {
                        continue;
                    }

                    $tokenStart = $match[0][1];
                    $origTokenLen = mb_strlen($match[0][0], 'UTF-8');
                    $cumulative = 0;
                    $startRun = -1;
                    $endRun = -1;

                    foreach ($origTexts as $index => $text) {
                        $runStart = $cumulative;
                        $runEnd = $cumulative + mb_strlen($text, 'UTF-8');

                        if ($startRun === -1 && $tokenStart < $runEnd && $tokenStart >= $runStart) {
                            $startRun = $index;
                        }
                        if ($startRun !== -1 && ($tokenStart + $origTokenLen) <= $runEnd) {
                            $endRun = $index;
                            break;
                        }

                        $cumulative = $runEnd;
                    }

                    if ($startRun === -1 || $endRun === -1) {
                        continue;
                    }

                    $runs[$startRun] = preg_replace(
                        '/<w:t[^>]*>.*?<\/w:t>/s',
                        '<w:t xml:space="preserve">' . $token . '</w:t>',
                        $runs[$startRun],
                        1
                    );
                    $runs[$startRun] = preg_replace(
                        '/(<w:t[^>]*>.*?<\/w:t>)(?:.*?<w:t[^>]*>.*?<\/w:t>)+/s',
                        '$1',
                        $runs[$startRun]
                    );

                    for ($i = $startRun + 1; $i <= $endRun; $i++) {
                        $runs[$i] = preg_replace('/<w:t[^>]*>.*?<\/w:t>/s', '<w:t></w:t>', $runs[$i]);
                    }

                    $origTexts[$startRun] = $token;
                    for ($i = $startRun + 1; $i <= $endRun; $i++) {
                        $origTexts[$i] = '';
                    }
                    $origCombined = implode('', $origTexts);
                }

                $fixedParagraph = $paragraph;
                foreach ($runs as $index => $fixedRun) {
                    $fixedParagraph = str_replace($runMatches[0][$index], $fixedRun, $fixedParagraph);
                }

                return $fixedParagraph;
            },
            $xml
        );
    }

    protected function injectRPSTable(\PhpOffice\PhpWord\TemplateProcessor $tp, $dataArtefak)
    {
        $headers = ['Tingkat', 'Kode Mata Kuliah', 'Nama Mata Kuliah', 'Inisial Dosen Pengampu', 'RPS (0=Tidak, 1=Yes)'];
        $widths = [800, 1800, 3000, 2200, 1400];

        $grouped = [];
        foreach ($dataArtefak['rps_details'] as $item) {
            $kodeMk  = $item->kode_mk ?? '';
            $tingkat = strlen($kodeMk) >= 4 ? substr($kodeMk, 3, 1) : '-';

            if (!isset($grouped[$kodeMk])) {
                $grouped[$kodeMk] = [
                    'tingkat' => $tingkat,
                    'kode_mk' => $kodeMk,
                    'nama_matkul' => $item->nama_matkul ?? '-',
                    'dosen' => [],
                    'status_rps' => $item->status_rps,
                ];
            }

            $dosenNama = $item->dosen->nama ?? $item->dosen->inisial_nama ?? (string) $item->pegawai_id;
            if (!in_array($dosenNama, $grouped[$kodeMk]['dosen'])) {
                $grouped[$kodeMk]['dosen'][] = $dosenNama;
            }
        }

        $dataRows = [];
        $lastTingkat = '';

        foreach ($grouped as $item) {
            $rps = ($item['status_rps'] === 'SUDAH UPLOAD') ? '1' : '0';
            $dosenGabung = implode(', ', $item['dosen']);
            $dataRows[] = [
                $item['tingkat'] !== $lastTingkat ? $item['tingkat'] : '',
                $item['kode_mk'],
                $item['nama_matkul'],
                $dosenGabung,
                $rps,
            ];
            $lastTingkat = $item['tingkat'];
        }

        $dataRows[] = [
            'Jumlah',
            (string) $dataArtefak['total_rps'],
            '',
            '',
            (string) $dataArtefak['rps_uploaded'],
        ];

        $xml = $this->buildWordTableXML($headers, $dataRows, $widths);

        $placeholders = ['TABEL_RPS', 'tabel_rps', 'TabelRPS'];
        foreach ($placeholders as $ph) {
            try {
                $this->replaceWithTable($tp, $ph, $xml);
                Log::info("RPS table injected with placeholder: {$ph}");
                return;
            } catch (\Exception $e) {
                // Lanjut ke placeholder berikutnya
            }
        }
    }

    protected function injectMateriTable(\PhpOffice\PhpWord\TemplateProcessor $tp, $dataArtefak)
    {
        $xml = $this->buildMateriTableXML($dataArtefak);

        $placeholders = ['TABEL_MATERI', 'tabel_materi', 'TabelMateri'];
        foreach ($placeholders as $ph) {
            try {
                $this->replaceWithTable($tp, $ph, $xml);
                Log::info("Materi table injected with placeholder: {$ph}");
                return;
            } catch (\Exception $e) {
                // Lanjut ke placeholder berikutnya
            }
        }
    }

    protected function buildMateriTableXML($dataArtefak): string
    {
        $wTingkat = 400;
        $wKodeMK = 750;
        $wNamaMK = 1400;
        $wDosen = 1000;
        $wTeori = 300;
        $wPraktikum = 300;
        $totalWeeks = 16;

        $fillHeader = 'D9E1F2';
        $fillSubHdr = 'EBF0FA';

        $makeCell = function (string $text, int $width, bool $bold = false, string $fill = 'auto', int $gridSpan = 1, bool $vMergeStart = false, bool $vMergeCont = false, string $align = 'center') use ($fillHeader): string {
            $safe = htmlspecialchars($text, ENT_XML1, 'UTF-8');

            $tcPr = '<w:tcPr>';
            $tcPr .= '<w:tcW w:w="' . $width . '" w:type="dxa"/>';
            if ($gridSpan > 1) {
                $tcPr .= '<w:gridSpan w:val="' . $gridSpan . '"/>';
            }
            if ($vMergeStart) {
                $tcPr .= '<w:vMerge w:val="restart"/>';
            } elseif ($vMergeCont) {
                $tcPr .= '<w:vMerge/>';
            }
            $tcPr .= '<w:shd w:val="clear" w:color="auto" w:fill="' . $fill . '"/>';
            $tcPr .= '</w:tcPr>';

            $boldTag = $bold ? '<w:b/>' : '';
            $para = '<w:p><w:pPr><w:jc w:val="' . $align . '"/></w:pPr>'
                . '<w:r><w:rPr>' . $boldTag . '<w:sz w:val="18"/></w:rPr>'
                . '<w:t xml:space="preserve">' . $safe . '</w:t></w:r></w:p>';

            return '<w:tc>' . $tcPr . $para . '</w:tc>';
        };

        $totalWidth = $wTingkat + $wKodeMK + $wNamaMK + $wDosen
            + ($wTeori + $wPraktikum) * $totalWeeks;

        $xml = '<w:tbl>';
        $xml .= '<w:tblPr>';
        $xml .= '<w:tblW w:w="' . $totalWidth . '" w:type="dxa"/>';
        $xml .= '<w:tblBorders>';
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $s) {
            $xml .= '<w:' . $s . ' w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        }
        $xml .= '</w:tblBorders>';
        $xml .= '</w:tblPr>';

        // Header Row 1
        $xml .= '<w:tr>';
        $xml .= $makeCell('Tingkat', $wTingkat, true, $fillHeader, 1, true, false);
        $xml .= $makeCell('Kode Mata Kuliah', $wKodeMK, true, $fillHeader, 1, true, false);
        $xml .= $makeCell('Nama Mata Kuliah', $wNamaMK, true, $fillHeader, 1, true, false);
        $xml .= $makeCell('Dosen Pengampu', $wDosen, true, $fillHeader, 1, true, false);
        for ($w = 1; $w <= $totalWeeks; $w++) {
            $xml .= $makeCell('Week ' . $w, $wTeori + $wPraktikum, true, $fillHeader, 2, false, false);
        }
        $xml .= '</w:tr>';

        // Header Row 2
        $xml .= '<w:tr>';
        $xml .= $makeCell('', $wTingkat, false, $fillHeader, 1, false, true);
        $xml .= $makeCell('', $wKodeMK, false, $fillHeader, 1, false, true);
        $xml .= $makeCell('', $wNamaMK, false, $fillHeader, 1, false, true);
        $xml .= $makeCell('', $wDosen, false, $fillHeader, 1, false, true);
        for ($w = 1; $w <= $totalWeeks; $w++) {
            $xml .= $makeCell('T', $wTeori, true, $fillSubHdr);
            $xml .= $makeCell('P', $wPraktikum, true, $fillSubHdr);
        }
        $xml .= '</w:tr>';

        // Data Rows
        $mkMap = [];
        foreach ($dataArtefak['materi_details'] as $item) {
            $kodeMk  = $item->kode_mk ?? '-';
            $tingkat = $item->tingkat ?? (strlen($kodeMk) >= 4 ? substr($kodeMk, 3, 1) : '-');
            $key = $kodeMk . '|' . $tingkat;

            if (!isset($mkMap[$key])) {
                $mkMap[$key] = [
                    'tingkat' => $tingkat,
                    'kode_mk' => $kodeMk,
                    'nama_mk' => $item->nama_matkul ?? '-',
                    'dosen' => $item->dosen->nama ?? $item->dosen->inisial_nama ?? '-',
                    'teori_weeks' => array_fill(0, 16, ''),
                    'prak_weeks' => array_fill(0, 16, ''),
                ];
            }

            $rawData = is_array($item->raw_data) ? $item->raw_data : [];
            $weeks = $rawData['weeks'] ?? [];

            if ($item->jenis_materi === 'Materi Teori') {
                for ($idx = 0; $idx < 16; $idx++) {
                    if (isset($weeks[$idx])) {
                        $val = $weeks[$idx];
                        $mkMap[$key]['teori_weeks'][$idx] = (is_null($val) || $val === '') ? '0' : (string) $val;
                    }
                }
            } elseif ($item->jenis_materi === 'Materi Praktikum') {
                for ($idx = 0; $idx < 16; $idx++) {
                    if (isset($weeks[$idx])) {
                        $val = $weeks[$idx];
                        $mkMap[$key]['prak_weeks'][$idx] = (is_null($val) || $val === '') ? '0' : (string) $val;
                    }
                }
            }
        }

        uasort($mkMap, function ($a, $b) {
            $t = strcmp($a['tingkat'], $b['tingkat']);
            return $t !== 0 ? $t : strcmp($a['kode_mk'], $b['kode_mk']);
        });

        $lastTingkat = '';
        foreach ($mkMap as $row) {
            $tingkat = $row['tingkat'];
            $xml .= '<w:tr>';
            $xml .= $makeCell($tingkat !== $lastTingkat ? $tingkat : '', $wTingkat, false, 'auto', 1, false, false, 'center');
            $xml .= $makeCell($row['kode_mk'], $wKodeMK, false, 'auto', 1, false, false, 'center');
            $xml .= $makeCell($row['nama_mk'], $wNamaMK, false, 'auto', 1, false, false, 'left');
            $xml .= $makeCell($row['dosen'], $wDosen, false, 'auto', 1, false, false, 'center');
            for ($w = 0; $w < $totalWeeks; $w++) {
                $xml .= $makeCell($row['teori_weeks'][$w] ?? '', $wTeori, false, 'auto', 1, false, false, 'center');
                $xml .= $makeCell($row['prak_weeks'][$w] ?? '', $wPraktikum, false, 'auto', 1, false, false, 'center');
            }
            $xml .= '</w:tr>';
            $lastTingkat = $tingkat;
        }

        $xml .= '</w:tbl>';

        return $xml;
    }

    /**
     * Build Word table XML dengan styling yang lebih baik
     */
    protected function buildWordTableXML(array $headers, array $dataRows, array $widths): string
    {
        $totalWidth = array_sum($widths);
        $maxWidth = 9000;
        if ($totalWidth > $maxWidth) {
            $ratio = $maxWidth / $totalWidth;
            foreach ($widths as &$w) {
                $w = max(700, (int) round($w * $ratio));
            }
            unset($w);
            $totalWidth = array_sum($widths);
        }

        $xml = '<w:tbl>';
        $xml .= '<w:tblPr>';
        $xml .= '<w:tblStyle w:val="TableGrid"/>';
        $xml .= '<w:tblW w:w="' . $totalWidth . '" w:type="dxa"/>';
        $xml .= '<w:tblLayout w:type="fixed"/>';
        $xml .= '<w:tblBorders>';
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $side) {
            $xml .= '<w:' . $side . ' w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        }
        $xml .= '</w:tblBorders>';
        $xml .= '<w:tblLook w:val="04A0" w:firstRow="1" w:lastRow="0" w:firstColumn="1" w:lastColumn="0" w:noHBand="0" w:noVBand="1"/>';
        $xml .= '</w:tblPr>';

        // Header row dengan background biru dan teks putih
        $xml .= '<w:tr>';
        foreach ($headers as $idx => $header) {
            $w = $widths[$idx] ?? 1800;
            $safe = htmlspecialchars($header, ENT_XML1, 'UTF-8');
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr>';
            $xml .= '<w:tcW w:w="' . $w . '" w:type="dxa"/>';
            $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="4F81BD"/>';
            $xml .= '<w:tcMar>';
            $xml .= '<w:top w:w="80" w:type="dxa"/>';
            $xml .= '<w:bottom w:w="80" w:type="dxa"/>';
            $xml .= '<w:left w:w="80" w:type="dxa"/>';
            $xml .= '<w:right w:w="80" w:type="dxa"/>';
            $xml .= '</w:tcMar>';
            $xml .= '</w:tcPr>';
            $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
            $xml .= '<w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="20"/></w:rPr>';
            $xml .= '<w:t xml:space="preserve">' . $safe . '</w:t></w:r>';
            $xml .= '</w:p></w:tc>';
        }
        $xml .= '</w:tr>';

        // Data rows dengan alternating background
        $rowIndex = 0;
        foreach ($dataRows as $row) {
            $isLastRow = ($row === end($dataRows));
            $fillColor = ($rowIndex % 2 == 0) ? 'FFFFFF' : 'D3DFEE';
            $xml .= '<w:tr>';
            foreach ($row as $idx => $cell) {
                $w = $widths[$idx] ?? 1800;
                $safe = htmlspecialchars((string) $cell, ENT_XML1, 'UTF-8');
                $bold = $isLastRow ? '<w:b/>' : '';
                $xml .= '<w:tc>';
                $xml .= '<w:tcPr>';
                $xml .= '<w:tcW w:w="' . $w . '" w:type="dxa"/>';
                $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="' . $fillColor . '"/>';
                $xml .= '<w:tcMar>';
                $xml .= '<w:top w:w="60" w:type="dxa"/>';
                $xml .= '<w:bottom w:w="60" w:type="dxa"/>';
                $xml .= '<w:left w:w="80" w:type="dxa"/>';
                $xml .= '<w:right w:w="80" w:type="dxa"/>';
                $xml .= '</w:tcMar>';
                $xml .= '</w:tcPr>';
                $xml .= '<w:p><w:pPr><w:jc w:val="left"/></w:pPr>';
                $xml .= '<w:r><w:rPr>' . $bold . '<w:sz w:val="18"/></w:rPr>';
                $xml .= '<w:t xml:space="preserve">' . $safe . '</w:t></w:r>';
                $xml .= '</w:p></w:tc>';
            }
            $xml .= '</w:tr>';
            $rowIndex++;
        }

        $xml .= '</w:tbl>';
        return $xml;
    }

    protected function replaceWithTable(\PhpOffice\PhpWord\TemplateProcessor $tp, string $placeholder, string $tableXml)
    {
        try {
            if (method_exists($tp, 'replaceXmlBlock')) {
                $tp->replaceXmlBlock($placeholder, $tableXml, 'w:p');
            } else {
                $tp->setValue($placeholder, '[Tabel tidak dapat dirender]');
            }
        } catch (\Exception $e) {
            Log::warning("replaceWithTable({$placeholder}) failed", [
                'error' => $e->getMessage()
            ]);
            $tp->setValue($placeholder, '[Tabel ' . $placeholder . ' tidak dapat dirender]');
        }
    }

    protected function buildFallbackContent($dataArtefak, $narasiAI)
    {
        $lines = [];
        $lines[] = "LAPORAN HASIL MONITORING ARTEFAK PERKULIAHAN RPS dan MATERI";
        $lines[] = "SEMESTER {$dataArtefak['semester']} TA {$dataArtefak['tahun_ajaran']}";
        $lines[] = "Program Studi: {$dataArtefak['prodi']}";
        $lines[] = "";
        $lines[] = "BAB 1 PENDAHULUAN";
        $lines[] = $narasiAI['latar_belakang'] ?? '';
        $lines[] = "";
        $lines[] = "1.2 Dasar Acuan";
        $lines[] = $narasiAI['dasar_acuan'] ?? '';
        $lines[] = "";
        $lines[] = "1.3 Tujuan";
        $lines[] = $narasiAI['tujuan'] ?? '';
        $lines[] = "";
        $lines[] = "1.4 Sasaran";
        $lines[] = $narasiAI['sasaran'] ?? '';
        $lines[] = "";
        $lines[] = "1.5 Waktu Pelaksanaan";
        $lines[] = $narasiAI['waktu_pelaksanaan'] ?? '';
        $lines[] = "";
        $lines[] = "1.6 Ruang Lingkup";
        $lines[] = $narasiAI['ruang'] ?? '';
        $lines[] = "";
        $lines[] = "1.7 Instrumen Pengukuran";
        $lines[] = $narasiAI['instrumen_pengukuran'] ?? '';
        $lines[] = "";
        $lines[] = "BAB 2 PROGRAM KERJA";
        $lines[] = $narasiAI['program_kerja'] ?? $this->getDefaultProgramKerja();
        $lines[] = "";
        $lines[] = "BAB 3 PELAKSANAAN";
        $lines[] = $narasiAI['pelaksanaan'] ?? $this->getDefaultPelaksanaan();
        $lines[] = "";
        $lines[] = "BAB 4 HAMBATAN";
        $lines[] = $narasiAI['hambatan_penjelasan'] ?? $this->getDefaultHambatanPenjelasan();
        $lines[] = "";
        $lines[] = "BAB 5 EVALUASI";
        $lines[] = "Total Mata Kuliah : {$dataArtefak['total_rps']}";
        $lines[] = "RPS Sudah Upload  : {$dataArtefak['rps_uploaded']} ({$dataArtefak['rps_percentage']}%)";
        $lines[] = "Materi Sudah Upload: {$dataArtefak['materi_uploaded']} ({$dataArtefak['materi_percentage']}%)";
        $lines[] = "";
        $lines[] = "Hasil Pemeriksaan";
        $lines[] = $narasiAI['hasil_pemeriksaan'] ?? '';
        $lines[] = "";
        $lines[] = "Analisis Ketercapaian";
        $lines[] = $narasiAI['analisis_ketercapaian'] ?? '';
        $lines[] = "";
        $lines[] = "Tindak Lanjut";
        $lines[] = $narasiAI['tindak_lanjut'] ?? '';
        $lines[] = "";
        $lines[] = "BAB 6 PENUTUP";
        $lines[] = $narasiAI['kesimpulan_penutup'] ?? '';

        return implode("\n", $lines);
    }

    protected function createWordDocument($content, $laporan)
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText(
            'LAPORAN MONITORING ARTEFAK PERKULIAHAN RPS & MATERI',
            ['bold' => true, 'size' => 16],
            ['alignment' => 'center']
        );
        $section->addText(
            "Semester {$laporan->bulan} {$laporan->tahun} — " . ($laporan->prodi->nama_prodi ?? ''),
            ['size' => 12],
            ['alignment' => 'center']
        );
        $section->addTextBreak(2);

        foreach (explode("\n", $content) as $line) {
            $line = trim($line);
            if (empty($line)) {
                $section->addTextBreak(1);
            } elseif (preg_match('/^#{1,6}\s+(.+)$/', $line, $m)) {
                $lvl = substr_count($line, '#');
                $size = max(10, 16 - $lvl * 2);
                $section->addText($m[1], ['bold' => true, 'size' => $size]);
            } elseif (preg_match('/^[-*]\s+(.+)$/', $line, $m)) {
                $section->addListItem($m[1], 0, null, null, ['size' => 11]);
            } else {
                $section->addText($line, ['size' => 11]);
            }
        }

        $fileName = 'laporan_artefak_' . $laporan->periode . '_' . time() . '.docx';
        $filePath = 'laporan_artefak/' . $fileName;
        $fullPath = storage_path('app/' . $filePath);

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($fullPath);
        Log::info('Word document (fallback) created', ['file_path' => $filePath]);

        return $filePath;
    }

    protected function callAI($systemMessage, $userPrompt, $maxTokens = 2000)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    $systemMessage,
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.7,
            ]);

            if (!$response->successful()) {
                Log::error('AI call failed', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? null;

        } catch (\Exception $e) {
            Log::error('AI call exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function processTemplateToVectorDB($templateId)
    {
        Log::info('Processing template to vector DB', ['template_id' => $templateId]);

        $template = TemplateLaporan::find($templateId);
        if (!$template || !$template->file_path) {
            throw new \Exception('Template not found or no file attached');
        }

        $extraction = $this->textExtractionService->extractFromFile($template->file_path);
        if (!$extraction['success']) {
            throw new \Exception('Failed to extract text: ' . ($extraction['metadata']['error'] ?? 'Unknown'));
        }

        $text = $this->textExtractionService->cleanText($extraction['text']);
        $structure = $this->structureService->extractStructure($text);

        $chunks = $this->advancedChunkingService->chunkWithMetadata($text, [
            'template_id' => $templateId,
            'template_name' => $template->nama_template,
            'prodi_id' => $template->prodi_id,
            'document_type' => 'template',
            'structure' => json_encode($structure),
        ], 'template');

        DocumentChunk::where('template_id', $templateId)->delete();

        $embeddingService = app(EmbeddingService::class);
        $indexed = 0;

        foreach ($chunks as $chunk) {
            try {
                $embedding = $embeddingService->generateEmbedding($chunk['text']);
                if (!$embedding)
                    continue;

                DocumentChunk::create([
                    'template_id' => $templateId,
                    'chunk_text' => $chunk['text'],
                    'chunk_index' => $chunk['index'],
                    'embedding' => $embedding,
                    'metadata' => array_merge($chunk['metadata'] ?? [], [
                        'section' => $chunk['section'] ?? null,
                        'chunk_type' => $chunk['type'] ?? 'text',
                    ]),
                ]);
                $indexed++;
            } catch (\Exception $e) {
                Log::error('Failed to index chunk', ['chunk' => $chunk['index'], 'error' => $e->getMessage()]);
            }
        }

        $template->update([
            'is_indexed' => true,
            'indexed_at' => now(),
            'total_chunks' => $indexed,
            'structure_metadata' => json_encode($structure),
        ]);

        Log::info('Template indexed', ['template_id' => $templateId, 'chunks' => $indexed]);

        return [
            'success' => true,
            'template_id' => $templateId,
            'chunks_indexed' => $indexed,
        ];
    }

    public function getPlaceholderHints($laporanId = null)
    {
        $hints = [
            '{{LATAR_BELAKANG}}' => 'BAB 1.1 Latar Belakang - Narasi konteks dan alasan monitoring',
            '{{DASAR_ACUAN}}' => 'BAB 1.2 Dasar Acuan - Regulasi dan kebijakan yang mendasari',
            '{{TUJUAN}}' => 'BAB 1.3 Tujuan - Tujuan dilaksanakannya monitoring',
            '{{SASARAN}}' => 'BAB 1.4 Sasaran - Target sasaran monitoring',
            '{{WAKTU_PELAKSANAAN}}' => 'BAB 1.5 Waktu Pelaksanaan - Jadwal pelaksanaan monitoring',
            '{{RUANG}}' => 'BAB 1.6 Ruang Lingkup - Batasan dan cakupan monitoring',
            '{{INSTRUMEN_PENGUKURAN}}' => 'BAB 1.7 Instrumen Pengukuran - Alat ukur yang digunakan',
            '{{PROGRAM_KERJA}}' => 'BAB 2 Program Kerja - Daftar program yang dilaksanakan',
            '{{PELAKSANAAN}}' => 'BAB 3 Pelaksanaan - Narasi pelaksanaan monitoring',
            '{{HAMBATAN_PENJELASAN}}' => 'BAB 4 Hambatan - Kendala yang dihadapi (tanpa format bold)',
            '{{HASIL_PEMERIKSAAN}}' => 'BAB 5 Hasil Pemeriksaan - Temuan hasil monitoring',
            '{{ANALISIS_KETERCAPAIAN}}' => 'BAB 5 Analisis Ketercapaian - Analisis capaian target',
            '{{TINDAK_LANJUT}}' => 'BAB 5 Tindak Lanjut - Rekomendasi tindak lanjut',
            '{{KESIMPULAN_PENUTUP}}' => 'BAB 6 Kesimpulan - Kesimpulan penutup laporan',
            '{{TABEL_RPS}}' => 'Tabel RPS per mata kuliah',
            '{{TABEL_MATERI}}' => 'Tabel Kelengkapan Materi per Minggu (1-16)',
            '{{TABEL_HAMBATAN}}' => 'Tabel Hambatan dan Saran Perbaikan (format tabel rapi)',
            '{{SEMESTER}}' => 'Semester saat ini (Ganjil/Genap)',
            '{{TAHUN_AJARAN}}' => 'Tahun Akademik (contoh: 2024/2025)',
            '{{NAMA_PRODI}}' => 'Nama Program Studi',
            '{{TANGGAL_LAPORAN}}' => 'Tanggal pembuatan laporan',
            '{{NAMA_KETUA_GKM}}' => 'Nama Ketua GKM',
            '{{TOTAL_MK}}' => 'Total jumlah mata kuliah',
            '{{TOTAL_RPS_UPLOAD}}' => 'Jumlah RPS yang sudah diupload',
            '{{STATISTIK_RPS_PERSEN}}' => 'Persentase upload RPS',
            '{{STATISTIK_MATERI_PERSEN}}' => 'Persentase kelengkapan materi',
        ];

        if ($laporanId) {
            try {
                $laporan = LaporanGKM::find($laporanId);
                if ($laporan && !empty($laporan->ai_preview_draft)) {
                    $excerpt = trim(substr(strip_tags($laporan->ai_preview_draft), 0, 300));
                    $hints['__AI_PREVIEW_EXCERPT__'] = $excerpt;
                }
            } catch (\Exception $e) {
                Log::warning('getPlaceholderHints failed to load laporan', ['laporan_id' => $laporanId, 'error' => $e->getMessage()]);
            }
        }

        return $hints;
    }
}
