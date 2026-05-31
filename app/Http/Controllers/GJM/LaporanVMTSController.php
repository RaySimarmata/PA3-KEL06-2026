<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\LaporanGJM;
use App\Services\VMTSExcelService;
use App\Services\UnifiedAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LaporanVMTSController extends Controller
{
    protected $vmtsExcelService;
    protected $aiService;
    protected $cacheService;

    public function __construct(VMTSExcelService $vmtsExcelService, UnifiedAIService $aiService)
    {
        $this->vmtsExcelService = $vmtsExcelService;
        $this->aiService = $aiService;
        $this->cacheService = app(\App\Services\AICacheService::class);
    }
    /**
     * Display a listing of laporan VMTS
     */
    public function index(Request $request)
    {
        $query = LaporanGJM::where('jenis_laporan', 'VMTS')
            ->where('created_by', Auth::id())
            ->orderBy('created_at', 'desc');

        // Filter by tahun akademik
        if ($request->filled('tahun_akademik')) {
            $query->whereRaw("JSON_EXTRACT(instruksi_prompt, '$.periode_VMTS') = ?", [$request->tahun_akademik]);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status_laporan', $request->status);
        }

        $laporanList = $query->paginate(10);

        // Add formatted data for display
        $laporanList->getCollection()->transform(function ($laporan) {
            // Ambil data dari instruksi_prompt JSON
            $instruksi = is_array($laporan->instruksi_prompt) ? $laporan->instruksi_prompt : json_decode($laporan->instruksi_prompt, true);
            
            $laporan->periode_VMTS = $instruksi['periode_VMTS'] ?? '-';
            $laporan->judul_laporan = $instruksi['judul'] ?? $laporan->ringkasan_mutu_institusi ?? '-';
            
            // Status badge
            switch ($laporan->status_laporan) {
                case 'completed':
                    $laporan->status_badge = 'success';
                    $laporan->status_label = 'Selesai';
                    break;
                case 'processing':
                    $laporan->status_badge = 'warning';
                    $laporan->status_label = 'Sedang Diproses';
                    break;
                case 'error':
                    $laporan->status_badge = 'danger';
                    $laporan->status_label = 'Error';
                    break;
                default:
                    $laporan->status_badge = 'info';
                    $laporan->status_label = 'Menunggu';
            }
            
            return $laporan;
        });

        // Generate academic years for filter
        $currentYear = date('Y');
        $tahunAkademikList = [];
        for ($i = -2; $i < 3; $i++) {
            $year = $currentYear + $i;
            $nextYear = $year + 1;
            $tahunAkademikList[] = $year . '/' . $nextYear;
        }

        return view('gjm.buat-laporan.vmts-index', compact('laporanList', 'tahunAkademikList'));
    }

    /**
     * Show form to create new laporan VMTS
     */
    public function create()
    {
        $user = Auth::user();
        
        // Generate academic years (current year and next 2 years)
        $currentYear = date('Y');
        $tahunAkademik = [];
        for ($i = 0; $i < 3; $i++) {
            $year = $currentYear + $i;
            $nextYear = $year + 1;
            $tahunAkademik[] = $year . '/' . $nextYear;
        }
        
        return view('gjm.buat-laporan.vmts-create', compact('user', 'tahunAkademik'));
    }

    /**
     * Display the specified laporan
     */
    public function show($id)
    {
        $laporan = LaporanGJM::where('id', $id)
            ->where('jenis_laporan', 'VMTS')
            ->where('created_by', Auth::id())
            ->firstOrFail();

        return view('gjm.buat-laporan.vmts-show', compact('laporan'));
    }

    /**
     * Download laporan in specified format
     */
    public function download($id, $format = 'word')
    {
        $laporan = LaporanGJM::where('id', $id)
            ->where('jenis_laporan', 'VMTS')
            ->where('created_by', Auth::id())
            ->firstOrFail();

        if ($format === 'word' && $laporan->file_word) {
            $filePath = storage_path('app/' . $laporan->file_word);
            
            if (file_exists($filePath)) {
                $instruksi = is_array($laporan->instruksi_prompt) ? $laporan->instruksi_prompt : json_decode($laporan->instruksi_prompt, true);
                $fileName = 'Laporan_VMTS_' . str_replace('/', '_', $instruksi['periode_VMTS'] ?? date('Y')) . '.docx';
                
                return response()->download($filePath, $fileName);
            }
        }

        return redirect()->back()->with('error', 'File tidak ditemukan');
    }

    /**
     * Remove the specified laporan
     */
    public function destroy($id)
    {
        $laporan = LaporanGJM::where('id', $id)
            ->where('jenis_laporan', 'VMTS')
            ->where('created_by', Auth::id())
            ->firstOrFail();

        // Delete associated files
        if ($laporan->file_word) {
            Storage::delete($laporan->file_word);
        }

        $laporan->delete();

        return redirect()->route('gjm.buat-laporan.vmts.index')
            ->with('success', 'Laporan berhasil dihapus');
    }

    /**
     * Store laporan VMTS
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'laporan_id' => 'required|exists:laporan_gjm,id',
                'ai_preview_data' => 'required|string',
            ]);

            $laporan = LaporanGJM::findOrFail($request->laporan_id);
            
            // Get and clean AI preview data
            $aiPreviewData = $request->ai_preview_data;
            
            // Log original data
            Log::info('Generating Word from AI data', [
                'laporan_id' => $laporan->id,
                'data_length' => strlen($aiPreviewData),
                'first_200_chars' => substr($aiPreviewData, 0, 200),
                'has_special_chars' => preg_match('/[<>&]/', $aiPreviewData) ? 'yes' : 'no',
            ]);
            
            // Update laporan with AI preview data
            $laporan->update([
                'ai_preview_draft' => $aiPreviewData,
                'status_laporan' => 'completed',
            ]);

            // Generate Word document
            $periode = $laporan->periode ?? date('Y');
            $judul = $laporan->judul ?? 'Laporan VMTS';
            
            // Use PHPWord to generate document
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            
            // Set document properties
            $properties = $phpWord->getDocInfo();
            $properties->setCreator('Institut Teknologi Del - GJM');
            $properties->setTitle($judul);
            $properties->setSubject('Laporan VMTS');

            // Set default font
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            // Add section with simple settings
            $section = $phpWord->addSection([
                'marginLeft'   => 1134,  // 2 cm
                'marginRight'  => 1134,  // 2 cm
                'marginTop'    => 1134,  // 2 cm
                'marginBottom' => 1134,  // 2 cm
            ]);

            // Add title
            $section->addText($judul, [
                'bold' => true,
                'size' => 16,
                'name' => 'Arial',
                'color' => '000000'
            ], [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter' => 200
            ]);

            $section->addText('Periode: ' . $periode, [
                'size' => 11,
                'name' => 'Arial',
                'color' => '000000'
            ], [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter' => 200
            ]);

            $section->addTextBreak(2);

            // Parse markdown content with safe method that supports tables and formatting
            $this->parseMarkdownToWordSafe($aiPreviewData, $section);

            // Save document
            $filename = 'Laporan_VMTS_' . str_replace(['/', ' '], ['_', '_'], $periode) . '_' . time() . '.docx';
            $filepath = storage_path('app/public/laporan_vmts/' . $filename);

            // Create directory if not exists
            if (!file_exists(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
                Log::info('Created directory: ' . dirname($filepath));
            }

            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

            // Save with error handling
            try {
                // Validate document before saving
                Log::info('Attempting to save Word document', [
                    'filepath' => $filepath,
                    'sections_count' => count($phpWord->getSections()),
                ]);

                $objWriter->save($filepath);
                
                Log::info('Word document saved successfully', [
                    'filepath' => $filepath,
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to save Word document', [
                    'filepath' => $filepath,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Try to save with minimal content as fallback
                $phpWordFallback = new \PhpOffice\PhpWord\PhpWord();
                $sectionFallback = $phpWordFallback->addSection();
                $sectionFallback->addText('Laporan VMTS', ['bold' => true, 'size' => 16]);
                $sectionFallback->addTextBreak();
                $sectionFallback->addText('Terjadi kesalahan saat memproses konten laporan.');
                $sectionFallback->addText('Silakan hubungi administrator.');
                
                $writerFallback = \PhpOffice\PhpWord\IOFactory::createWriter($phpWordFallback, 'Word2007');
                $writerFallback->save($filepath);
                
                Log::info('Fallback document saved', ['filepath' => $filepath]);
            }

            // Verify file was created and is valid
            if (!file_exists($filepath)) {
                throw new \Exception('File tidak berhasil dibuat di: ' . $filepath);
            }

            $fileSize = filesize($filepath);
            
            // Check if file size is reasonable (at least 5KB for a valid Word document)
            if ($fileSize < 5120) {
                Log::error('Generated file is too small', [
                    'filepath' => $filepath,
                    'filesize' => $fileSize
                ]);
                throw new \Exception('File yang dihasilkan terlalu kecil (' . $fileSize . ' bytes), kemungkinan corrupt');
            }
            
            // Try to verify the file is a valid ZIP (DOCX is a ZIP file)
            $zip = new \ZipArchive();
            $zipStatus = $zip->open($filepath, \ZipArchive::CHECKCONS);
            if ($zipStatus !== true) {
                Log::error('Generated file is not a valid ZIP/DOCX', [
                    'filepath' => $filepath,
                    'zip_status' => $zipStatus,
                    'filesize' => $fileSize
                ]);
                throw new \Exception('File yang dihasilkan bukan DOCX yang valid (ZIP status: ' . $zipStatus . ')');
            }
            $zip->close();
            
            Log::info('File created and validated successfully', [
                'filepath' => $filepath,
                'filesize' => $fileSize,
            ]);

            // Update laporan with document path
            $laporan->update([
                'dokumen_hasil_path' => 'laporan_vmts/' . $filename,
            ]);

            Log::info('Laporan VMTS Word generated', [
                'laporan_id' => $laporan->id,
                'filename' => $filename,
                'file_size_bytes' => $fileSize,
            ]);

            // Return file as download response (same as Triwulan and Semester)
            return response()->download($filepath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate VMTS Word', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate laporan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse markdown content and add to Word document (SAFE VERSION)
     */
    private function parseMarkdownToWordSafe($markdown, $section)
    {
        // Clean markdown first
        $markdown = $this->cleanMarkdownForWord($markdown);
        
        $lines = explode("\n", $markdown);
        $inList = false;
        $inTable = false;
        $tableRows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                if ($inList) {
                    $section->addTextBreak();
                    $inList = false;
                }
                continue;
            }

            try {
                // Check for table rows
                if (preg_match('/^\|(.+)\|$/', $line)) {
                    // Skip separator rows (|---|---|)
                    if (preg_match('/^\|[\s\-:|]+\|$/', $line)) {
                        continue;
                    }
                    
                    $inTable = true;
                    $tableRows[] = $line;
                    continue;
                } else if ($inTable && !empty($tableRows)) {
                    // Render accumulated table
                    $this->renderSimpleTable($section, $tableRows);
                    $tableRows = [];
                    $inTable = false;
                    $section->addTextBreak();
                }

                // Heading 1 (# )
                if (preg_match('/^#\s+(.+)$/', $line, $matches)) {
                    $text = $this->sanitizeTextForWord($matches[1]);
                    if (!empty($text)) {
                        $section->addText($text, [
                            'bold' => true,
                            'size' => 14,
                            'name' => 'Arial',
                            'color' => '1F3864',
                        ], [
                            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
                            'spaceAfter' => 200,
                            'spaceBefore' => 200
                        ]);
                    }
                    $inList = false;
                    continue;
                }

                // Heading 2 (## )
                if (preg_match('/^##\s+(.+)$/', $line, $matches)) {
                    $text = $this->sanitizeTextForWord($matches[1]);
                    if (!empty($text)) {
                        $section->addText($text, [
                            'bold' => true,
                            'size' => 12,
                            'name' => 'Arial',
                            'color' => '1F3864',
                        ], [
                            'spaceAfter' => 150,
                            'spaceBefore' => 200
                        ]);
                    }
                    $inList = false;
                    continue;
                }

                // Heading 3 (### )
                if (preg_match('/^###\s+(.+)$/', $line, $matches)) {
                    $text = $this->sanitizeTextForWord($matches[1]);
                    if (!empty($text)) {
                        $section->addText($text, [
                            'bold' => true,
                            'size' => 11,
                            'name' => 'Arial',
                            'color' => '2E4C7E',
                        ], [
                            'spaceAfter' => 100,
                            'spaceBefore' => 150
                        ]);
                    }
                    $inList = false;
                    continue;
                }

                // Bullet list (- or *)
                if (preg_match('/^[\-\*]\s+(.+)$/', $line, $matches)) {
                    $text = $this->sanitizeTextForWord($matches[1]);
                    // Remove markdown formatting
                    $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
                    $text = preg_replace('/\*(.+?)\*/', '$1', $text);
                    
                    if (!empty($text)) {
                        $section->addListItem($text, 0, [
                            'size' => 11,
                            'name' => 'Arial',
                        ]);
                        $inList = true;
                    }
                    continue;
                }

                // Numbered list
                if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                    $text = $this->sanitizeTextForWord($matches[1]);
                    // Remove markdown formatting
                    $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
                    $text = preg_replace('/\*(.+?)\*/', '$1', $text);
                    
                    if (!empty($text)) {
                        $section->addListItem($text, 0, [
                            'size' => 11,
                            'name' => 'Arial',
                        ], null, \PhpOffice\PhpWord\Style\ListItem::TYPE_NUMBER);
                        $inList = true;
                    }
                    continue;
                }

                // Normal paragraph
                $text = $this->sanitizeTextForWord($line);
                // Remove markdown formatting
                $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
                $text = preg_replace('/\*(.+?)\*/', '$1', $text);
                $text = preg_replace('/__(.+?)__/', '$1', $text);
                $text = preg_replace('/_(.+?)_/', '$1', $text);
                
                if (!empty($text)) {
                    $section->addText($text, [
                        'size' => 11,
                        'name' => 'Arial',
                    ], [
                        'spaceAfter' => 100,
                        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH
                    ]);
                }
                $inList = false;

            } catch (\Exception $e) {
                Log::warning('Error parsing line in markdown', [
                    'line' => mb_substr($line, 0, 100),
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        // Render any remaining table
        if ($inTable && !empty($tableRows)) {
            $this->renderSimpleTable($section, $tableRows);
        }
    }

    /**
     * Render simple table from markdown
     */
    private function renderSimpleTable($section, $tableRows)
    {
        if (empty($tableRows)) {
            return;
        }

        try {
            // Parse table rows
            $parsedRows = [];
            foreach ($tableRows as $row) {
                $row = trim($row);
                $row = preg_replace('/^\||\|$/', '', $row); // Remove outer pipes
                $cells = array_map('trim', explode('|', $row));
                
                // Filter out empty rows
                if (!empty(array_filter($cells, fn($c) => !empty($c)))) {
                    $parsedRows[] = $cells;
                }
            }

            if (empty($parsedRows)) {
                return;
            }

            // Calculate column count
            $colCount = max(array_map('count', $parsedRows));
            if ($colCount === 0) {
                return;
            }

            // Calculate cell width
            $cellWidth = (int) (9000 / $colCount);

            // Create table
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '999999',
                'cellMargin' => 80,
            ]);

            foreach ($parsedRows as $rowIdx => $cells) {
                $isHeader = ($rowIdx === 0);
                $table->addRow();
                
                for ($c = 0; $c < $colCount; $c++) {
                    $cellText = isset($cells[$c]) ? $cells[$c] : '';
                    
                    // Remove markdown formatting
                    $cellText = preg_replace('/\*\*(.+?)\*\*/', '$1', $cellText);
                    $cellText = preg_replace('/\*(.+?)\*/', '$1', $cellText);
                    $cellText = $this->sanitizeTextForWord($cellText);

                    $cellStyle = $isHeader ? ['bgColor' => '1F3864'] : [];
                    $cell = $table->addCell($cellWidth, $cellStyle);
                    
                    $cell->addText($cellText, [
                        'name' => 'Arial',
                        'size' => 10,
                        'bold' => $isHeader,
                        'color' => $isHeader ? 'FFFFFF' : '000000',
                    ], [
                        'spaceAfter' => 40
                    ]);
                }
            }

            $section->addTextBreak();

        } catch (\Exception $e) {
            Log::error('Error rendering table', [
                'error' => $e->getMessage(),
                'rows_count' => count($tableRows),
            ]);
        }
    }

    /**
     * Parse markdown content and add to Word document
     */
    private function parseMarkdownToWord($markdown, $section)
    {
        // Clean and sanitize markdown first
        $markdown = $this->cleanMarkdownForWord($markdown);

        $lines = explode("\n", $markdown);
        $inList   = false;
        $inTable  = false;
        $tableBuffer = [];

        $flushTable = function () use ($section, &$tableBuffer, &$inTable) {
            if (!empty($tableBuffer)) {
                $this->renderMarkdownTableToWord($section, $tableBuffer);
            }
            $tableBuffer = [];
            $inTable = false;
        };

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);

            // ── Markdown table detection ──────────────────────────────────
            if (preg_match('/^\|/', $line)) {
                $inTable = true;
                $tableBuffer[] = $line;
                $inList = false;
                continue;
            } elseif ($inTable) {
                $flushTable();
                // Fall through to process current line normally
            }

            if (empty($line)) {
                $section->addTextBreak();
                $inList = false;
                continue;
            }

            try {
                // Check headings from most-specific (####) to least-specific (#)
                // so that ## doesn't accidentally consume ### lines.

                // Heading 4
                if (preg_match('/^####\s+(.+)$/', $line, $matches)) {
                    $text = $this->sanitizeTextForWord($matches[1]);
                    if (!empty($text)) {
                        $section->addText($text, [
                            'bold'   => true,
                            'italic' => true,
                            'size'   => 11,
                            'name'   => 'Arial',
                        ], ['spaceAfter' => 60]);
                    }
                    $inList = false;
                    continue;
                }

                // Heading 3
                if (preg_match('/^###\s+(.+)$/', $line, $matches)) {
                    $text = $this->sanitizeTextForWord($matches[1]);
                    if (!empty($text)) {
                        $section->addText($text, [
                            'bold'  => true,
                            'size'  => 11,
                            'name'  => 'Arial',
                            'color' => '2E4C7E',
                        ], ['spaceAfter' => 80, 'spaceBefore' => 100]);
                    }
                    $inList = false;
                    continue;
                }

                // Heading 2
                if (preg_match('/^##\s+(.+)$/', $line, $matches)) {
                    $text = $this->sanitizeTextForWord($matches[1]);
                    if (!empty($text)) {
                        $section->addText($text, [
                            'bold'  => true,
                            'size'  => 12,
                            'name'  => 'Arial',
                            'color' => '1F3864',
                        ], ['spaceAfter' => 100, 'spaceBefore' => 160]);
                    }
                    $inList = false;
                    continue;
                }

                // Heading 1
                if (preg_match('/^#\s+(.+)$/', $line, $matches)) {
                    $text = $this->sanitizeTextForWord($matches[1]);
                    if (!empty($text)) {
                        $section->addText($text, [
                            'bold'  => true,
                            'size'  => 14,
                            'name'  => 'Arial',
                            'color' => '1F3864',
                        ], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 160]);
                    }
                    $inList = false;
                    continue;
                }

                // Bullet list items
                if (preg_match('/^[\-\*]\s+(.+)$/', $line, $matches)) {
                    $text = $this->sanitizeTextForWord($matches[1]);
                    $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
                    if (!empty($text)) {
                        $section->addListItem($text, 0, [
                            'size'  => 11,
                            'name'  => 'Arial',
                            'color' => '000000',
                        ]);
                        $inList = true;
                    }
                    continue;
                }

                // Numbered list
                if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                    $text = $this->sanitizeTextForWord($matches[1]);
                    $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
                    if (!empty($text)) {
                        $section->addListItem($text, 0, [
                            'size'  => 11,
                            'name'  => 'Arial',
                            'color' => '000000',
                        ], null, \PhpOffice\PhpWord\Style\ListItem::TYPE_NUMBER);
                        $inList = true;
                    }
                    continue;
                }

                // Normal paragraph text
                $text = $this->sanitizeTextForWord($line);
                if (!empty($text)) {
                    // Strip markdown bold/italic markers
                    $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
                    $text = preg_replace('/\*(.+?)\*/', '$1', $text);
                    $text = preg_replace('/__(.+?)__/', '$1', $text);
                    $text = preg_replace('/_(.+?)_/', '$1', $text);

                    $section->addText($text, [
                        'size'  => 11,
                        'name'  => 'Arial',
                        'color' => '000000',
                    ], ['spaceAfter' => 80, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]);
                }
                $inList = false;

            } catch (\Exception $e) {
                Log::warning('Error parsing line in markdown', [
                    'line'  => mb_substr($line, 0, 100),
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        // Flush remaining table buffer
        if ($inTable && !empty($tableBuffer)) {
            $flushTable();
        }
    }

    /**
     * Render markdown table lines to a PhpWord table.
     */
    private function renderMarkdownTableToWord($section, array $tableLines): void
    {
        // Skip separator-only rows (|---|---|)
        $dataRows = array_filter($tableLines, function ($line) {
            return !preg_match('/^\|[\s\-:|]+\|/', trim($line));
        });

        if (empty($dataRows)) {
            return;
        }

        $parsedRows = [];
        foreach ($dataRows as $line) {
            $line = trim($line);
            $line = preg_replace('/^\||\|$/', '', $line); // remove outer pipes
            $cells = array_map('trim', explode('|', $line));
            if (!empty(array_filter($cells, fn($c) => $c !== ''))) {
                $parsedRows[] = $cells;
            }
        }

        if (empty($parsedRows)) {
            return;
        }

        $colCount = max(array_map('count', $parsedRows));
        
        // Calculate cell width in twips (total width ~9000 twips for content area)
        $cellWidth = (int) (9000 / $colCount);

        $table = $section->addTable([
            'borderSize'  => 6,
            'borderColor' => '999999',
            'cellMargin'  => 80,
        ]);

        foreach ($parsedRows as $rowIdx => $cells) {
            $isHeader = ($rowIdx === 0);
            $table->addRow();
            for ($c = 0; $c < $colCount; $c++) {
                $rawCell = $cells[$c] ?? '';
                // Strip bold markers from cell text
                $cellText = preg_replace('/\*\*(.+?)\*\*/', '$1', $rawCell);
                $cellText = $this->sanitizeTextForWord($cellText);

                $cellStyle = $isHeader ? ['bgColor' => '1F3864'] : [];
                $cell = $table->addCell($cellWidth, $cellStyle);
                $cell->addText($cellText, [
                    'name'  => 'Arial',
                    'size'  => 10,
                    'bold'  => $isHeader,
                    'color' => $isHeader ? 'FFFFFF' : '000000',
                ], ['spaceAfter' => 40]);
            }
        }

        $section->addTextBreak(1);
    }

    /**
     * Clean markdown content for Word compatibility
     */
    private function cleanMarkdownForWord($content)
    {
        if (empty($content)) {
            return '';
        }
        
        // Remove null bytes first
        $content = str_replace("\0", '', $content);
        
        // Remove HTML tags
        $content = strip_tags($content);
        
        // Remove code blocks (triple backticks)
        $content = preg_replace('/```[\s\S]*?```/', '', $content);
        
        // Remove inline code (single backticks)
        $content = preg_replace('/`([^`]+)`/', '$1', $content);
        
        // Remove markdown links but keep text: [text](url) -> text
        $content = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $content);
        
        // Remove markdown images: ![alt](url) -> (removed)
        $content = preg_replace('/!\[([^\]]*)\]\([^\)]+\)/', '', $content);
        
        // Remove horizontal rules
        $content = preg_replace('/^[\-\*_]{3,}$/m', '', $content);
        
        // Remove excessive blank lines (more than 2 consecutive)
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        // Remove control characters except newlines, tabs, and carriage returns
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
        
        // Remove problematic Unicode characters
        $content = preg_replace('/[\x{FEFF}\x{FFFD}\x{200B}-\x{200D}\x{2060}\x{FFFE}\x{FFFF}]/u', '', $content);
        
        // Remove XML special characters that might cause issues
        $content = str_replace(['<', '>'], ['(', ')'], $content);
        // Replace & with 'dan' only if it's not part of a word
        $content = preg_replace('/\s+&\s+/', ' dan ', $content);
        
        // Ensure valid UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        }
        
        // Final cleanup
        $content = trim($content);
        
        return $content;
    }

    /**
     * Sanitize text for Word document compatibility (SAFE VERSION)
     */
    private function sanitizeTextForWord($text)
    {
        if (empty($text)) {
            return '';
        }
        
        // Convert to string if not already
        $text = (string) $text;
        
        // Remove null bytes
        $text = str_replace("\0", '', $text);
        
        // Remove control characters except newlines, tabs, and carriage returns
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Remove zero-width characters and other invisible Unicode characters
        $text = preg_replace('/[\x{FEFF}\x{FFFD}\x{200B}-\x{200D}\x{2060}\x{FFFE}\x{FFFF}]/u', '', $text);
        
        // Ensure valid UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        
        // Trim whitespace
        $text = trim($text);
        
        // Limit length to prevent memory issues
        if (mb_strlen($text) > 10000) {
            $text = mb_substr($text, 0, 10000) . '...';
        }
        
        return $text;
    }

    /**
     * Create draft laporan VMTS
     */
    public function createDraft(Request $request)
    {
        try {
            $request->validate([
                'judul_laporan' => 'required|string|max:255',
                'periode_VMTS' => 'required|string',
            ]);

            // Simpan judul dan periode di instruksi_prompt sebagai JSON
            $instruksiPrompt = [
                'judul' => $request->judul_laporan,
                'periode_VMTS' => $request->periode_VMTS,
            ];

            $laporan = LaporanGJM::create([
                'jenis_laporan' => 'VMTS',
                'ringkasan_mutu_institusi' => $request->judul_laporan, // Simpan juga di field ini sebagai fallback
                'instruksi_prompt' => $instruksiPrompt,
                'status_laporan' => 'draft',
                'created_by' => Auth::id(),
            ]);

            Log::info('Draft Laporan VMTS created', [
                'laporan_id' => $laporan->id,
                'user_id' => Auth::id(),
                'judul' => $request->judul_laporan,
                'periode' => $request->periode_VMTS,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Draft laporan VMTS berhasil dibuat',
                'data' => [
                    'id' => $laporan->id,
                    'judul' => $request->judul_laporan,
                    'periode' => $request->periode_VMTS,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $e->validator->errors()->all()),
                'errors' => $e->validator->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to create draft laporan VMTS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat draft: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AI Prompt Assistant for VMTS
     */
    public function aiPrompt(Request $request)
    {
        try {
            $request->validate([
                'prompt' => 'required|string',
                'file_referensi' => 'nullable|array',
                'file_referensi.*' => 'file|mimes:docx,doc,pdf,txt,xlsx,xls,jpg,jpeg,png,gif,webp|max:10240',
                'conversation_history' => 'nullable|string',
                'laporan_id' => 'nullable|exists:laporan_gjm,id',
            ]);

            $userPrompt = $request->input('prompt');
            $conversationHistory = [];
            
            // Parse conversation history
            if ($request->has('conversation_history')) {
                $conversationHistory = json_decode($request->input('conversation_history'), true) ?? [];
            }

            // Process uploaded files
            $fileContents = [];
            if ($request->hasFile('file_referensi')) {
                foreach ($request->file('file_referensi') as $file) {
                    try {
                        $fileContent = $this->extractFileContent($file);
                        if ($fileContent) {
                            $fileContents[] = [
                                'filename' => $file->getClientOriginalName(),
                                'type' => $file->getClientOriginalExtension(),
                                'content' => $fileContent
                            ];
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to extract file content', [
                            'filename' => $file->getClientOriginalName(),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Build comprehensive prompt
            $fullPrompt = $this->buildVMTSPrompt($userPrompt, $fileContents, $conversationHistory);

            Log::info('VMTS AI Prompt', [
                'prompt_length' => strlen($fullPrompt),
                'files_count' => count($fileContents),
                'has_history' => !empty($conversationHistory)
            ]);

            // ========== CACHE CHECK ==========
            // Build cache context for VMTS feature
            $cacheContext = [
                'feature' => 'vmts',  // ← IMPORTANT for evaluation tracking
                'type' => 'laporan_vmts',
                'laporan_id' => $request->input('laporan_id'),
                'has_files' => !empty($fileContents),
                'has_conversation' => !empty($conversationHistory),
            ];

            // Check cache first (only for single messages, not conversations)
            $hasConversationHistory = !empty($conversationHistory);
            $cachedResponse = null;
            
            if (!$hasConversationHistory) {
                $cachedResponse = $this->cacheService->getCachedResponse($userPrompt, $cacheContext);
                
                if ($cachedResponse && $cachedResponse['success']) {
                    Log::info('VMTS AI: Cache hit', [
                        'cache_id' => $cachedResponse['cache_id'] ?? null,
                        'usage_count' => $cachedResponse['usage_count'] ?? 0,
                        'similarity' => $cachedResponse['similarity'] ?? 1.0,
                    ]);

                    return response()->json([
                        'success' => true,
                        'response' => $cachedResponse['text'],
                        'model_info' => $cachedResponse['model'] ?? 'AI',
                        'provider' => $cachedResponse['provider'] ?? 'cache',
                        'files_processed' => count($fileContents),
                        'cached' => true,
                        'cache_id' => $cachedResponse['cache_id'] ?? null,
                        'usage_count' => $cachedResponse['usage_count'] ?? 0,
                    ]);
                }
            }
            // ========== END CACHE CHECK ==========

            // Call AI service
            $result = $this->aiService->generateText($fullPrompt, [
                'temperature' => 0.7,
                'max_tokens' => 8192 // Increased for comprehensive reports
            ]);

            if ($result['success']) {
                // ========== CACHE SAVE ==========
                // Save to cache only for single messages (not conversations)
                if (!$hasConversationHistory && !($result['cached'] ?? false)) {
                    $responseTime = isset($result['processing_time_ms']) ? $result['processing_time_ms'] / 1000 : null;
                    $this->cacheService->cacheResponse(
                        $userPrompt,
                        $cacheContext,
                        $result['text'],
                        $result['provider'] ?? 'unknown',
                        $result['model'] ?? 'unknown',
                        $responseTime
                    );
                    
                    Log::info('VMTS AI: Response cached', [
                        'prompt_length' => strlen($userPrompt),
                        'response_length' => strlen($result['text']),
                        'response_time' => $responseTime,
                        'provider' => $result['provider'],
                        'model' => $result['model']
                    ]);
                }
                // ========== END CACHE SAVE ==========

                return response()->json([
                    'success' => true,
                    'response' => $result['text'],
                    'model_info' => $result['model'] ?? 'AI',
                    'provider' => $result['provider'] ?? 'unknown',
                    'files_processed' => count($fileContents),
                    'cached' => $result['cached'] ?? false,
                ]);
            } else {
                throw new \Exception($result['error'] ?? 'AI generation failed');
            }

        } catch (\Exception $e) {
            Log::error('AI Prompt failed for VMTS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses prompt: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save AI Preview to Database
     * Called after user gets AI response to store it for Word generation
     */
    public function savePreview(Request $request)
    {
        try {
            $request->validate([
                'laporan_id' => 'required|exists:laporan_gjm,id',
                'ai_preview_draft' => 'required|string',
                'ai_sections' => 'nullable|string', // JSON string
                'ocr_images' => 'nullable|string', // JSON string of image paths
            ]);

            $laporanId = $request->input('laporan_id');
            $aiPreviewDraft = $request->input('ai_preview_draft');
            $aiSectionsJson = $request->input('ai_sections', '[]');
            $ocrImagesJson = $request->input('ocr_images', '[]');
            
            // Parse sections from JSON
            $sections = [];
            try {
                $sectionsArray = json_decode($aiSectionsJson, true);
                if (is_array($sectionsArray)) {
                    // Convert sections array to associative array
                    foreach ($sectionsArray as $section) {
                        if (isset($section['title']) && isset($section['content'])) {
                            $key = $this->sectionTitleToKey($section['title']);
                            $sections[$key] = $section['content'];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to parse AI sections JSON', [
                    'laporan_id' => $laporanId,
                    'error' => $e->getMessage()
                ]);
            }

            // Parse OCR images from JSON
            $ocrImages = [];
            try {
                $ocrImagesArray = json_decode($ocrImagesJson, true);
                if (is_array($ocrImagesArray)) {
                    $ocrImages = $ocrImagesArray;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to parse OCR images JSON', [
                    'laporan_id' => $laporanId,
                    'error' => $e->getMessage()
                ]);
            }

            // Update laporan with AI preview data
            $laporan = LaporanGJM::findOrFail($laporanId);
            $laporan->update([
                'ai_preview_draft' => $aiPreviewDraft,
                'ai_sections' => json_encode($sections),
                'ocr_images' => json_encode($ocrImages),
            ]);

            Log::info('AI preview saved for VMTS', [
                'laporan_id' => $laporanId,
                'sections_count' => count($sections),
                'images_count' => count($ocrImages),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AI preview saved successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Save AI preview failed for VMTS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert section title to database key
     */
    private function sectionTitleToKey($title)
    {
        $title = strtolower(trim($title));
        
        $mapping = [
            'pendahuluan' => 'pendahuluan',
            'metode penelitian' => 'metode_penelitian',
            'hasil analisis deskriptif' => 'hasil_analisis',
            'pembahasan' => 'pembahasan',
            'kesimpulan' => 'kesimpulan',
            'rekomendasi' => 'rekomendasi',
            'saran' => 'rekomendasi',
        ];
        
        return $mapping[$title] ?? str_replace(' ', '_', $title);
    }

    /**
     * Extract content from uploaded file
     */
    private function extractFileContent($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $tempPath = $file->store('temp_vmts', 'local');
        $fullPath = storage_path('app/' . $tempPath);

        try {
            $content = '';

            switch ($extension) {
                case 'xlsx':
                case 'xls':
                    $content = $this->extractExcelContent($fullPath);
                    break;

                case 'pdf':
                    $content = $this->extractPdfContent($fullPath);
                    break;

                case 'docx':
                case 'doc':
                    $content = $this->extractWordContent($fullPath);
                    break;

                case 'txt':
                    $content = file_get_contents($fullPath);
                    break;

                default:
                    Log::warning("Unsupported file type: {$extension}");
            }

            return $content;

        } catch (\Exception $e) {
            Log::error('File extraction error', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            return null;
        } finally {
            // Clean up temp file
            @unlink($fullPath);
        }
    }

    /**
     * Extract content from Excel file with detailed structure
     */
    private function extractExcelContent($filePath)
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $content = "=== DATA EXCEL SURVEI SOSIALISASI DAN PEMAHAMAN VISI-MISI ===\n\n";
            $content .= "INSTRUKSI PENTING UNTUK AI:\n";
            $content .= "- Baca SEMUA sheet/tab yang ada di bawah ini\n";
            $content .= "- Setiap sheet berisi data survei untuk unit yang berbeda\n";
            $content .= "- Analisis data responden, pertanyaan, dan jawaban\n";
            $content .= "- Hitung statistik (jumlah responden, distribusi jawaban)\n";
            $content .= "- Buat tabel perbandingan antar unit\n";
            $content .= "- GUNAKAN DATA AKTUAL YANG ADA DI BAWAH INI - JANGAN GUNAKAN PLACEHOLDER!\n\n";
            $content .= str_repeat('=', 100) . "\n\n";

            $sheetCount = 0;
            $totalDataExtracted = 0;
            
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $sheetCount++;
                $sheetName = $sheet->getTitle();
                
                $content .= "╔" . str_repeat('═', 98) . "╗\n";
                $content .= "║ SHEET #{$sheetCount}: {$sheetName}" . str_repeat(' ', 98 - strlen("║ SHEET #{$sheetCount}: {$sheetName}")) . "║\n";
                $content .= "╚" . str_repeat('═', 98) . "╝\n\n";

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                $content .= "📊 INFORMASI SHEET:\n";
                $content .= "   - Total Baris: {$highestRow}\n";
                $content .= "   - Total Kolom: {$highestColumnIndex}\n";
                $content .= "   - Nama Sheet: {$sheetName}\n\n";

                // Read ALL data as table (preserve structure)
                $content .= "📋 DATA LENGKAP (Format Tabel Markdown):\n\n";
                
                $tableData = [];
                $respondentCount = 0;
                
                for ($row = 1; $row <= min($highestRow, 500); $row++) {
                    $rowData = [];
                    $hasContent = false;
                    
                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $cell = $sheet->getCellByColumnAndRow($col, $row);
                        $cellValue = $cell->getCalculatedValue();
                        
                        // Clean cell value
                        if ($cellValue !== null && $cellValue !== '') {
                            $hasContent = true;
                            $cellValue = trim(str_replace(["\n", "\r", "\t"], ' ', $cellValue));
                        } else {
                            $cellValue = '';
                        }
                        
                        $rowData[] = $cellValue;
                    }
                    
                    if ($hasContent) {
                        $tableData[] = $rowData;
                        if ($row > 1) $respondentCount++; // Count data rows (excluding header)
                    }
                }

                // Format as Markdown table
                if (!empty($tableData)) {
                    $content .= "| " . implode(" | ", $tableData[0]) . " |\n"; // Header
                    $content .= "|" . str_repeat(" --- |", count($tableData[0])) . "\n"; // Separator
                    
                    // Data rows (limit to first 100 for readability)
                    $dataRowCount = min(100, count($tableData) - 1);
                    for ($i = 1; $i <= $dataRowCount; $i++) {
                        if (isset($tableData[$i])) {
                            $content .= "| " . implode(" | ", $tableData[$i]) . " |\n";
                        }
                    }
                    
                    if (count($tableData) > 101) {
                        $content .= "\n... (dan " . (count($tableData) - 101) . " baris data lainnya)\n";
                    }
                    
                    $totalDataExtracted += $respondentCount;
                }

                $content .= "\n";
                $content .= "✅ JUMLAH RESPONDEN DI SHEET INI: {$respondentCount}\n";
                $content .= "\n" . str_repeat('=', 100) . "\n\n";
            }

            $content .= "📊 RINGKASAN EKSTRAKSI:\n";
            $content .= "   - Total Sheet: {$sheetCount}\n";
            $content .= "   - Total Responden: {$totalDataExtracted}\n";
            $content .= "   - Status: Data berhasil diekstrak\n\n";
            
            $content .= "⚠️ PERINGATAN UNTUK AI:\n";
            $content .= "   - GUNAKAN data di atas untuk membuat laporan\n";
            $content .= "   - HITUNG jumlah responden dari setiap sheet\n";
            $content .= "   - ANALISIS distribusi jawaban untuk setiap pertanyaan\n";
            $content .= "   - JANGAN gunakan placeholder seperti [hitung dari data] atau [distribusi]\n";
            $content .= "   - WAJIB mengisi semua tabel dengan ANGKA AKTUAL dari data di atas\n\n";

            Log::info('Excel content extracted successfully', [
                'sheets' => $sheetCount,
                'total_respondents' => $totalDataExtracted,
            ]);

            return $content;

        } catch (\Exception $e) {
            Log::error('Excel extraction error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return "❌ Error membaca file Excel: " . $e->getMessage() . "\n\nSilakan upload file Excel yang valid.";
        }
    }

    /**
     * Extract content from PDF file
     */
    private function extractPdfContent($filePath)
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            if (strlen($text) > 50000) {
                $text = substr($text, 0, 50000) . "\n\n[Konten dipotong...]";
            }

            return "=== KONTEN PDF (TEMPLATE REFERENSI) ===\n\n" . $text;

        } catch (\Exception $e) {
            Log::error('PDF extraction error', ['error' => $e->getMessage()]);
            return "Error membaca file PDF: " . $e->getMessage();
        }
    }

    /**
     * Extract content from Word file
     */
    private function extractWordContent($filePath)
    {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
            $content = "=== KONTEN WORD (TEMPLATE REFERENSI) ===\n\n";

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text = $element->getText();
                        if (!empty($text)) {
                            $content .= $text . "\n";
                        }
                    }
                }
            }

            if (strlen($content) > 50000) {
                $content = substr($content, 0, 50000) . "\n\n[Konten dipotong...]";
            }

            return $content;

        } catch (\Exception $e) {
            Log::error('Word extraction error', ['error' => $e->getMessage()]);
            return "Error membaca file Word: " . $e->getMessage();
        }
    }

    /**
     * Build comprehensive VMTS prompt
     */
    private function buildVMTSPrompt($userMessage, $fileContents, $conversationHistory)
    {
        $prompt = "Anda adalah AI Expert untuk Gugus Jaminan Mutu (GJM) Institut Teknologi Del yang SANGAT AHLI dalam analisis data survei dan pembuatan laporan akademik.\n\n";
        
        $prompt .= "TUGAS UTAMA: Membuat LAPORAN ANALISIS DATA HASIL SURVEI SOSIALISASI DAN PEMAHAMAN VISI-MISI yang LENGKAP dan PROFESIONAL.\n\n";
        
        $prompt .= "STRUKTUR LAPORAN WAJIB (HARUS LENGKAP):\n\n";
        
        $prompt .= "# I. Pendahuluan\n";
        $prompt .= "- Jelaskan pentingnya visi-misi dalam institusi pendidikan tinggi\n";
        $prompt .= "- Tujuan survei: mengukur tingkat sosialisasi, pemahaman, dan implementasi visi-misi\n";
        $prompt .= "- Responden: Fakultas/Program Studi yang disurvei (sebutkan dari data)\n";
        $prompt .= "- Konteks: Institut Teknologi Del\n\n";
        
        $prompt .= "# II. Metode Penelitian\n";
        $prompt .= "- Instrumen: Kuesioner dengan skala Likert 1-6\n";
        $prompt .= "- Skala: 1 = sangat tidak setuju, 6 = sangat setuju\n";
        $prompt .= "- Analisis: Statistical Package for the Social Sciences (SPSS)\n";
        $prompt .= "- Statistik: mean, median, variance untuk setiap butir pertanyaan (P1-P10)\n\n";
        
        $prompt .= "# III. Hasil Analisis Deskriptif\n\n";
        
        $prompt .= "## 1. Gambaran Umum Responden\n";
        $prompt .= "WAJIB buat tabel Markdown seperti ini (gunakan data AKTUAL dari Excel):\n\n";
        $prompt .= "| Unit | Jumlah Responden | Rentang Skala | Catatan |\n";
        $prompt .= "|------|------------------|---------------|----------|\n";
        $prompt .= "| Fakultas Vokasi | [hitung dari data] | 1-6 | Data agregat keseluruhan |\n";
        $prompt .= "| Perguruan Tinggi | [hitung dari data] | 1-6 | [keterangan] |\n";
        $prompt .= "| Program Studi D4 TRPL | [hitung dari data] | 1-6 | [keterangan] |\n";
        $prompt .= "| Program Studi D3 TI | [hitung dari data] | 1-6 | [keterangan] |\n";
        $prompt .= "| Program Studi D3 TK | [hitung dari data] | 1-6 | [keterangan] |\n\n";
        
        $prompt .= "CARA MENGHITUNG:\n";
        $prompt .= "- Hitung jumlah responden di setiap sheet Excel\n";
        $prompt .= "- Sheet 'Fakultas Vokasi' = jumlah responden Fakultas Vokasi\n";
        $prompt .= "- Sheet 'Perguruan Tinggi' = jumlah responden Perguruan Tinggi\n";
        $prompt .= "- Sheet 'Program Studi D4 TRPL' = jumlah responden D4 TRPL\n";
        $prompt .= "- Sheet 'Program Studi D3 Teknologi Informasi' = jumlah responden D3 TI\n";
        $prompt .= "- Sheet 'Program Studi D3 Teknologi Komputer' = jumlah responden D3 TK\n\n";
        
        $prompt .= "## 2. Analisis Per Butir Pertanyaan\n";
        $prompt .= "Dari data Excel, identifikasi pertanyaan-pertanyaan survei (biasanya di baris pertama).\n";
        $prompt .= "Untuk setiap pertanyaan, analisis distribusi jawaban dari semua responden.\n\n";
        
        $prompt .= "CONTOH PERTANYAAN YANG MUNGKIN ADA:\n";
        $prompt .= "- Status responden (Mahasiswa/Stakeholder/Dosen/Karyawan)\n";
        $prompt .= "- Lama mengenal IT Del\n";
        $prompt .= "- Tingkat pengetahuan visi-misi\n";
        $prompt .= "- Sumber informasi visi-misi\n";
        $prompt .= "- Frekuensi sosialisasi\n";
        $prompt .= "- Tingkat pemahaman\n";
        $prompt .= "- Aspek yang terakomodasi\n";
        $prompt .= "- Dukungan terhadap kompetensi\n";
        $prompt .= "- Kebutuhan perbaikan\n\n";
        
        $prompt .= "BUAT TABEL ANALISIS seperti ini (gunakan data AKTUAL):\n\n";
        $prompt .= "| No | Aspek yang Dinilai | Fak. Vokasi | Perg. Tinggi | D4 TRPL | D3 TI | D3 TK | Interpretasi |\n";
        $prompt .= "|----|--------------------|-----------|--------------|---------|---------|---------|--------------|\n";
        $prompt .= "| 1 | Status Responden | [distribusi] | [distribusi] | [distribusi] | [distribusi] | [distribusi] | [analisis] |\n";
        $prompt .= "| 2 | Lama Mengenal IT Del | [distribusi] | [distribusi] | [distribusi] | [distribusi] | [distribusi] | [analisis] |\n";
        $prompt .= "| 3 | Tingkat Pengetahuan Visi-Misi | [distribusi] | [distribusi] | [distribusi] | [distribusi] | [distribusi] | [analisis] |\n";
        $prompt .= "| ... | ... | ... | ... | ... | ... | ... | ... |\n\n";
        
        $prompt .= "CARA ANALISIS:\n";
        $prompt .= "1. Untuk setiap pertanyaan, hitung berapa responden yang menjawab setiap opsi\n";
        $prompt .= "2. Contoh: 'Mengetahui' = 30 orang (60%), 'Cukup Mengetahui' = 15 orang (30%), dst\n";
        $prompt .= "3. Bandingkan distribusi antar unit\n";
        $prompt .= "4. Berikan interpretasi: unit mana yang paling baik/perlu perbaikan\n\n";
        
        $prompt .= "INTERPRETASI NILAI:\n";
        $prompt .= "- 1.0 - 2.0 = Rendah (perlu perbaikan mendesak)\n";
        $prompt .= "- 2.1 - 4.0 = Sedang (perlu peningkatan)\n";
        $prompt .= "- 4.1 - 6.0 = Tinggi (sudah baik, pertahankan)\n\n";
        
        $prompt .= "# IV. Pembahasan\n\n";
        
        $prompt .= "## 1. Pola Umum\n";
        $prompt .= "Analisis pola umum dari data (minimal 5 poin):\n";
        $prompt .= "- Aspek mana yang paling tinggi/rendah?\n";
        $prompt .= "- Program studi mana yang paling baik/perlu perbaikan?\n";
        $prompt .= "- Tren sosialisasi vs pemahaman vs implementasi\n";
        $prompt .= "- Kesenjangan antar program studi\n";
        $prompt .= "- Faktor-faktor yang mempengaruhi\n\n";
        
        $prompt .= "## 2. Analisis Komparatif\n";
        $prompt .= "Bandingkan hasil survei antar unit dengan membuat tabel:\n\n";
        $prompt .= "| Aspek | Fak. Vokasi | Perg. Tinggi | D4 TRPL | D3 TI | D3 TK | Kesimpulan |\n";
        $prompt .= "|-------|-------------|--------------|---------|-------|-------|-------------|\n";
        $prompt .= "| Tingkat Pengetahuan Visi-Misi | [%] | [%] | [%] | [%] | [%] | [analisis perbandingan] |\n";
        $prompt .= "| Frekuensi Sosialisasi | [%] | [%] | [%] | [%] | [%] | [analisis perbandingan] |\n";
        $prompt .= "| Tingkat Pemahaman | [%] | [%] | [%] | [%] | [%] | [analisis perbandingan] |\n";
        $prompt .= "| Dukungan terhadap Kompetensi | [%] | [%] | [%] | [%] | [%] | [analisis perbandingan] |\n";
        $prompt .= "| Kebutuhan Perbaikan | [%] | [%] | [%] | [%] | [%] | [analisis perbandingan] |\n\n";
        
        $prompt .= "CARA MENGHITUNG PERSENTASE:\n";
        $prompt .= "- Hitung berapa responden yang menjawab positif (Mengetahui, Paham, Mendukung, dll)\n";
        $prompt .= "- Bagi dengan total responden di unit tersebut\n";
        $prompt .= "- Kalikan 100 untuk mendapat persentase\n";
        $prompt .= "- Contoh: 40 dari 50 responden 'Mengetahui' = 80%\n\n";
        
        $prompt .= "ANALISIS MENDALAM:\n";
        $prompt .= "Setelah tabel, tulis 3-4 paragraf yang membahas:\n\n";
        $prompt .= "**Paragraf 1: Temuan Utama**\n";
        $prompt .= "- Unit mana yang memiliki tingkat pengetahuan tertinggi?\n";
        $prompt .= "- Unit mana yang perlu peningkatan?\n";
        $prompt .= "- Apa pola umum yang terlihat?\n\n";
        
        $prompt .= "**Paragraf 2: Perbandingan Antar Unit**\n";
        $prompt .= "- Mengapa ada perbedaan antar unit?\n";
        $prompt .= "- Faktor apa yang mempengaruhi?\n";
        $prompt .= "- Apakah ada korelasi antara lama mengenal IT Del dengan tingkat pemahaman?\n\n";
        
        $prompt .= "**Paragraf 3: Implikasi dan Rekomendasi**\n";
        $prompt .= "- Apa implikasi temuan ini terhadap kebijakan institusi?\n";
        $prompt .= "- Strategi apa yang perlu dilakukan untuk unit yang lemah?\n";
        $prompt .= "- Bagaimana best practice dari unit terbaik bisa diterapkan ke unit lain?\n\n";
        
        $prompt .= "# V. Kesimpulan\n";
        $prompt .= "Buat 4-5 poin kesimpulan yang:\n";
        $prompt .= "1. Merangkum temuan utama dari analisis\n";
        $prompt .= "2. Menyebutkan program studi dengan performa terbaik/terlemah\n";
        $prompt .= "3. Mengidentifikasi aspek yang perlu diperbaiki\n";
        $prompt .= "4. Menyimpulkan tingkat keberhasilan sosialisasi visi-misi secara keseluruhan\n\n";
        
        $prompt .= "# VI. Rekomendasi\n";
        $prompt .= "Buat 5 rekomendasi SPESIFIK dan ACTIONABLE:\n";
        $prompt .= "1. Strategi peningkatan sosialisasi (dengan metode konkret)\n";
        $prompt .= "2. Integrasi visi-misi dalam kurikulum (dengan cara implementasi)\n";
        $prompt .= "3. Evaluasi berkala (dengan frekuensi dan metode)\n";
        $prompt .= "4. Pelatihan untuk dosen dan tenaga kependidikan (dengan topik spesifik)\n";
        $prompt .= "5. Membangun budaya institusional berbasis visi-misi (dengan kegiatan konkret)\n\n";
        
        $prompt .= str_repeat('=', 100) . "\n\n";
        
        $prompt .= "ATURAN PENTING:\n";
        $prompt .= "✓ Gunakan Bahasa Indonesia formal dan profesional\n";
        $prompt .= "✓ SEMUA tabel HARUS format Markdown dengan separator | dan header row dengan |---|---|\n";
        $prompt .= "✓ Gunakan data AKTUAL dari file Excel yang diupload - JANGAN buat data fiktif\n";
        $prompt .= "✓ Setiap nilai harus disertai interpretasi yang bermakna\n";
        $prompt .= "✓ Analisis harus mendalam, bukan hanya deskripsi angka\n";
        $prompt .= "✓ Gunakan heading Markdown: # untuk judul utama, ## untuk sub-judul\n";
        $prompt .= "✓ Gunakan bullet points (- atau *) untuk list\n";
        $prompt .= "✓ Paragraf harus koheren dan mengalir dengan baik\n\n";
        
        $prompt .= str_repeat('=', 100) . "\n\n";

        // Add conversation history
        if (!empty($conversationHistory)) {
            $prompt .= "=== RIWAYAT PERCAKAPAN ===\n";
            foreach ($conversationHistory as $msg) {
                $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
                $prompt .= "{$role}: {$msg['content']}\n\n";
            }
            $prompt .= str_repeat('=', 100) . "\n\n";
        }

        // Add file contents
        if (!empty($fileContents)) {
            $prompt .= "=== FILE YANG DIUPLOAD (BACA DENGAN TELITI) ===\n\n";
            foreach ($fileContents as $file) {
                $prompt .= "📄 Filename: {$file['filename']}\n";
                $prompt .= "📋 Type: {$file['type']}\n\n";
                $prompt .= $file['content'] . "\n\n";
                $prompt .= str_repeat('=', 100) . "\n\n";
            }
        }

        // Add user message
        $prompt .= "=== INSTRUKSI USER ===\n";
        $prompt .= $userMessage . "\n\n";
        $prompt .= str_repeat('=', 100) . "\n\n";

        // Add task instructions
        if (!empty($fileContents)) {
            $hasExcel = false;
            $hasTemplate = false;

            foreach ($fileContents as $file) {
                if (in_array($file['type'], ['xlsx', 'xls'])) $hasExcel = true;
                if (in_array($file['type'], ['pdf', 'docx', 'doc'])) $hasTemplate = true;
            }

            $prompt .= "=== TUGAS ANDA (WAJIB DILAKUKAN) ===\n";
            if ($hasExcel) {
                $prompt .= "1. BACA dan ANALISIS SEMUA DATA dalam file Excel:\n";
                $prompt .= "   - Semua sheet (tab) yang ada\n";
                $prompt .= "   - Semua tabel data responden\n";
                $prompt .= "   - Semua nilai statistik (mean, median, variance)\n";
                $prompt .= "   - Semua pertanyaan P1-P10 dengan nilai per program studi\n\n";
                
                $prompt .= "2. EKSTRAK informasi penting:\n";
                $prompt .= "   - Nama fakultas dan program studi\n";
                $prompt .= "   - Jumlah responden per unit\n";
                $prompt .= "   - Nilai mean untuk setiap pertanyaan P1-P10\n";
                $prompt .= "   - Interpretasi yang sudah ada di Excel (jika ada)\n\n";
                
                $prompt .= "3. BUAT laporan LENGKAP dengan struktur I-VI di atas\n\n";
                
                $prompt .= "4. PASTIKAN setiap tabel menggunakan format Markdown yang BENAR:\n";
                $prompt .= "   | Header 1 | Header 2 | Header 3 |\n";
                $prompt .= "   |----------|----------|----------|\n";
                $prompt .= "   | Data 1   | Data 2   | Data 3   |\n\n";
                
                $prompt .= "5. BERIKAN interpretasi mendalam untuk setiap temuan:\n";
                $prompt .= "   - Apa arti nilai tersebut?\n";
                $prompt .= "   - Mengapa nilai tinggi/rendah?\n";
                $prompt .= "   - Apa implikasinya?\n";
                $prompt .= "   - Apa yang perlu dilakukan?\n\n";
            }
            
            if ($hasTemplate) {
                $prompt .= "6. GUNAKAN template sebagai referensi:\n";
                $prompt .= "   - Format penulisan\n";
                $prompt .= "   - Gaya bahasa\n";
                $prompt .= "   - Struktur paragraf\n";
                $prompt .= "   - Cara menyajikan data\n\n";
            }
        }

        $prompt .= "OUTPUT YANG DIHARAPKAN:\n";
        $prompt .= "- Laporan LENGKAP dan KOMPREHENSIF dalam format Markdown\n";
        $prompt .= "- Minimal 5-7 halaman jika dikonversi ke Word\n";
        $prompt .= "- Semua bagian (I-VI) harus ada dan lengkap\n";
        $prompt .= "- Semua tabel harus format Markdown yang valid\n";
        $prompt .= "- Analisis harus mendalam dan bermakna\n";
        $prompt .= "- Siap dikonversi ke Word dengan format yang rapi\n\n";
        
        $prompt .= "MULAI MEMBUAT LAPORAN SEKARANG!\n";

        return $prompt;
    }

    /**
     * Download Word document directly (force download)
     */
    public function downloadWord($id)
    {
        try {
            $laporan = LaporanGJM::findOrFail($id);
            
            if (empty($laporan->dokumen_hasil_path)) {
                abort(404, 'File tidak ditemukan');
            }

            $filepath = storage_path('app/public/' . $laporan->dokumen_hasil_path);
            
            if (!file_exists($filepath)) {
                Log::error('File not found for download', [
                    'laporan_id' => $id,
                    'filepath' => $filepath,
                ]);
                abort(404, 'File tidak ditemukan di server');
            }

            $filename = basename($filepath);

            Log::info('Downloading VMTS Word file', [
                'laporan_id' => $id,
                'filename' => $filename,
                'filepath' => $filepath,
            ]);

            return response()->download($filepath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to download VMTS Word', [
                'error' => $e->getMessage(),
                'laporan_id' => $id,
            ]);

            abort(500, 'Gagal mengunduh file: ' . $e->getMessage());
        }
    }

    /**
     * Upload and process Excel file
     */
    public function uploadExcel(Request $request)
    {
        try {
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
            ]);

            $file = $request->file('excel_file');
            
            // Store file temporarily
            $filePath = $file->store('temp_vmts', 'local');
            $fullPath = storage_path('app/' . $filePath);

            // Validate Excel structure
            $validation = $this->vmtsExcelService->validateExcelStructure($fullPath);
            
            if (!$validation['valid']) {
                @unlink($fullPath);
                return response()->json([
                    'success' => false,
                    'message' => $validation['message'],
                ], 400);
            }

            // Extract VMTS data
            $result = $this->vmtsExcelService->extractVMTSData($fullPath);
            
            if (!$result['success']) {
                @unlink($fullPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengekstrak data: ' . $result['error'],
                ], 400);
            }

            // Get preview
            $preview = $this->vmtsExcelService->getExcelPreview($fullPath);

            // Clean up temp file
            @unlink($fullPath);

            return response()->json([
                'success' => true,
                'message' => 'File Excel berhasil diproses',
                'data' => $result['data'],
                'preview' => $preview['preview'] ?? [],
                'filename' => $file->getClientOriginalName(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to upload Excel for VMTS', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file Excel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate report from Excel data using AI
     */
    public function generateFromExcel(Request $request)
    {
        try {
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
                'judul' => 'required|string',
                'periode' => 'required|string',
                'template_id' => 'nullable|exists:template_laporan,id',
            ]);

            $file = $request->file('excel_file');
            
            // Store file temporarily
            $filePath = $file->store('temp_vmts', 'local');
            $fullPath = storage_path('app/' . $filePath);

            // Extract VMTS data
            $result = $this->vmtsExcelService->extractVMTSData($fullPath);
            
            if (!$result['success']) {
                @unlink($fullPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengekstrak data: ' . $result['error'],
                ], 400);
            }

            $vmtsData = $result['data'];

            // Generate basic report content
            $basicContent = $this->vmtsExcelService->generateReportContent(
                $vmtsData,
                $request->judul,
                $request->periode
            );

            // Enhance with AI
            $fullPrompt = "Anda adalah AI Assistant untuk membuat laporan VMTS (Visi, Misi, Tujuan, dan Sasaran).\n\n";
            $fullPrompt .= "Tugas Anda: Memperbaiki dan memperkaya konten laporan VMTS berikut dengan bahasa yang lebih formal dan profesional.\n\n";
            $fullPrompt .= "ATURAN:\n";
            $fullPrompt .= "1. Pertahankan struktur dan informasi yang ada\n";
            $fullPrompt .= "2. Perbaiki tata bahasa dan format\n";
            $fullPrompt .= "3. Tambahkan penjelasan yang relevan jika diperlukan\n";
            $fullPrompt .= "4. Gunakan Bahasa Indonesia formal\n\n";
            $fullPrompt .= "Berikut adalah konten laporan VMTS yang diekstrak dari Excel:\n\n";
            $fullPrompt .= $basicContent . "\n\n";
            $fullPrompt .= "Tolong perbaiki dan perkaya konten di atas menjadi laporan yang lebih profesional.";

            $result = $this->aiService->generateText($fullPrompt, [
                'temperature' => 0.7,
                'max_tokens' => 4096
            ]);

            $enhancedContent = $result['success'] ? $result['text'] : $basicContent;

            // Clean up temp file
            @unlink($fullPath);

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil di-generate dari Excel',
                'content' => $enhancedContent,
                'basic_content' => $basicContent,
                'extracted_data' => $vmtsData,
                'ai_used' => $result['success'],
                'provider' => $result['provider'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate report from Excel', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate laporan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
