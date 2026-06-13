<?php

namespace App\Services;

use App\Models\LaporanBulanan;
use App\Models\TemplateLaporan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Service untuk generate Word document dari laporan kuesioner
 * dengan support untuk template placeholder
 */
class KuesioneWordGenerationService
{
    /**
     * Pending table replacements after template fill: token => xml
     */
    private $pendingTableReplacements = [];

    /**
     * Generate Word document from laporan data
     * Uses template if available and compatible, otherwise generates from scratch
     */
    public function generateWordDocument($laporan)
    {
        Log::info('Generating Word document for laporan', [
            'laporan_id' => $laporan->id,
            'periode' => $laporan->periode,
            'template_id' => $laporan->template_id
        ]);

        // Check if template is available and should be used
        if ($laporan->template_id) {
            $template = $laporan->template ?: TemplateLaporan::find($laporan->template_id);

            if ($template && $template->file_path) {
                $templatePath = $this->resolveTemplatePath($template);

                if ($templatePath && file_exists($templatePath)) {
                    Log::info('Using template-based Word generation', [
                        'template_id' => $template->id,
                        'template_name' => $template->nama_template,
                        'file_path' => $template->file_path
                    ]);

                    return $this->generateWordFromTemplate($laporan, $template);
                } else {
                    Log::warning('Template file not found, falling back to scratch generation', [
                        'template_id' => $template->id,
                        'file_path' => $template->file_path
                    ]);
                }
            }
        }

        // Fallback: generate from scratch
        Log::info('Using generateWordFromScratch for proper table rendering', [
            'laporan_id' => $laporan->id,
            'reason' => 'No template available or template not found'
        ]);

        return $this->generateWordFromScratch($laporan);
    }

    /**
     * Resolve template file path from multiple possible locations
     */
    private function resolveTemplatePath($template)
    {
        $templateRelativePath = ltrim($template->file_path, '/');
        $templateRelativePath = preg_replace('#^public[\\/]+#', '', $templateRelativePath);

        $possiblePaths = [
            storage_path('app/public/' . $templateRelativePath),
            storage_path('app/' . $templateRelativePath),
            storage_path('app/public/' . $template->file_path),
            storage_path('app/' . $template->file_path),
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                Log::info('Template file found', ['path' => $path]);
                return $path;
            }
        }

        Log::warning('Template file not found in any location', [
            'tried_paths' => $possiblePaths
        ]);

        return null;
    }

    /**
     * Generate Word from template with placeholders filled from AI results
     */
    private function generateWordFromTemplate($laporan, $template)
    {
        try {
            $templateRelativePath = ltrim($template->file_path, '/');
            $templateRelativePath = preg_replace('#^public[\\/]+#', '', $templateRelativePath);

            $templatePath = storage_path('app/public/' . $templateRelativePath);
            if (!file_exists($templatePath)) {
                $alternatePath = storage_path('app/' . $templateRelativePath);
                if (file_exists($alternatePath)) {
                    $templatePath = $alternatePath;
                }
            }

            if (!file_exists($templatePath)) {
                Log::warning('Template file not found', ['path' => $templatePath]);
                return $this->generateWordFromScratch($laporan);
            }

            Log::info('Generating Word from template', [
                'template_id' => $template->id,
                'template_name' => $template->nama_template,
                'laporan_id' => $laporan->id
            ]);

            // Normalize placeholder runs inside the document XML, then load template
            $templatePath = $this->normalizeTemplatePath($templatePath);
            $templateProcessor = new TemplateProcessor($templatePath);

            // Try double-brace macros first, then fallback to single-brace if no variables found
            $templateProcessor->setMacroChars('{{', '}}');
            $availableVars = $templateProcessor->getVariables();

            if (empty($availableVars)) {
                Log::info('No variables found with {{ }} macro, trying { } macro');
                $templateProcessor->setMacroChars('{', '}');
                $availableVars = $templateProcessor->getVariables();
            }

            Log::info('Template variables found', [
                'variables' => $availableVars,
                'total_count' => count($availableVars),
                'has_gabungan_i' => in_array('GABUNGAN_TINGKAT_I', $availableVars),
                'has_gabungan_ii' => in_array('GABUNGAN_TINGKAT_II', $availableVars),
                'has_gabungan_iii' => in_array('GABUNGAN_TINGKAT_III', $availableVars),
                'has_gabungan_iv' => in_array('GABUNGAN_TINGKAT_IV', $availableVars),
                'gabungan_related' => array_filter($availableVars, function($v) {
                    return strpos($v, 'GABUNGAN') !== false || strpos($v, 'TINGKAT') !== false;
                })
            ]);

            // Extract placeholder values from hasil_laporan
            $placeholders = $this->extractPlaceholdersFromLaporan($laporan);

            Log::info('Extracted placeholders from laporan', [
                'count' => count($placeholders),
                'keys' => array_keys($placeholders),
                'has_gabungan_i' => isset($placeholders['GABUNGAN_TINGKAT_I']),
                'has_gabungan_ii' => isset($placeholders['GABUNGAN_TINGKAT_II']),
                'has_gabungan_iii' => isset($placeholders['GABUNGAN_TINGKAT_III']),
                'has_gabungan_iv' => isset($placeholders['GABUNGAN_TINGKAT_IV']),
                'gabungan_i_length' => isset($placeholders['GABUNGAN_TINGKAT_I']) ? strlen($placeholders['GABUNGAN_TINGKAT_I']) : 0,
                'sample_values' => array_map(function($v) {
                    return is_string($v) ? substr($v, 0, 100) : gettype($v);
                }, array_slice($placeholders, 0, 5))
            ]);

            // Fill placeholders
            $filledCount = 0;
            foreach ($placeholders as $key => $value) {
                // Skip table placeholders - they will be injected with replaceXmlBlock
                if (isset($this->pendingTableReplacements[$key])) {
                    Log::debug('Skipping table placeholder (will be injected with XML)', ['key' => $key]);
                    continue;
                }

                if (in_array($key, $availableVars)) {
                    try {
                        // Convert arrays to formatted text
                        if (is_array($value)) {
                            $value = $this->formatArrayForWord($value);
                        }

                        // Replace placeholder
                        $templateProcessor->setValue($key, $value);
                        $filledCount++;

                        Log::debug('Filled placeholder', [
                            'key' => $key,
                            'value_length' => strlen($value),
                            'is_gabungan' => strpos($key, 'GABUNGAN') !== false
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to fill placeholder', [
                            'key' => $key,
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    Log::debug('Placeholder not in template', [
                        'key' => $key,
                        'is_gabungan' => strpos($key, 'GABUNGAN') !== false
                    ]);
                }
            }

            Log::info('Placeholder filling completed', [
                'filled' => $filledCount,
                'total_placeholders' => count($placeholders),
                'template_vars' => count($availableVars),
                'pending_table_replacements' => count($this->pendingTableReplacements)
            ]);

            // Inject tables BEFORE saving (like LaporanArtefakService does)
            if (!empty($this->pendingTableReplacements)) {
                Log::info('Injecting XML tables using replaceXmlBlock', [
                    'tokens_to_replace' => array_keys($this->pendingTableReplacements),
                    'available_vars' => $availableVars,
                    'gabungan_keys' => array_filter(array_keys($this->pendingTableReplacements), function($k) {
                        return strpos($k, 'GABUNGAN') !== false;
                    }),
                    'gabungan_in_template' => [
                        'GABUNGAN_TINGKAT_I' => in_array('GABUNGAN_TINGKAT_I', $availableVars),
                        'GABUNGAN_TINGKAT_II' => in_array('GABUNGAN_TINGKAT_II', $availableVars),
                        'GABUNGAN_TINGKAT_III' => in_array('GABUNGAN_TINGKAT_III', $availableVars),
                        'GABUNGAN_TINGKAT_IV' => in_array('GABUNGAN_TINGKAT_IV', $availableVars),
                    ]
                ]);

                $injectedCount = 0;
                $skippedPlaceholders = [];

                foreach ($this->pendingTableReplacements as $placeholder => $xmlContent) {
                    try {
                        // Check if placeholder exists in template
                        $existsInTemplate = in_array($placeholder, $availableVars);

                        Log::info('Attempting to inject table', [
                            'placeholder' => $placeholder,
                            'exists_in_template' => $existsInTemplate,
                            'xml_length' => strlen($xmlContent),
                            'is_gabungan' => strpos($placeholder, 'GABUNGAN') !== false
                        ]);

                        // Try replaceXmlBlock first
                        if ($existsInTemplate) {
                            $this->replaceWithTable($templateProcessor, $placeholder, $xmlContent);
                            $injectedCount++;

                            Log::info('Injected XML table via replaceXmlBlock', [
                                'placeholder' => $placeholder,
                                'xml_length' => strlen($xmlContent)
                            ]);
                        } else {
                            $skippedPlaceholders[] = $placeholder;
                            Log::warning('Placeholder not found in template variables - SKIPPING INJECTION', [
                                'placeholder' => $placeholder,
                                'is_gabungan' => strpos($placeholder, 'GABUNGAN') !== false,
                                'suggestion' => 'Please add {{' . $placeholder . '}} to your Word template'
                            ]);
                            
                            // Don't try to inject if placeholder doesn't exist in template
                            // This will prevent corruption
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to inject table', [
                            'placeholder' => $placeholder,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }

                Log::info('Table injection summary', [
                    'injected' => $injectedCount,
                    'total' => count($this->pendingTableReplacements),
                    'skipped' => $skippedPlaceholders,
                    'skipped_count' => count($skippedPlaceholders),
                    'gabungan_skipped' => array_filter($skippedPlaceholders, function($p) {
                        return strpos($p, 'GABUNGAN') !== false;
                    })
                ]);
                
                // Log important message if GABUNGAN placeholders are skipped
                $gabunganSkipped = array_filter($skippedPlaceholders, function($p) {
                    return strpos($p, 'GABUNGAN') !== false;
                });
                
                if (!empty($gabunganSkipped)) {
                    Log::warning('⚠️ GABUNGAN placeholders not found in template!', [
                        'skipped_gabungan' => $gabunganSkipped,
                        'action_required' => 'Please add these placeholders to your Word template: ' . implode(', ', array_map(function($p) { return '{{' . $p . '}}'; }, $gabunganSkipped))
                    ]);
                }
            }

            // Save to temporary location first
            $fileName = 'laporan_kuesioner_' . $laporan->periode . '_' . time() . '.docx';
            $filePath = 'laporan_kuesioner/' . $fileName;
            $fullPath = storage_path('app/' . $filePath);

            // Create directory if not exists
            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save template processor result
            $templateProcessor->saveAs($fullPath);

            // Update laporan
            $laporan->file_word = $filePath;
            $laporan->save();

            Log::info('Word document generated from template', [
                'laporan_id' => $laporan->id,
                'file_path' => $filePath,
                'filled_placeholders' => $filledCount,
                'table_replacements' => count($this->pendingTableReplacements)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to generate Word from template', [
                'laporan_id' => $laporan->id,
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback to simple generation
            Log::info('Falling back to generateWordFromScratch', [
                'laporan_id' => $laporan->id,
                'reason' => 'Template generation failed'
            ]);

            return $this->generateWordFromScratch($laporan);
        }
    }

    /**
     * Extract placeholder values from hasil_laporan JSON
     * Maps AI results to template placeholder names
     * HANDLES TWO FORMATS:
     * 1. AI-generated format: PENDAHULUAN_TUJUAN, HASIL_KUESIONER_TINGKAT_I, etc.
     * 2. Complex format: ringkasan_eksekutif, statistik_utama, analisis_per_kuesioner, etc.
     */
    private function extractPlaceholdersFromLaporan($laporan)
    {
        $placeholders = [];
        $hasilLaporan = $laporan->hasil_laporan ?? [];

        Log::info('Starting placeholder extraction', [
            'laporan_id' => $laporan->id,
            'hasil_laporan_keys' => array_keys($hasilLaporan),
            'has_ai_format' => isset($hasilLaporan['PENDAHULUAN_TUJUAN']),
            'has_complex_format' => isset($hasilLaporan['ringkasan_eksekutif'])
        ]);

        $periodeContext = $this->resolveLaporanPeriodeContext($laporan);
        $periodeObj = $periodeContext['periodeObj'];
        $year = $periodeContext['year'];
        $month = $periodeContext['month'];
        $semesterText = $periodeContext['semesterText'];
        $tahunAkademik = $periodeContext['tahunAkademik'];
        $jenisUjian = $periodeContext['jenisUjian'];

        $placeholders['PERIODE'] = $periodeObj->locale('id')->translatedFormat('F Y');
        $placeholders['BULAN'] = $periodeObj->locale('id')->translatedFormat('F');
        $placeholders['TAHUN'] = (string)$year;
        $placeholders['TAHUN_AKADEMIK'] = $tahunAkademik;
        $placeholders['SEMESTER'] = $semesterText;
        $placeholders['JENIS_UJIAN'] = $jenisUjian;

        // Judul Laporan
        $placeholders['JUDUL_LAPORAN'] = $laporan->judul_laporan ?? 'Laporan Monitoring Kuesioner Kepuasan Mahasiswa';

        // Additional metadata placeholders for template
        $user = \App\Models\User::find($laporan->user_id);
        $prodi = $user && $user->prodi ? $user->prodi : null;
        $placeholders['PRODI'] = $prodi ? $prodi->nama_prodi : 'Program Studi';
        $placeholders['JENIS_UJIAN'] = $jenisUjian ?: 'Kuesioner Kepuasan Mahasiswa';
        $placeholders['TEMPAT'] = 'Laguboti';

        // Use the 10th day of the month after periode for TANGGAL
        $reportDate = $periodeContext['reportDate'];
        $placeholders['TANGGAL'] = $reportDate->locale('id')->translatedFormat('d F Y');

        // =================================================================
        // HANDLE AI-GENERATED FORMAT (from generateLaporanWithPlaceholders)
        // =================================================================
        if (isset($hasilLaporan['PENDAHULUAN_TUJUAN']) || isset($hasilLaporan['HASIL_KUESIONER_TINGKAT_I'])) {
            Log::info('Using AI-generated format for placeholders');

            // Use AI-generated content for narrative/text sections
            $placeholders['PENDAHULUAN_TUJUAN'] = $hasilLaporan['PENDAHULUAN_TUJUAN'] ?? $this->generatePendahuluanTujuan($laporan);
            $placeholders['PENDAHULUAN_WAKTU']  = $hasilLaporan['PENDAHULUAN_WAKTU']  ?? $this->generatePendahuluanWaktu($laporan);

            // PENDAHULUAN_RUANG_LINGKUP contains a real Word table (Tabel 1 skala likert).
            // Generate XML table and register it for injection
            $ruangLingkupXml = $this->generateRuangLingkupXml($laporan);
            $this->pendingTableReplacements['PENDAHULUAN_RUANG_LINGKUP'] = $ruangLingkupXml;

            // HASIL_KUESIONER_TINGKAT_X and GABUNGAN_TINGKAT_X
            // Generate XML tables for post-processing replacement
            $this->generateHasilKuesionePerTingkat($placeholders, $hasilLaporan, $laporan);

            // Kesimpulan and saran from AI (plain text, no table needed)
            $placeholders['KESIMPULAN']       = $hasilLaporan['KESIMPULAN']       ?? $this->generateKesimpulanFromDatabase($laporan, $hasilLaporan);
            $placeholders['SARAN_REKOMENDASI'] = $hasilLaporan['SARAN_REKOMENDASI'] ?? '';

            Log::info('AI-generated format placeholders extracted', [
                'total_placeholders' => count($placeholders),
                'has_tingkat_i'      => isset($placeholders['HASIL_KUESIONER_TINGKAT_I']),
                'pending_tables'     => array_keys($this->pendingTableReplacements),
            ]);

            return $placeholders;
        }

        // =================================================================
        // FALLBACK: Complex format with ringkasan_eksekutif, etc.
        // =================================================================
        Log::info('Using complex format for placeholders');

        // Pendahuluan Section
        $placeholders['PENDAHULUAN_TUJUAN'] = $this->generatePendahuluanTujuan($laporan);
        $placeholders['PENDAHULUAN_WAKTU']  = $this->generatePendahuluanWaktu($laporan);
        // Ruang lingkup with proper Word table (not plain text)
        $ruangLingkupXml = $this->generateRuangLingkupXml($laporan);
        $this->pendingTableReplacements['PENDAHULUAN_RUANG_LINGKUP'] = $ruangLingkupXml;

        // Metadata from hasil_laporan
        if (isset($hasilLaporan['metadata'])) {
            $metadata = $hasilLaporan['metadata'];
            if (isset($metadata['periode'])) $placeholders['PERIODE_METADATA'] = $metadata['periode'];
            if (isset($metadata['bulan'])) $placeholders['BULAN_METADATA'] = $metadata['bulan'];
            if (isset($metadata['tahun'])) $placeholders['TAHUN_METADATA'] = (string)$metadata['tahun'];
        }

        // Ringkasan Eksekutif
        if (isset($hasilLaporan['ringkasan_eksekutif'])) {
            $ringkasan = $hasilLaporan['ringkasan_eksekutif'];

            $placeholders['RINGKASAN_EKSEKUTIF'] = $this->formatRingkasanEksekutif($ringkasan);
            $placeholders['RINGKASAN_OVERVIEW'] = $ringkasan['overview'] ?? '';
            $placeholders['HIGHLIGHT_POSITIF'] = $this->formatListToText($ringkasan['highlight_positif'] ?? []);
            $placeholders['HIGHLIGHT_NEGATIF'] = $this->formatListToText($ringkasan['highlight_negatif'] ?? []);
            $placeholders['TREND'] = $ringkasan['trend'] ?? '';
        }

        // Statistik Utama
        if (isset($hasilLaporan['statistik_utama'])) {
            $stats = $hasilLaporan['statistik_utama'];

            $placeholders['STATISTIK_UTAMA'] = $this->formatStatistikUtama($stats);
            $placeholders['TOTAL_KUESIONER'] = (string)($stats['total_kuesioner'] ?? 0);
            $placeholders['TOTAL_RESPONDEN'] = (string)($stats['total_responden'] ?? 0);
            $placeholders['INDEX_KEPUASAN'] = (string)($stats['index_kepuasan_rata_rata'] ?? 0);
            $placeholders['PERSEN_KEPUASAN'] = (string)($stats['persen_kepuasan_rata_rata'] ?? 0) . '%';
        }

        // Analisis Per Kuesioner (table format)
        if (isset($hasilLaporan['analisis_per_kuesioner'])) {
            $placeholders['ANALISIS_PER_KUESIONER'] = $this->formatAnalisisPerKuesioner($hasilLaporan['analisis_per_kuesioner']);
        }

        // Top 5 Tertinggi
        if (isset($hasilLaporan['top_5_tertinggi'])) {
            $placeholders['TOP_5_TERTINGGI'] = $this->formatTop5($hasilLaporan['top_5_tertinggi']);
        }

        // Top 5 Terendah
        if (isset($hasilLaporan['top_5_terendah'])) {
            $placeholders['TOP_5_TERENDAH'] = $this->formatTop5($hasilLaporan['top_5_terendah']);
        }

        // Insight Utama
        if (isset($hasilLaporan['insight_utama'])) {
            $placeholders['INSIGHT_UTAMA'] = $this->formatListToText($hasilLaporan['insight_utama'], true);
        }

        // Rekomendasi
        if (isset($hasilLaporan['rekomendasi'])) {
            $placeholders['REKOMENDASI'] = $this->formatRekomendasi($hasilLaporan['rekomendasi']);
        }

        // Kesimpulan (if AI provides it)
        if (isset($hasilLaporan['kesimpulan'])) {
            $placeholders['KESIMPULAN'] = $hasilLaporan['kesimpulan'];
        } else {
            // Generate kesimpulan from actual database data
            $placeholders['KESIMPULAN'] = $this->generateKesimpulanFromDatabase($laporan, $hasilLaporan);
        }

        // Generate Hasil Kuesioner per Tingkat based on database data (with XML tables)
        $this->generateHasilKuesionePerTingkat($placeholders, $hasilLaporan, $laporan);

        Log::info('Complex format placeholders extracted', [
            'total_placeholders' => count($placeholders)
        ]);

        return $placeholders;
    }

    /**
     * Generate Pendahuluan - Tujuan
     */
    private function generatePendahuluanTujuan($laporan)
    {
        $periodeContext = $this->resolveLaporanPeriodeContext($laporan);
        $semester = $periodeContext['semesterText'];
        $tahunAkademik = $periodeContext['tahunAkademik'];

        // Get user's prodi name
        $user = \App\Models\User::find($laporan->user_id);
        $prodiNama = $user && $user->prodi ? $user->prodi->nama_prodi : 'program studi';

        $text = "Survei ini bertujuan untuk mengevaluasi mata kuliah pada semester {$semester} {$tahunAkademik} di lingkungan {$prodiNama} Fakultas Vokasi Institut Teknologi Del.";

        return $text;
    }

    /**
     * Generate Pendahuluan - Waktu Pelaksanaan
     */
    private function generatePendahuluanWaktu($laporan)
    {
        $periodeContext = $this->resolveLaporanPeriodeContext($laporan);
        $periodeObj = $periodeContext['periodeObj'];
        $bulan = $periodeObj->locale('id')->translatedFormat('F');
        $tahun = $periodeObj->year;

        $text = "Penyebaran kuesioner evaluasi mata kuliah dilaksanakan pada bulan {$bulan} {$tahun}. ";
        $text .= "Penyebaran kuesioner dibagi menjadi 2 tahap yaitu pembagian pertama dilakukan pada minggu ke-14 dan pembagian kedua dilakukan pada minggu ke-15. ";
        $text .= "Kuesioner evaluasi mata kuliah ini dilaksanakan pada semua mata kuliah dengan menyebarkan kuesioner online melalui website cis.del.ac.id.";

        return $text;
    }

    /**
     * Generate Pendahuluan - Ruang Lingkup as plain text (no table injection)
     */
    private function generatePendahuluanRuangLingkupText($laporan)
    {
        $hasilLaporan = $laporan->hasil_laporan ?? [];
        $totalKuesioner = $hasilLaporan['statistik_utama']['total_kuesioner'] ?? 0;
        $totalResponden = $hasilLaporan['statistik_utama']['total_responden'] ?? 0;

        $text = "Yang menjadi responden pada survei ini adalah mahasiswa yang mengambil mata kuliah prodi, mata kuliah fakultas dan mata kuliah institut. ";
        $text .= "Adapun pihak terkait yang kualitas layanannya dinilai pada survei ini yaitu dosen, teaching assistant yang berhubungan dengan mata kuliah.\n\n";
        $text .= "Kuesioner yang dibagikan terdiri dari 17 pertanyaan dengan kuesioner yang dibagikan memiliki 4 kriteria penilaian (skala likert) yaitu Sangat Setuju, Setuju, Cukup Setuju, dan Tidak Setuju. ";
        $text .= "Agar mempermudah perhitungan kuesioner, kriteria Sangat Setuju Setuju, Cukup Setuju, dan Tidak Setuju akan dihitung dan dilaporkan dalam bentuk indeks skala 4 dengan bobot yang mengikuti Tabel 1.\n\n";
        $text .= "Tabel 1. Skala Likert Kuesioner\n\n";
        $text .= "Pernyataan: Tidak setuju (TS) - Kode: TS - Skala: 1\n";
        $text .= "Pernyataan: Cukup Setuju (CS) - Kode: CS - Skala: 2\n";
        $text .= "Pernyataan: Setuju (S) - Kode: S - Skala: 3\n";
        $text .= "Pernyataan: Sangat Setuju (SS) - Kode: SS - Skala: 4";

        return $text;
    }

    /**
     * Generate Hasil Kuesioner per Tingkat as formatted text (not XML tokens)
     * Uses database queries for accurate data
     */
    private function generateHasilKuesionePerTingkatAsText(&$placeholders, $hasilLaporan, $laporan)
    {
        $userId = $laporan->user_id;
        $periodeContext = $this->resolveLaporanPeriodeContext($laporan);
        $semester = $periodeContext['semester'];
        $jenisUjian = $periodeContext['jenisUjian'];

        // Get user's prodi
        $user = \App\Models\User::find($userId);
        $prodiKode = $user && $user->prodi ? $user->prodi->kode_prodi : null;

        Log::info('Generating Hasil Kuesioner per Tingkat as text', [
            'semester' => $semester,
            'jenis_ujian' => $jenisUjian,
            'prodi_kode' => $prodiKode,
            'user_id' => $userId
        ]);

        // Query kuesioner_uploads directly
        $uploads = \App\Models\KuesioneUpload::query()
            ->where('semester', $semester)
            ->when($jenisUjian, function($q) use ($jenisUjian) {
                // Filter by jenis_kuesioner (UTS or UAS)
                $q->where('jenis_kuesioner', 'like', '%' . $jenisUjian . '%');
            })
            ->when($prodiKode, function($q) use ($prodiKode) {
                $q->whereHas('user', function($uq) use ($prodiKode) {
                    $uq->whereHas('prodi', function($pq) use ($prodiKode) {
                        $pq->where('kode_prodi', $prodiKode);
                    });
                });
            })
            ->with('user.prodi')
            ->orderBy('tingkat', 'asc')
            ->orderBy('nama_matakuliah', 'asc')
            ->get();

        // Group by tingkat
        $dataByTingkat = $uploads->groupBy(function($item) {
            $tingkat = $item->tingkat;
            if (is_numeric($tingkat)) {
                return (int)$tingkat;
            }
            if (in_array($tingkat, ['I', 'II', 'III', 'IV', 'V'])) {
                return $this->romanToNumber($tingkat);
            }
            return $this->extractTingkatFromKode($item->kode_matakuliah);
        });

        // Generate for each tingkat (I, II, III, IV)
        for ($i = 1; $i <= 4; $i++) {
            $tingkatRoman = $this->numberToRoman($i);

            $hasilKey = "HASIL_KUESIONER_TINGKAT_" . $tingkatRoman;
            $gabunganKey = "GABUNGAN_TINGKAT_" . $tingkatRoman;

            $kuesioneData = $dataByTingkat->get($i, collect());

            if ($kuesioneData->isNotEmpty()) {
                // Generate as formatted text tables
                $hasilText = $this->generateHasilKuesioneTableFromUploads($kuesioneData, $i);

                $placeholders[$hasilKey] = $hasilText;
                $placeholders[$gabunganKey] = $hasilText;

                Log::info("Generated text tables for Tingkat {$tingkatRoman}", [
                    'hasil_length' => strlen($hasilText),
                ]);
            } else {
                $noDataMsg = "Tidak ada data kuesioner untuk Tingkat {$tingkatRoman}";
                $placeholders[$hasilKey] = "\n" . $noDataMsg;
                $placeholders[$gabunganKey] = "\n" . $noDataMsg;
            }
        }
    }

    /**
     * Generate Pendahuluan - Ruang Lingkup
     */
    private function generatePendahuluanRuangLingkup($laporan)
    {
        $hasilLaporan = $laporan->hasil_laporan ?? [];
        $totalKuesioner = $hasilLaporan['statistik_utama']['total_kuesioner'] ?? 0;
        $totalResponden = $hasilLaporan['statistik_utama']['total_responden'] ?? 0;

        $text = "Yang menjadi responden pada survei ini adalah mahasiswa yang mengambil mata kuliah prodi, mata kuliah fakultas dan mata kuliah institut. ";
        $text .= "Adapun pihak terkait yang kualitas layanannya dinilai pada survei ini yaitu dosen, teaching assistant yang berhubungan dengan mata kuliah. ";
        $text .= "Kuesioner yang dibagikan terdiri dari 17 pertanyaan dengan kuesioner yang dibagikan memiliki 4 kriteria penilaian (skala likert) yaitu Sangat Setuju, Setuju, Cukup Setuju, dan Tidak Setuju. ";
        $text .= "Agar mempermudah perhitungan kuesioner, kriteria Sangat Setuju Setuju, Cukup Setuju, dan Tidak Setuju akan dihitung dan dilaporkan dalam bentuk indeks skala 4 dengan bobot yang mengikuti Tabel 1.\n\n";
        $text .= "Tabel 1. Matakuliah Mahasiswa Tingkat\n\n";
        $text .= "| Pernyataan | Kode | Skala |\n";
        $text .= "|------------|------|-------|\n";
        $text .= "| Tidak setuju (TS) | TS | 1 |\n";
        $text .= "| Cukup Setuju (CS) | CS | 2 |\n";
        $text .= "| Setuju (S) | S | 3 |\n";
        $text .= "| Sangat Setuju (SS) | SS | 4 |";

        return $text;
    }

    /**
     * Generate Kesimpulan section with proper formatting from database
     */
    private function generateKesimpulanFromDatabase($laporan, $hasilLaporan)
    {
        // Query actual data from database to calculate real average
        $userId = $laporan->user_id;
        $periodeContext = $this->resolveLaporanPeriodeContext($laporan);
        $semester = $periodeContext['semester'];
        $jenisUjian = $periodeContext['jenisUjian'];

        // Get user's prodi
        $user = \App\Models\User::find($userId);
        $prodiKode = $user && $user->prodi ? $user->prodi->kode_prodi : null;

        // Query uploads
        $uploads = \App\Models\KuesioneUpload::query()
            ->where('semester', $semester)
            ->when($jenisUjian, function($q) use ($jenisUjian) {
                // Filter by jenis_kuesioner (UTS or UAS)
                $q->where('jenis_kuesioner', 'like', '%' . $jenisUjian . '%');
            })
            ->when($prodiKode, function($q) use ($prodiKode) {
                $q->whereHas('user', function($uq) use ($prodiKode) {
                    $uq->whereHas('prodi', function($pq) use ($prodiKode) {
                        $pq->where('kode_prodi', $prodiKode);
                    });
                });
            })
            ->get();

        // Calculate actual average
        $indexKepuasan = $uploads->count() > 0 ? $uploads->avg('index_kepuasan') : 0;
        $indexKepuasan = round($indexKepuasan, 2);

        $kesimpulan = "- Adanya matakuliah yang dihitung berdasarkan kode matakuliah, sebaiknya tetap dipisahkan sesuai prodi meskipun memiliki kode matakuliah yang sama.\n";
        $kesimpulan .= "- Indeks Kepuasan semua matakuliah di prodi adalah " . number_format($indexKepuasan, 2) . ". ";

        if ($indexKepuasan >= 2.8) {
            $kesimpulan .= "Nilai kepuasan ini telah melampaui indikator kepuasan pembelajaran mahasiswa minimum yaitu 2.8.";
        } else {
            $kesimpulan .= "Nilai kepuasan ini belum mencapai indikator kepuasan pembelajaran mahasiswa minimum yaitu 2.8. Perlu dilakukan perbaikan.";
        }

        return $kesimpulan;
    }

    /**
     * Generate Kesimpulan section with proper formatting (legacy method)
     */
    private function generateKesimpulan($hasilLaporan)
    {
        $stats = $hasilLaporan['statistik_utama'] ?? [];
        $indexKepuasan = $stats['index_kepuasan_rata_rata'] ?? 0;
        $totalKuesioner = $stats['total_kuesioner'] ?? 0;

        $kesimpulan = "- Adanya matakuliah yang dihitung berdasarkan kode matakuliah, sebaiknya tetap dipisahkan sesuai prodi meskipun memiliki kode matakuliah yang sama.\n";
        $kesimpulan .= "- Indeks Kepuasan semua matakuliah di prodi adalah " . number_format($indexKepuasan, 2) . ". ";

        if ($indexKepuasan >= 2.8) {
            $kesimpulan .= "Nilai kepuasan ini telah melampaui indikator kepuasan pembelajaran mahasiswa minimum yaitu 2.8.";
        } else {
            $kesimpulan .= "Nilai kepuasan ini belum mencapai indikator kepuasan pembelajaran mahasiswa minimum yaitu 2.8. Perlu dilakukan perbaikan.";
        }

        return $kesimpulan;
    }

    /**
     * Generate Hasil Kuesioner per Tingkat
     * Query directly from kuesioner_uploads table to ensure fresh, accurate data
     */
    private function generateHasilKuesionePerTingkat(&$placeholders, $hasilLaporan, $laporan)
    {
        $userId = $laporan->user_id;
        $periodeContext = $this->resolveLaporanPeriodeContext($laporan);
        $semester = $periodeContext['semester'];
        $jenisUjian = $periodeContext['jenisUjian']; // UTS or UAS

        // Get user's prodi
        $user = \App\Models\User::find($userId);
        $prodiKode = $user && $user->prodi ? $user->prodi->kode_prodi : null;

        Log::info('Generating Hasil Kuesioner per Tingkat from database', [
            'semester' => $semester,
            'jenis_ujian' => $jenisUjian,
            'prodi_kode' => $prodiKode,
            'user_id' => $userId
        ]);

        // Query kuesioner_uploads directly
        $uploads = \App\Models\KuesioneUpload::query()
            ->where('semester', $semester)
            ->when($jenisUjian, function($q) use ($jenisUjian) {
                // Filter by jenis_kuesioner (UTS or UAS)
                $q->where('jenis_kuesioner', 'like', '%' . $jenisUjian . '%');
            })
            ->when($prodiKode, function($q) use ($prodiKode) {
                $q->whereHas('user', function($uq) use ($prodiKode) {
                    $uq->whereHas('prodi', function($pq) use ($prodiKode) {
                        $pq->where('kode_prodi', $prodiKode);
                    });
                });
            })
            ->with('user.prodi')
            ->orderBy('tingkat', 'asc')
            ->orderBy('nama_matakuliah', 'asc')
            ->get();

        Log::info('Kuesioner uploads fetched', [
            'total_uploads' => $uploads->count(),
            'sample_tingkat_values' => $uploads->pluck('tingkat')->unique()->toArray(),
            'sample_jenis_kuesioner' => $uploads->pluck('jenis_kuesioner')->unique()->toArray()
        ]);

        // Group by tingkat - cast to integer to ensure proper grouping
        $dataByTingkat = $uploads->groupBy(function($item) {
            // Cast tingkat to integer, handle null/empty
            $tingkat = $item->tingkat;
            if (is_numeric($tingkat)) {
                return (int)$tingkat;
            }
            // If it's Roman numeral, convert it
            if (in_array($tingkat, ['I', 'II', 'III', 'IV', 'V'])) {
                return $this->romanToNumber($tingkat);
            }
            // If empty/null, try to extract from kode_matakuliah
            return $this->extractTingkatFromKode($item->kode_matakuliah);
        });

        Log::info('Kuesioner data grouped by tingkat', [
            'tingkat_groups' => $dataByTingkat->keys()->toArray(),
            'counts_per_tingkat' => $dataByTingkat->map->count()->toArray()
        ]);

        // Generate for each tingkat (I, II, III, IV)
        for ($i = 1; $i <= 4; $i++) {
            $tingkatRoman = $this->numberToRoman($i);

            $hasilKey = "HASIL_KUESIONER_TINGKAT_" . $tingkatRoman;
            $gabunganKey = "GABUNGAN_TINGKAT_" . $tingkatRoman;

            $kuesioneData = $dataByTingkat->get($i, collect());

            Log::info("Processing Tingkat {$tingkatRoman}", [
                'count' => $kuesioneData->count(),
                'key' => $i
            ]);

            $noDataMsg = "Tidak ada data kuesioner untuk Tingkat {$tingkatRoman}";

            if ($kuesioneData->isNotEmpty()) {
                // Generate table placeholders for template
                // Store the XML directly for injection (will be injected with replaceXmlBlock)
                $hasilXml = $this->generateHasilKuesioneTableXml($kuesioneData, $i);
                $gabunganXml = $this->generateGabunganTableXml($kuesioneData, $i);
                
                // BOTH use XML injection now (not text)
                $this->pendingTableReplacements[$hasilKey] = $hasilXml;
                $this->pendingTableReplacements[$gabunganKey] = $gabunganXml;

                // IMPORTANT: Set dummy values in $placeholders so the keys exist
                // This allows the skip logic in setValue loop to work
                // The actual value doesn't matter because it will be skipped and replaced by XML
                $placeholders[$hasilKey] = '';
                $placeholders[$gabunganKey] = '';

                Log::info("Prepared content for Tingkat {$tingkatRoman}", [
                    'hasil_xml_length'    => strlen($hasilXml),
                    'gabungan_xml_length' => strlen($gabunganXml),
                    'gabungan_key' => $gabunganKey,
                    'placeholders_has_gabungan' => isset($placeholders[$gabunganKey]),
                    'pending_has_gabungan' => isset($this->pendingTableReplacements[$gabunganKey])
                ]);
            } else {
                // When no data exists, set fallback messages for all placeholders
                $placeholders[$hasilKey] = "\n" . $noDataMsg;
                $placeholders[$gabunganKey] = "\n" . $noDataMsg;

                Log::info("No data for Tingkat {$tingkatRoman}, set fallback messages for all placeholders");
            }
        }
    }

    /**
     * Convert Roman numeral to integer
     */
    private function romanToNumber($roman)
    {
        $map = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5];
        return $map[$roman] ?? 1;
    }

    /**
     * Extract tingkat from kode matakuliah (e.g., KU44201 -> 4, 4142101 -> 1)
     */
    private function extractTingkatFromKode($kodeMk)
    {
        if (empty($kodeMk)) return 1;

        // Try to extract tingkat from common patterns
        // Pattern 1: KU44201 -> first digit after letters is tingkat
        if (preg_match('/^[A-Z]+(\d)/', $kodeMk, $matches)) {
            return (int)$matches[1];
        }

        // Pattern 2: 4142101 -> first digit is tingkat
        if (preg_match('/^(\d)/', $kodeMk, $matches)) {
            return (int)$matches[1];
        }

        return 1; // Default to tingkat 1
    }

    private function groupMatakuliahUploads($kuesioneData)
    {
        $groups = [];

        foreach ($kuesioneData as $kuesioner) {
            $kodeMk = trim((string) ($kuesioner->kode_matakuliah ?? ''));
            $namaMk = trim((string) ($kuesioner->nama_matakuliah ?? ''));
            $groupKey = $kodeMk !== '' ? 'KODE::' . strtoupper($kodeMk) : 'NAMA::' . mb_strtolower($namaMk);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'kode_matakuliah' => $kodeMk !== '' ? $kodeMk : ($namaMk !== '' ? '-' : '-'),
                    'nama_matakuliah' => $namaMk !== '' ? $namaMk : '-',
                    'dosen' => [],
                    'indices' => [],
                    'rekomendasi' => [],
                    'rekomendasi_keys' => [],
                    'area_perbaikan' => [],
                    'area_perbaikan_keys' => [],
                    'ringkasan' => '',
                ];
            }

            if (!empty($kuesioner->dosen_pengampu)) {
                $dosen = trim($kuesioner->dosen_pengampu);
                if ($dosen !== '' && !in_array($dosen, $groups[$groupKey]['dosen'], true)) {
                    $groups[$groupKey]['dosen'][] = $dosen;
                }
            }

            if (is_numeric($kuesioner->index_kepuasan)) {
                $groups[$groupKey]['indices'][] = (float) $kuesioner->index_kepuasan;
            }

            $analisis = [];
            if (!empty($kuesioner->hasil_analisis)) {
                $analisis = is_string($kuesioner->hasil_analisis)
                    ? json_decode($kuesioner->hasil_analisis, true)
                    : $kuesioner->hasil_analisis;
            }

            if (isset($analisis['rekomendasi']) && is_array($analisis['rekomendasi'])) {
                foreach ($analisis['rekomendasi'] as $item) {
                    $item = trim((string) $item);
                    $key = mb_strtolower($item);
                    if ($item !== '' && !in_array($key, $groups[$groupKey]['rekomendasi_keys'], true)) {
                        $groups[$groupKey]['rekomendasi_keys'][] = $key;
                        $groups[$groupKey]['rekomendasi'][] = $item;
                    }
                }
            }

            if (isset($analisis['area_perbaikan']) && is_array($analisis['area_perbaikan'])) {
                foreach ($analisis['area_perbaikan'] as $item) {
                    $item = trim((string) $item);
                    $key = mb_strtolower($item);
                    if ($item !== '' && !in_array($key, $groups[$groupKey]['area_perbaikan_keys'], true)) {
                        $groups[$groupKey]['area_perbaikan_keys'][] = $key;
                        $groups[$groupKey]['area_perbaikan'][] = $item;
                    }
                }
            }

            if (empty($groups[$groupKey]['ringkasan']) && isset($analisis['ringkasan']) && trim((string) $analisis['ringkasan']) !== '') {
                $groups[$groupKey]['ringkasan'] = trim((string) $analisis['ringkasan']);
            }
        }

        return $groups;
    }

    /**
     * Generate Hasil Kuesioner Table from KuesioneUpload models
     */
    private function generateHasilKuesioneTableFromUploads($kuesioneData, $tingkat)
    {
        $tingkatRoman = $this->numberToRoman($tingkat);
        $groups = $this->groupMatakuliahUploads($kuesioneData);

        $text = "\nPada tingkat {$tingkatRoman} terdapat " . count($groups) . " matakuliah dengan detail sebagai berikut:\n\n";
        $text .= "Tabel " . (($tingkat * 2) - 1) . ". Matakuliah Mahasiswa Tingkat {$tingkatRoman}\n\n";
        $text .= "| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Indeks Kepuasan |\n";
        $text .= "|-----------------|-----------------|----------------|------------------|\n";

        $sumAllIndices = 0;
        $countAllIndices = 0;

        foreach ($groups as $group) {
            $kodeMk = $group['kode_matakuliah'] ?: '-';
            $namaMk = $group['nama_matakuliah'] ?: '-';
            $dosen = !empty($group['dosen']) ? implode(', ', $group['dosen']) : '-';

            $groupIndexCount = count($group['indices']);
            $groupAvgIndex = $groupIndexCount > 0 ? round(array_sum($group['indices']) / $groupIndexCount, 5) : 0;

            $sumAllIndices += array_sum($group['indices']);
            $countAllIndices += $groupIndexCount;

            $text .= "| {$kodeMk} | {$namaMk} | {$dosen} | {$groupAvgIndex} |\n";
        }

        $avgIndex = $countAllIndices > 0 ? round($sumAllIndices / $countAllIndices, 5) : 0;
        $text .= "\nRata Indeks Kepuasan: {$avgIndex}\n\n";

        return $text;
    }

    /**
     * Generate Word XML for HASIL_KUESIONER table (per-dosen, one row per kode_mk + dosen)
     * Kolom: Kode MK | Nama MK | Dosen | Indeks
     */
    private function generateHasilKuesioneTableXml($kuesioneData, $tingkat)
    {
        $tingkatRoman = $this->numberToRoman($tingkat);
        $cols = ['Kode MK', 'Nama MK', 'Dosen', 'Indeks'];

        $xml = '';

        // Paragraph before table
        $xml .= '<w:p>';
        $xml .= '<w:pPr><w:spacing w:after="100"/></w:pPr>';
        $xml .= '<w:r><w:t>Pada tingkat ' . $tingkatRoman . ' terdapat ' . $kuesioneData->count() . ' kuesioner dengan detail sebagai berikut:</w:t></w:r>';
        $xml .= '</w:p>';

        // Build table
        $colWidth = intval(9000 / count($cols));

        $xml .= '<w:tbl>';
        $xml .= '<w:tblPr>';
        $xml .= '<w:tblStyle w:val="TableGrid"/>';
        $xml .= '<w:tblW w:w="9000" w:type="dxa"/>';
        $xml .= '<w:tblBorders>';
        $xml .= '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '</w:tblBorders>';
        $xml .= '</w:tblPr>';

        $xml .= '<w:tblGrid>';
        foreach ($cols as $c) {
            $xml .= '<w:gridCol w:w="' . $colWidth . '"/>';
        }
        $xml .= '</w:tblGrid>';

        // Header row
        $xml .= '<w:tr>';
        foreach ($cols as $c) {
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
            $xml .= '<w:r><w:rPr><w:b/></w:rPr><w:t>' . $this->escapeXml($c) . '</w:t></w:r>';
            $xml .= '</w:p></w:tc>';
        }
        $xml .= '</w:tr>';

        // Data rows - ONE ROW PER KUESIONER (per dosen)
        $sumIndices = 0;
        $countIndices = 0;

        foreach ($kuesioneData as $kuesioner) {
            $kode = $this->escapeXml($kuesioner->kode_matakuliah ?: '-');
            $nama = $this->escapeXml($kuesioner->nama_matakuliah ?: '-');
            $dosen = $this->escapeXml($kuesioner->dosen_pengampu ?: '-');
            $indeks = is_numeric($kuesioner->index_kepuasan) ? (float)$kuesioner->index_kepuasan : 0;

            if (is_numeric($kuesioner->index_kepuasan)) {
                $sumIndices += $indeks;
                $countIndices++;
            }

            $xml .= '<w:tr>';

            // Kode MK
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:r><w:t>' . $kode . '</w:t></w:r></w:p>';
            $xml .= '</w:tc>';

            // Nama MK
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:r><w:t>' . $nama . '</w:t></w:r></w:p>';
            $xml .= '</w:tc>';

            // Dosen
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:r><w:t>' . $dosen . '</w:t></w:r></w:p>';
            $xml .= '</w:tc>';

            // Indeks
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
            $xml .= '<w:r><w:t>' . number_format($indeks, 2, '.', '') . '</w:t></w:r>';
            $xml .= '</w:p></w:tc>';

            $xml .= '</w:tr>';
        }

        $xml .= '</w:tbl>';

        // Footer paragraph
        $avgIndex = $countIndices > 0 ? round($sumIndices / $countIndices, 2) : 0;
        $xml .= '<w:p>';
        $xml .= '<w:pPr><w:spacing w:before="100" w:after="200"/></w:pPr>';
        $xml .= '<w:r><w:t>Rata-rata Indeks Kepuasan: ' . number_format($avgIndex, 2, '.', '') . '</w:t></w:r>';
        $xml .= '</w:p>';

        return $xml;
    }

    /**
     * Generate Word XML for GABUNGAN table (combined per matakuliah)
     * Dosens are combined with comma, indeks is averaged
     * Kolom: Kode MK | Nama MK | Dosen | Indeks
     */
    private function generateGabunganTableXml($kuesioneData, $tingkat)
    {
        $tingkatRoman = $this->numberToRoman($tingkat);
        $cols = ['Kode MK', 'Nama MK', 'Dosen', 'Indeks'];

        // Group by kode_mk and nama_mk
        $grouped = [];
        foreach ($kuesioneData as $kuesioner) {
            $kode = trim($kuesioner->kode_matakuliah ?: '');
            $nama = trim($kuesioner->nama_matakuliah ?: '');
            $key = strtoupper($kode) . '|' . mb_strtolower($nama);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'kode' => $kode ?: '-',
                    'nama' => $nama ?: '-',
                    'dosens' => [],
                    'indices' => []
                ];
            }

            $dosen = trim($kuesioner->dosen_pengampu ?: '');
            if ($dosen && !in_array($dosen, $grouped[$key]['dosens'])) {
                $grouped[$key]['dosens'][] = $dosen;
            }

            if (is_numeric($kuesioner->index_kepuasan)) {
                $grouped[$key]['indices'][] = (float)$kuesioner->index_kepuasan;
            }
        }

        $xml = '';

        // Paragraph before table
        $xml .= '<w:p>';
        $xml .= '<w:pPr><w:spacing w:after="100"/></w:pPr>';
        $xml .= '<w:r><w:t>Gabungan hasil kuesioner per mata kuliah tingkat ' . $tingkatRoman . ':</w:t></w:r>';
        $xml .= '</w:p>';

        // Build table
        $colWidth = intval(9000 / count($cols));

        $xml .= '<w:tbl>';
        $xml .= '<w:tblPr>';
        $xml .= '<w:tblStyle w:val="TableGrid"/>';
        $xml .= '<w:tblW w:w="9000" w:type="dxa"/>';
        $xml .= '<w:tblBorders>';
        $xml .= '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '</w:tblBorders>';
        $xml .= '</w:tblPr>';

        $xml .= '<w:tblGrid>';
        foreach ($cols as $c) {
            $xml .= '<w:gridCol w:w="' . $colWidth . '"/>';
        }
        $xml .= '</w:tblGrid>';

        // Header row
        $xml .= '<w:tr>';
        foreach ($cols as $c) {
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
            $xml .= '<w:r><w:rPr><w:b/></w:rPr><w:t>' . $this->escapeXml($c) . '</w:t></w:r>';
            $xml .= '</w:p></w:tc>';
        }
        $xml .= '</w:tr>';

        // Data rows - ONE ROW PER MATA KULIAH (dosens combined, index averaged)
        $sumIndices = 0;
        $countIndices = 0;

        foreach ($grouped as $group) {
            $kode = $this->escapeXml($group['kode']);
            $nama = $this->escapeXml($group['nama']);
            $dosen = $this->escapeXml(!empty($group['dosens']) ? implode(', ', $group['dosens']) : '-');

            $avgIndeks = 0;
            if (!empty($group['indices'])) {
                $avgIndeks = array_sum($group['indices']) / count($group['indices']);
                $sumIndices += array_sum($group['indices']);
                $countIndices += count($group['indices']);
            }

            $xml .= '<w:tr>';

            // Kode MK
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:r><w:t>' . $kode . '</w:t></w:r></w:p>';
            $xml .= '</w:tc>';

            // Nama MK
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:r><w:t>' . $nama . '</w:t></w:r></w:p>';
            $xml .= '</w:tc>';

            // Dosen (combined with comma)
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:r><w:t>' . $dosen . '</w:t></w:r></w:p>';
            $xml .= '</w:tc>';

            // Indeks (averaged)
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
            $xml .= '<w:r><w:t>' . number_format($avgIndeks, 2, '.', '') . '</w:t></w:r>';
            $xml .= '</w:p></w:tc>';

            $xml .= '</w:tr>';
        }

        $xml .= '</w:tbl>';

        // Footer paragraph
        $avgIndex = $countIndices > 0 ? round($sumIndices / $countIndices, 2) : 0;
        $xml .= '<w:p>';
        $xml .= '<w:pPr><w:spacing w:before="100" w:after="200"/></w:pPr>';
        $xml .= '<w:r><w:t>Rata-rata Indeks Kepuasan: ' . number_format($avgIndex, 2, '.', '') . '</w:t></w:r>';
        $xml .= '</w:p>';

        return $xml;
    }

    /**
     * Generate GABUNGAN table as plain text (not XML)
     * This is more reliable than XML injection for GABUNGAN_TINGKAT_X placeholders
     * 
     * Kolom: Kode MK | Nama MK | Dosen | Rata-rata Indeks
     * - Dosen digabung dengan koma (,) jika ada lebih dari 1 dosen
     * - Indeks adalah rata-rata dari semua kuesioner dengan kode MK dan nama MK yang sama
     * - Data diambil langsung dari tabel kuesioner_uploads
     */
    private function generateGabunganTableAsText($kuesioneData, $tingkat)
    {
        $tingkatRoman = $this->numberToRoman($tingkat);

        // Group by kode_mk and nama_mk
        $grouped = [];
        foreach ($kuesioneData as $kuesioner) {
            $kode = trim($kuesioner->kode_matakuliah ?: '');
            $nama = trim($kuesioner->nama_matakuliah ?: '');
            
            // Use kode and nama as composite key
            $key = strtoupper($kode) . '|' . mb_strtolower($nama);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'kode' => $kode ?: '-',
                    'nama' => $nama ?: '-',
                    'dosens' => [],
                    'indices' => []
                ];
            }

            // Collect unique dosens
            $dosen = trim($kuesioner->dosen_pengampu ?: '');
            if ($dosen && !in_array($dosen, $grouped[$key]['dosens'])) {
                $grouped[$key]['dosens'][] = $dosen;
            }

            // Collect all indices for averaging
            if (is_numeric($kuesioner->index_kepuasan)) {
                $grouped[$key]['indices'][] = (float)$kuesioner->index_kepuasan;
            }
        }

        // Build text table
        $text = "\nPada tingkat {$tingkatRoman} terdapat " . count($grouped) . " kuesioner dengan detail sebagai berikut:\n\n";
        
        // Add table number
        $tableNum = ($tingkat * 2) + 1;
        $text .= "Tabel {$tableNum}. Matakuliah Mahasiswa Tingkat {$tingkatRoman}\n\n";

        // Table header with fixed widths for alignment
        $text .= str_pad("Kode MK", 15) . " | ";
        $text .= str_pad("Nama MK", 50) . " | ";
        $text .= str_pad("Dosen", 45) . " | ";
        $text .= str_pad("Indeks", 10) . "\n";
        $text .= str_repeat("-", 125) . "\n";

        // Data rows
        $sumIndices = 0;
        $countIndices = 0;

        foreach ($grouped as $group) {
            $kode = $group['kode'];
            $nama = $group['nama'];
            
            // Truncate nama if too long
            if (mb_strlen($nama) > 50) {
                $nama = mb_substr($nama, 0, 47) . '...';
            }

            // Combine dosens with comma
            $dosen = !empty($group['dosens']) ? implode(', ', $group['dosens']) : '-';
            
            // Truncate dosen if too long
            if (mb_strlen($dosen) > 45) {
                $dosen = mb_substr($dosen, 0, 42) . '...';
            }

            // Calculate average indeks from all kuesioner with same kode MK and nama MK
            $avgIndeks = 0;
            if (!empty($group['indices'])) {
                $avgIndeks = array_sum($group['indices']) / count($group['indices']);
                $sumIndices += array_sum($group['indices']);
                $countIndices += count($group['indices']);
            }

            $text .= str_pad($kode, 15) . " | ";
            $text .= str_pad($nama, 50) . " | ";
            $text .= str_pad($dosen, 45) . " | ";
            $text .= str_pad(number_format($avgIndeks, 2), 10, ' ', STR_PAD_LEFT) . "\n";
        }

        // Footer with average
        $avgIndex = $countIndices > 0 ? round($sumIndices / $countIndices, 2) : 0;
        $text .= "\n";
        $text .= "Rata-rata Indeks Kepuasan: " . number_format($avgIndex, 2) . "\n";

        return $text;
    }

    /**
     * Generate Word XML for a table from groups data
     * DEPRECATED: Replaced by generateHasilKuesioneTableXml and generateGabunganTableXml
     * Kept for backward compatibility only
     */
    private function generateWordTableXml($groups, $tingkat, $type = 'hasil')
    {
        // Only support 'hasil' type now - masukan/saran functionality removed
        $cols = ['Kode Matakuliah', 'Nama Matakuliah', 'Dosen Pengampu', 'Indeks Kepuasan'];

        $tingkatRoman = $this->numberToRoman($tingkat);
        $count = count($groups);

        $xml = '';

        // Paragraph before table
        $xml .= '<w:p>';
        $xml .= '<w:pPr><w:spacing w:after="100"/></w:pPr>';
        $xml .= '<w:r><w:t>Pada tingkat ' . $tingkatRoman . ' terdapat ' . $count . ' matakuliah dengan detail sebagai berikut:</w:t></w:r>';
        $xml .= '</w:p>';

        // Build table
        $colWidth = intval(9000 / count($cols));

        $xml .= '<w:tbl>';
        $xml .= '<w:tblPr>';
        $xml .= '<w:tblStyle w:val="TableGrid"/>';
        $xml .= '<w:tblW w:w="9000" w:type="dxa"/>';
        $xml .= '<w:tblBorders>';
        $xml .= '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '</w:tblBorders>';
        $xml .= '</w:tblPr>';

        $xml .= '<w:tblGrid>';
        foreach ($cols as $c) {
            $xml .= '<w:gridCol w:w="' . $colWidth . '"/>';
        }
        $xml .= '</w:tblGrid>';

        // Header row
        $xml .= '<w:tr>';
        foreach ($cols as $c) {
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
            $xml .= '<w:r><w:rPr><w:b/></w:rPr><w:t>' . $this->escapeXml($c) . '</w:t></w:r>';
            $xml .= '</w:p></w:tc>';
        }
        $xml .= '</w:tr>';

        // Data rows
        $sumAllIndices = 0;
        $countAllIndices = 0;
        foreach ($groups as $group) {
            $kode = $this->escapeXml($group['kode_matakuliah'] ?: '-');
            $nama = $this->escapeXml($group['nama_matakuliah'] ?: '-');
            $dosen = $this->escapeXml(!empty($group['dosen']) ? implode(', ', $group['dosen']) : '-');

            $xml .= '<w:tr>';

            // Column 1: Kode
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:r><w:t>' . $kode . '</w:t></w:r></w:p>';
            $xml .= '</w:tc>';

            // Column 2: Nama
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:r><w:t>' . $nama . '</w:t></w:r></w:p>';
            $xml .= '</w:tc>';

            // Column 3: Dosen
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:r><w:t>' . $dosen . '</w:t></w:r></w:p>';
            $xml .= '</w:tc>';

            // Column 4: Indeks Kepuasan
            $groupIndexCount = count($group['indices']);
            $groupAvgIndex = $groupIndexCount > 0 ? round(array_sum($group['indices']) / $groupIndexCount, 5) : 0;
            $sumAllIndices += array_sum($group['indices']);
            $countAllIndices += $groupIndexCount;

            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
            $xml .= '<w:r><w:t>' . number_format($groupAvgIndex, 5, '.', '') . '</w:t></w:r>';
            $xml .= '</w:p></w:tc>';

            $xml .= '</w:tr>';
        }

        $xml .= '</w:tbl>';

        // Footer paragraph
        $avgIndex = $countAllIndices > 0 ? round($sumAllIndices / $countAllIndices, 5) : 0;
        $xml .= '<w:p>';
        $xml .= '<w:pPr><w:spacing w:before="100" w:after="200"/></w:pPr>';
        $xml .= '<w:r><w:t>Rata Indeks Kepuasan: ' . number_format($avgIndex, 5, '.', '') . '</w:t></w:r>';
        $xml .= '</w:p>';

        return $xml;
    }

    /**
     * Generate Word XML fragment for PENDAHULUAN_RUANG_LINGKUP including
     * the introductory text and Tabel 1 (Skala Likert) as a real <w:tbl>.
     */
    private function generateRuangLingkupXml($laporan)
    {
        $xml = '';

        // Paragraph 1
        $xml .= '<w:p>';
        $xml .= '<w:pPr><w:spacing w:after="200"/></w:pPr>';
        $xml .= '<w:r><w:t xml:space="preserve">Yang menjadi responden pada survei ini adalah mahasiswa yang mengambil mata kuliah prodi, mata kuliah fakultas dan mata kuliah institut. Adapun pihak terkait yang kualitas layanannya dinilai pada survei ini yaitu dosen, teaching assistant yang berhubungan dengan mata kuliah.</w:t></w:r>';
        $xml .= '</w:p>';

        // Paragraph 2
        $xml .= '<w:p>';
        $xml .= '<w:pPr><w:spacing w:after="200"/></w:pPr>';
        $xml .= '<w:r><w:t xml:space="preserve">Kuesioner yang dibagikan terdiri dari 17 pertanyaan dengan kuesioner yang dibagikan memiliki 4 kriteria penilaian (skala likert) yaitu Sangat Setuju, Setuju, Cukup Setuju, dan Tidak Setuju. Agar mempermudah perhitungan kuesioner, kriteria Sangat Setuju, Setuju, Cukup Setuju, dan Tidak Setuju akan dihitung dan dilaporkan dalam bentuk indeks skala 4 dengan bobot yang mengikuti Tabel 1.</w:t></w:r>';
        $xml .= '</w:p>';

        // Table title
        $xml .= '<w:p>';
        $xml .= '<w:pPr><w:spacing w:before="200" w:after="100"/></w:pPr>';
        $xml .= '<w:r><w:rPr><w:b/></w:rPr><w:t>Tabel 1. Skala Likert Kuesioner</w:t></w:r>';
        $xml .= '</w:p>';

        // Build Tabel 1: skala likert
        $xml .= '<w:tbl>';
        $xml .= '<w:tblPr>';
        $xml .= '<w:tblStyle w:val="TableGrid"/>';
        $xml .= '<w:tblW w:w="9000" w:type="dxa"/>';
        $xml .= '<w:tblBorders>';
        $xml .= '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        $xml .= '</w:tblBorders>';
        $xml .= '</w:tblPr>';
        $xml .= '<w:tblGrid><w:gridCol w:w="4500"/><w:gridCol w:w="2250"/><w:gridCol w:w="2250"/></w:tblGrid>';

        // Header row
        $xml .= '<w:tr>';
        foreach (['Pernyataan', 'Kode', 'Skala'] as $header) {
            $xml .= '<w:tc>';
            $xml .= '<w:tcPr><w:tcW w:w="' . ($header === 'Pernyataan' ? '4500' : '2250') . '" w:type="dxa"/></w:tcPr>';
            $xml .= '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>';
            $xml .= '<w:r><w:rPr><w:b/></w:rPr><w:t>' . $this->escapeXml($header) . '</w:t></w:r>';
            $xml .= '</w:p></w:tc>';
        }
        $xml .= '</w:tr>';

        // Data rows
        $rows = [
            ['Tidak setuju (TS)', 'TS', '1'],
            ['Cukup Setuju (CS)', 'CS', '2'],
            ['Setuju (S)', 'S', '3'],
            ['Sangat Setuju (SS)', 'SS', '4'],
        ];
        foreach ($rows as $row) {
            $xml .= '<w:tr>';
            foreach ($row as $idx => $cell) {
                $width = $idx === 0 ? '4500' : '2250';
                $xml .= '<w:tc>';
                $xml .= '<w:tcPr><w:tcW w:w="' . $width . '" w:type="dxa"/></w:tcPr>';
                $xml .= '<w:p><w:r><w:t>' . $this->escapeXml($cell) . '</w:t></w:r></w:p>';
                $xml .= '</w:tc>';
            }
            $xml .= '</w:tr>';
        }
        $xml .= '</w:tbl>';

        return $xml;
    }

    private function escapeXml($s)
    {
        return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Normalize a Word template file so split placeholders are joined into single text runs.
     */
    private function normalizeTemplatePath($templatePath)
    {
        $tempPath = storage_path('app/temp_template_' . uniqid() . '.docx');
        copy($templatePath, $tempPath);

        $zip = new \ZipArchive();
        if ($zip->open($tempPath) !== true) {
            return $templatePath;
        }

        $xmlFiles = array_merge(['word/document.xml'], array_map(function ($i) {
            return 'word/header' . $i . '.xml';
        }, range(0, 9)), array_map(function ($i) {
            return 'word/footer' . $i . '.xml';
        }, range(0, 9)));

        foreach ($xmlFiles as $xmlFile) {
            if (!$zip->locateName($xmlFile)) {
                continue;
            }

            $xml = $zip->getFromName($xmlFile);
            if ($xml === false) {
                continue;
            }

            $normalizedXml = $this->normalizeTemplatePlaceholdersXml($xml);
            if ($normalizedXml !== $xml) {
                $zip->addFromString($xmlFile, $normalizedXml);
            }
        }

        $zip->close();
        return $tempPath;
    }

    /**
     * Normalize placeholder fragments in Word document XML.
     */
    private function normalizeTemplatePlaceholdersXml($xml)
    {
        $runs = [];
        preg_match_all('#<w:r[^>]*>.*?</w:r>#s', $xml, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $match) {
            $runs[] = [
                'text' => $match[0],
                'pos' => $match[1],
                'length' => strlen($match[0]),
            ];
        }

        if (empty($runs)) {
            return $xml;
        }

        $output = '';
        $lastPos = 0;
        $buffer = [];
        $bufferStart = null;

        foreach ($runs as $run) {
            if ($bufferStart === null) {
                if ($this->isPlaceholderStartRun($run['text'])) {
                    $buffer = [$run];
                    $bufferStart = $run['pos'];
                    continue;
                }

                $output .= substr($xml, $lastPos, $run['pos'] - $lastPos);
                $output .= $run['text'];
                $lastPos = $run['pos'] + $run['length'];
                continue;
            }

            $buffer[] = $run;
            $combinedText = '';
            foreach ($buffer as $bufferRun) {
                $combinedText .= $this->extractTextFromRun($bufferRun['text']);
            }
            $normalizedText = preg_replace('/\s+/', '', $combinedText);

            if ($this->isCompletedPlaceholderText($normalizedText)) {
                $output .= substr($xml, $lastPos, $bufferStart - $lastPos);
                $output .= '<w:r><w:t>{{' . $this->normalizePlaceholderText($normalizedText) . '}}</w:t></w:r>';
                $lastPos = $run['pos'] + $run['length'];
                $buffer = [];
                $bufferStart = null;
            }
        }

        if ($bufferStart !== null && !empty($buffer)) {
            $output .= substr($xml, $lastPos, $bufferStart - $lastPos);
            foreach ($buffer as $bufferRun) {
                $output .= $bufferRun['text'];
            }
            $lastPos = end($buffer)['pos'] + end($buffer)['length'];
        }

        $output .= substr($xml, $lastPos);
        return $output;
    }

    private function isPlaceholderStartRun($run)
    {
        return preg_match('#\{\{(?:HASIL_KUESIONER_TINGKAT_|GABUNGAN_TINGKAT_)#', $run);
    }

    private function extractTextFromRun($run)
    {
        preg_match_all('#<w:t[^>]*>(.*?)</w:t>#s', $run, $matches);
        return implode('', $matches[1]);
    }

    private function isCompletedPlaceholderText($text)
    {
        return preg_match('#^\{\{(?:HASIL_KUESIONER_TINGKAT_[A-Z]+|GABUNGAN_TINGKAT_[A-Z]+)\}\}$#', $text);
    }

    private function normalizePlaceholderText($text)
    {
        preg_match('#^\{\{((?:HASIL_KUESIONER_TINGKAT_|GABUNGAN_TINGKAT_)[A-Z]+)\}\}$#', $text, $matches);
        return $matches[1] ?? $text;
    }

    /**
     * Convert number to Roman numeral
     */
    private function numberToRoman($number)
    {
        $map = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];
        return $map[$number] ?? (string)$number;
    }

    /**
     * Format Ringkasan Eksekutif section
     */
    private function formatRingkasanEksekutif($ringkasan)
    {
        $text = "";

        if (isset($ringkasan['overview'])) {
            $text .= $ringkasan['overview'] . "\n\n";
        }

        if (!empty($ringkasan['highlight_positif'])) {
            $text .= "Highlight Positif:\n";
            foreach ($ringkasan['highlight_positif'] as $idx => $point) {
                $text .= ($idx + 1) . ". " . $point . "\n";
            }
            $text .= "\n";
        }

        if (!empty($ringkasan['highlight_negatif'])) {
            $text .= "Area Perhatian:\n";
            foreach ($ringkasan['highlight_negatif'] as $idx => $point) {
                $text .= ($idx + 1) . ". " . $point . "\n";
            }
            $text .= "\n";
        }

        if (isset($ringkasan['trend'])) {
            $text .= "Trend: " . $ringkasan['trend'] . "\n";
        }

        return trim($text);
    }

    /**
     * Format Statistik Utama section
     */
    private function formatStatistikUtama($stats)
    {
        $text = "STATISTIK UTAMA\n\n";
        $text .= "Total Kuesioner: " . ($stats['total_kuesioner'] ?? 0) . "\n";
        $text .= "Total Responden: " . ($stats['total_responden'] ?? 0) . "\n";
        $text .= "Indeks Kepuasan Rata-rata: " . ($stats['index_kepuasan_rata_rata'] ?? 0) . "\n";
        $text .= "Persen Kepuasan Rata-rata: " . ($stats['persen_kepuasan_rata_rata'] ?? 0) . "%\n";

        return $text;
    }

    /**
     * Format Analisis Per Kuesioner (table-like format)
     */
    private function formatAnalisisPerKuesioner($analisis)
    {
        if (empty($analisis)) return "Tidak ada data analisis.";

        $text = "ANALISIS PER KUESIONER\n\n";

        foreach ($analisis as $idx => $item) {
            $no = $idx + 1;
            $nama = $item['nama'] ?? $item['nama_kuesioner'] ?? '';
            $index = $item['index'] ?? $item['index_kepuasan'] ?? 0;
            $kategori = $item['kategori'] ?? $this->getKategoriKepuasan($index);
            $ringkasan = $item['ringkasan'] ?? '';

            $text .= "{$no}. {$nama}\n";
            $text .= "   Indeks Kepuasan: {$index} ({$kategori})\n";
            if ($ringkasan) {
                $text .= "   Ringkasan: {$ringkasan}\n";
            }
            $text .= "\n";
        }

        return $text;
    }

    /**
     * Format Top 5 list
     */
    private function formatTop5($items)
    {
        if (empty($items)) return "Tidak ada data.";

        $text = "";
        foreach ($items as $idx => $item) {
            $no = $idx + 1;
            $nama = $item['nama'] ?? $item['nama_kuesioner'] ?? '';
            $index = $item['index'] ?? $item['index_kepuasan'] ?? 0;
            $persen = isset($item['persen']) ? $item['persen'] : (($index / 4) * 100);

            $text .= "{$no}. {$nama} - Indeks: {$index} ({$persen}%)\n";
        }

        return $text;
    }

    /**
     * Format Rekomendasi list
     */
    private function formatRekomendasi($rekomendasi)
    {
        if (empty($rekomendasi)) return "Tidak ada rekomendasi.";

        $text = "";
        foreach ($rekomendasi as $idx => $rek) {
            $no = $idx + 1;

            if (is_array($rek)) {
                $rekText = $rek['rekomendasi'] ?? '';
                $prioritas = $rek['prioritas'] ?? 'Medium';
                $timeline = $rek['timeline'] ?? '';

                $text .= "{$no}. {$rekText}";
                if ($prioritas) $text .= " [Prioritas: {$prioritas}]";
                if ($timeline) $text .= " (Timeline: {$timeline})";
                $text .= "\n";
            } else {
                $text .= "{$no}. {$rek}\n";
            }
        }

        return $text;
    }

    /**
     * Format array to numbered list text
     */
    private function formatListToText($items, $numbered = true)
    {
        if (empty($items)) return "";

        $text = "";
        foreach ($items as $idx => $item) {
            if ($numbered) {
                $text .= ($idx + 1) . ". " . $item . "\n";
            } else {
                $text .= "• " . $item . "\n";
            }
        }

        return $text;
    }

    /**
     * Format arrays for Word document (recursive)
     */
    private function formatArrayForWord($data)
    {
        if (is_string($data)) return $data;
        if (is_numeric($data)) return (string)$data;

        if (is_array($data)) {
            // Check if it's a simple list
            if (array_keys($data) === range(0, count($data) - 1)) {
                // Indexed array - format as list
                return $this->formatListToText($data);
            } else {
                // Associative array - format as key: value
                $text = "";
                foreach ($data as $key => $value) {
                    $text .= ucfirst($key) . ": ";
                    if (is_array($value)) {
                        $text .= "\n" . $this->formatArrayForWord($value);
                    } else {
                        $text .= $value . "\n";
                    }
                }
                return $text;
            }
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get kategori kepuasan based on index
     */
    private function getKategoriKepuasan($index)
    {
        if ($index > 3.5) return "Sangat Baik";
        if ($index >= 3.0) return "Baik";
        if ($index >= 2.5) return "Cukup";
        return "Kurang";
    }

    /**
     * Resolve laporan periode context from periode or periode_akademik relationship
     */
    private function resolveLaporanPeriodeContext($laporan)
    {
        $context = [
            'original' => $laporan->periode,
            'periodeIso' => null,
            'periodeObj' => null,
            'year' => null,
            'month' => null,
            'semester' => null,
            'semesterText' => null,
            'tahunAkademik' => null,
            'jenisUjian' => null,
            'reportDate' => null,
        ];

        // Prefer periode_akademik relationship when available
        if ($laporan->periode_akademik_id && $laporan->periodeAkademik) {
            $akademik = $laporan->periodeAkademik;
            if ($akademik->start_date) {
                $context['periodeObj'] = Carbon::parse($akademik->start_date);
            } elseif ($akademik->end_date) {
                $context['periodeObj'] = Carbon::parse($akademik->end_date);
            } else {
                $tahunAjaran = (string) $akademik->tahun_ajaran;
                $semester = (int) $akademik->semester;

                if ($semester === 1) {
                    $year = is_numeric($tahunAjaran) && strlen($tahunAjaran) === 4 ? (int) $tahunAjaran : Carbon::now()->year;
                    $context['periodeObj'] = Carbon::create($year, 8, 1);
                } else {
                    if (preg_match('/^(\d{4})\\/(\d{4})$/', $tahunAjaran, $matches)) {
                        $year = (int) $matches[2];
                    } elseif (preg_match('/^(\d{2})\\/(\d{2})$/', $tahunAjaran, $matches)) {
                        $year = 2000 + (int) $matches[2];
                    } else {
                        $year = is_numeric($tahunAjaran) && strlen($tahunAjaran) === 4 ? (int) $tahunAjaran + 1 : Carbon::now()->year;
                    }
                    $context['periodeObj'] = Carbon::create($year, 2, 1);
                }
            }

            $context['semester'] = (int) $akademik->semester;
            $context['semesterText'] = strtoupper($akademik->semester_label ?? ($context['semester'] === 1 ? 'GANJIL' : 'GENAP'));
            $context['tahunAkademik'] = is_numeric($akademik->tahun_ajaran) && strlen($akademik->tahun_ajaran) === 4
                ? $akademik->tahun_ajaran . '/' . ((int) $akademik->tahun_ajaran + 1)
                : $akademik->tahun_ajaran;
        }

        // Fallback to periode string if needed
        if (!$context['periodeObj'] && !empty($laporan->periode)) {
            if (preg_match('/^(\d{4})-(\d{2})$/', $laporan->periode, $matches)) {
                $context['periodeObj'] = Carbon::createFromFormat('Y-m', $laporan->periode);
            } elseif (preg_match('/\b(GANJIL|GENAP)\b/i', $laporan->periode, $matches)) {
                $semester = strtoupper($matches[1]) === 'GANJIL' ? 1 : 2;
                $year = null;

                if (preg_match('/(\d{4})/', $laporan->periode, $yearMatch)) {
                    $year = (int) $yearMatch[1];
                } elseif (preg_match('/(\d{2})\\/(\d{2})/', $laporan->periode, $yearMatch)) {
                    $year = 2000 + (int) $yearMatch[2];
                }

                if ($year === null) {
                    $year = Carbon::now()->year;
                }

                if ($semester === 1) {
                    $context['periodeObj'] = Carbon::create($year, 8, 1);
                } else {
                    $context['periodeObj'] = Carbon::create($year, 2, 1);
                }

                $context['semester'] = $semester;
                $context['semesterText'] = $semester === 1 ? 'GANJIL' : 'GENAP';
                $context['tahunAkademik'] = $semester === 1 ? $year . '/' . ($year + 1) : ($year - 1) . '/' . $year;
            }
        }

        if (!$context['periodeObj']) {
            $context['periodeObj'] = Carbon::now();
        }

        $context['year'] = $context['periodeObj']->year;
        $context['month'] = $context['periodeObj']->month;

        if (!$context['semester']) {
            $context['semester'] = $context['month'] <= 6 ? 2 : 1;
            $context['semesterText'] = $context['semester'] === 1 ? 'GANJIL' : 'GENAP';
        }

        if (!$context['tahunAkademik']) {
            $context['tahunAkademik'] = $context['semester'] === 1
                ? $context['year'] . '/' . ($context['year'] + 1)
                : ($context['year'] - 1) . '/' . $context['year'];
        }

        $context['periodeIso'] = $context['periodeObj']->format('Y-m');
        $context['reportDate'] = $context['periodeObj']->copy()->addMonth()->day(10);

        $context['jenisUjian'] = $laporan->tipe_laporan ??
            (($context['month'] >= 3 && $context['month'] <= 4) || ($context['month'] >= 10 && $context['month'] <= 11)
                ? 'UTS'
                : 'UAS');

        return $context;
    }

    /**
     * Get tahun akademik from periode string
     */
    private function getTahunAkademik($periode)
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $periode, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            return $month <= 6 ? ($year - 1) . '/' . $year : $year . '/' . ($year + 1);
        }

        if (preg_match('/\b(GANJIL|GENAP)\b/i', $periode, $matches)) {
            $semester = strtoupper($matches[1]) === 'GANJIL' ? 1 : 2;
            if (preg_match('/(\d{4})/', $periode, $yearMatch)) {
                $year = (int) $yearMatch[1];
            } elseif (preg_match('/(\d{2})\/(\d{2})/', $periode, $yearMatch)) {
                $year = 2000 + (int) $yearMatch[1];
            } else {
                $year = Carbon::now()->year;
            }
            return $semester === 1 ? $year . '/' . ($year + 1) : ($year - 1) . '/' . $year;
        }

        return Carbon::now()->year . '/' . (Carbon::now()->year + 1);
    }

    /**
     * Get semester from periode string
     */
    private function getSemester($periode)
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $periode, $matches)) {
            $month = (int) $matches[2];
            return $month <= 6 ? 'Genap' : 'Ganjil';
        }

        if (preg_match('/\b(GANJIL|GENAP)\b/i', $periode, $matches)) {
            return strtoupper($matches[1]) === 'GANJIL' ? 'Ganjil' : 'Genap';
        }

        return Carbon::now()->month <= 6 ? 'Genap' : 'Ganjil';
    }

    /**
     * Generate Word document from scratch (no template)
     * Query database langsung untuk data kuesioner
     */
    private function generateWordFromScratch($laporan)
    {
        try {
            Log::info('Generating Word from scratch - querying database', [
                'laporan_id' => $laporan->id
            ]);

            // Query database untuk data kuesioner
            $userId = $laporan->user_id;
            $periodeContext = $this->resolveLaporanPeriodeContext($laporan);
            $semester = $periodeContext['semester'];
            $jenisUjian = $periodeContext['jenisUjian'];

            // Get user's prodi
            $user = \App\Models\User::find($userId);
            $prodiKode = $user && $user->prodi ? $user->prodi->kode_prodi : null;
            $prodiNama = $user && $user->prodi ? $user->prodi->nama_prodi : 'Program Studi';

            // Query kuesioner uploads
            $uploads = \App\Models\KuesioneUpload::query()
                ->where('semester', $semester)
                ->when($jenisUjian, function($q) use ($jenisUjian) {
                    // Filter by jenis_kuesioner (UTS or UAS)
                    $q->where('jenis_kuesioner', 'like', '%' . $jenisUjian . '%');
                })
                ->when($prodiKode, function($q) use ($prodiKode) {
                    $q->whereHas('user', function($uq) use ($prodiKode) {
                        $uq->whereHas('prodi', function($pq) use ($prodiKode) {
                            $pq->where('kode_prodi', $prodiKode);
                        });
                    });
                })
                ->with('user.prodi')
                ->orderBy('tingkat', 'asc')
                ->orderBy('nama_matakuliah', 'asc')
                ->get();

            Log::info('Query completed', [
                'total_uploads' => $uploads->count(),
                'jenis_ujian_filter' => $jenisUjian
            ]);

            // Initialize PHPWord
            $phpWord = new PhpWord();

            // Define styles
            $phpWord->addTitleStyle(1, ['size' => 16, 'bold' => true]);
            $phpWord->addTitleStyle(2, ['size' => 14, 'bold' => true]);
            $phpWord->addTitleStyle(3, ['size' => 12, 'bold' => true]);

            $section = $phpWord->addSection();

            // HEADER/TITLE
            $periodeContext = $this->resolveLaporanPeriodeContext($laporan);
            $periodeObj = $periodeContext['periodeObj'];
            $bulan = $periodeObj->locale('id')->translatedFormat('F');
            $tahun = $periodeObj->year;
            $semesterText = $periodeContext['semesterText'];
            $tahunAkademik = $periodeContext['tahunAkademik'];

            $section->addText(
                "LAPORAN HASIL KEPUASAN MAHASISWA",
                ['size' => 16, 'bold' => true],
                ['alignment' => 'center']
            );
            $section->addText(
                "Semester {$semesterText} {$tahunAkademik}",
                ['size' => 14, 'bold' => true],
                ['alignment' => 'center']
            );
            $section->addText(
                "Program Studi {$prodiNama}",
                ['size' => 12],
                ['alignment' => 'center']
            );
            $section->addTextBreak(2);

            // I. PENDAHULUAN
            $section->addTitle("I. PENDAHULUAN", 1);

            $section->addTitle("a. Tujuan", 2);
            $section->addText("Survei ini bertujuan untuk mengevaluasi mata kuliah pada semester {$semesterText} {$tahunAkademik} di lingkungan {$prodiNama} Fakultas Vokasi Institut Teknologi Del.");
            $section->addTextBreak();

            $section->addTitle("b. Waktu Pelaksanaan", 2);
            $section->addText("Penyebaran kuesioner evaluasi mata kuliah dilaksanakan pada bulan {$bulan} {$tahun}. Penyebaran kuesioner dibagi menjadi 2 tahap yaitu pembagian pertama dilakukan pada minggu ke-14 dan pembagian kedua dilakukan pada minggu ke-15. Kuesioner evaluasi mata kuliah ini dilaksanakan pada semua mata kuliah dengan menyebarkan kuesioner online melalui website cis.del.ac.id.");
            $section->addTextBreak();

            $section->addTitle("c. Ruang Lingkup", 2);
            $section->addText("Yang menjadi responden pada survei ini adalah mahasiswa yang mengambil mata kuliah prodi, mata kuliah fakultas dan mata kuliah institut. Adapun pihak terkait yang kualitas layanannya dinilai pada survei ini yaitu dosen, teaching assistant yang berhubungan dengan mata kuliah.");
            $section->addText("Kuesioner yang dibagikan terdiri dari 17 pertanyaan dengan kuesioner yang dibagikan memiliki 4 kriteria penilaian (skala likert) yaitu Sangat Setuju, Setuju, Cukup Setuju, dan Tidak Setuju. Agar mempermudah perhitungan kuesioner, kriteria Sangat Setuju Setuju, Cukup Setuju, dan Tidak Setuju akan dihitung dan dilaporkan dalam bentuk indeks skala 4 dengan bobot yang mengikuti Tabel 1.");
            $section->addTextBreak();

            // Tabel 1: Skala Likert
            $section->addText("Tabel 1. Skala Likert Kuesioner", ['bold' => true]);
            $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
            $table->addRow();
            $table->addCell(4000)->addText('Pernyataan', ['bold' => true]);
            $table->addCell(2000)->addText('Kode', ['bold' => true]);
            $table->addCell(2000)->addText('Skala', ['bold' => true]);

            $table->addRow();
            $table->addCell(4000)->addText('Tidak setuju (TS)');
            $table->addCell(2000)->addText('TS');
            $table->addCell(2000)->addText('1');

            $table->addRow();
            $table->addCell(4000)->addText('Cukup Setuju (CS)');
            $table->addCell(2000)->addText('CS');
            $table->addCell(2000)->addText('2');

            $table->addRow();
            $table->addCell(4000)->addText('Setuju (S)');
            $table->addCell(2000)->addText('S');
            $table->addCell(2000)->addText('3');

            $table->addRow();
            $table->addCell(4000)->addText('Sangat Setuju (SS)');
            $table->addCell(2000)->addText('SS');
            $table->addCell(2000)->addText('4');

            $section->addTextBreak(2);

            // II. HASIL KUESIONER - Group by Tingkat
            $section->addTitle("II. HASIL KUESIONER", 1);

            $dataByTingkat = $uploads->groupBy(function($item) {
                $tingkat = $item->tingkat;
                if (is_numeric($tingkat)) {
                    return (int)$tingkat;
                }
                return $this->extractTingkatFromKode($item->kode_matakuliah);
            });

            $tabelCounter = 2; // Start from Table 2

            for ($tingkatNum = 1; $tingkatNum <= 4; $tingkatNum++) {
                $tingkatRoman = $this->numberToRoman($tingkatNum);
                $kuesioneData = $dataByTingkat->get($tingkatNum, collect());

                $section->addTitle("{$tingkatRoman}. Tingkat {$tingkatRoman}", 2);

                if ($kuesioneData->isNotEmpty()) {
                    $count = $kuesioneData->count();
                    $section->addText("Pada tingkat {$tingkatRoman} terdapat {$count} matakuliah dengan detail sebagai berikut:");
                    $section->addTextBreak();

                    // Tabel Hasil Kuesioner
                    $section->addText("Tabel {$tabelCounter}. Matakuliah Mahasiswa Tingkat {$tingkatRoman}", ['bold' => true]);
                    $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
                    $table->addRow();
                    $table->addCell(2000)->addText('Kode MK', ['bold' => true]);
                    $table->addCell(3000)->addText('Nama MK', ['bold' => true]);
                    $table->addCell(3000)->addText('Dosen', ['bold' => true]);
                    $table->addCell(2000)->addText('Indeks', ['bold' => true]);

                    $totalIndex = 0;
                    foreach ($kuesioneData as $kuesioner) {
                        $table->addRow();
                        $table->addCell(2000)->addText($kuesioner->kode_matakuliah ?? '-');
                        $table->addCell(3000)->addText($kuesioner->nama_matakuliah ?? '-');
                        $table->addCell(3000)->addText($kuesioner->dosen_pengampu ?? '-');
                        $table->addCell(2000)->addText((string)($kuesioner->index_kepuasan ?? 0));
                        $totalIndex += $kuesioner->index_kepuasan ?? 0;
                    }

                    $avgIndex = round($totalIndex / $count, 5);
                    $section->addTextBreak();
                    $section->addText("Rata Indeks Kepuasan: {$avgIndex}");
                    $section->addTextBreak(2);

                    $tabelCounter++;

                    // Tabel Masukan/Saran
                    $section->addText("Adapun masukan/saran untuk perbaikan mata kuliah ini dapat dilihat pada Tabel {$tabelCounter}:");
                    $section->addTextBreak();
                    $section->addText("Tabel {$tabelCounter}. Masukan/saran setiap Matakuliah", ['bold' => true]);

                    $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
                    $table->addRow();
                    $table->addCell(2000)->addText('Kode MK', ['bold' => true]);
                    $table->addCell(3000)->addText('Nama MK', ['bold' => true]);
                    $table->addCell(3000)->addText('Dosen', ['bold' => true]);
                    $table->addCell(4000)->addText('Masukan/Saran', ['bold' => true]);

                    foreach ($kuesioneData as $kuesioner) {
                        $masukanSaran = '-';
                        if (!empty($kuesioner->hasil_analisis)) {
                            $analisis = is_string($kuesioner->hasil_analisis)
                                ? json_decode($kuesioner->hasil_analisis, true)
                                : $kuesioner->hasil_analisis;

                            if (isset($analisis['rekomendasi']) && is_array($analisis['rekomendasi'])) {
                                $masukanSaran = implode('; ', array_slice($analisis['rekomendasi'], 0, 2));
                            } elseif (isset($analisis['ringkasan'])) {
                                $masukanSaran = $analisis['ringkasan'];
                            }
                        }

                        if ($masukanSaran === '-' || empty(trim($masukanSaran))) {
                            if ($kuesioner->index_kepuasan >= 3.5) {
                                $masukanSaran = 'Kepuasan mahasiswa dalam kategori sangat baik.';
                            } elseif ($kuesioner->index_kepuasan >= 3.0) {
                                $masukanSaran = 'Kepuasan mahasiswa dalam kategori baik.';
                            } else {
                                $masukanSaran = 'Perlu peningkatan kualitas pembelajaran.';
                            }
                        }

                        $table->addRow();
                        $table->addCell(2000)->addText($kuesioner->kode_matakuliah ?? '-');
                        $table->addCell(3000)->addText($kuesioner->nama_matakuliah ?? '-');
                        $table->addCell(3000)->addText($kuesioner->dosen_pengampu ?? '-');
                        $table->addCell(4000)->addText($masukanSaran);
                    }

                    $section->addTextBreak(2);
                    $tabelCounter++;

                } else {
                    $section->addText("Tidak ada data kuesioner untuk Tingkat {$tingkatRoman}");
                    $section->addTextBreak(2);
                }
            }

            // III. KESIMPULAN
            $section->addTitle("III. KESIMPULAN DAN SARAN", 1);

            $indexKepuasan = $uploads->count() > 0 ? round($uploads->avg('index_kepuasan'), 2) : 0;

            $section->addText("- Adanya matakuliah yang dihitung berdasarkan kode matakuliah, sebaiknya tetap dipisahkan sesuai prodi meskipun memiliki kode matakuliah yang sama.");
            $section->addTextBreak();

            $section->addText("- Indeks Kepuasan semua matakuliah di prodi adalah {$indexKepuasan}. " .
                ($indexKepuasan >= 2.8
                    ? "Nilai kepuasan ini telah melampaui indikator kepuasan pembelajaran mahasiswa minimum yaitu 2.8."
                    : "Nilai kepuasan ini belum mencapai indikator kepuasan pembelajaran mahasiswa minimum yaitu 2.8. Perlu dilakukan perbaikan."));

            $section->addTextBreak(3);

            // Tanggal dan Tempat
            $tanggal = Carbon::now()->locale('id')->translatedFormat('d F Y');
            $section->addText("Laguboti, {$tanggal}", null, ['alignment' => 'right']);

            // Save Word file
            $fileName = 'laporan_kuesioner_' . $laporan->periode . '_' . time() . '.docx';
            $filePath = 'laporan_kuesioner/' . $fileName;
            $fullPath = storage_path('app/' . $filePath);

            // Create directory if not exists
            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($fullPath);

            // Update laporan
            $laporan->file_word = $filePath;
            $laporan->save();

            Log::info('✅ Word document generated from scratch with real data', [
                'laporan_id' => $laporan->id,
                'file_path' => $filePath,
                'total_kuesioner' => $uploads->count()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Failed to generate Word document from scratch', [
                'laporan_id' => $laporan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Replace a placeholder with an XML table (like LaporanArtefakService does)
     * Uses replaceXmlBlock to inject Word XML table directly
     */
    private function replaceWithTable(\PhpOffice\PhpWord\TemplateProcessor $tp, string $placeholder, string $tableXml)
    {
        try {
            if (method_exists($tp, 'replaceXmlBlock')) {
                // Replace the entire paragraph containing the token with the table XML
                $tp->replaceXmlBlock($placeholder, $tableXml, 'w:p');

                Log::info('Table replaced successfully using replaceXmlBlock', [
                    'placeholder' => $placeholder,
                    'xml_preview' => substr($tableXml, 0, 200)
                ]);
            } else {
                // Fallback if method doesn't exist
                $tp->setValue($placeholder, '[Tabel tidak dapat dirender - method tidak tersedia]');

                Log::warning('replaceXmlBlock method not available', [
                    'placeholder' => $placeholder
                ]);
            }
        } catch (\Exception $e) {
            Log::error('replaceWithTable failed', [
                'placeholder' => $placeholder,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'xml_length' => strlen($tableXml)
            ]);

            // Try to set error message as placeholder value
            try {
                $tp->setValue($placeholder, '[Tabel ' . $placeholder . ' tidak dapat dirender: ' . $e->getMessage() . ']');
            } catch (\Exception $e2) {
                Log::error('Even setValue failed', [
                    'placeholder' => $placeholder,
                    'error' => $e2->getMessage()
                ]);
            }
        }
    }

    /**
     * Replace table tokens (like __TABLE_HASIL_TINGKAT_I__) with actual Word XML tables
     * Post-processing step after template save
     * DEPRECATED: Now using replaceWithTable() before save instead (like LaporanArtefakService)
     */
    private function replaceTableTokensWithXml($docxPath, $replacements)
    {
        if (empty($replacements)) {
            return true;
        }

        try {
            Log::info('Starting table token replacement', [
                'docx_path' => $docxPath,
                'tokens_count' => count($replacements)
            ]);

            // Open DOCX as ZIP
            $zip = new \ZipArchive();
            if ($zip->open($docxPath) !== true) {
                Log::error('Could not open DOCX for table replacement', ['path' => $docxPath]);
                return false;
            }

            // Read document.xml
            $documentXml = $zip->getFromName('word/document.xml');
            if ($documentXml === false) {
                $zip->close();
                Log::error('Could not read document.xml from DOCX');
                return false;
            }

            Log::info('Original document.xml length', ['length' => strlen($documentXml)]);

            // Replace each token with its XML - use simple string replacement
            $replacedCount = 0;
            foreach ($replacements as $token => $xmlContent) {
                if (strpos($documentXml, $token) !== false) {
                    // Simple approach: Replace token directly with XML content
                    // Find the paragraph containing the token and replace it
                    $beforeToken = strstr($documentXml, $token, true);
                    $afterTokenTemp = strstr($documentXml, $token);

                    if ($beforeToken !== false && $afterTokenTemp !== false) {
                        // Find the start of paragraph before token
                        $lastPStart = strrpos($beforeToken, '<w:p>');
                        if ($lastPStart === false) {
                            $lastPStart = strrpos($beforeToken, '<w:p ');
                        }

                        // Find the end of paragraph after token
                        $afterToken = substr($afterTokenTemp, strlen($token));
                        $pEndPos = strpos($afterToken, '</w:p>');

                        if ($lastPStart !== false && $pEndPos !== false) {
                            // Extract parts
                            $before = substr($documentXml, 0, $lastPStart);
                            $after = substr($afterToken, $pEndPos + 6); // 6 = length of '</w:p>'

                            // Rebuild document with XML table
                            $documentXml = $before . $xmlContent . $after;
                            $replacedCount++;

                            Log::info('Replaced token successfully', [
                                'token' => $token,
                                'xml_length' => strlen($xmlContent)
                            ]);
                        } else {
                            Log::warning('Could not find paragraph boundaries for token', [
                                'token' => $token
                            ]);
                        }
                    }
                } else {
                    Log::warning('Token not found in document', ['token' => $token]);
                }
            }

            Log::info('Modified document.xml length', [
                'length' => strlen($documentXml),
                'replaced_count' => $replacedCount
            ]);

            // Write back to ZIP
            $zip->addFromString('word/document.xml', $documentXml);
            $zip->close();

            Log::info('Table tokens successfully replaced', [
                'docx_path' => $docxPath,
                'replacements_count' => $replacedCount
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to replace table tokens', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
