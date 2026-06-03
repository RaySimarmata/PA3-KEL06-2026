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
     * Generate Word document from laporan data
     * ALWAYS generate from scratch untuk avoid placeholder issues
     */
    public function generateWordDocument($laporan)
    {
        Log::info('Generating Word document for laporan', [
            'laporan_id' => $laporan->id,
            'periode' => $laporan->periode
        ]);
        
        // ALWAYS use generateWordFromScratch (no template placeholder issues)
        return $this->generateWordFromScratch($laporan);
    }

    /**
     * Generate Word from template with placeholders filled from AI results
     */
    private function generateWordFromTemplate($laporan, $template)
    {
        try {
            $templatePath = storage_path('app/' . $template->file_path);
            
            if (!file_exists($templatePath)) {
                Log::warning('Template file not found', ['path' => $templatePath]);
                return $this->generateWordFromScratch($laporan);
            }

            Log::info('Generating Word from template', [
                'template_id' => $template->id,
                'template_name' => $template->nama_template,
                'laporan_id' => $laporan->id
            ]);

            // Load template
            $templateProcessor = new TemplateProcessor($templatePath);
            
            // Set macro chars based on template format (default {{VAR}})
            $templateProcessor->setMacroChars('{{', '}}');
            
            // Get available variables in template
            $availableVars = $templateProcessor->getVariables();
            Log::info('Template variables found', ['variables' => $availableVars]);

            // Extract placeholder values from hasil_laporan
            $placeholders = $this->extractPlaceholdersFromLaporan($laporan);
            
            Log::info('Extracted placeholders from laporan', [
                'count' => count($placeholders),
                'keys' => array_keys($placeholders),
                'sample_values' => array_map(function($v) {
                    return is_string($v) ? substr($v, 0, 100) : gettype($v);
                }, array_slice($placeholders, 0, 5))
            ]);

            // Fill placeholders
            $filledCount = 0;
            foreach ($placeholders as $key => $value) {
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
                            'value_length' => strlen($value)
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to fill placeholder', [
                            'key' => $key,
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    Log::debug('Placeholder not in template', ['key' => $key]);
                }
            }

            Log::info('Placeholder filling completed', [
                'filled' => $filledCount,
                'total_placeholders' => count($placeholders),
                'template_vars' => count($availableVars)
            ]);

            // Save Word file
            $fileName = 'laporan_kuesioner_' . $laporan->periode . '_' . time() . '.docx';
            $filePath = 'laporan_kuesioner/' . $fileName;
            $fullPath = storage_path('app/' . $filePath);

            // Create directory if not exists
            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save to temporary location first
            $tempPath = storage_path('app/temp_' . uniqid() . '.docx');
            $templateProcessor->saveAs($tempPath);

            // Move to final location
            if (!rename($tempPath, $fullPath)) {
                throw new \Exception('Failed to save Word document');
            }

            // Update laporan
            $laporan->file_word = $filePath;
            $laporan->save();

            Log::info('Word document generated from template', [
                'laporan_id' => $laporan->id,
                'file_path' => $filePath,
                'filled_placeholders' => $filledCount
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to generate Word from template', [
                'laporan_id' => $laporan->id,
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback ke simple generation
            return $this->generateWordFromScratch($laporan);
        }
    }

    /**
     * Extract placeholder values from hasil_laporan JSON
     * Maps AI results to template placeholder names
     */
    private function extractPlaceholdersFromLaporan($laporan)
    {
        $placeholders = [];
        $hasilLaporan = $laporan->hasil_laporan ?? [];

        // Basic metadata placeholders
        $periodeObj = Carbon::createFromFormat('Y-m', $laporan->periode);
        $year = $periodeObj->year;
        $month = $periodeObj->month;
        
        // Determine semester and tahun akademik
        if ($month <= 6) {
            $semesterText = 'GENAP';
            $tahunAkademik = ($year - 1) . '/' . $year;
        } else {
            $semesterText = 'GANJIL';
            $tahunAkademik = $year . '/' . ($year + 1);
        }
        
        // Determine UTS/UAS
        $jenisUjian = '';
        if (($month >= 3 && $month <= 4) || ($month >= 10 && $month <= 11)) {
            $jenisUjian = 'UTS';
        } elseif (($month >= 5 && $month <= 6) || ($month >= 12 || $month <= 1)) {
            $jenisUjian = 'UAS';
        }
        
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
        $reportDate = $periodeObj->copy()->addMonth()->day(10);
        $placeholders['TANGGAL'] = $reportDate->locale('id')->translatedFormat('d F Y');

        // Pendahuluan Section
        $placeholders['PENDAHULUAN_TUJUAN'] = $this->generatePendahuluanTujuan($laporan);
        $placeholders['PENDAHULUAN_WAKTU'] = $this->generatePendahuluanWaktu($laporan);
        $placeholders['PENDAHULUAN_RUANG_LINGKUP'] = $this->generatePendahuluanRuangLingkup($laporan);

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

        // Generate Hasil Kuesioner per Tingkat based on database data
        $this->generateHasilKuesionePerTingkat($placeholders, $hasilLaporan, $laporan);

        return $placeholders;
    }

    /**
     * Generate Pendahuluan - Tujuan
     */
    private function generatePendahuluanTujuan($laporan)
    {
        $periodeObj = Carbon::createFromFormat('Y-m', $laporan->periode);
        $year = $periodeObj->year;
        $month = $periodeObj->month;
        
        // Determine tahun akademik and semester
        if ($month <= 6) {
            $semester = 'GENAP';
            $tahunAkademik = ($year - 1) . '/' . $year;
        } else {
            $semester = 'GANJIL';
            $tahunAkademik = $year . '/' . ($year + 1);
        }
        
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
        $periodeObj = Carbon::createFromFormat('Y-m', $laporan->periode);
        $bulan = $periodeObj->locale('id')->translatedFormat('F');
        $tahun = $periodeObj->year;
        
        // Determine if UTS or UAS based on month
        $month = $periodeObj->month;
        $jenisUjian = ($month >= 3 && $month <= 5) || ($month >= 10 && $month <= 12) ? 'UTS dan UAS' : 'evaluasi';
        
        $text = "Penyebaran kuesioner evaluasi mata kuliah dilaksanakan pada bulan {$bulan} {$tahun}. ";
        $text .= "Penyebaran kuesioner dibagi menjadi 2 tahap yaitu pembagian pertama dilakukan pada minggu ke-14 dan pembagian kedua dilakukan pada minggu ke-15. ";
        $text .= "Kuesioner evaluasi mata kuliah ini dilaksanakan pada semua mata kuliah dengan menyebarkan kuesioner online melalui website cis.del.ac.id.";
        
        return $text;
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
        $periode = $laporan->periode;
        $userId = $laporan->user_id;
        
        // Determine semester
        $year = (int) substr($periode, 0, 4);
        $month = (int) substr($periode, 5, 2);
        $semester = ($month <= 6) ? 2 : 1;
        
        // Get user's prodi
        $user = \App\Models\User::find($userId);
        $prodiKode = $user && $user->prodi ? $user->prodi->kode_prodi : null;
        
        // Query uploads
        $uploads = \App\Models\KuesioneUpload::query()
            ->where('semester', $semester)
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
        // Get periode and user info from laporan to query kuesioner_uploads
        $periode = $laporan->periode;
        $userId = $laporan->user_id;
        
        // Determine semester from periode
        $year = (int) substr($periode, 0, 4);
        $month = (int) substr($periode, 5, 2);
        
        if ($month <= 6) {
            $semester = 2; // Genap
        } else {
            $semester = 1; // Ganjil
        }
        
        // Get user's prodi
        $user = \App\Models\User::find($userId);
        $prodiKode = $user && $user->prodi ? $user->prodi->kode_prodi : null;
        
        Log::info('Generating Hasil Kuesioner per Tingkat from database', [
            'periode' => $periode,
            'semester' => $semester,
            'prodi_kode' => $prodiKode,
            'user_id' => $userId
        ]);
        
        // Query kuesioner_uploads directly
        $uploads = \App\Models\KuesioneUpload::query()
            ->where('semester', $semester)
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
            'sample_tingkat_values' => $uploads->pluck('tingkat')->unique()->toArray()
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
            $masukanKey = "MASUKAN_SARAN_TINGKAT_" . $tingkatRoman;
            
            $kuesioneData = $dataByTingkat->get($i, collect());
            
            Log::info("Processing Tingkat {$tingkatRoman}", [
                'count' => $kuesioneData->count(),
                'key' => $i
            ]);
            
            if ($kuesioneData->isNotEmpty()) {
                // Generate Hasil Kuesioner table
                $hasilText = $this->generateHasilKuesioneTableFromUploads($kuesioneData, $i);
                $placeholders[$hasilKey] = $hasilText;
                
                // Generate Masukan/Saran table
                $masukanText = $this->generateMasukanSaranTableFromUploads($kuesioneData, $i);
                $placeholders[$masukanKey] = $masukanText;
                
                Log::info("Generated content for Tingkat {$tingkatRoman}", [
                    'hasil_length' => strlen($hasilText),
                    'masukan_length' => strlen($masukanText)
                ]);
            } else {
                $placeholders[$hasilKey] = "\nTidak ada data kuesioner untuk Tingkat {$tingkatRoman}";
                $placeholders[$masukanKey] = "";
                
                Log::info("No data for Tingkat {$tingkatRoman}");
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

    /**
     * Generate Hasil Kuesioner Table from KuesioneUpload models
     */
    private function generateHasilKuesioneTableFromUploads($kuesioneData, $tingkat)
    {
        $tingkatRoman = $this->numberToRoman($tingkat);
        
        $text = "\nPada tingkat {$tingkatRoman} terdapat " . $kuesioneData->count() . " matakuliah dengan detail sebagai berikut:\n\n";
        $text .= "Tabel " . (($tingkat * 2) - 1) . ". Matakuliah Mahasiswa Tingkat {$tingkatRoman}\n\n";
        $text .= "| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Indeks Kepuasan |\n";
        $text .= "|-----------------|-----------------|----------------|------------------|\n";
        
        $totalIndex = 0;
        foreach ($kuesioneData as $kuesioner) {
            $kodeMk = $kuesioner->kode_matakuliah ?? '-';
            $namaMk = $kuesioner->nama_matakuliah ?? '-';
            $dosen = $kuesioner->dosen_pengampu ?? '-';
            $index = $kuesioner->index_kepuasan ?? 0;
            
            $totalIndex += $index;
            
            $text .= "| {$kodeMk} | {$namaMk} | {$dosen} | {$index} |\n";
        }
        
        // Calculate average
        $count = $kuesioneData->count();
        $avgIndex = $count > 0 ? round($totalIndex / $count, 5) : 0;
        
        $text .= "\nRata Indeks Kepuasan: {$avgIndex}\n\n";
        
        return $text;
    }

    /**
     * Generate Masukan/Saran Table from KuesioneUpload models
     */
    private function generateMasukanSaranTableFromUploads($kuesioneData, $tingkat)
    {
        $tingkatRoman = $this->numberToRoman($tingkat);
        
        $text = "Adapun masukan/saran untuk perbaikan mata kuliah ini dapat dilihat pada Tabel " . ($tingkat * 2) . ":\n\n";
        $text .= "Tabel " . ($tingkat * 2) . ". Masukan/saran setiap Matakuliah\n\n";
        $text .= "| Kode Matakuliah | Nama Matakuliah | Dosen Pengampu | Masukan/Saran |\n";
        $text .= "|-----------------|-----------------|----------------|---------------|\n";
        
        foreach ($kuesioneData as $kuesioner) {
            $kodeMk = $kuesioner->kode_matakuliah ?? '-';
            $namaMk = $kuesioner->nama_matakuliah ?? '-';
            $dosen = $kuesioner->dosen_pengampu ?? '-';
            
            // Extract masukan/saran from hasil_analisis
            $masukanSaran = '-';
            if (!empty($kuesioner->hasil_analisis)) {
                $analisis = is_string($kuesioner->hasil_analisis) 
                    ? json_decode($kuesioner->hasil_analisis, true) 
                    : $kuesioner->hasil_analisis;
                    
                if (isset($analisis['rekomendasi']) && is_array($analisis['rekomendasi'])) {
                    $masukanSaran = implode('; ', array_slice($analisis['rekomendasi'], 0, 2));
                } elseif (isset($analisis['area_perbaikan']) && is_array($analisis['area_perbaikan'])) {
                    $masukanSaran = implode('; ', array_slice($analisis['area_perbaikan'], 0, 2));
                } elseif (isset($analisis['ringkasan'])) {
                    $masukanSaran = $analisis['ringkasan'];
                }
            }
            
            // Jika masih kosong, gunakan default berdasarkan index_kepuasan
            if ($masukanSaran === '-' || empty(trim($masukanSaran))) {
                if ($kuesioner->index_kepuasan >= 3.5) {
                    $masukanSaran = 'Kepuasan mahasiswa dalam kategori sangat baik.';
                } elseif ($kuesioner->index_kepuasan >= 3.0) {
                    $masukanSaran = 'Kepuasan mahasiswa dalam kategori baik.';
                } else {
                    $masukanSaran = 'Perlu peningkatan kualitas pembelajaran.';
                }
            }
            
            $text .= "| {$kodeMk} | {$namaMk} | {$dosen} | {$masukanSaran} |\n";
        }
        
        $text .= "\n";
        
        return $text;
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
     * Get tahun akademik from periode (YYYY-MM)
     */
    private function getTahunAkademik($periode)
    {
        $year = (int) substr($periode, 0, 4);
        $month = (int) substr($periode, 5, 2);
        
        if ($month <= 6) {
            return ($year - 1) . '/' . $year;
        } else {
            return $year . '/' . ($year + 1);
        }
    }

    /**
     * Get semester from periode (YYYY-MM)
     */
    private function getSemester($periode)
    {
        $month = (int) substr($periode, 5, 2);
        return $month <= 6 ? 'Genap' : 'Ganjil';
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
            $periode = $laporan->periode;
            $userId = $laporan->user_id;
            
            // Determine semester
            $year = (int) substr($periode, 0, 4);
            $month = (int) substr($periode, 5, 2);
            $semester = ($month <= 6) ? 2 : 1;
            
            // Get user's prodi
            $user = \App\Models\User::find($userId);
            $prodiKode = $user && $user->prodi ? $user->prodi->kode_prodi : null;
            $prodiNama = $user && $user->prodi ? $user->prodi->nama_prodi : 'Program Studi';
            
            // Query kuesioner uploads
            $uploads = \App\Models\KuesioneUpload::query()
                ->where('semester', $semester)
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
                'total_uploads' => $uploads->count()
            ]);
            
            // Initialize PHPWord
            $phpWord = new PhpWord();
            
            // Define styles
            $phpWord->addTitleStyle(1, ['size' => 16, 'bold' => true]);
            $phpWord->addTitleStyle(2, ['size' => 14, 'bold' => true]);
            $phpWord->addTitleStyle(3, ['size' => 12, 'bold' => true]);
            
            $section = $phpWord->addSection();

            // HEADER/TITLE
            $periodeObj = Carbon::createFromFormat('Y-m', $periode);
            $bulan = $periodeObj->locale('id')->translatedFormat('F');
            $tahun = $periodeObj->year;
            $semesterText = ($semester == 1) ? 'GANJIL' : 'GENAP';
            $tahunAkademik = ($semester == 1) ? $tahun . '/' . ($tahun + 1) : ($tahun - 1) . '/' . $tahun;
            
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
}
