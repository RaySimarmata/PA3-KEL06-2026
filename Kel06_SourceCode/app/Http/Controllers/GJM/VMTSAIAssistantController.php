<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Services\VMTSAIAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class VMTSAIAssistantController extends Controller
{
    protected $vmtsAIService;

    public function __construct(VMTSAIAssistantService $vmtsAIService)
    {
        $this->vmtsAIService = $vmtsAIService;
    }

    /**
     * Show AI Assistant page
     */
    public function index()
    {
        $user = Auth::user();
        return view('gjm.vmts-ai-assistant.index', compact('user'));
    }

    /**
     * Process chat with AI
     */
    public function chat(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string',
                'files.*' => 'nullable|file|mimes:xlsx,xls,pdf,docx,doc|max:10240',
                'conversation_history' => 'nullable|array'
            ]);

            $message = $request->input('message');
            $files = $request->file('files', []);
            $conversationHistory = $request->input('conversation_history', []);

            Log::info('VMTS AI Chat Request', [
                'user_id' => Auth::id(),
                'message_length' => strlen($message),
                'files_count' => count($files)
            ]);

            $result = $this->vmtsAIService->processChat($message, $files, $conversationHistory);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'response' => $result['response'],
                    'files_processed' => $result['files_processed'],
                    'file_contents' => $result['file_contents'] ?? [], // Pass file contents for RAGAS
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('VMTS AI Chat Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses chat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate Word document from chat result
     */
    public function downloadWord(Request $request)
    {
        try {
            $request->validate([
                'content' => 'required|string',
                'judul' => 'required|string',
                'periode' => 'required|string',
                'user_message' => 'nullable|string',
                'file_contents' => 'nullable|array',
            ]);

            $content = $request->input('content');
            $judul = $request->input('judul');
            $periode = $request->input('periode');
            $userMessage = $request->input('user_message', 'Generate laporan VMTS');
            $fileContents = $request->input('file_contents', []);

            Log::info('VMTS Generate Word Request', [
                'user_id' => Auth::id(),
                'judul' => $judul,
                'periode' => $periode
            ]);

            // Generate Word document
            $result = $this->vmtsAIService->generateWordDocument($content, $judul, $periode);

            if ($result['success']) {
                // Save laporan to database with RAGAS evaluation
                $saveResult = $this->vmtsAIService->saveLaporanWithRAGAS(
                    $content,
                    $judul,
                    $periode,
                    $userMessage,
                    $fileContents
                );

                if ($saveResult['success']) {
                    Log::info('VMTS laporan saved with RAGAS', [
                        'laporan_id' => $saveResult['laporan_id'],
                        'ragas_score' => $saveResult['ragas_score'],
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'filename' => $result['filename'],
                    'url' => $result['url'],
                    'laporan_id' => $saveResult['laporan_id'] ?? null,
                    'ragas_score' => $saveResult['ragas_score'] ?? null,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('VMTS Word Generation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate Word: ' . $e->getMessage()
            ], 500);
        }
    }
}
