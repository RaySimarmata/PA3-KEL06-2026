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

    public function __construct(VMTSExcelService $vmtsExcelService, UnifiedAIService $aiService)
    {
        $this->vmtsExcelService = $vmtsExcelService;
        $this->aiService = $aiService;
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
            $instruksi = json_decode($laporan->instruksi_prompt, true);
            $laporan->periode_VMTS = $instruksi['periode_VMTS'] ?? '-';
            $laporan->judul_laporan = $instruksi['judul'] ?? $laporan->ringkasan_mutu_institusi;
            
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
                $instruksi = json_decode($laporan->instruksi_prompt, true);
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
            
            // Log original data length
            Log::info('Generating Word from AI data', [
                'laporan_id' => $laporan->id,
                'data_length' => strlen($aiPreviewData),
                'first_100_chars' => substr($aiPreviewData, 0, 100)
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

            // Add section with proper settings
            // Use integer twip values (1 inch = 1440 twips, A4 = 11906 x 16838 twips)
            $section = $phpWord->addSection([
                'paperSize'    => 'A4',
                'orientation'  => 'portrait',
                'marginLeft'   => 1701,   // ~3 cm
                'marginRight'  => 1701,   // ~3 cm
                'marginTop'    => 1701,   // ~3 cm
                'marginBottom' => 1701,   // ~3 cm
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

            // Parse markdown content and add to document
            try {
                $this->parseMarkdownToWord($aiPreviewData, $section);
            } catch (\Exception $e) {
                Log::error('Failed to parse markdown', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'data_sample' => mb_substr($aiPreviewData, 0, 200)
                ]);
                
                // Fallback: add as plain text with better formatting
                $cleanText = $this->sanitizeTextForWord($aiPreviewData);
                if (!empty($cleanText)) {
                    // Split by double newlines for paragraphs
                    $paragraphs = preg_split('/\n\s*\n/', $cleanText);
                    foreach ($paragraphs as $para) {
                        $para = trim($para);
                        if (!empty($para)) {
                            $section->addText($para, [
                                'size' => 11,
                                'name' => 'Arial',
                                'color' => '000000'
                            ], ['spaceAfter' => 120]);
                        }
                    }
                }
            }

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
                $objWriter->save($filepath);
            } catch (\Exception $e) {
                Log::error('Failed to save Word document', [
                    'filepath' => $filepath,
                    'error'    => $e->getMessage(),
                    'trace'    => $e->getTraceAsString(),
                ]);
                throw new \Exception('Gagal menyimpan dokumen Word: ' . $e->getMessage());
            }

            // ── Post-process: fix float values in w:pgSz / w:pgMar ────────
            // PhpWord's cmToTwip() returns floats (e.g. 11905.511...) which
            // Microsoft Word rejects. We round all numeric attribute values
            // inside word/document.xml to integers after saving.
            try {
                $zip = new \ZipArchive();
                if ($zip->open($filepath) === true) {
                    $docXml = $zip->getFromName('word/document.xml');
                    if ($docXml !== false) {
                        // Round all w:w, w:h, w:top, w:bottom, w:left,
                        // w:right, w:gutter, w:space attributes to integers
                        $fixed = preg_replace_callback(
                            '/(w:(?:w|h|top|bottom|left|right|gutter|space|header|footer))="([\d]+\.[\d]+)"/',
                            fn($m) => $m[1] . '="' . (string) (int) round((float) $m[2]) . '"',
                            $docXml
                        );
                        if ($fixed && $fixed !== $docXml) {
                            $zip->addFromString('word/document.xml', $fixed);
                            Log::info('Fixed float values in word/document.xml');
                        }
                    }
                    $zip->close();
                }
            } catch (\Exception $e) {
                Log::warning('Post-process float-fix failed (file may still open)', [
                    'error' => $e->getMessage(),
                ]);
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
                throw new \Exception('File yang dihasilkan terlalu kecil, kemungkinan corrupt');
            }
            
            Log::info('File created successfully', [
                'filepath' => $filepath,
                'filesize' => $fileSize,
            ]);

            // Update laporan with document path
            $laporan->update([
                'dokumen_hasil_path' => 'laporan_vmts/' . $filename,
            ]);

            $downloadUrl = asset('storage/laporan_vmts/' . $filename);

            Log::info('Laporan VMTS Word generated', [
                'laporan_id' => $laporan->id,
                'filename' => $filename,
                'download_url' => $downloadUrl,
                'file_size_bytes' => $fileSize,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Laporan VMTS berhasil di-generate',
                'download_url' => $downloadUrl,
                'filename' => $filename,
                'file_size' => $fileSize,
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
                $cell = $table->addCell(null, $cellStyle);
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
        
        // Ensure valid UTF-8
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        
        return $content;
    }

    /**
     * Sanitize text for Word document compatibility
     */
    private function sanitizeTextForWord($text)
    {
        if (empty($text)) {
            return '';
        }
        
        // Convert to string if not already
        $text = (string) $text;
        
        // Remove null bytes first
        $text = str_replace("\0", '', $text);
        
        // Remove control characters except newlines, tabs, and carriage returns
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // NOTE: Do NOT HTML-encode &, <, > here.
        // PhpWord handles XML escaping internally — double-encoding corrupts the .docx file.
        
        // Remove zero-width characters and other invisible Unicode characters
        $text = preg_replace('/[\x{FEFF}\x{FFFD}\x{200B}-\x{200D}\x{2060}\x{FFFE}\x{FFFF}]/u', '', $text);
        
        // Remove any invalid UTF-8 sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Trim whitespace
        $text = trim($text);
        
        // Limit length to prevent memory issues (per line)
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

            $laporan = LaporanGJM::create([
                'user_id' => Auth::id(),
                'judul' => $request->judul_laporan,
                'periode' => $request->periode_VMTS,
                'jenis_laporan' => 'vmts',
                'konten' => '',
                'status' => 'draft',
                'status_laporan' => 'draft',
                'created_by' => Auth::id(),
            ]);

            Log::info('Draft Laporan VMTS created', [
                'laporan_id' => $laporan->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Draft laporan VMTS berhasil dibuat',
                'data' => [
                    'id' => $laporan->id,
                    'judul' => $laporan->judul,
                    'periode' => $laporan->periode,
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

            // Call AI service
            $result = $this->aiService->generateText($fullPrompt, [
                'temperature' => 0.7,
                'max_tokens' => 8192 // Increased for comprehensive reports
            ]);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'response' => $result['text'],
                    'model_info' => $result['model'] ?? 'AI',
                    'provider' => $result['provider'] ?? 'unknown',
                    'files_processed' => count($fileContents),
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
     * Extract content from Excel file
     */
    private function extractExcelContent($filePath)
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $content = "=== DATA EXCEL SURVEI VMTS ===\n\n";

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $sheetName = $sheet->getTitle();
                $content .= "SHEET: {$sheetName}\n";
                $content .= str_repeat('=', 80) . "\n\n";

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                // Read all data
                for ($row = 1; $row <= min($highestRow, 1000); $row++) {
                    $rowData = [];
                    $hasContent = false;
                    
                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $cell = $sheet->getCellByColumnAndRow($col, $row);
                        $cellValue = $cell->getCalculatedValue();
                        
                        if ($cellValue !== null && $cellValue !== '') {
                            $hasContent = true;
                            $rowData[] = $cellValue;
                        } else {
                            $rowData[] = '';
                        }
                    }
                    
                    if ($hasContent) {
                        $content .= implode(' | ', $rowData) . "\n";
                    }
                }
                
                $content .= "\n\n";
            }

            return $content;

        } catch (\Exception $e) {
            Log::error('Excel extraction error', ['error' => $e->getMessage()]);
            return "Error membaca file Excel: " . $e->getMessage();
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
        $prompt = "Anda adalah AI Expert untuk Gugus Jaminan Mutu (GJM) Institut Teknologi Del.\n\n";
        
        $prompt .= "SPESIALISASI: Membuat LAPORAN ANALISIS DATA HASIL SURVEI SOSIALISASI DAN PEMAHAMAN VISI-MISI.\n\n";
        
        $prompt .= "STRUKTUR LAPORAN:\n";
        $prompt .= "I. Pendahuluan\n";
        $prompt .= "II. Metode Penelitian (Kuesioner Likert 1-6, SPSS)\n";
        $prompt .= "III. Hasil Analisis Deskriptif\n";
        $prompt .= "   - Gambaran Umum Responden (tabel)\n";
        $prompt .= "   - Analisis Per Butir Pertanyaan P1-P10 (tabel dengan interpretasi)\n";
        $prompt .= "IV. Pembahasan (Pola Umum + Analisis Komparatif)\n";
        $prompt .= "V. Kesimpulan (4-5 poin)\n";
        $prompt .= "VI. Rekomendasi (5 rekomendasi spesifik)\n\n";
        
        $prompt .= "ATURAN:\n";
        $prompt .= "1. Gunakan Bahasa Indonesia formal dan profesional\n";
        $prompt .= "2. Semua tabel HARUS format Markdown: | Kolom | Data |\n";
        $prompt .= "3. Interpretasi: 1.0-2.0=rendah, 2.1-4.0=sedang, 4.1-6.0=tinggi\n";
        $prompt .= "4. Analisis mendalam, bukan hanya deskripsi angka\n";
        $prompt .= "5. Gunakan data AKTUAL dari file, jangan buat data fiktif\n\n";
        
        $prompt .= str_repeat('=', 80) . "\n\n";

        // Add conversation history
        if (!empty($conversationHistory)) {
            $prompt .= "=== RIWAYAT PERCAKAPAN ===\n";
            foreach ($conversationHistory as $msg) {
                $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
                $prompt .= "{$role}: {$msg['content']}\n\n";
            }
            $prompt .= str_repeat('=', 80) . "\n\n";
        }

        // Add file contents
        if (!empty($fileContents)) {
            $prompt .= "=== FILE YANG DIUPLOAD ===\n\n";
            foreach ($fileContents as $file) {
                $prompt .= "📄 Filename: {$file['filename']}\n";
                $prompt .= "📋 Type: {$file['type']}\n\n";
                $prompt .= $file['content'] . "\n\n";
                $prompt .= str_repeat('=', 80) . "\n\n";
            }
        }

        // Add user message
        $prompt .= "=== INSTRUKSI USER ===\n";
        $prompt .= $userMessage . "\n\n";

        // Add task instructions
        if (!empty($fileContents)) {
            $hasExcel = false;
            $hasTemplate = false;

            foreach ($fileContents as $file) {
                if (in_array($file['type'], ['xlsx', 'xls'])) $hasExcel = true;
                if (in_array($file['type'], ['pdf', 'docx', 'doc'])) $hasTemplate = true;
            }

            $prompt .= "=== TUGAS ANDA ===\n";
            if ($hasExcel) {
                $prompt .= "1. ANALISIS SEMUA DATA dalam Excel (semua sheet, tabel, statistik)\n";
                $prompt .= "2. Ekstrak data responden dan nilai mean per program studi\n";
                $prompt .= "3. Buat laporan LENGKAP dengan semua bagian (I-VI)\n";
                $prompt .= "4. Setiap tabel HARUS format Markdown yang benar\n";
                $prompt .= "5. Berikan interpretasi mendalam untuk setiap temuan\n\n";
            }
            if ($hasTemplate) {
                $prompt .= "- Gunakan template sebagai referensi format dan gaya penulisan\n\n";
            }
        }

        $prompt .= "OUTPUT: Laporan lengkap dalam format Markdown, siap dikonversi ke Word.\n";

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
