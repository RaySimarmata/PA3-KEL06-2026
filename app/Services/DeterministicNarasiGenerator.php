<?php

namespace App\Services;

/**
 * ROUND 3 FIX: Deterministic Narasi Generator
 * 
 * Generate narasi laporan TANPA AI untuk mencapai 100% faithfulness
 * AI masih hallucinate meskipun temperature 0.1 dan prompt ketat
 * Solusi: Template-based generation yang 100% deterministik
 */
class DeterministicNarasiGenerator
{
    /**
     * Generate narasi berdasarkan data numerik
     * ZERO HALLUCINATION - semua output traced langsung ke input data
     */
    public static function generate($dataArtefak)
    {
        $rpsBelum    = $dataArtefak['total_rps'] - $dataArtefak['rps_uploaded'];
        $materiBelum = $dataArtefak['total_materi'] - $dataArtefak['materi_uploaded'];
        
        return [
            'hasil_pemeriksaan' => self::generateHasilPemeriksaan($dataArtefak, $rpsBelum, $materiBelum),
            'analisis_ketercapaian' => self::generateAnalisisKetercapaian($dataArtefak, $rpsBelum, $materiBelum),
            'tindak_lanjut' => self::generateTindakLanjut($dataArtefak, $rpsBelum, $materiBelum),
            'kesimpulan_penutup' => self::generateKesimpulan($dataArtefak, $rpsBelum, $materiBelum),
        ];
    }
    
    private static function generateHasilPemeriksaan($data, $rpsBelum, $materiBelum)
    {
        $p1 = "Berdasarkan hasil pemeriksaan pada semester {$data['semester']} Tahun Ajaran {$data['tahun_ajaran']}, ";
        $p1 .= "dari {$data['total_rps']} mata kuliah yang dimonitoring di program studi {$data['prodi']}, ";
        $p1 .= "sebanyak {$data['rps_uploaded']} mata kuliah telah mengunggah RPS ";
        
        if ($rpsBelum > 0) {
            $p1 .= "dan {$rpsBelum} mata kuliah belum mengunggah RPS.";
        } else {
            $p1 .= "sehingga seluruh mata kuliah telah memenuhi kewajiban upload RPS.";
        }
        
        $p2 = "Untuk kelengkapan materi perkuliahan, ";
        $p2 .= "dari {$data['total_materi']} mata kuliah yang dimonitoring, ";
        $p2 .= "sebanyak {$data['materi_uploaded']} mata kuliah telah melengkapi materi per minggu ";
        
        if ($materiBelum > 0) {
            $p2 .= "dan {$materiBelum} mata kuliah belum melengkapi materi.";
        } else {
            $p2 .= "sehingga seluruh mata kuliah telah memenuhi kelengkapan materi.";
        }
        
        $p3 = "";
        if ($rpsBelum === 0 && $materiBelum === 0) {
            $p3 = "Status kelengkapan artefak perkuliahan menunjukkan seluruh mata kuliah telah memenuhi standar yang ditetapkan.";
        } elseif ($rpsBelum > 0 || $materiBelum > 0) {
            $items = [];
            if ($rpsBelum > 0) $items[] = "RPS";
            if ($materiBelum > 0) $items[] = "materi perkuliahan";
            $p3 = "Masih terdapat beberapa mata kuliah yang perlu melengkapi " . implode(" dan ", $items) . ".";
        }
        
        return $p3 ? ($p1 . "\n\n" . $p2 . "\n\n" . $p3) : ($p1 . "\n\n" . $p2);
    }

    
    private static function generateAnalisisKetercapaian($data, $rpsBelum, $materiBelum)
    {
        $p1 = "Persentase kelengkapan RPS mencapai {$data['rps_percentage']}% ";
        $p1 .= "dan materi perkuliahan mencapai {$data['materi_percentage']}%. ";
        
        $rpsOk = $data['rps_percentage'] >= 80;
        $matOk = $data['materi_percentage'] >= 80;
        
        if ($rpsOk && $matOk) {
            $p1 .= "Capaian ini menunjukkan tingkat kepatuhan dosen yang baik terhadap kewajiban kelengkapan artefak perkuliahan.";
        } elseif ($rpsOk || $matOk) {
            $p1 .= "Capaian ini menunjukkan masih ada ruang untuk peningkatan menuju target ideal 100%.";
        } else {
            $p1 .= "Capaian ini menunjukkan perlu adanya upaya lebih intensif untuk meningkatkan kelengkapan dokumen.";
        }
        
        $p2 = "";
        if ($data['rps_percentage'] < 100 || $data['materi_percentage'] < 100) {
            $gap = [];
            if ($data['rps_percentage'] < 100) {
                $gapRPS = 100 - $data['rps_percentage'];
                $gap[] = "RPS masih kurang " . round($gapRPS, 1) . "%";
            }
            if ($data['materi_percentage'] < 100) {
                $gapMat = 100 - $data['materi_percentage'];
                $gap[] = "materi masih kurang " . round($gapMat, 1) . "%";
            }
            $p2 = "Target ketercapaian 100% belum terpenuhi, dimana " . implode(" dan ", $gap) . " dari target ideal. ";
            $p2 .= "GKM perlu melakukan tindak lanjut untuk mendorong kepatuhan dosen pada periode berikutnya.";
        } else {
            $p2 = "Target ketercapaian 100% telah terpenuhi untuk kedua aspek monitoring. ";
            $p2 .= "GKM perlu mempertahankan capaian ini pada periode mendatang.";
        }
        
        return $p1 . "\n\n" . $p2;
    }
    
    private static function generateTindakLanjut($data, $rpsBelum, $materiBelum)
    {
        $p1 = "";
        if ($rpsBelum > 0 || $materiBelum > 0) {
            $p1 = "GKM perlu mengirimkan reminder kepada dosen yang belum melengkapi RPS dan materi perkuliahan. ";
            $p1 .= "Koordinasi dengan ketua program studi diperlukan untuk memastikan kelengkapan dokumen pada periode berikutnya. ";
            $p1 .= "Penetapan deadline yang jelas dan monitoring berkala menjadi kunci untuk meningkatkan tingkat kepatuhan.";
        } else {
            $p1 = "Meskipun semua mata kuliah telah lengkap, GKM perlu mempertahankan sistem monitoring yang ada. ";
            $p1 .= "Koordinasi dengan ketua program studi tetap diperlukan untuk memastikan konsistensi kelengkapan dokumen. ";
            $p1 .= "Apresiasi kepada dosen yang telah memenuhi kewajiban dapat meningkatkan motivasi di periode mendatang.";
        }
        
        $p2 = "Langkah monitoring lanjutan yang perlu dilakukan meliputi pemeriksaan berkala terhadap kualitas konten dokumen yang telah diunggah, ";
        $p2 .= "evaluasi kesesuaian antara RPS dan pelaksanaan perkuliahan, serta dokumentasi kendala yang dihadapi dosen ";
        $p2 .= "untuk perbaikan sistem di masa mendatang.";
        
        return $p1 . "\n\n" . $p2;
    }
    
    private static function generateKesimpulan($data, $rpsBelum, $materiBelum)
    {
        $k = "Monitoring artefak perkuliahan semester {$data['semester']} Tahun Ajaran {$data['tahun_ajaran']} ";
        $k .= "pada program studi {$data['prodi']} menunjukkan ";
        
        if ($data['rps_percentage'] >= 80 && $data['materi_percentage'] >= 80) {
            $k .= "{$data['rps_uploaded']} dari {$data['total_rps']} mata kuliah telah lengkap untuk RPS ({$data['rps_percentage']}%) ";
            $k .= "dan {$data['materi_uploaded']} dari {$data['total_materi']} mata kuliah telah lengkap untuk materi ({$data['materi_percentage']}%). ";
            $k .= "Capaian ini menunjukkan komitmen yang baik dari dosen dalam memenuhi kelengkapan dokumen akademik.";
        } else {
            $k .= "{$data['rps_uploaded']} dari {$data['total_rps']} mata kuliah telah lengkap untuk RPS ({$data['rps_percentage']}%) ";
            $k .= "dan {$data['materi_uploaded']} dari {$data['total_materi']} mata kuliah telah lengkap untuk materi ({$data['materi_percentage']}%). ";
            $k .= "Diperlukan upaya lebih lanjut untuk mencapai target kelengkapan 100%.";
        }
        
        return $k;
    }
}
