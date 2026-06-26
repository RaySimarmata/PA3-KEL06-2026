<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LogEmail;
use Carbon\Carbon;

class CheckLLMUsage extends Command
{
    protected $signature = 'llm:usage {--days=7 : Jumlah hari untuk cek usage}';
    protected $description = 'Cek penggunaan LLM untuk monitoring free tier';

    public function handle()
    {
        $days = $this->option('days');
        $startDate = Carbon::now()->subDays($days);

        $this->info("📊 Monitoring LLM Usage - {$days} hari terakhir");
        $this->info("Periode: {$startDate->format('d M Y')} - " . Carbon::now()->format('d M Y'));
        $this->newLine();

        // Hitung total email yang dikirim (asumsi 1 email = 1 LLM call)
        $totalEmails = LogEmail::where('created_at', '>=', $startDate)->count();
        
        // Hitung per hari
        $perDay = $totalEmails / $days;
        
        // Groq free tier limit
        $dailyLimit = 14400;
        $minuteLimit = 30;
        
        // Persentase usage
        $usagePercent = ($perDay / $dailyLimit) * 100;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Emails', number_format($totalEmails)],
                ['Rata-rata per Hari', number_format($perDay, 1)],
                ['Daily Limit (Groq)', number_format($dailyLimit)],
                ['Usage', number_format($usagePercent, 2) . '%'],
                ['Sisa Quota per Hari', number_format($dailyLimit - $perDay)],
            ]
        );

        $this->newLine();

        // Status
        if ($usagePercent < 10) {
            $this->info('✅ Status: SANGAT AMAN - Usage sangat rendah');
        } elseif ($usagePercent < 50) {
            $this->info('✅ Status: AMAN - Masih jauh dari limit');
        } elseif ($usagePercent < 80) {
            $this->warn('⚠️  Status: PERHATIAN - Mendekati 80% limit');
        } else {
            $this->error('❌ Status: BAHAYA - Hampir mencapai limit!');
            $this->warn('Pertimbangkan untuk menggunakan fallback template lebih sering');
        }

        $this->newLine();
        $this->info('💡 Tips: Groq free tier = 14,400 requests/day (gratis selamanya)');
        $this->info('📖 Baca: GROQ_FREE_TIER_GUIDE.md untuk info lengkap');

        return 0;
    }
}
