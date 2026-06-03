<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\LaporanBulanan;
use App\Models\TemplateLaporan;
use App\Models\Prodi;
use App\Services\LaporanKuesioneService;
use App\Jobs\GenerateLaporanBulananJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LaporanKuesioneController extends Controller
{
    protected $laporanService;

    public function __construct(LaporanKuesioneService $laporanService)
    {
        $this->laporanService = $laporanService;
    }

    /**
     * Display list of laporan
     */
    public function index(Request $request)
    {
        $query = LaporanBulanan::with(['user', 'prodi', 'template'])
            ->orderBy('created_at', 'desc');

        // Filter by periode
        if ($request->filled('periode')) {
            $query->where('periode', $request->periode);
        }

        // Filter by prodi (for GKM TRPL)
        $user = Auth::user();
        if ($user->prodi_id) {
            $query->where('prodi_id', $user->prodi_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $laporanList = $query->paginate(10);

        // Get available periodes for filter
        $periodes = LaporanBulanan::select('periode', 'bulan', 'tahun')
            ->distinct()
            ->orderBy('periode', 'desc')
            ->get();

        return view('gkm.laporan-kuesioner.index', compact('laporanList', 'periodes'));
    }

    /**
     * Show form to create new laporan
     */
    public function create()
    {
        $user = Auth::user();

        // Get active template directly from model
        $template = TemplateLaporan::where('jenis_template', 'laporan_bulanan')
            ->where('is_active', true)
            ->first();

        // Get available templates
        $templates = TemplateLaporan::where('jenis_template', 'laporan_bulanan')
            ->where('is_active', true)
            ->get();

        // Generate periode options (last 6 months)
        $periodes = [];
        for ($i = 0; $i < 6; $i++) {
            $date = Carbon::now()->subMonths($i);
            $periodes[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->locale('id')->translatedFormat('F Y')
            ];
        }

        return view('gkm.laporan-kuesioner.create', compact('template', 'templates', 'periodes'));
    }

    /**
     * Store new laporan (trigger generation)
     */
    public function store(Request $request)
    {
        $request->validate([
            'periode' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'template_id' => 'nullable|exists:template_laporan,id',
            'mode' => 'nullable|in:sync,async', // sync = langsung, async = queue
        ]);

        $user = Auth::user();
        $periode = $request->periode;
        $prodiId = $user->prodi_id;
        $mode = $request->input('mode', 'sync'); // Default: sync (langsung)

        // Check if laporan already exists
        $existing = LaporanBulanan::where('periode', $periode)
            ->where('prodi_id', $prodiId)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Laporan untuk periode ini sudah ada. Silakan hapus terlebih dahulu jika ingin generate ulang.');
        }

        // Parse periode
        $periodeObj = Carbon::createFromFormat('Y-m', $periode);
        $bulan = $periodeObj->locale('id')->translatedFormat('F');
        $tahun = $periodeObj->year;

        // Create laporan record
        $laporan = LaporanBulanan::create([
            'periode' => $periode,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'prodi_id' => $prodiId,
            'user_id' => $user->id,
            'template_id' => $request->template_id,
            'status' => 'pending',
        ]);

        if ($mode === 'sync') {
            // Generate langsung (synchronous) - tidak perlu queue worker
            try {
                $job = new GenerateLaporanBulananJob($laporan->id);
                $job->handle(app(LaporanKuesioneService::class));

                return redirect()->route('gkm.laporan-kuesioner.show', $laporan->id)
                    ->with('success', 'Laporan berhasil di-generate!');
            } catch (\Exception $e) {
                \Log::error('Sync laporan generation failed', [
                    'laporan_id' => $laporan->id,
                    'error' => $e->getMessage()
                ]);

                return redirect()->route('gkm.laporan-kuesioner.show', $laporan->id)
                    ->with('error', 'Gagal generate laporan: ' . $e->getMessage());
            }
        } else {
            // Generate dengan queue (asynchronous) - perlu queue worker
            GenerateLaporanBulananJob::dispatch($laporan->id);

            return redirect()->route('gkm.laporan-kuesioner.show', $laporan->id)
                ->with('success', 'Laporan sedang diproses oleh AI Agent. Halaman akan otomatis refresh.');
        }
    }

    /**
     * Show laporan detail
     */
    public function show($id)
    {
        $laporan = LaporanBulanan::with(['user', 'prodi', 'template'])
            ->findOrFail($id);

        // Check access
        $user = Auth::user();
        if ($user->prodi_id && $laporan->prodi_id != $user->prodi_id) {
            abort(403, 'Unauthorized access');
        }

        return view('gkm.laporan-kuesioner.show', compact('laporan'));
    }

    /**
     * Download laporan
     */
    public function download($id, $format = 'word')
    {
        try {
            $laporan = LaporanBulanan::findOrFail($id);

            // Check access
            $user = Auth::user();
            if ($user->prodi_id && $laporan->prodi_id != $user->prodi_id) {
                abort(403, 'Unauthorized access');
            }

            if ($laporan->status != 'completed') {
                return redirect()->back()->with('error', 'Laporan belum selesai diproses.');
            }

            if ($format == 'word' && $laporan->file_word) {
                $filePath = storage_path('app/' . $laporan->file_word);

                \Log::info('Attempting to download file', [
                    'laporan_id' => $id,
                    'file_path' => $filePath,
                    'file_exists' => file_exists($filePath)
                ]);

                if (!file_exists($filePath)) {
                    \Log::error('File not found', ['path' => $filePath]);
                    return redirect()->back()->with('error', 'File tidak ditemukan di server.');
                }

                $fileName = 'Laporan_Kuesioner_' . $laporan->periode . '_' . ($laporan->prodi->kode_prodi ?? 'GKM') . '.docx';

                return response()->download($filePath, $fileName, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ]);
            } elseif ($format == 'pdf' && $laporan->file_pdf) {
                $filePath = storage_path('app/' . $laporan->file_pdf);

                if (!file_exists($filePath)) {
                    return redirect()->back()->with('error', 'File tidak ditemukan di server.');
                }

                $fileName = 'Laporan_Kuesioner_' . $laporan->periode . '_' . ($laporan->prodi->kode_prodi ?? 'GKM') . '.pdf';

                return response()->download($filePath, $fileName, [
                    'Content-Type' => 'application/pdf',
                ]);
            }

            return redirect()->back()->with('error', 'File tidak tersedia.');

        } catch (\Exception $e) {
            \Log::error('Download error', [
                'laporan_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Terjadi kesalahan saat download: ' . $e->getMessage());
        }
    }

    /**
     * Delete laporan
     */
    public function destroy($id)
    {
        $laporan = LaporanBulanan::findOrFail($id);

        // Check access
        $user = Auth::user();
        if ($user->prodi_id && $laporan->prodi_id != $user->prodi_id) {
            abort(403, 'Unauthorized access');
        }

        // Delete files
        if ($laporan->file_word) {
            Storage::delete($laporan->file_word);
        }
        if ($laporan->file_pdf) {
            Storage::delete($laporan->file_pdf);
        }

        $laporan->delete();

        return redirect()->route('gkm.laporan-kuesioner.index')
            ->with('success', 'Laporan berhasil dihapus.');
    }

    /**
     * Template Management - Index
     */
    public function templateIndex()
    {
        $templates = TemplateLaporan::with(['prodi', 'uploader'])
            ->jenis('laporan_bulanan')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('gkm.laporan-kuesioner.template.index', compact('templates'));
    }

    /**
     * Template Management - Upload Form
     */
    public function templateUpload()
    {
        return view('gkm.laporan-kuesioner.template.upload');
    }

    /**
     * Template Management - Store
     */
    public function templateStore(Request $request)
    {
        $request->validate([
            'nama_template' => 'required|string|max:255',
            'file_template' => 'required|file|mimes:docx,doc,pdf|max:10240',
            'deskripsi' => 'nullable|string',
            'contoh_konten' => 'nullable|string',
        ]);

        $user = Auth::user();
        $file = $request->file('file_template');

        // Validate that the uploaded file is a valid Word document
        if ($file->getClientOriginalExtension() === 'docx' || $file->getClientOriginalExtension() === 'doc') {
            $tempPath = $file->getRealPath();
            $zip = new \ZipArchive();
            $checkResult = $zip->open($tempPath, \ZipArchive::CHECKCONS);

            if ($checkResult !== true) {
                return redirect()->back()
                    ->withErrors(['file_template' => 'File Word yang diupload tidak valid atau corrupt. Silakan coba file lain.'])
                    ->withInput();
            }
            $zip->close();
        }

        // Store file
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('templates', $fileName, 'public');

        // Create template record
        $template = TemplateLaporan::create([
            'prodi_id' => $user->prodi_id,
            'nama_template' => $request->nama_template,
            'nama_file' => $file->getClientOriginalName(),
            'jenis_file' => $file->getClientOriginalExtension(),
            'file_path' => $filePath,
            'ukuran_file' => $file->getSize(),
            'deskripsi' => $request->deskripsi,
            'uploaded_by' => $user->id,
            'jenis_template' => 'laporan_bulanan',
            'contoh_konten' => $request->contoh_konten,
            'is_active' => true,
        ]);

        // Process template structure ke vector DB
        try {
            $this->laporanService->processTemplateToVectorDB($template->id);
            $message = 'Template berhasil diupload dan diproses ke vector database.';
        } catch (\Exception $e) {
            Log::error('Failed to process template to vector DB', [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);
            $message = 'Template berhasil diupload, tapi gagal diproses ke vector DB: ' . $e->getMessage();
        }

        return redirect()->route('gkm.laporan-kuesioner.template.index')
            ->with('success', $message);
    }

    /**
     * Reprocess template ke vector DB
     */
    public function templateReindex($id)
    {
        try {
            $result = $this->laporanService->processTemplateToVectorDB($id);

            return redirect()->back()->with('success',
                "Template berhasil di-reindex. Total chunks: {$result['chunks_indexed']}"
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error',
                'Gagal reindex template: ' . $e->getMessage()
            );
        }
    }

    /**
     * Template Management - Toggle Active
     */
    public function templateToggle($id)
    {
        $template = TemplateLaporan::findOrFail($id);
        $template->is_active = !$template->is_active;
        $template->save();

        return redirect()->back()->with('success', 'Status template berhasil diubah.');
    }

    /**
     * Template Management - Delete
     */
    public function templateDestroy($id)
    {
        $template = TemplateLaporan::findOrFail($id);

        // Check if template is being used
        $usageCount = LaporanBulanan::where('template_id', $id)->count();
        if ($usageCount > 0) {
            return redirect()->back()->with('error', "Template tidak dapat dihapus karena sedang digunakan oleh {$usageCount} laporan.");
        }

        // Delete file
        if ($template->file_path) {
            Storage::disk('public')->delete($template->file_path);
        }

        $template->delete();

        return redirect()->route('gkm.laporan-kuesioner.template.index')
            ->with('success', 'Template berhasil dihapus.');
    }

    /**
     * Download template file
     */
    public function templateDownload($id)
    {
        try {
            $template = TemplateLaporan::findOrFail($id);

            \Log::info('Template download attempt', [
                'template_id' => $id,
                'file_path' => $template->file_path,
                'nama_file' => $template->nama_file
            ]);

            // Try multiple possible file paths
            $possiblePaths = [
                storage_path('app/public/' . $template->file_path),
                storage_path('app/' . $template->file_path),
                storage_path('app/public/templates/' . $template->nama_file),
                storage_path('app/templates/' . $template->nama_file),
            ];

            $filePath = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $filePath = $path;
                    break;
                }
            }

            if (!$filePath) {
                \Log::error('Template file not found', [
                    'template_id' => $id,
                    'checked_paths' => $possiblePaths
                ]);
                return redirect()->back()->with('error', 'File template tidak ditemukan.');
            }

            \Log::info('Template download successful', [
                'template_id' => $id,
                'file_path' => $filePath
            ]);

            $fileName = $template->nama_file;

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);

        } catch (\Exception $e) {
            \Log::error('Template download error', [
                'template_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Terjadi kesalahan saat download: ' . $e->getMessage());
        }
    }
}