<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LaporanGJM;
use App\Models\LaporanBulanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class N8nCallbackController extends Controller
{
    /**
     * Callback dari n8n setelah generate laporan GJM selesai
     */
    public function laporanCallback(Request $request)
    {
        Log::info("Received n8n callback for laporan", [
            'payload' => $request->all()
        ]);

        try {
            $validated = $request->validate([
                'laporan_id' => 'required|integer',
                'success' => 'required|boolean',
                'content' => 'nullable|string',
                'document_path' => 'nullable|string',
                'error_message' => 'nullable|string',
                'execution_id' => 'nullable|string',
            ]);

            $laporan = LaporanGJM::findOrFail($validated['laporan_id']);

            if ($validated['success']) {
                // Success - update laporan
                $updateData = [
                    'status_laporan' => 'completed',
                    'tanggal_submit' => now(),
                ];

                if (!empty($validated['content'])) {
                    $updateData['ringkasan_mutu_institusi'] = $validated['content'];
                }

                if (!empty($validated['document_path'])) {
                    $updateData['dokumen_hasil_path'] = $validated['document_path'];
                }

                $laporan->update($updateData);

                Log::info("Laporan updated successfully from n8n", [
                    'laporan_id' => $validated['laporan_id']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Laporan callback processed successfully'
                ]);

            } else {
                // Failed - update status
                $laporan->update([
                    'status_laporan' => 'failed',
                ]);

                Log::error("Laporan generation failed in n8n", [
                    'laporan_id' => $validated['laporan_id'],
                    'error' => $validated['error_message'] ?? 'Unknown error'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Laporan generation failed',
                    'error' => $validated['error_message'] ?? 'Unknown error'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error("Failed to process n8n callback", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process callback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Callback dari n8n setelah generate laporan kuesioner selesai
     */
    public function kuesioneCallback(Request $request)
    {
        Log::info("Received n8n callback for kuesioner", [
            'payload' => $request->all()
        ]);

        try {
            $validated = $request->validate([
                'laporan_id' => 'required|integer',
                'success' => 'required|boolean',
                'content' => 'nullable|string',
                'document_path' => 'nullable|string',
                'error_message' => 'nullable|string',
            ]);

            $laporan = LaporanBulanan::findOrFail($validated['laporan_id']);

            if ($validated['success']) {
                $updateData = [
                    'status' => 'completed',
                ];

                if (!empty($validated['content'])) {
                    $updateData['konten_laporan'] = $validated['content'];
                }

                if (!empty($validated['document_path'])) {
                    $updateData['file_path'] = $validated['document_path'];
                }

                $laporan->update($updateData);

                return response()->json([
                    'success' => true,
                    'message' => 'Kuesioner callback processed successfully'
                ]);

            } else {
                $laporan->update(['status' => 'failed']);

                return response()->json([
                    'success' => false,
                    'message' => 'Kuesioner generation failed'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error("Failed to process kuesioner callback", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process callback'
            ], 500);
        }
    }

    /**
     * Webhook untuk menerima file hasil generate dari n8n
     */
    public function receiveDocument(Request $request)
    {
        try {
            $validated = $request->validate([
                'laporan_id' => 'required|integer',
                'laporan_type' => 'required|in:gjm,gkm',
                'document' => 'required|file|mimes:doc,docx,pdf|max:10240',
            ]);

            $file = $request->file('document');
            $filename = 'laporan_' . $validated['laporan_id'] . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            $path = $file->storeAs('temp', $filename, 'local');

            Log::info("Document received from n8n", [
                'laporan_id' => $validated['laporan_id'],
                'path' => $path
            ]);

            return response()->json([
                'success' => true,
                'path' => $path,
                'filename' => $filename
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to receive document from n8n", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get laporan status (untuk polling dari frontend)
     */
    public function getLaporanStatus($id)
    {
        try {
            $laporan = LaporanGJM::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'laporan_id' => $laporan->id,
                    'status' => $laporan->status_laporan,
                    'is_completed' => $laporan->status_laporan === 'completed',
                    'is_failed' => $laporan->status_laporan === 'failed',
                    'has_document' => !empty($laporan->dokumen_hasil_path),
                    'updated_at' => $laporan->updated_at->toIso8601String(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Laporan not found'
            ], 404);
        }
    }
}
