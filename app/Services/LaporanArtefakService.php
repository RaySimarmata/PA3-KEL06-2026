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
        // Use centralized LLM configuration from config/services.php
        $this->apiKey = config('services.llm.api_key');
        $this->baseUrl = config('services.llm.base_url');
        $this->model = config('services.llm.model');

        $this->textExtractionService = $textExtractionService;
        $this->structureService = $structureService;
        $this->advancedChunkingService = $advancedChunkingService;
    }

    // =========================================================================
    // MAIN ENTRY POINT
    // =========================================================================

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

            // 1. Kumpulkan data artefak dari DB
            $dataArtefak = $this->collectArtefakData($laporan);

            // 2. Generate narasi AI
            $narasiAI = $this->generateNarasiWithAI($dataArtefak);

            // 3. Tentukan metode: template-based atau full-AI
            if ($laporan->template_id) {
                $wordPath = $this->generateFromTemplate($laporan, $dataArtefak, $narasiAI);
                $content = $narasiAI['hasil_pemeriksaan'] . "\n\n" . $narasiAI['analisis_ketercapaian'];
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

    // =========================================================================
    // STEP 1 — COLLECT DATA
    // =========================================================================

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

        // Ambil SEMUA data dari snapshot — tanpa filter prodi/semester/tahun
        $rpsData = RpsMonitoringSnapshot::with('dosen')
            ->orderByRaw("SUBSTRING(kode_mk, 4, 1)") // urutkan per tingkat
            ->orderBy('kode_mk')
            ->get();

        $materiData = PerkuliahanMonitoringSnapshot::with('dosen')
            ->orderByRaw("SUBSTRING(kode_mk, 4, 1)") // urutkan per tingkat
            ->orderBy('kode_mk')
            ->orderBy('jenis_materi') // Teori dulu, Praktikum kemudian
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


    // =========================================================================
    // STEP 2 — GENERATE NARASI AI
    // =========================================================================

    protected function generateNarasiWithAI($dataArtefak)
    {
        // Buat daftar MK yang belum upload untuk konteks AI
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
        $prompt .= '  "tabel_hambatan": [' . "\n";
        $prompt .= '    {' . "\n";
        $prompt .= '      "program_kerja": "Nama program kerja (misal: Pemeriksaan RPS)",' . "\n";
        $prompt .= '      "catatan": "Catatan singkat pelaksanaan",' . "\n";
        $prompt .= '      "hambatan": "Hambatan yang ditemukan",' . "\n";
        $prompt .= '      "saran": "Saran atau pemecahan masalah"' . "\n";
        $prompt .= '    }' . "\n";
        $prompt .= '  ],' . "\n";
        $prompt .= '  "kesimpulan_penutup": "narasi 1 paragraf penutup"' . "\n";
        $prompt .= "}\n";
        $prompt .= "PENTING:\n";
        $prompt .= "- tabel_hambatan HARUS berupa array dengan minimal 2 baris (satu per program kerja)\n";
        $prompt .= "- Gunakan data MK yang belum upload sebagai dasar hambatan\n";
        $prompt .= "- Hanya JSON valid, tanpa teks tambahan\n";

        $raw = $this->callAI([
            'role' => 'system',
            'content' => 'Anda adalah AI Agent GKM ahli laporan akademik. Balas HANYA JSON valid.',
        ], $prompt, 2500);

        $defaults = [
            'hasil_pemeriksaan' => 'Pemeriksaan artefak perkuliahan telah dilaksanakan.',
            'analisis_ketercapaian' => 'Sebagian besar dosen telah memenuhi kewajiban upload.',
            'tindak_lanjut' => 'GKM merekomendasikan koordinasi lebih awal.',
            'tabel_hambatan' => [
                [
                    'program_kerja' => 'Pemeriksaan RPS dan artefak perkuliahan minggu ke 1',
                    'catatan' => 'Dilaksanakan sesuai jadwal',
                    'hambatan' => 'Beberapa dosen belum mengunggah RPS tepat waktu',
                    'saran' => 'Mengirimkan reminder lebih awal sebelum batas unggah',
                ],
                [
                    'program_kerja' => 'Konfirmasi kelengkapan artefak kepada dosen pengampu',
                    'catatan' => 'Dilaksanakan melalui email dan grup WhatsApp',
                    'hambatan' => 'Tidak semua dosen merespons konfirmasi dengan cepat',
                    'saran' => 'Koordinasi melalui Ketua Program Studi untuk peneguran langsung',
                ],
            ],
            'kesimpulan_penutup' => 'Monitoring berjalan lancar dan menjadi bahan evaluasi.',
        ];

        if (!$raw) {
            return $defaults;
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
        if (!$result) {
            return $defaults;
        }

        // Pastikan tabel_hambatan adalah array (fallback jika AI kirim string)
        if (isset($result['tabel_hambatan']) && !is_array($result['tabel_hambatan'])) {
            $result['tabel_hambatan'] = $defaults['tabel_hambatan'];
        }

        return array_merge($defaults, $result);
    }

    // =========================================================================
    // STEP 3 — PLACEHOLDER MAP
    // =========================================================================

    /**
     * Buat mapping placeholder → nilai aktual.
     * Format placeholder di template Word: {{NAMA_PLACEHOLDER}}
     */
    protected function buildPlaceholderMap($dataArtefak, $laporan, $narasiAI)
    {
        $now = Carbon::now()->locale('id');
        $namaKetua = $laporan->user->name ?? 'Ketua GKM D4 TRPL';
        $tanggalLaporan = $now->translatedFormat('d F Y');

        // Tentukan rentang bulan program kerja dari periode
        $periodeObj = Carbon::createFromFormat('Y-m', $laporan->periode);
        $rentangBulan = $periodeObj->locale('id')->translatedFormat('F') . ' s/d ' .
            $periodeObj->addMonths(1)->locale('id')->translatedFormat('F Y');

        $tabelRPS = $this->buildTabelRPS($dataArtefak);
        $tabelMateri = $this->buildTabelMateri($dataArtefak);

        return [
            '{{SEMESTER}}' => $dataArtefak['semester'],
            '{{TAHUN_AJARAN}}' => $dataArtefak['tahun_ajaran'],
            '{{NAMA_PRODI}}' => $dataArtefak['prodi'],
            '{{TANGGAL_LAPORAN}}' => 'Sitoluama, ' . $tanggalLaporan,
            '{{NAMA_KETUA_GKM}}' => $namaKetua,
            '{{RENTANG_BULAN_PROGRAM_KERJA}}' => $rentangBulan,
            '{{TANGGAL_REMINDER}}' => $tanggalLaporan,
            '{{TANGGAL_PEMERIKSAAN}}' => $tanggalLaporan,
            '{{TOTAL_MK}}' => (string) $dataArtefak['total_rps'],
            '{{TOTAL_RPS_UPLOAD}}' => (string) $dataArtefak['rps_uploaded'],
            '{{TOTAL_RPS_KURIKULUM_UPLOAD}}' => (string) $dataArtefak['rps_uploaded'],
            '{{STATISTIK_RPS_PERSEN}}' => $dataArtefak['rps_percentage'] . '%',
            '{{STATISTIK_MATERI_PERSEN}}' => $dataArtefak['materi_percentage'] . '%',
            '{{HASIL_PEMERIKSAAN}}' => $narasiAI['hasil_pemeriksaan'],
            '{{ANALISIS_KETERCAPAIAN}}' => $narasiAI['analisis_ketercapaian'],
            '{{TINDAK_LANJUT}}' => $narasiAI['tindak_lanjut'],
            '{{KESIMPULAN_PENUTUP}}' => $narasiAI['kesimpulan_penutup'],
        ];
    }

    // =========================================================================
    // STEP 4 — BUILD TABLES
    // =========================================================================

    protected function buildTabelRPS($dataArtefak)
    {
        $rows = "Semester\tKode MK\tNama MK\tInisial Dosen\tRPS (0=Tidak, 1=Ya)\n";
        $rows .= str_repeat("-", 80) . "\n";

        foreach ($dataArtefak['rps_details'] as $item) {
            $rps = ($item->status_rps === 'SUDAH UPLOAD') ? '1' : '0';
            $inisial = $item->dosen->inisial_nama ?? (string) $item->pegawai_id;
            // Tingkat diambil dari raw_data jika ada
            $tingkat = $item->raw_data['tingkat'] ?? $item->raw_data['semester'] ?? '-';

            $rows .= implode("\t", [
                $tingkat,
                $item->kode_mk ?? '-',
                $item->nama_matkul ?? '-',
                $inisial,
                $rps,
            ]) . "\n";
        }

        $rows .= str_repeat("-", 80) . "\n";
        $rows .= "Jumlah\t{$dataArtefak['total_rps']}\t\t\t{$dataArtefak['rps_uploaded']}\n";

        return $rows;
    }

    protected function buildTabelMateri($dataArtefak)
    {
        $rows = "Semester\tKode MK\tNama MK\tInisial Dosen\tTeori (Slide/Video/Catatan)\n";
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

    /**
     * Format array baris hambatan dari AI menjadi teks plain untuk placeholder {{TABEL_HAMBATAN}}.
     * Setiap baris berisi: No | Program Kerja | Catatan | Hambatan | Saran
     */
    protected function buildTabelHambatan($rows)
    {
        if (!is_array($rows) || empty($rows)) {
            return '-';
        }

        $lines = [];
        $lines[] = "No.\tProgram Kerja\tCatatan\tHambatan\tSaran atau Pemecahan Masalah";
        $lines[] = str_repeat("-", 100);

        foreach ($rows as $i => $row) {
            $no = ($i + 1) . '.';
            $programKerja = $row['program_kerja'] ?? '-';
            $catatan = $row['catatan'] ?? '-';
            $hambatan = $row['hambatan'] ?? '-';
            $saran = $row['saran'] ?? '-';

            $lines[] = implode("\t", [$no, $programKerja, $catatan, $hambatan, $saran]);
        }

        return implode("\n", $lines);
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

        // Preprocess: konversi {{PLACEHOLDER}} → ${PLACEHOLDER} di raw XML
        // agar TemplateProcessor::fixBrokenMacros() berjalan benar (default delimiters)
        $preprocessedPath = $this->preprocessTemplate($templateFilePath);

        try {
            $tp = new \PhpOffice\PhpWord\TemplateProcessor($preprocessedPath);
            // Gunakan default delimiters ${ } — sudah diconvert saat preprocess

            // 1. Replace placeholder teks biasa
            $map = $this->buildPlaceholderMap($dataArtefak, $laporan, $narasiAI);
            foreach ($map as $placeholder => $value) {
                $key = trim($placeholder, '{}');
                $tp->setValue($key, htmlspecialchars((string) $value, ENT_XML1, 'UTF-8'));
            }

            // 2. Inject tabel RPS (OOXML) — cukup {{TABEL_RPS}} di template
            $this->injectRPSTable($tp, $dataArtefak);

            // 3. Inject tabel Materi (OOXML) — cukup {{TABEL_MATERI}} di template
            $this->injectMateriTable($tp, $dataArtefak);

            // 4. Inject tabel Hambatan (OOXML, diisi AI) — cukup {{TABEL_HAMBATAN}} di template
            $this->injectHambatanTable($tp, $narasiAI['tabel_hambatan']);

            // Simpan
            $fileName = 'laporan_artefak_' . $laporan->periode . '_' . time() . '.docx';
            $filePath = 'laporan_artefak/' . $fileName;
            $fullPath = storage_path('app/' . $filePath);

            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            $tp->saveAs($fullPath);

            Log::info('Template-based Word document created', ['file_path' => $filePath]);
            return $filePath;

        } finally {
            // Hapus file temp hasil preprocessing
            if (isset($preprocessedPath) && file_exists($preprocessedPath)) {
                @unlink($preprocessedPath);
            }
        }
    }

    /**
     * Preprocess template docx: konversi {{PLACEHOLDER}} → ${PLACEHOLDER}
     * di raw XML, termasuk menangani placeholder yang terpecah antar XML run
     * (misalnya {{NAMA_ → <xml> → PRODI}} akibat spell-check Word).
     */
    protected function preprocessTemplate(string $sourcePath): string
    {
        $tmpPath = storage_path('app/tmp_tpl_' . uniqid() . '.docx');
        copy($sourcePath, $tmpPath);

        $zip = new \ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            throw new \RuntimeException('Cannot open template docx for preprocessing');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('/\.xml$/i', $name)) continue;

            $content = $zip->getFromName($name);
            if ($content === false) continue;

            $converted = $this->convertDoubleBracePlaceholders($content);

            if ($converted !== $content) {
                $zip->addFromString($name, $converted);
                Log::debug("preprocessTemplate: converted in {$name}");
            }
        }

        $zip->close();
        return $tmpPath;
    }

    /**
     * Konversi {{PLACEHOLDER}} → ${PLACEHOLDER} di raw OOXML.
     * Menangani dua kasus:
     *   1. Placeholder utuh dalam satu run: {{NAMA_PRODI}}
     *   2. Placeholder terpecah antar run oleh XML tags Word:
     *      {{NAMA_ + <w:proofErr.../> + PRODI}}
     */
    protected function convertDoubleBracePlaceholders(string $xml): string
    {
        // Pass 1: match {{...}} yang mungkin mengandung XML tags di tengah.
        // Regex ini mengizinkan XML element di antara {{ dan }} lalu strip tags-nya.
        $xml = preg_replace_callback(
            '/\{\{((?:[^{}]|<[^>]*>)*?)\}\}/s',
            function ($m) {
                // Hapus semua XML tags dari konten placeholder
                $key = preg_replace('/<[^>]+>/', '', $m[1]);
                $key = preg_replace('/\s+/', '', $key); // buang whitespace
                if (preg_match('/^[A-Z0-9_]+$/', $key)) {
                    return '${' . $key . '}';
                }
                return $m[0]; // bukan placeholder valid, biarkan
            },
            $xml
        );

        // Pass 2: fix ${PLACEHOLDER} yang masih terpecah antar run
        // (sama seperti PhpWord fixBrokenMacros tapi untuk format ${})
        $xml = preg_replace_callback(
            '/\$[^{<]*(?:<[^>]*>[^{<]*)*\{[^}<]*(?:<[^>]*>[^}<]*)*\}/',
            function ($m) {
                $stripped = strip_tags($m[0]);
                if (preg_match('/^\$\{([A-Z0-9_]+)\}$/', $stripped)) {
                    return $stripped;
                }
                return $m[0];
            },
            $xml
        );

        return $xml;
    }


    // =========================================================================
    // TABLE OOXML INJECTION
    // Cukup tulis {{TABEL_RPS}}, {{TABEL_MATERI}}, {{TABEL_HAMBATAN}}
    // satu kali di paragraf template Word.
    // Sistem akan replace paragraf itu dengan tabel Word sungguhan.
    // =========================================================================


    /**
     * Inject tabel RPS ke posisi {{TABEL_RPS}} di template.
     * Data diambil dari rps_monitoring_snapshots.
     */
    protected function injectRPSTable(\PhpOffice\PhpWord\TemplateProcessor $tp, $dataArtefak)
    {
        $headers = ['Semester', 'Kode Mata Kuliah', 'Nama Mata Kuliah', 'Inisial Dosen Pengampu', 'RPS (0=Tidak, 1=Yes)'];
        $widths = [1200, 1800, 3000, 2200, 1400];

        $dataRows = [];
        $lastTingkat = '';
        foreach ($dataArtefak['rps_details'] as $item) {
            $rps = ($item->status_rps === 'SUDAH UPLOAD') ? '1' : '0';

            // Tingkat dari karakter ke-4 kode_mk (sama seperti Monitoring RPS PDF)
            $kodeMk  = $item->kode_mk ?? '';
            $tingkat = strlen($kodeMk) >= 4 ? substr($kodeMk, 3, 1) : '-';

            // Dosen: ambil dari raw_data['dosen_pengampu'] (nama lengkap dari API)
            // fallback ke inisial dari relasi dosen
            $rawData     = is_array($item->raw_data) ? $item->raw_data : [];
            $dosenNama   = $rawData['dosen_pengampu']
                        ?? $item->dosen->nama
                        ?? $item->dosen->inisial_nama
                        ?? (string) $item->pegawai_id;

            $dataRows[] = [
                $tingkat !== $lastTingkat ? 'Tingkat ' . $tingkat : '',
                $item->kode_mk     ?? '-',
                $item->nama_matkul ?? '-',
                $dosenNama,
                $rps,
            ];
            $lastTingkat = $tingkat;
        }

        // Baris jumlah
        $dataRows[] = [
            'Jumlah',
            (string) $dataArtefak['total_rps'],
            '',
            '',
            (string) $dataArtefak['rps_uploaded'],
        ];

        $xml = $this->buildWordTableXML($headers, $dataRows, $widths);
        $this->replaceWithTable($tp, 'TABEL_RPS', $xml);

        Log::info('RPS table injected', ['rows' => count($dataRows)]);
    }

    /**
     * Inject tabel Materi ke posisi {{TABEL_MATERI}} di template.
     * Format: header 2 baris merged — Semester | Kode MK | Nama MK | Dosen | Week 1..16 (Teori + Praktikum)
     * Week 1 diisi dari DB (status_upload), Week 2-16 dikosongkan.
     */
    protected function injectMateriTable(\PhpOffice\PhpWord\TemplateProcessor $tp, $dataArtefak)
    {
        $xml = $this->buildMateriTableXML($dataArtefak);
        $this->replaceWithTable($tp, 'TABEL_MATERI', $xml);
        Log::info('Materi table (week 1-16) injected');
    }

    /**
     * Build OOXML tabel Materi dengan merged header Week 1–16.
     *
     * Struktur:
     *   Row 1: Semester | Kode MK | Nama MK | Dosen | [Week 1 colspan=2] | [Week 2 colspan=2] | ... | [Week 16 colspan=2]
     *   Row 2: (vmerge) | (vmerge) | (vmerge) | (vmerge) | Teori | Praktikum | Teori | Praktikum | ... (×16)
     *   Data : nilai dari DB / kosong
     */
    protected function buildMateriTableXML($dataArtefak): string
    {
        // Ukuran kolom dalam twip
        $wSemester = 800;
        $wKodeMK = 1200;
        $wNamaMK = 2000;
        $wDosen = 1500;
        $wTeori = 600;
        $wPraktikum = 600;
        $totalWeeks = 16;

        // Warna header
        $fillHeader = 'D9E1F2'; // biru muda
        $fillSubHdr = 'EBF0FA'; // sedikit lebih muda
        $fillJumlah = 'F2F2F2'; // abu-abu untuk baris jumlah

        // =====================================================================
        // Helper inline untuk membangun <w:tc>
        // =====================================================================
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
            $tcPr .= '<w:tcMar><w:top w:w="60" w:type="dxa"/><w:bottom w:w="60" w:type="dxa"/></w:tcMar>';
            $tcPr .= '</w:tcPr>';

            $boldTag = $bold ? '<w:b/>' : '';
            $para = '<w:p><w:pPr><w:jc w:val="' . $align . '"/></w:pPr>'
                . '<w:r><w:rPr>' . $boldTag . '<w:sz w:val="18"/></w:rPr>'
                . '<w:t xml:space="preserve">' . $safe . '</w:t></w:r></w:p>';

            return '<w:tc>' . $tcPr . $para . '</w:tc>';
        };

        // =====================================================================
        // tblPr — lebar total = 4 kolom tetap + 16×2 kolom week
        // =====================================================================
        $totalWidth = $wSemester + $wKodeMK + $wNamaMK + $wDosen
            + ($wTeori + $wPraktikum) * $totalWeeks;

        $xml = '<w:tbl>';
        $xml .= '<w:tblPr>';
        $xml .= '<w:tblStyle w:val="TableGrid"/>';
        $xml .= '<w:tblW w:w="' . $totalWidth . '" w:type="dxa"/>';
        $xml .= '<w:tblBorders>';
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $s) {
            $xml .= '<w:' . $s . ' w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        }
        $xml .= '</w:tblBorders>';
        $xml .= '<w:tblLook w:val="04A0" w:firstRow="1" w:lastRow="0" w:firstColumn="1" w:lastColumn="0" w:noHBand="0" w:noVBand="1"/>';
        $xml .= '</w:tblPr>';

        // =====================================================================
        // HEADER ROW 1 — 4 kolom tetap (vMerge restart) + Week 1..16 (colspan 2)
        // =====================================================================
        $xml .= '<w:tr>';
        $xml .= $makeCell('Semester', $wSemester, true, $fillHeader, 1, true, false);
        $xml .= $makeCell('Kode Mata Kuliah', $wKodeMK, true, $fillHeader, 1, true, false);
        $xml .= $makeCell('Nama Mata Kuliah', $wNamaMK, true, $fillHeader, 1, true, false);
        $xml .= $makeCell('Dosen Pengampu', $wDosen, true, $fillHeader, 1, true, false);
        for ($w = 1; $w <= $totalWeeks; $w++) {
            $xml .= $makeCell('Week ' . $w, $wTeori + $wPraktikum, true, $fillHeader, 2, false, false);
        }
        $xml .= '</w:tr>';

        // =====================================================================
        // HEADER ROW 2 — 4 kolom kosong (vMerge cont) + Teori|Praktikum ×16
        // =====================================================================
        $xml .= '<w:tr>';
        $xml .= $makeCell('', $wSemester, false, $fillHeader, 1, false, true);
        $xml .= $makeCell('', $wKodeMK,   false, $fillHeader, 1, false, true);
        $xml .= $makeCell('', $wNamaMK,   false, $fillHeader, 1, false, true);
        $xml .= $makeCell('', $wDosen,    false, $fillHeader, 1, false, true);
        for ($w = 1; $w <= $totalWeeks; $w++) {
            // Sub-header dikosongkan agar kolom muat — isi tetap 0/1 di data row
            $xml .= $makeCell('T', $wTeori,    true, $fillSubHdr);
            $xml .= $makeCell('P', $wPraktikum,true, $fillSubHdr);
        }
        $xml .= '</w:tr>';


        // =====================================================================
        // DATA ROWS — kelompokkan per MK (kode_mk unik), Week 1 diisi dari DB
        // =====================================================================

        // Kelompokkan per kode_mk — isi weeks[0..15] dari raw_data
        $mkMap = [];
        foreach ($dataArtefak['materi_details'] as $item) {
            $kodeMk  = $item->kode_mk ?? '-';
            $tingkat = $item->tingkat
                    ?? (strlen($kodeMk) >= 4 ? substr($kodeMk, 3, 1) : '-');

            $key = $kodeMk . '|' . $tingkat;

            if (!isset($mkMap[$key])) {
                $rawAny    = is_array($item->raw_data) ? $item->raw_data : [];
                $dosenNama = $rawAny['dosen']
                          ?? $rawAny['dosen_pengampu']
                          ?? $item->dosen->nama
                          ?? $item->dosen->inisial_nama
                          ?? (string) $item->pegawai_id;

                $mkMap[$key] = [
                    'tingkat'     => $tingkat,
                    'kode_mk'     => $kodeMk,
                    'nama_mk'     => $item->nama_matkul ?? '-',
                    'dosen'       => $dosenNama,
                    'teori_weeks' => array_fill(0, 16, ''),  // W1-W16 teori
                    'prak_weeks'  => array_fill(0, 16, ''),  // W1-W16 praktikum
                ];
            }

            // Ambil array weeks[0..15] dari raw_data
            $rawData = is_array($item->raw_data) ? $item->raw_data : [];
            $weeks   = $rawData['weeks'] ?? [];

            if ($item->jenis_materi === 'Materi Teori') {
                foreach ($weeks as $idx => $val) {
                    if ($idx >= 0 && $idx < 16) {
                        $mkMap[$key]['teori_weeks'][$idx] = (string) $val;
                    }
                }
            } elseif ($item->jenis_materi === 'Materi Praktikum') {
                foreach ($weeks as $idx => $val) {
                    if ($idx >= 0 && $idx < 16) {
                        $mkMap[$key]['prak_weeks'][$idx] = is_null($val) ? '' : (string) $val;
                    }
                }
            }
        }



        // Urutkan per tingkat lalu kode_mk
        uasort($mkMap, function ($a, $b) {
            $t = strcmp($a['tingkat'], $b['tingkat']);
            return $t !== 0 ? $t : strcmp($a['kode_mk'], $b['kode_mk']);
        });

        $lastTingkat = '';
        foreach ($mkMap as $row) {
            $tingkat = $row['tingkat'];
            $xml .= '<w:tr>';
            $xml .= $makeCell($tingkat !== $lastTingkat ? 'Tingkat ' . $tingkat : '', $wSemester, false, 'auto', 1, false, false, 'center');
            $xml .= $makeCell($row['kode_mk'], $wKodeMK, false, 'auto', 1, false, false, 'center');
            $xml .= $makeCell($row['nama_mk'], $wNamaMK, false, 'auto', 1, false, false, 'left');
            $xml .= $makeCell($row['dosen'],   $wDosen,  false, 'auto', 1, false, false, 'center');
            // Week 1-16: teori & praktikum dari raw_data['weeks']
            for ($w = 0; $w < $totalWeeks; $w++) {
                $t = $row['teori_weeks'][$w] ?? '';
                $p = $row['prak_weeks'][$w]  ?? '';
                $xml .= $makeCell($t, $wTeori,    false, 'auto', 1, false, false, 'center');
                $xml .= $makeCell($p, $wPraktikum,false, 'auto', 1, false, false, 'center');
            }
            $xml .= '</w:tr>';
            $lastTingkat = $tingkat;
        }


        $xml .= '</w:tbl>';
        return $xml;
    }


    /**
     * Inject tabel Hambatan ke posisi {{TABEL_HAMBATAN}} di template.
     * Data dari AI (array of rows).
     */
    protected function injectHambatanTable(\PhpOffice\PhpWord\TemplateProcessor $tp, $rows)
    {
        $headers = ['No.', 'Program Kerja', 'Catatan', 'Hambatan', 'Saran atau Pemecahan Masalah'];
        $widths = [600, 2400, 1800, 2400, 2400];

        $dataRows = [];
        if (is_array($rows)) {
            foreach ($rows as $i => $row) {
                $dataRows[] = [
                    (string) ($i + 1) . '.',
                    $row['program_kerja'] ?? '-',
                    $row['catatan'] ?? '-',
                    $row['hambatan'] ?? '-',
                    $row['saran'] ?? '-',
                ];
            }
        }

        if (empty($dataRows)) {
            $dataRows[] = ['1.', '-', '-', '-', '-'];
            $dataRows[] = ['2.', '-', '-', '-', '-'];
        }

        $xml = $this->buildWordTableXML($headers, $dataRows, $widths);
        $this->replaceWithTable($tp, 'TABEL_HAMBATAN', $xml);

        Log::info('Hambatan table injected', ['rows' => count($dataRows)]);
    }

    /**
     * Build OOXML tabel Word dari header + baris data.
     *
     * @param array $headers  Label kolom header
     * @param array $dataRows Array of arrays (setiap sub-array = 1 baris)
     * @param array $widths   Lebar tiap kolom dalam twip (1 cm ≈ 567 twip)
     */
    protected function buildWordTableXML(array $headers, array $dataRows, array $widths): string
    {
        $totalWidth = array_sum($widths);

        $xml = '<w:tbl>';
        $xml .= '<w:tblPr>';
        $xml .= '<w:tblW w:w="' . $totalWidth . '" w:type="dxa"/>';
        $xml .= '<w:tblBorders>';
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $side) {
            $xml .= '<w:' . $side . ' w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        }
        $xml .= '</w:tblBorders>';
        $xml .= '<w:tblLook w:val="04A0" w:firstRow="1" w:lastRow="0" w:firstColumn="1" w:lastColumn="0" w:noHBand="0" w:noVBand="1"/>';
        $xml .= '</w:tblPr>';

        // Header row
        $xml .= '<w:tr>';
        foreach ($headers as $idx => $header) {
            $w = $widths[$idx] ?? 1800;
            $safe = htmlspecialchars($header, ENT_XML1, 'UTF-8');
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $w . '" w:type="dxa"/>';
            $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="D9E1F2"/></w:tcPr>';
            $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
            $xml .= '<w:r><w:rPr><w:b/><w:sz w:val="20"/></w:rPr><w:t xml:space="preserve">' . $safe . '</w:t></w:r>';
            $xml .= '</w:p></w:tc>';
        }
        $xml .= '</w:tr>';

        // Data rows
        foreach ($dataRows as $row) {
            $isLastRow = ($row === end($dataRows));
            $xml .= '<w:tr>';
            foreach ($row as $idx => $cell) {
                $w = $widths[$idx] ?? 1800;
                $safe = htmlspecialchars((string) $cell, ENT_XML1, 'UTF-8');
                $bold = $isLastRow ? '<w:b/>' : '';
                $fill = $isLastRow ? 'F2F2F2' : 'auto';
                $xml .= '<w:tc>';
                $xml .= '<w:tcPr><w:tcW w:w="' . $w . '" w:type="dxa"/>';
                $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="' . $fill . '"/></w:tcPr>';
                $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
                $xml .= '<w:r><w:rPr>' . $bold . '<w:sz w:val="20"/></w:rPr><w:t xml:space="preserve">' . $safe . '</w:t></w:r>';
                $xml .= '</w:p></w:tc>';
            }
            $xml .= '</w:tr>';
        }

        $xml .= '</w:tbl>';
        return $xml;
    }

    /**
     * Replace placeholder paragraph dengan tabel OOXML di TemplateProcessor.
     * Mencoba replaceXmlBlock (PhpWord >= 0.18), fallback ke setValue jika gagal.
     */
    protected function replaceWithTable(\PhpOffice\PhpWord\TemplateProcessor $tp, string $placeholder, string $tableXml)
    {
        try {
            // replaceXmlBlock mengganti seluruh <w:p>...</w:p> yang mengandung placeholder
            $tp->replaceXmlBlock($placeholder, $tableXml, 'w:p');
        } catch (\Exception $e) {
            Log::warning("replaceXmlBlock({$placeholder}) failed, fallback to setValue", [
                'error' => $e->getMessage()
            ]);
            // Fallback: set sebagai teks jika replaceXmlBlock tidak tersedia
            $tp->setValue($placeholder, '[Tabel tidak dapat dirender]');
        }
    }

    // =========================================================================
    // FALLBACK — Full-AI content (jika tidak ada template)
    // =========================================================================

    protected function buildFallbackContent($dataArtefak, $narasiAI)
    {
        $lines = [];
        $lines[] = "LAPORAN HASIL MONITORING ARTEFAK PERKULIAHAN RPS dan MATERI";
        $lines[] = "SEMESTER {$dataArtefak['semester']} TA {$dataArtefak['tahun_ajaran']}";
        $lines[] = "Program Studi: {$dataArtefak['prodi']}";
        $lines[] = "";
        $lines[] = "BAB 1 PENDAHULUAN";
        $lines[] = "Program Studi {$dataArtefak['prodi']} melakukan monitoring artefak perkuliahan semester {$dataArtefak['semester']} TA {$dataArtefak['tahun_ajaran']}.";
        $lines[] = "";
        $lines[] = "BAB 2 PROGRAM KERJA";
        $lines[] = "1. Pemeriksaan RPS dan artefak perkuliahan week 1 di CIS.";
        $lines[] = "2. Konfirmasi kelengkapan artefak kepada dosen pengampu.";
        $lines[] = "";
        $lines[] = "BAB 5 EVALUASI";
        $lines[] = "Total Mata Kuliah : {$dataArtefak['total_rps']}";
        $lines[] = "RPS Sudah Upload  : {$dataArtefak['rps_uploaded']} ({$dataArtefak['rps_percentage']}%)";
        $lines[] = "Materi Sudah Upload: {$dataArtefak['materi_uploaded']} ({$dataArtefak['materi_percentage']}%)";
        $lines[] = "";
        $lines[] = "TABEL RPS:";
        $lines[] = $this->buildTabelRPS($dataArtefak);
        $lines[] = "";
        $lines[] = "TABEL MATERI:";
        $lines[] = $this->buildTabelMateri($dataArtefak);
        $lines[] = "";
        $lines[] = "Hasil Pemeriksaan";
        $lines[] = $narasiAI['hasil_pemeriksaan'];
        $lines[] = "";
        $lines[] = "Analisis Ketercapaian";
        $lines[] = $narasiAI['analisis_ketercapaian'];
        $lines[] = "";
        $lines[] = "Tindak Lanjut";
        $lines[] = $narasiAI['tindak_lanjut'];
        $lines[] = "";
        $lines[] = "BAB 6 PENUTUP";
        $lines[] = $narasiAI['kesimpulan_penutup'];

        return implode("\n", $lines);
    }

    // =========================================================================
    // WORD DOCUMENT (fallback non-template)
    // =========================================================================

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

    // =========================================================================
    // AI CALL
    // =========================================================================

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

    // =========================================================================
    // TEMPLATE VECTOR DB (dipakai oleh LaporanArtefakController)
    // =========================================================================

    public function processTemplateToVectorDB($templateId)
    {
        Log::info('Processing template to vector DB', ['template_id' => $templateId]);

        $template = TemplateLaporan::find($templateId);
        if (!$template || !$template->file_path) {
            throw new \Exception('Template not found or no file attached');
        }

        // Extract text
        $extraction = $this->textExtractionService->extractFromFile($template->file_path);
        if (!$extraction['success']) {
            throw new \Exception('Failed to extract text: ' . ($extraction['metadata']['error'] ?? 'Unknown'));
        }

        $text = $this->textExtractionService->cleanText($extraction['text']);
        $structure = $this->structureService->extractStructure($text);

        // Chunk
        $chunks = $this->advancedChunkingService->chunkWithMetadata($text, [
            'template_id' => $templateId,
            'template_name' => $template->nama_template,
            'prodi_id' => $template->prodi_id,
            'document_type' => 'template',
            'structure' => json_encode($structure),
        ], 'template');

        // Delete old chunks
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
}
