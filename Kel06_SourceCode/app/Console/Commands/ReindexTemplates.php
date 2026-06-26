<?php

namespace App\Console\Commands;

use App\Models\TemplateLaporan;
use App\Services\LaporanKuesioneService;
use Illuminate\Console\Command;

class ReindexTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'template:reindex {id?} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex template(s) to vector database';

    /**
     * Execute the console command.
     */
    public function handle(LaporanKuesioneService $laporanService)
    {
        $this->info('=== Template Reindex Command ===');
        $this->newLine();

        if ($this->option('all')) {
            // Reindex all templates
            $templates = TemplateLaporan::all();
            
            if ($templates->isEmpty()) {
                $this->warn('No templates found.');
                return 0;
            }

            $this->info("Found {$templates->count()} template(s) to reindex.");
            $this->newLine();

            $bar = $this->output->createProgressBar($templates->count());
            $bar->start();

            $success = 0;
            $failed = 0;

            foreach ($templates as $template) {
                try {
                    $result = $laporanService->processTemplateToVectorDB($template->id);
                    $success++;
                    $bar->advance();
                } catch (\Exception $e) {
                    $failed++;
                    $this->newLine();
                    $this->error("Failed to reindex template {$template->id}: {$e->getMessage()}");
                    $bar->advance();
                }
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("Reindex completed!");
            $this->table(
                ['Status', 'Count'],
                [
                    ['Success', $success],
                    ['Failed', $failed],
                    ['Total', $templates->count()]
                ]
            );

        } else if ($this->argument('id')) {
            // Reindex specific template
            $templateId = $this->argument('id');
            $template = TemplateLaporan::find($templateId);

            if (!$template) {
                $this->error("Template with ID {$templateId} not found.");
                return 1;
            }

            $this->info("Reindexing template: {$template->nama_template}");
            $this->newLine();

            try {
                $result = $laporanService->processTemplateToVectorDB($template->id);

                $this->info('✅ Template reindexed successfully!');
                $this->newLine();

                $this->table(
                    ['Property', 'Value'],
                    [
                        ['Template ID', $template->id],
                        ['Template Name', $template->nama_template],
                        ['Chunks Indexed', $result['chunks_indexed']],
                        ['Sections', count($result['structure']['sections'])],
                        ['Patterns', count($result['structure']['patterns'])],
                        ['Indexed At', now()->format('Y-m-d H:i:s')]
                    ]
                );

                if (!empty($result['structure']['sections'])) {
                    $this->newLine();
                    $this->info('Sections found:');
                    foreach ($result['structure']['sections'] as $section) {
                        $this->line("  - {$section['title']}");
                    }
                }

            } catch (\Exception $e) {
                $this->error('❌ Failed to reindex template');
                $this->error($e->getMessage());
                $this->newLine();
                $this->line('Stack trace:');
                $this->line($e->getTraceAsString());
                return 1;
            }

        } else {
            // Show usage
            $this->warn('Please specify a template ID or use --all flag');
            $this->newLine();
            $this->info('Usage:');
            $this->line('  php artisan template:reindex 1           # Reindex template with ID 1');
            $this->line('  php artisan template:reindex --all       # Reindex all templates');
            $this->newLine();

            // Show available templates
            $templates = TemplateLaporan::all();
            if ($templates->isNotEmpty()) {
                $this->info('Available templates:');
                $this->table(
                    ['ID', 'Name', 'Indexed', 'Chunks'],
                    $templates->map(function($t) {
                        return [
                            $t->id,
                            $t->nama_template,
                            $t->is_indexed ? '✅' : '❌',
                            $t->total_chunks
                        ];
                    })
                );
            }
        }

        return 0;
    }
}
