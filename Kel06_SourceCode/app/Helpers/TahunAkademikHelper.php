<?php

namespace App\Helpers;

use Carbon\Carbon;

class TahunAkademikHelper
{
    /**
     * Calculate TAHUN_AKADEMIK based on a given date
     * 
     * Rules:
     * - Feb - Jul (month 2-7): Semester Genap → (year-1)/year
     * - Aug - Jan (month 8-12, 1): Semester Ganjil → year/(year+1)
     * 
     * Examples:
     * - Feb 2026 → 2025/2026
     * - Jul 2026 → 2025/2026
     * - Aug 2026 → 2026/2027
     * - Jan 2027 → 2026/2027
     * 
     * @param Carbon|string|null $date Date to calculate from (defaults to now)
     * @return string Tahun akademik in format "YYYY/YYYY"
     */
    public static function calculate($date = null): string
    {
        if ($date === null) {
            $date = Carbon::now();
        } elseif (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $month = $date->month;
        $year = $date->year;

        // Januari (bulan 1) masih termasuk semester ganjil tahun sebelumnya
        if ($month === 1) {
            // Januari 2027 → 2026/2027
            return ($year - 1) . '/' . $year;
        }
        
        // Februari - Juli (bulan 2-7): Semester Genap
        if ($month >= 2 && $month <= 7) {
            // Feb-Jul 2026 → 2025/2026
            return ($year - 1) . '/' . $year;
        }
        
        // Agustus - Desember (bulan 8-12): Semester Ganjil
        // Aug-Dec 2026 → 2026/2027
        return $year . '/' . ($year + 1);
    }

    /**
     * Calculate TAHUN_AKADEMIK from periode_mulai and periode_akhir
     * Uses the start date of the period
     * 
     * @param Carbon|string $periodeMulai Start date of period
     * @param Carbon|string|null $periodeAkhir End date of period (optional)
     * @return string Tahun akademik in format "YYYY/YYYY"
     */
    public static function fromPeriode($periodeMulai, $periodeAkhir = null): string
    {
        return self::calculate($periodeMulai);
    }

    /**
     * Get semester type from a given date
     * 
     * @param Carbon|string|null $date Date to check
     * @return string "ganjil" or "genap"
     */
    public static function getSemester($date = null): string
    {
        if ($date === null) {
            $date = Carbon::now();
        } elseif (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $month = $date->month;

        // Januari atau Agustus-Desember = Ganjil
        if ($month === 1 || ($month >= 8 && $month <= 12)) {
            return 'ganjil';
        }
        
        // Februari-Juli = Genap
        return 'genap';
    }

    /**
     * Get semester label from a given date
     * 
     * @param Carbon|string|null $date Date to check
     * @return string "Semester Ganjil" or "Semester Genap"
     */
    public static function getSemesterLabel($date = null): string
    {
        $semester = self::getSemester($date);
        return $semester === 'ganjil' ? 'Semester Ganjil' : 'Semester Genap';
    }
}
