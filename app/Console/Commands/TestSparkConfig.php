<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestSparkConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spark:test-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Spark and Python configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=================================');
        $this->info('SPARK CONFIGURATION TEST');
        $this->info('=================================');
        
        // Check Python
        $this->info("\n--- Python Configuration ---");
        
        $pythonFromEnv = env('PYTHON_EXECUTABLE', 'python');
        $this->line("Python from .env: {$pythonFromEnv}");
        
        if (file_exists($pythonFromEnv)) {
            $this->info("✓ Python executable found: {$pythonFromEnv}");
            
            // Get Python version
            exec("\"{$pythonFromEnv}\" --version 2>&1", $pythonVersion);
            $this->line("Version: " . implode(', ', $pythonVersion));
        } else {
            $this->error("✗ Python NOT found at: {$pythonFromEnv}");
            
            // Try to find Python in PATH
            exec("where python 2>&1", $pythonPaths, $returnCode);
            
            if ($returnCode === 0 && !empty($pythonPaths)) {
                $this->warn("\nPython ditemukan di PATH:");
                foreach ($pythonPaths as $path) {
                    $this->line("  - " . trim($path));
                }
                $this->warn("\nUpdate .env dengan salah satu path di atas:");
                $this->comment("PYTHON_EXECUTABLE=" . trim($pythonPaths[0]));
            } else {
                $this->error("\nPython TIDAK ditemukan di sistem!");
                $this->warn("Install Python dari: https://www.python.org/downloads/");
            }
        }
        
        // Check Spark
        $this->info("\n--- Spark Configuration ---");
        
        $sparkHome = env('SPARK_HOME', '');
        $sparkSubmit = env('SPARK_SUBMIT_CMD', 'spark-submit');
        
        if (!empty($sparkHome)) {
            $sparkPath = rtrim($sparkHome, '\\/') . '\\bin\\' . $sparkSubmit . '.cmd';
            $this->line("Spark Home: {$sparkHome}");
            $this->line("Spark Submit: {$sparkPath}");
            
            if (file_exists($sparkPath)) {
                $this->info("✓ Spark found: {$sparkPath}");
            } else {
                $this->error("✗ Spark NOT found at: {$sparkPath}");
            }
        } else {
            $this->warn("SPARK_HOME not set in .env");
            $this->line("Trying to find spark-submit in PATH...");
            
            exec("where spark-submit 2>&1", $sparkPaths, $returnCode);
            
            if ($returnCode === 0 && !empty($sparkPaths)) {
                $this->info("✓ Spark found in PATH:");
                foreach ($sparkPaths as $path) {
                    $this->line("  - " . trim($path));
                }
            } else {
                $this->error("✗ Spark NOT found in PATH");
                $this->warn("\nInstall Apache Spark:");
                $this->comment("1. Download dari: https://spark.apache.org/downloads.html");
                $this->comment("2. Extract ke folder (contoh: C:\\spark)");
                $this->comment("3. Set SPARK_HOME di .env:");
                $this->comment("   SPARK_HOME=C:\\spark");
            }
        }
        
        // Check Python script
        $this->info("\n--- Python Script ---");
        
        $pythonFile = base_path('spark/spark_kuesioner.py');
        $this->line("Script path: {$pythonFile}");
        
        if (file_exists($pythonFile)) {
            $this->info("✓ Python script found");
            
            $fileSize = filesize($pythonFile);
            $this->line("File size: " . number_format($fileSize) . " bytes");
        } else {
            $this->error("✗ Python script NOT found");
        }
        
        // Check PySpark
        if (file_exists($pythonFromEnv)) {
            $this->info("\n--- PySpark Installation ---");
            
            exec("\"{$pythonFromEnv}\" -c \"import pyspark; print(pyspark.__version__)\" 2>&1", $pysparkCheck, $pysparkReturn);
            
            if ($pysparkReturn === 0) {
                $this->info("✓ PySpark installed: " . implode('', $pysparkCheck));
            } else {
                $this->error("✗ PySpark NOT installed");
                $this->warn("\nInstall PySpark:");
                $this->comment("pip install pyspark");
            }
        }
        
        // Summary
        $this->info("\n=================================");
        $this->info("SUMMARY");
        $this->info("=================================");
        
        $issues = [];
        
        if (!file_exists($pythonFromEnv) && $pythonFromEnv !== 'python') {
            $issues[] = "Python executable not found";
        }
        
        if (empty($sparkHome)) {
            exec("where spark-submit 2>&1", $sparkPaths, $returnCode);
            if ($returnCode !== 0) {
                $issues[] = "Spark not configured";
            }
        }
        
        if (!file_exists($pythonFile)) {
            $issues[] = "Python script missing";
        }
        
        if (empty($issues)) {
            $this->info("✓ All checks passed!");
            $this->info("\nYou can run Spark analysis from dashboard.");
        } else {
            $this->error("✗ Issues found:");
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
            $this->warn("\nFix the issues above before running Spark analysis.");
        }
        
        return 0;
    }
}
