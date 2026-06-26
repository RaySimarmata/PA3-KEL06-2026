<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestDynamicTahunAjaran extends Command
{
    protected $signature = 'test:dynamic-tahun {--year= : Test with specific year}';
    protected $description = 'Test dynamic tahun ajaran generation for Monitoring RPS';

    public function handle()
    {
        $this->info("=== TEST DYNAMIC TAHUN AJARAN ===\n");

        // Test with current year
        $currentYear = (int) date('Y');
        $this->testYearRange($currentYear, "Current Year");

        // Test with specific year if provided
        if ($this->option('year')) {
            $testYear = (int) $this->option('year');
            $this->testYearRange($testYear, "Custom Test Year");
        } else {
            // Test multiple scenarios
            $this->newLine();
            $this->info("Testing Multiple Scenarios:");
            $this->newLine();

            $testYears = [2025, 2029, 2030, 2034, 2035, 2040, 2050];
            foreach ($testYears as $year) {
                $this->testYearRange($year, "Test: {$year}");
            }
        }

        $this->newLine();
        $this->info("=== EXPLANATION ===");
        $this->line("Formula: baseYear = floor(currentYear / 5) * 5");
        $this->line("Range: baseYear to baseYear + 5 (6 years total)");
        $this->newLine();
        
        $this->table(
            ['Period', 'Base Year', 'Range'],
            [
                ['2025-2029', '2025', '2025, 2026, 2027, 2028, 2029, 2030'],
                ['2030-2034', '2030', '2030, 2031, 2032, 2033, 2034, 2035'],
                ['2035-2039', '2035', '2035, 2036, 2037, 2038, 2039, 2040'],
                ['2040-2044', '2040', '2040, 2041, 2042, 2043, 2044, 2045'],
            ]
        );

        return 0;
    }

    private function testYearRange($year, $label)
    {
        $baseYear = floor($year / 5) * 5;
        $years = [];
        
        for ($i = 0; $i <= 5; $i++) {
            $years[] = $baseYear + $i;
        }

        $this->line("<fg=cyan>{$label}: {$year}</>");
        $this->line("  Base Year: <fg=green>{$baseYear}</>");
        $this->line("  Range: <fg=yellow>" . implode(', ', $years) . "</>");
        $this->newLine();
    }
}
