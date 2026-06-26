<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugJadwalDosen extends Command
{
    protected $signature = 'debug:jadwal-dosen {--semester=} {--tahun=}';
    protected $description = 'Debug jadwal dosen data for monitoring RPS';

    public function handle()
    {
        $semester = $this->option('semester') ?? '1';
        $tahun = $this->option('tahun') ?? '2025';

        $this->info("=== DEBUG JADWAL DOSEN ===");
        $this->info("Semester: {$semester}");
        $this->info("Tahun Ajaran: {$tahun}");
        $this->newLine();

        // 1. Check total records
        $totalRecords = DB::table('jadwal_dosen')->count();
        $this->info("Total records in jadwal_dosen: {$totalRecords}");
        $this->newLine();

        // 2. Check unique semester values
        $this->info("=== UNIQUE SEMESTER VALUES ===");
        $semesterValues = DB::table('jadwal_dosen')
            ->select('semester', DB::raw('COUNT(*) as count'))
            ->groupBy('semester')
            ->get();
        
        foreach ($semesterValues as $item) {
            $this->line("  Semester: '{$item->semester}' - Count: {$item->count}");
        }
        $this->newLine();

        // 3. Check unique tahun_ajaran values
        $this->info("=== UNIQUE TAHUN AJARAN VALUES ===");
        $tahunValues = DB::table('jadwal_dosen')
            ->select('tahun_ajaran', DB::raw('COUNT(*) as count'))
            ->groupBy('tahun_ajaran')
            ->get();
        
        foreach ($tahunValues as $item) {
            $this->line("  Tahun: '{$item->tahun_ajaran}' - Count: {$item->count}");
        }
        $this->newLine();

        // 4. Check with filter
        $semesterVariations = [];
        if ($semester == '1') {
            $semesterVariations = ['1', 'Ganjil', 'ganjil', 'GANJIL'];
        } elseif ($semester == '2') {
            $semesterVariations = ['2', 'Genap', 'genap', 'GENAP'];
        }

        $this->info("=== RECORDS WITH FILTER ===");
        $this->line("Semester variations: " . implode(', ', $semesterVariations));
        
        $filtered = DB::table('jadwal_dosen as jd')
            ->leftJoin('dosenn as d', 'jd.pegawai_id', '=', 'd.pegawai_id')
            ->whereIn('jd.semester', $semesterVariations)
            ->where(function ($q) use ($tahun) {
                $q->where('jd.tahun_ajaran', $tahun)
                  ->orWhere('jd.tahun_ajaran', 'LIKE', $tahun . '%')
                  ->orWhere('jd.tahun_ajaran', 'LIKE', '%' . $tahun)
                  ->orWhere('jd.tahun_ajaran', 'LIKE', '%' . $tahun . '%');
            })
            ->select(
                'jd.kode_mk',
                'jd.pegawai_id',
                'd.nama',
                'jd.semester',
                'jd.tahun_ajaran'
            )
            ->get();

        $this->info("Filtered count: {$filtered->count()}");
        $this->newLine();

        if ($filtered->count() > 0) {
            $this->info("=== SAMPLE FILTERED DATA (First 10) ===");
            $this->table(
                ['Kode MK', 'Pegawai ID', 'Nama Dosen', 'Semester', 'Tahun Ajaran'],
                $filtered->take(10)->map(function($item) {
                    return [
                        $item->kode_mk,
                        $item->pegawai_id,
                        $item->nama ?? '-',
                        $item->semester,
                        $item->tahun_ajaran
                    ];
                })->toArray()
            );

            // Group by kode_mk
            $this->newLine();
            $this->info("=== GROUPED BY KODE_MK (First 5) ===");
            $grouped = $filtered->groupBy('kode_mk')->take(5);
            foreach ($grouped as $kodeMk => $items) {
                $this->line("Kode MK: {$kodeMk}");
                $this->line("  Dosen: " . $items->pluck('nama')->filter()->unique()->implode(', '));
                $this->line("  Count: " . $items->count());
            }
        } else {
            $this->warn("No data found with current filter!");
            
            // Try without semester filter
            $this->newLine();
            $this->info("=== TRYING WITHOUT SEMESTER FILTER ===");
            $withoutSemester = DB::table('jadwal_dosen as jd')
                ->leftJoin('dosenn as d', 'jd.pegawai_id', '=', 'd.pegawai_id')
                ->where(function ($q) use ($tahun) {
                    $q->where('jd.tahun_ajaran', $tahun)
                      ->orWhere('jd.tahun_ajaran', 'LIKE', $tahun . '%')
                      ->orWhere('jd.tahun_ajaran', 'LIKE', '%' . $tahun)
                      ->orWhere('jd.tahun_ajaran', 'LIKE', '%' . $tahun . '%');
                })
                ->count();
            
            $this->line("Count without semester filter: {$withoutSemester}");
            
            if ($withoutSemester > 0) {
                $sample = DB::table('jadwal_dosen')
                    ->where('tahun_ajaran', 'LIKE', '%' . $tahun . '%')
                    ->take(5)
                    ->get(['kode_mk', 'pegawai_id', 'semester', 'tahun_ajaran']);
                
                $this->table(
                    ['Kode MK', 'Pegawai ID', 'Semester', 'Tahun Ajaran'],
                    $sample->map(fn($i) => [$i->kode_mk, $i->pegawai_id, $i->semester, $i->tahun_ajaran])->toArray()
                );
            }
        }

        return 0;
    }
}
