<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateLogEmailProdi extends Command
{
    protected $signature = 'log-email:update-prodi';
    protected $description = 'Update prodi_id in log_email table based on dosen email';

    public function handle()
    {
        $this->info('========================================');
        $this->info('Update Log Email Prodi ID');
        $this->info('========================================');
        $this->newLine();

        try {
            // Check if prodi_id column exists
            $this->info('[1/4] Checking if prodi_id column exists...');
            
            if (!Schema::hasColumn('log_email', 'prodi_id')) {
                $this->warn('Column prodi_id does not exist. Adding column...');
                
                DB::statement('
                    ALTER TABLE log_email 
                    ADD COLUMN prodi_id BIGINT UNSIGNED NULL AFTER reminder_id
                ');
                
                DB::statement('
                    ALTER TABLE log_email 
                    ADD CONSTRAINT log_email_prodi_id_foreign 
                    FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE CASCADE
                ');
                
                $this->info('✓ Column prodi_id added successfully');
            } else {
                $this->info('✓ Column prodi_id already exists');
            }
            
            $this->newLine();

            // Count records without prodi_id
            $this->info('[2/4] Checking log_email records without prodi_id...');
            $countBefore = DB::table('log_email')
                ->whereNull('prodi_id')
                ->count();
            
            $this->info("Found {$countBefore} records without prodi_id");
            $this->newLine();

            if ($countBefore == 0) {
                $this->info('✓ All records already have prodi_id');
                $this->newLine();
                return 0;
            }

            // Update records
            $this->info('[3/4] Updating log_email records with prodi_id...');
            
            $updated = DB::table('log_email as le')
                ->join('dosen as d', 'le.penerima_email', '=', 'd.kontak_email')
                ->whereNull('le.prodi_id')
                ->update(['le.prodi_id' => DB::raw('d.prodi_id')]);

            $this->info("✓ Updated {$updated} records");
            $this->newLine();

            // Show statistics
            $this->info('[4/4] Statistics by Prodi');
            $this->newLine();

            $stats = DB::table('log_email as le')
                ->leftJoin('prodi as p', 'le.prodi_id', '=', 'p.id')
                ->select(
                    DB::raw('COALESCE(p.nama_prodi, "NULL") as nama_prodi'),
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN le.status_pengiriman = "success" THEN 1 ELSE 0 END) as success'),
                    DB::raw('SUM(CASE WHEN le.status_pengiriman = "failed" THEN 1 ELSE 0 END) as failed'),
                    DB::raw('SUM(CASE WHEN le.status_pengiriman = "pending" THEN 1 ELSE 0 END) as pending')
                )
                ->groupBy('p.nama_prodi')
                ->get();

            $headers = ['Prodi', 'Total', 'Success', 'Failed', 'Pending'];
            $rows = [];

            foreach ($stats as $stat) {
                $rows[] = [
                    $stat->nama_prodi,
                    $stat->total,
                    $stat->success,
                    $stat->failed,
                    $stat->pending
                ];
            }

            $this->table($headers, $rows);
            $this->newLine();

            $this->info('========================================');
            $this->info('✓ UPDATE COMPLETED SUCCESSFULLY');
            $this->info('========================================');
            $this->newLine();

            return 0;

        } catch (\Exception $e) {
            $this->error('✗ ERROR: ' . $e->getMessage());
            $this->newLine();
            return 1;
        }
    }
}
