<?php

namespace App\Services;

use App\Models\LaporanGJM;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Fill;

/**
 * PPT Generation Service
 * 
 * Generate PPT dari AI preview yang sudah disimpan di database
 * Menggunakan sections dari AI preview untuk membuat slide yang terstruktur
 */
class PPTGenerationService
{
    protected $claudeService;
    protected $textExtraction;

    public function __construct(
        ClaudeAIService $claudeService,
        TextExtractionService $textExtraction
    ) {
        $this->claudeService = $claudeService;
        $this->textExtraction = $textExtraction;
    }

    /**
     * Generate PPT from Laporan GJM
     * Menggunakan AI preview dari database
     */
    public function generateFromLaporan($laporanId, $judulPresentasi)
    {
        Log::info("=== Starting PPT Generation ===", [
            'laporan_id' => $laporanId,
            'judul' => $judulPresentasi
        ]);

        $laporan = LaporanGJM::with(['template'])->find($laporanId);
        
        if (!$laporan) {
            throw new \Exception("Laporan not found");
        }

        try {
            // 1. Get AI preview dari database
            $cacheService = app(AIPreviewCacheService::class);
            $preview = $cacheService->getAIPreview($laporanId);

            if (!$preview) {
                throw new \Exception('AI preview belum dibuat. Silakan generate preview terlebih dahulu melalui chat assistant.');
            }

            Log::info('AI preview retrieved from database', [
                'laporan_id' => $laporanId,
                'preview_length' => strlen($preview['draft']),
                'sections_count' => count($preview['sections'])
            ]);

            // 2. Build PPT structure dari AI preview sections
            $pptStructure = $this->buildPPTStructureFromPreview($preview, $judulPresentasi, $laporan);
            
            // 3. Create PowerPoint presentation
            $pptPath = $this->createPowerPointFile($pptStructure, $judulPresentasi);
            
            // 4. Mark preview sebagai sudah digunakan
            $cacheService->markAsUsedForGeneration($laporanId);

            Log::info("PPT generated successfully", [
                'laporan_id' => $laporanId,
                'file_path' => $pptPath
            ]);

            return [
                'success' => true,
                'laporan_id' => $laporanId,
                'file_path' => $pptPath,
                'structure' => $pptStructure
            ];

        } catch (\Exception $e) {
            Log::error("Failed to generate PPT", [
                'laporan_id' => $laporanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Build PPT structure dari AI preview sections
     */
    private function buildPPTStructureFromPreview($preview, $judulPresentasi, $laporan)
    {
        Log::info('Building PPT structure from AI preview');

        $sections = $preview['sections'] ?? [];

        $structure = [
            'title' => $judulPresentasi,
            'subtitle' => $laporan->getPeriodeLabel(),
            'slides' => []
        ];

        // Title slide
        $structure['slides'][] = [
            'type' => 'title',
            'title' => $judulPresentasi,
            'content' => "Gugus Jaminan Mutu Fakultas Vokasi\n" . $laporan->getPeriodeLabel() . "\nInstitut Teknologi Del\n" . ($laporan->ajaran ? $laporan->ajaran->tahun_ajaran : '2025/2026')
        ];

        // Agenda slide
        $structure['slides'][] = [
            'type' => 'content',
            'title' => 'Agenda Presentasi',
            'content' => 'Presentasi ini akan membahas laporan ' . $laporan->getJenisLaporanLabel() . ' GJM Fakultas Vokasi',
            'bullet_points' => [
                'Latar Belakang dan Dasar Penyusunan',
                'Tujuan dan Ruang Lingkup Laporan',
                'Program Kerja yang Dilaksanakan',
                'Pelaksanaan dan Capaian',
                'Hambatan dan Pemecahan Masalah',
                'Evaluasi dan Analisis',
                'Kesimpulan dan Rekomendasi',
                'Rencana Tindak Lanjut'
            ]
        ];

        // Latar Belakang
        $latar_belakang = $sections['latar_belakang'] ?? '';
        $structure['slides'][] = [
            'type' => 'section',
            'title' => 'Latar Belakang',
            'content' => $latar_belakang
        ];

        // Dasar Penyusunan
        $dasar = $sections['dasar'] ?? '';
        if (!empty($dasar)) {
            $structure['slides'][] = [
                'type' => 'content',
                'title' => 'Dasar Penyusunan Laporan',
                'content' => $dasar
            ];
        }

        // Tujuan
        $tujuan = $sections['tujuan'] ?? '';
        $structure['slides'][] = [
            'type' => 'content',
            'title' => 'Tujuan Laporan',
            'content' => $tujuan
        ];

        // Ruang Lingkup
        $ruang_lingkup = $sections['ruang_lingkup'] ?? '';
        if (!empty($ruang_lingkup)) {
            $structure['slides'][] = [
                'type' => 'content',
                'title' => 'Ruang Lingkup Laporan',
                'content' => $ruang_lingkup
            ];
        }

        // Program Kerja
        $program_kerja = $sections['program_kerja'] ?? '';
        $structure['slides'][] = [
            'type' => 'section',
            'title' => 'Program Kerja',
            'content' => $program_kerja
        ];

        // Pelaksanaan
        $pelaksanaan = $sections['pelaksanaan'] ?? '';
        $structure['slides'][] = [
            'type' => 'content',
            'title' => 'Pelaksanaan Program Kerja',
            'content' => $pelaksanaan
        ];

        // Hambatan dan Pemecahan Masalah
        $hambatan = $sections['hambatan'] ?? '';
        $pemecahan = $sections['pemecahan_masalah'] ?? '';
        $structure['slides'][] = [
            'type' => 'content',
            'title' => 'Hambatan dan Pemecahan Masalah',
            'content' => $hambatan . "\n\n" . $pemecahan
        ];

        // Evaluasi
        $evaluasi = $sections['evaluasi'] ?? '';
        $structure['slides'][] = [
            'type' => 'content',
            'title' => 'Evaluasi dan Analisis',
            'content' => $evaluasi
        ];

        // Kesimpulan dan Rekomendasi
        $rekomendasi = $sections['rekomendasi'] ?? $sections['kesimpulan'] ?? '';
        $structure['slides'][] = [
            'type' => 'summary',
            'title' => 'Kesimpulan dan Rekomendasi',
            'content' => $rekomendasi
        ];

        // Penutup
        $structure['slides'][] = [
            'type' => 'content',
            'title' => 'Penutup',
            'content' => 'Terima kasih atas perhatian dan dukungan dalam pelaksanaan program kerja GJM. Semoga laporan ini bermanfaat untuk perbaikan dan peningkatan kualitas pendidikan di Fakultas Vokasi Institut Teknologi Del.'
        ];

        Log::info('PPT structure built from AI preview', [
            'slides_count' => count($structure['slides']),
            'title' => $structure['title']
        ]);

        return $structure;
    }

    /**
     * Create PowerPoint file using PhpPresentation
     */
    private function createPowerPointFile($structure, $judulPresentasi)
    {
        $presentation = new PhpPresentation();
        
        // Set presentation properties
        $presentation->getDocumentProperties()
            ->setCreator('GJM System')
            ->setTitle($judulPresentasi)
            ->setSubject('Laporan GJM')
            ->setDescription('Generated automatically by GJM AI Agent');

        // Set default slide size (16:9 aspect ratio)
        $presentation->getLayout()->setDocumentLayout(\PhpOffice\PhpPresentation\DocumentLayout::LAYOUT_SCREEN_16X9);

        // Remove default slide
        $presentation->removeSlideByIndex(0);

        // Create slides based on structure
        foreach ($structure['slides'] as $slideData) {
            $slide = $presentation->createSlide();
            
            // Ensure slide has proper dimensions
            $slide->setName($slideData['title'] ?? 'Slide');
            
            $this->createSlideContent($slide, $slideData);
        }

        // Save presentation
        $fileName = 'ppt_' . time() . '.pptx';
        $filePath = 'ppt_generated/' . $fileName;
        $fullPath = storage_path('app/' . $filePath);

        // Create directory if not exists
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $writer = IOFactory::createWriter($presentation, 'PowerPoint2007');
        $writer->save($fullPath);

        Log::info('PowerPoint file created', [
            'file_path' => $fullPath,
            'file_size' => filesize($fullPath),
            'slides_count' => count($structure['slides'])
        ]);

        return $filePath;
    }

    /**
     * Create slide content based on slide data
     */
    private function createSlideContent($slide, $slideData)
    {
        $slideType = $slideData['type'] ?? 'content';
        
        switch ($slideType) {
            case 'title':
                $this->createTitleSlide($slide, $slideData);
                break;
            case 'section':
                $this->createSectionSlide($slide, $slideData);
                break;
            case 'summary':
                $this->createSummarySlide($slide, $slideData);
                break;
            default:
                $this->createContentSlide($slide, $slideData);
                break;
        }
    }

    /**
     * Create title slide
     */
    private function createTitleSlide($slide, $slideData)
    {
        // Create a full-slide background shape with gradient
        $backgroundShape = $slide->createRichTextShape()
            ->setHeight(540)
            ->setWidth(960)
            ->setOffsetX(0)
            ->setOffsetY(0);
        
        $backgroundShape->getFill()
            ->setFillType(Fill::FILL_GRADIENT_LINEAR)
            ->setStartColor(new Color('FF1E3A8A'))  // Deep blue
            ->setEndColor(new Color('FF0F172A'))   // Very dark blue
            ->setRotation(45);

        // Add vibrant accent stripe
        $accentShape = $slide->createRichTextShape()
            ->setHeight(12)
            ->setWidth(960)
            ->setOffsetX(0)
            ->setOffsetY(100);
        $accentShape->getFill()
            ->setFillType(Fill::FILL_GRADIENT_LINEAR)
            ->setStartColor(new Color('FFFF6B35'))  // Bright orange
            ->setEndColor(new Color('FFEF4444'));  // Red-orange

        // Add decorative geometric shapes
        $decorShape1 = $slide->createRichTextShape()
            ->setHeight(150)
            ->setWidth(150)
            ->setOffsetX(750)
            ->setOffsetY(350);
        $decorShape1->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('4DFF6B35'));  // Semi-transparent orange

        $decorShape2 = $slide->createRichTextShape()
            ->setHeight(100)
            ->setWidth(100)
            ->setOffsetX(50)
            ->setOffsetY(400);
        $decorShape2->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('4D3B82F6'));  // Semi-transparent blue

        // Title with enhanced styling
        $titleShape = $slide->createRichTextShape()
            ->setHeight(160)
            ->setWidth(800)
            ->setOffsetX(80)
            ->setOffsetY(130);
        
        $titleShape->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $titleText = $titleShape->createTextRun($slideData['title'] ?? 'Untitled');
        $titleText->getFont()
            ->setBold(true)
            ->setSize(36)
            ->setColor(new Color('FFFFFFFF'))  // Pure white
            ->setName('Segoe UI');

        // Subtitle with better contrast
        if (!empty($slideData['content'])) {
            $subtitleShape = $slide->createRichTextShape()
                ->setHeight(140)
                ->setWidth(800)
                ->setOffsetX(80)
                ->setOffsetY(290);
            
            $subtitleShape->getActiveParagraph()
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Clean content and split into lines
            $subtitleContent = $slideData['content'];
            $subtitleContent = preg_replace('/^#\s*/', '', $subtitleContent);
            $lines = explode("\n", trim($subtitleContent));
            
            foreach ($lines as $i => $line) {
                $line = trim($line);
                if (!empty($line)) {
                    if ($i > 0) {
                        $subtitleShape->createParagraph();
                    }
                    $subtitleText = $subtitleShape->createTextRun($line);
                    $subtitleText->getFont()
                        ->setSize($i === 0 ? 24 : 20)
                        ->setColor(new Color('FFE2E8F0'))  // Light gray-blue
                        ->setBold($i === 0)
                        ->setName('Segoe UI');
                }
            }
        }

        // Enhanced footer with institution name
        $footerShape = $slide->createRichTextShape()
            ->setHeight(50)
            ->setWidth(600)
            ->setOffsetX(180)
            ->setOffsetY(450);
        
        $footerShape->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $footerText = $footerShape->createTextRun('Institut Teknologi Del');
        $footerText->getFont()
            ->setSize(18)
            ->setColor(new Color('FFFF6B35'))  // Bright orange
            ->setBold(true)
            ->setName('Segoe UI');
    }

    /**
     * Create section slide
     */
    private function createSectionSlide($slide, $slideData)
    {
        // Create vibrant gradient background
        $backgroundShape = $slide->createRichTextShape()
            ->setHeight(540)
            ->setWidth(960)
            ->setOffsetX(0)
            ->setOffsetY(0);
        
        $backgroundShape->getFill()
            ->setFillType(Fill::FILL_GRADIENT_LINEAR)
            ->setStartColor(new Color('FFFF6B35'))  // Bright orange
            ->setEndColor(new Color('FFDC2626'))   // Deep red
            ->setRotation(135);

        // Add dynamic geometric elements
        $accentShape1 = $slide->createRichTextShape()
            ->setHeight(200)
            ->setWidth(200)
            ->setOffsetX(700)
            ->setOffsetY(300);
        $accentShape1->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('33FFFFFF'));  // Semi-transparent white

        $accentShape2 = $slide->createRichTextShape()
            ->setHeight(120)
            ->setWidth(120)
            ->setOffsetX(50)
            ->setOffsetY(50);
        $accentShape2->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('33FFFFFF'));  // Semi-transparent white

        // Section title with shadow effect
        $titleShape = $slide->createRichTextShape()
            ->setHeight(140)
            ->setWidth(700)
            ->setOffsetX(80)
            ->setOffsetY(180);
        
        $titleShape->getActiveParagraph()
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $titleText = $titleShape->createTextRun($slideData['title'] ?? 'Section');
        $titleText->getFont()
            ->setBold(true)
            ->setSize(44)
            ->setColor(new Color('FFFFFFFF'))  // Pure white
            ->setName('Segoe UI');

        // Add decorative line with gradient
        $lineShape = $slide->createRichTextShape()
            ->setHeight(8)
            ->setWidth(400)
            ->setOffsetX(80)
            ->setOffsetY(340);
        $lineShape->getFill()
            ->setFillType(Fill::FILL_GRADIENT_LINEAR)
            ->setStartColor(new Color('FFFFFFFF'))  // White
            ->setEndColor(new Color('00FFFFFF'));  // Transparent white

        // Add subtitle if content exists
        if (!empty($slideData['content'])) {
            $contentShape = $slide->createRichTextShape()
                ->setHeight(100)
                ->setWidth(600)
                ->setOffsetX(80)
                ->setOffsetY(370);
            
            $contentText = $contentShape->createTextRun(substr($slideData['content'], 0, 150) . '...');
            $contentText->getFont()
                ->setSize(18)
                ->setColor(new Color('FFFEF7F0'))  // Very light orange-white
                ->setName('Segoe UI');
        }
    }

    /**
     * Create content slide
     */
    private function createContentSlide($slide, $slideData)
    {
        // Create clean white background with subtle gradient
        $backgroundShape = $slide->createRichTextShape()
            ->setHeight(540)
            ->setWidth(960)
            ->setOffsetX(0)
            ->setOffsetY(0);
        
        $backgroundShape->getFill()
            ->setFillType(Fill::FILL_GRADIENT_LINEAR)
            ->setStartColor(new Color('FFFFFFFF'))  // Pure white
            ->setEndColor(new Color('FFF8FAFC'))   // Very light gray
            ->setRotation(180);

        // Bold header bar with enhanced gradient
        $headerShape = $slide->createRichTextShape()
            ->setHeight(100)
            ->setWidth(960)
            ->setOffsetX(0)
            ->setOffsetY(0);
        $headerShape->getFill()
            ->setFillType(Fill::FILL_GRADIENT_LINEAR)
            ->setStartColor(new Color('FF1E3A8A'))  // Deep blue
            ->setEndColor(new Color('FF3B82F6'));  // Bright blue

        // Vibrant accent stripe
        $accentShape = $slide->createRichTextShape()
            ->setHeight(8)
            ->setWidth(960)
            ->setOffsetX(0)
            ->setOffsetY(92);
        $accentShape->getFill()
            ->setFillType(Fill::FILL_GRADIENT_LINEAR)
            ->setStartColor(new Color('FFFF6B35'))  // Bright orange
            ->setEndColor(new Color('FFEF4444'));  // Red-orange

        // Add side accent
        $sideAccent = $slide->createRichTextShape()
            ->setHeight(440)
            ->setWidth(6)
            ->setOffsetX(0)
            ->setOffsetY(100);
        $sideAccent->getFill()
            ->setFillType(Fill::FILL_GRADIENT_LINEAR)
            ->setStartColor(new Color('FF3B82F6'))  // Blue
            ->setEndColor(new Color('FF06B6D4'));  // Cyan

        // Title with enhanced styling
        $titleShape = $slide->createRichTextShape()
            ->setHeight(80)
            ->setWidth(800)
            ->setOffsetX(60)
            ->setOffsetY(10);
        
        $titleText = $titleShape->createTextRun($slideData['title'] ?? 'Untitled');
        $titleText->getFont()
            ->setBold(true)
            ->setSize(28)
            ->setColor(new Color('FFFFFFFF'))  // Pure white
            ->setName('Segoe UI');

        // Content area with better spacing and styling
        $contentShape = $slide->createRichTextShape()
            ->setHeight(420)
            ->setWidth(880)
            ->setOffsetX(40)
            ->setOffsetY(120);

        // Add bullet points if available
        if (!empty($slideData['bullet_points'])) {
            foreach ($slideData['bullet_points'] as $i => $point) {
                if ($i > 0) {
                    $contentShape->createParagraph();
                }
                $paragraph = $contentShape->getActiveParagraph();
                $paragraph->getBulletStyle()->setBulletType(\PhpOffice\PhpPresentation\Style\Bullet::TYPE_BULLET);
                $paragraph->getAlignment()->setMarginLeft(40);
                $paragraph->getAlignment()->setIndent(-30);
                $paragraph->getAlignment()->setLevel(0);
                
                $textRun = $paragraph->createTextRun($point);
                $textRun->getFont()
                    ->setSize(20)
                    ->setColor(new Color('FF1F2937'))  // Dark gray for better readability
                    ->setBold(false)
                    ->setName('Segoe UI');
            }
        } else {
            // Add regular content - split by lines and create paragraphs
            $content = $slideData['content'] ?? '';
            if (!empty($content)) {
                $lines = explode("\n", $content);
                $lineCount = 0;
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        // Skip markdown headers
                        if (strpos($line, '#') === 0) {
                            continue;
                        }
                        
                        if ($lineCount > 0) {
                            $contentShape->createParagraph();
                        }
                        $paragraph = $contentShape->getActiveParagraph();
                        $textRun = $paragraph->createTextRun($line);
                        $textRun->getFont()
                            ->setSize(20)
                            ->setColor(new Color('FF1F2937'))  // Dark gray
                            ->setName('Segoe UI');
                        $lineCount++;
                        
                        // Limit to prevent overcrowding
                        if ($lineCount >= 8) break;
                    }
                }
            } else {
                // Fallback: create a default paragraph
                $paragraph = $contentShape->getActiveParagraph();
                $textRun = $paragraph->createTextRun('Konten slide akan ditampilkan di sini.');
                $textRun->getFont()
                    ->setSize(20)
                    ->setColor(new Color('FF6B7280'))  // Medium gray
                    ->setName('Segoe UI');
            }
        }

        // Add decorative corner element
        $cornerShape = $slide->createRichTextShape()
            ->setHeight(60)
            ->setWidth(60)
            ->setOffsetX(880)
            ->setOffsetY(460);
        $cornerShape->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('4DFF6B35'));  // Semi-transparent orange
    }

    /**
     * Create summary slide
     */
    private function createSummarySlide($slide, $slideData)
    {
        // Create vibrant gradient background
        $backgroundShape = $slide->createRichTextShape()
            ->setHeight(540)
            ->setWidth(960)
            ->setOffsetX(0)
            ->setOffsetY(0);
        
        $backgroundShape->getFill()
            ->setFillType(Fill::FILL_GRADIENT_LINEAR)
            ->setStartColor(new Color('FF059669'))  // Emerald green
            ->setEndColor(new Color('FF047857'))   // Dark emerald
            ->setRotation(45);

        // Header with enhanced gradient
        $headerShape = $slide->createRichTextShape()
            ->setHeight(100)
            ->setWidth(960)
            ->setOffsetX(0)
            ->setOffsetY(0);
        $headerShape->getFill()
            ->setFillType(Fill::FILL_GRADIENT_LINEAR)
            ->setStartColor(new Color('FF065F46'))  // Very dark green
            ->setEndColor(new Color('FF059669'));  // Emerald green

        // Gold accent stripe with gradient
        $accentShape = $slide->createRichTextShape()
            ->setHeight(8)
            ->setWidth(960)
            ->setOffsetX(0)
            ->setOffsetY(92);
        $accentShape->getFill()
            ->setFillType(Fill::FILL_GRADIENT_LINEAR)
            ->setStartColor(new Color('FFFBBF24'))  // Bright yellow
            ->setEndColor(new Color('FFF59E0B'));  // Amber

        // Add decorative elements
        $decorShape1 = $slide->createRichTextShape()
            ->setHeight(120)
            ->setWidth(120)
            ->setOffsetX(800)
            ->setOffsetY(380);
        $decorShape1->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('33FBBF24'));  // Semi-transparent yellow

        $decorShape2 = $slide->createRichTextShape()
            ->setHeight(80)
            ->setWidth(80)
            ->setOffsetX(60)
            ->setOffsetY(420);
        $decorShape2->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new Color('33FFFFFF'));  // Semi-transparent white

        // Title with enhanced styling
        $titleShape = $slide->createRichTextShape()
            ->setHeight(80)
            ->setWidth(800)
            ->setOffsetX(60)
            ->setOffsetY(10);
        
        $titleText = $titleShape->createTextRun($slideData['title'] ?? 'Summary');
        $titleText->getFont()
            ->setBold(true)
            ->setSize(30)
            ->setColor(new Color('FFFFFFFF'))  // Pure white
            ->setName('Segoe UI');

        // Content area with better styling
        $contentShape = $slide->createRichTextShape()
            ->setHeight(420)
            ->setWidth(880)
            ->setOffsetX(40)
            ->setOffsetY(120);

        // Add bullet points if available
        if (!empty($slideData['bullet_points'])) {
            foreach ($slideData['bullet_points'] as $i => $point) {
                if ($i > 0) {
                    $contentShape->createParagraph();
                }
                $paragraph = $contentShape->getActiveParagraph();
                $paragraph->getBulletStyle()->setBulletType(\PhpOffice\PhpPresentation\Style\Bullet::TYPE_BULLET);
                $paragraph->getAlignment()->setMarginLeft(40);
                $paragraph->getAlignment()->setIndent(-30);
                
                $textRun = $paragraph->createTextRun($point);
                $textRun->getFont()
                    ->setSize(20)
                    ->setColor(new Color('FFFFFFFF'))  // Pure white
                    ->setBold(true)
                    ->setName('Segoe UI');
            }
        } else {
            // Add regular content
            $content = $slideData['content'] ?? '';
            if (!empty($content)) {
                $lines = explode("\n", $content);
                $lineCount = 0;
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        if (strpos($line, '#') === 0) {
                            continue;
                        }
                        
                        if ($lineCount > 0) {
                            $contentShape->createParagraph();
                        }
                        $paragraph = $contentShape->getActiveParagraph();
                        $textRun = $paragraph->createTextRun($line);
                        $textRun->getFont()
                            ->setSize(20)
                            ->setColor(new Color('FFFFFFFF'))  // Pure white
                            ->setName('Segoe UI');
                        $lineCount++;
                        
                        if ($lineCount >= 6) break;
                    }
                }
            }
        }
    }
}
