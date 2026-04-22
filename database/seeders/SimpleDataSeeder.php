<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ajaran;
use App\Models\Prodi;
use App\Models\RPS;
use App\Models\Materi;
use App\Models\Matakuliah;
use App\Models\Dosen;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SimpleDataSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            // 1. Seed Ajaran jika belum ada
            if (Ajaran::count() == 0) {
                $prodi = Prodi::first();
                if ($prodi) {
                    Ajaran::create([
                        'prodi_id' => $prodi->id,
                        'tahun_ajaran' => '2024/2025',
                        'semester' => 1,
                        'tanggal_mulai' => Carbon::create(2024, 9, 1),
                        'tanggal_akhir' => Carbon::create(2025, 1, 31),
                        'status' => 'aktif',
                    ]);
                    $this->command->info('Ajaran seeded successfully!');
                }
            }

            // 2. Seed RPS jika belum ada
            if (RPS::count() == 0) {
                $matakuliah = Matakuliah::limit(5)->get();
                $dosen = Dosen::limit(3)->get();
                $ajaran = Ajaran::first();
                
                if ($matakuliah->isNotEmpty() && $dosen->isNotEmpty() && $ajaran) {
                    foreach ($matakuliah as $mk) {
                        $hasFile = rand(1, 100) <= 80;
                        
                        RPS::create([
                            'matakuliah_id' => $mk->id,
                            'ajaran_id' => $ajaran->id,
                            'dosen_id' => $dosen->random()->id,
                            'deskripsi' => 'RPS untuk mata kuliah ' . $mk->nama_mk,
                            'capaian_pembelajaran' => 'Mahasiswa mampu memahami konsep ' . $mk->nama_mk,
                            'strategi_pembelajaran' => 'Ceramah, diskusi, praktikum',
                            'penugasan' => 'Tugas individu dan kelompok',
                            'penilaian' => 'UTS 30%, UAS 40%, Tugas 30%',
                            'file_rps' => $hasFile ? 'uploads/rps/rps_' . $mk->id . '.pdf' : null,
                            'status_rps' => $hasFile ? 'sudah_divalidasi' : 'draft',
                            'tanggal_upload' => $hasFile ? Carbon::now()->subDays(rand(1, 30)) : null,
                            'created_at' => Carbon::now()->subDays(rand(1, 60)),
                            'updated_at' => Carbon::now()->subDays(rand(1, 30)),
                        ]);
                    }
                    $this->command->info('RPS seeded successfully!');
                }
            }

            // 3. Seed Materi jika belum ada
            if (Materi::count() == 0) {
                $rps = RPS::limit(3)->get();
                $matakuliah = Matakuliah::limit(3)->get();
                $dosen = Dosen::limit(3)->get();
                
                if ($rps->isNotEmpty() && $matakuliah->isNotEmpty() && $dosen->isNotEmpty()) {
                    foreach ($matakuliah as $mk) {
                        $rpsItem = $rps->where('matakuliah_id', $mk->id)->first();
                        
                        for ($i = 1; $i <= 5; $i++) {
                            $hasFile = rand(1, 100) <= 75;
                            
                            Materi::create([
                                'rps_id' => $rpsItem ? $rpsItem->id : null,
                                'matakuliah_id' => $mk->id,
                                'dosen_id' => $dosen->random()->id,
                                'judul_materi' => "Pertemuan {$i}: Materi " . $mk->nama_mk,
                                'deskripsi_materi' => "Materi pembelajaran pertemuan {$i}",
                                'file_materi' => $hasFile ? "uploads/materi/{$mk->kode_mk}/pertemuan_{$i}.pdf" : null,
                                'jenis_file' => $hasFile ? 'pdf' : null,
                                'status_upload_materi' => $hasFile ? 'uploaded' : 'not_uploaded',
                                'tanggal_upload_materi' => $hasFile ? Carbon::now()->subDays(rand(1, 30)) : null,
                                'lokasi_upload' => $hasFile ? 'server' : null,
                                'created_at' => Carbon::now()->subDays(rand(1, 60)),
                                'updated_at' => Carbon::now()->subDays(rand(1, 30)),
                            ]);
                        }
                    }
                    $this->command->info('Materi seeded successfully!');
                }
            }

            DB::commit();
            $this->command->info('All seeders completed successfully!');
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error('Seeder failed: ' . $e->getMessage());
        }
    }
}