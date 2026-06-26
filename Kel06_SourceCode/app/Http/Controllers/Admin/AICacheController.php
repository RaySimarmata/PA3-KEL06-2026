<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AICacheService;
use App\Models\AIResponseCacheMongo;
use Illuminate\Http\Request;

class AICacheController extends Controller
{
    protected $cacheService;

    public function __construct(AICacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Show cache statistics and management page
     */
    public function index()
    {
        $stats = $this->cacheService->getStatistics();

        $recentEntries = AIResponseCacheMongo::orderBy('last_used_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($entry) {

                return [
                    'id' => $entry->id,
                    'prompt_preview' => substr($entry->original_prompt, 0, 100) . '...',
                    'provider' => $entry->ai_provider,
                    'model' => $entry->ai_model,
                    'usage_count' => $entry->usage_count,

                    'last_used' => $entry->last_used_at
                        ? $entry->last_used_at->diffForHumans()
                        : '-',

                    'response_length' => number_format(
                        $entry->response_length ?? 0
                    ),
                ];
            });

        return view(
            'admin.ai-cache.index',
            compact('stats', 'recentEntries')
        );
    }

    /**
     * Clean old cache entries
     */
    public function clean(Request $request)
    {
        $days = $request->input('days', 30);

        $this->cacheService->setMaxCacheAge($days);

        $deleted = $this->cacheService->cleanOldEntries();

        return response()->json([
            'success' => true,
            'message' => "Berhasil menghapus {$deleted} entri cache lama.",
            'deleted_count' => $deleted
        ]);
    }

    /**
     * Get cache statistics as JSON
     */
    public function stats()
    {
        $stats = $this->cacheService->getStatistics();

        return response()->json($stats);
    }

    /**
     * Invalidate cache by context
     */
    public function invalidate(Request $request)
    {
        $request->validate([
            'context' => 'required|array',
        ]);

        $deleted = $this->cacheService
            ->invalidateByContext($request->context);

        return response()->json([
            'success' => true,
            'message' => "Berhasil menginvalidasi {$deleted} entri cache.",
            'deleted_count' => $deleted
        ]);
    }
}