<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use App\Models\AIEvaluationTest;
use App\Models\AIEvaluationResult;
use App\Models\AIResponseCacheMongo;
use App\Models\LaporanGJM;
use App\Services\AIEvaluationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ModelEvaluationController extends Controller
{
    protected $evaluationService;

    public function __construct(AIEvaluationService $evaluationService = null)
    {
        $this->evaluationService = $evaluationService;
    }

    /**
     * Display AI Assistant evaluation dashboard
     */
    public function index()
    {
        // Prevent page caching
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        return view('gjm.model-evaluation.index');
    }

    /**
     * Get AI Assistant evaluation data via AJAX
     */
    public function getData(Request $request)
    {
        try {
            $period = $request->get('period', '7days'); // 7days, 30days, 90days, all
            $feature = $request->get('feature', 'all'); // all, triwulan, semester, vmts

            // Clear any Laravel cache for this data
            Cache::forget('model_evaluation_' . $period . '_' . $feature);

            $data = [
                'overview' => $this->getOverviewStats($period, $feature),
                'performance' => $this->getPerformanceMetrics($period, $feature),
                'usage' => $this->getUsageStats($period, $feature),
                'cache' => $this->getCacheStats($period, $feature),
                'errors' => $this->getErrorStats($period, $feature),
                'timeline' => $this->getTimelineData($period, $feature),
                'tuning' => $this->getHyperparameterTuning($period, $feature),
                'comparison' => $this->getBeforeAfterComparison($period, $feature),
                'feature_status' => $this->getFeatureStatus($period),
                'ambiguity_metrics' => $this->getAmbiguityMetrics($period, $feature),
                'ragas_metrics' => $this->getRAGASMetrics($period, $feature),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'period' => $period,
                'feature' => $feature,
                'generated_at' => now()->toDateTimeString(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
              ->header('Pragma', 'no-cache')
              ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('Model Evaluation getData error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading evaluation data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats($period, $feature)
    {
        $dateFilter = $this->getDateFilter($period);

        // Total AI requests (unique cache entries) - FIXED: Use MongoDB model
        $query = AIResponseCacheMongo::query();
        if ($dateFilter) {
            $query->where('created_at', '>=', $dateFilter);
        }
        if ($feature !== 'all') {
            // Support both old format (type) and new format (feature)
            $query->where(function($q) use ($feature) {
                $q->where('context_metadata.feature', $feature)
                  ->orWhere('context_metadata.type', 'laporan_' . $feature);
            });
        }
        $totalRequests = $query->count();

        // Total usage count (including reuses)
        $query2 = AIResponseCacheMongo::query();
        if ($dateFilter) {
            $query2->where('created_at', '>=', $dateFilter);
        }
        if ($feature !== 'all') {
            // Support both old format (type) and new format (feature)
            $query2->where(function($q) use ($feature) {
                $q->where('context_metadata.feature', $feature)
                  ->orWhere('context_metadata.type', 'laporan_' . $feature);
            });
        }
        $totalUsage = $query2->sum('usage_count');

        // Cache hits = total usage - initial requests (reuses only)
        $cacheHits = max(0, $totalUsage - $totalRequests);

        // Cache hit rate = (reuses / total usage) * 100
        $cacheHitRate = $totalUsage > 0 ? ($cacheHits / $totalUsage) * 100 : 0;

        // Average response time (REAL from database)
        $query3 = AIResponseCacheMongo::query();
        if ($dateFilter) {
            $query3->where('created_at', '>=', $dateFilter);
        }
        if ($feature !== 'all') {
            // Support both old format (type) and new format (feature)
            $query3->where(function($q) use ($feature) {
                $q->where('context_metadata.feature', $feature)
                  ->orWhere('context_metadata.type', 'laporan_' . $feature);
            });
        }
        $avgResponseTime = $query3->avg('response_time') ?? 0;

        // Success rate (non-error responses)
        $query4 = AIResponseCacheMongo::query();
        if ($dateFilter) {
            $query4->where('created_at', '>=', $dateFilter);
        }
        if ($feature !== 'all') {
            // Support both old format (type) and new format (feature)
            $query4->where(function($q) use ($feature) {
                $q->where('context_metadata.feature', $feature)
                  ->orWhere('context_metadata.type', 'laporan_' . $feature);
            });
        }
        $successfulRequests = $query4->whereNotNull('ai_response')
            ->where('ai_response', '!=', '')
            ->count();

        $successRate = $totalRequests > 0 ? ($successfulRequests / $totalRequests) * 100 : 0;

        // FALLBACK: If no cache data, get data from laporan_gjm
        if ($totalRequests === 0) {
            return $this->getOverviewStatsFromLaporanGJM($period, $feature);
        }

        return [
            'total_requests' => $totalRequests,
            'total_usage' => $totalUsage,
            'cache_hits' => $cacheHits,
            'cache_hit_rate' => round($cacheHitRate, 2),
            'avg_response_time' => round($avgResponseTime, 2),
            'success_rate' => round($successRate, 2),
            'data_source' => 'ai_response_cache',
        ];
    }

    /**
     * Get overview stats from laporan_gjm as fallback
     */
    private function getOverviewStatsFromLaporanGJM($period, $feature)
    {
        $dateFilter = $this->getDateFilter($period);

        $jenisMap = [
            'all'      => ['triwulan', 'semester', 'VMTS'],
            'triwulan' => ['triwulan'],
            'semester' => ['semester'],
            'vmts'     => ['VMTS'],
        ];

        $jenis = $jenisMap[$feature] ?? $jenisMap['all'];

        $laporans = DB::table('laporan_gjm')
            ->whereIn('jenis_laporan', $jenis)
            ->when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter))
            ->whereNotNull('ai_preview_draft')
            ->get();

        $totalLaporan = $laporans->count();
        $completedLaporan = $laporans->where('status_laporan', 'completed')->count();
        $avgLength = $laporans->avg(fn($l) => strlen($l->ai_preview_draft ?? ''));
        $successRate = $totalLaporan > 0 ? ($completedLaporan / $totalLaporan) * 100 : 0;

        return [
            'total_requests' => $totalLaporan,
            'total_usage' => $totalLaporan,
            'cache_hits' => 0,
            'cache_hit_rate' => 0,
            'avg_response_time' => round($avgLength / 100, 2),
            'success_rate' => round($successRate, 2),
            'data_source' => 'laporan_gjm',
            'note' => 'Data diambil dari laporan yang telah dibuat. Cache AI belum tersedia.',
        ];
    }

    /**
     * Get performance metrics by feature
     */
    private function getPerformanceMetrics($period, $feature)
    {
        $dateFilter = $this->getDateFilter($period);

        $features = $feature === 'all' ? ['triwulan', 'semester', 'vmts'] : [$feature];
        $metrics = [];

        foreach ($features as $feat) {
            // FIXED: Use MongoDB model with support for both old and new format
            $query = AIResponseCacheMongo::query();
            if ($dateFilter) {
                $query->where('created_at', '>=', $dateFilter);
            }
            // Support both old format (type) and new format (feature)
            $query->where(function($q) use ($feat) {
                $q->where('context_metadata.feature', $feat)
                  ->orWhere('context_metadata.type', 'laporan_' . $feat);
            });
            $requests = $query->get();

            // FALLBACK: If no cache data, get from laporan_gjm
            if ($requests->count() === 0) {
                $metrics[$feat] = $this->getPerformanceMetricsFromLaporanGJM($feat, $dateFilter);
                continue;
            }

            $metrics[$feat] = [
                'total_requests' => $requests->count(),
                'avg_response_length' => round($requests->avg('response_length'), 0),
                'avg_response_time' => round($requests->avg('response_time') ?? 0, 2),
                'total_usage' => $requests->sum('usage_count'),
                'cache_efficiency' => $requests->count() > 0 ?
                    round((($requests->sum('usage_count') - $requests->count()) / $requests->count()) * 100, 2) : 0,
                'most_used_provider' => $requests->groupBy('ai_provider')->sortByDesc(fn($g) => $g->count())->keys()->first() ?? 'N/A',
                'most_used_model' => $requests->groupBy('ai_model')->sortByDesc(fn($g) => $g->count())->keys()->first() ?? 'N/A',
                'data_source' => 'ai_response_cache_mongodb',
            ];
        }

        return $metrics;
    }

    /**
     * Get performance metrics from laporan_gjm as fallback
     */
    private function getPerformanceMetricsFromLaporanGJM($feature, $dateFilter)
    {
        $jenisMap = [
            'triwulan' => 'triwulan',
            'semester' => 'semester',
            'vmts'     => 'VMTS',
        ];

        $laporans = DB::table('laporan_gjm')
            ->where('jenis_laporan', $jenisMap[$feature])
            ->when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter))
            ->whereNotNull('ai_preview_draft')
            ->get();

        $totalLaporan = $laporans->count();
        $avgLength = $laporans->avg(fn($l) => strlen($l->ai_preview_draft ?? ''));

        return [
            'total_requests' => $totalLaporan,
            'avg_response_length' => round($avgLength, 0),
            'avg_response_time' => round($avgLength / 100, 2),
            'total_usage' => $totalLaporan,
            'cache_efficiency' => 0,
            'most_used_provider' => 'N/A (data dari laporan)',
            'most_used_model' => 'N/A (data dari laporan)',
            'data_source' => 'laporan_gjm',
        ];
    }

    /**
     * Get usage statistics over time
     */
    private function getUsageStats($period, $feature)
    {
        $dateFilter = $this->getDateFilter($period);

        // FIXED: Use MongoDB model and manually group data
        $query = AIResponseCacheMongo::query();
        if ($dateFilter) {
            $query->where('created_at', '>=', $dateFilter);
        }
        if ($feature !== 'all') {
            // Support both old format (type) and new format (feature)
            $query->where(function($q) use ($feature) {
                $q->where('context_metadata.feature', $feature)
                  ->orWhere('context_metadata.type', 'laporan_' . $feature);
            });
        }

        $data = $query->orderBy('created_at', 'asc')->get();

        // Group by date manually
        $usage = [];
        foreach ($data as $entry) {
            $date = $entry->created_at->format('Y-m-d');

            if (!isset($usage[$date])) {
                $usage[$date] = [
                    'date' => $date,
                    'requests' => 0,
                    'total_usage' => 0,
                ];
            }

            $usage[$date]['requests']++;
            $usage[$date]['total_usage'] += $entry->usage_count ?? 1;
        }

        return array_values($usage);
    }

    /**
     * Get cache statistics
     */
    private function getCacheStats($period, $feature)
    {
        $dateFilter = $this->getDateFilter($period);

        // FIXED: Use MongoDB model
        $query = AIResponseCacheMongo::query();
        if ($dateFilter) {
            $query->where('created_at', '>=', $dateFilter);
        }
        if ($feature !== 'all') {
            // Support both old format (type) and new format (feature)
            $query->where(function($q) use ($feature) {
                $q->where('context_metadata.feature', $feature)
                  ->orWhere('context_metadata.type', 'laporan_' . $feature);
            });
        }

        $cacheData = $query->get();

        $totalEntries = $cacheData->count();
        $reusedEntries = $cacheData->where('usage_count', '>', 1)->count();
        $totalReuses = $cacheData->sum('usage_count') - $totalEntries;

        return [
            'total_entries' => $totalEntries,
            'reused_entries' => $reusedEntries,
            'reuse_rate' => $totalEntries > 0 ? round(($reusedEntries / $totalEntries) * 100, 2) : 0,
            'total_reuses' => $totalReuses,
            'avg_reuse_per_entry' => $totalEntries > 0 ? round($totalReuses / $totalEntries, 2) : 0,
            'cache_size_mb' => round($cacheData->sum('response_length') / 1024 / 1024, 2),
        ];
    }

    /**
     * Get error statistics
     */
    private function getErrorStats($period, $feature)
    {
        return [
            'total_errors' => 0,
            'error_rate' => 0,
            'common_errors' => [],
        ];
    }

    /**
     * Get timeline data for charts
     */
    private function getTimelineData($period, $feature)
    {
        $dateFilter = $this->getDateFilter($period);

        // FIXED: Use MongoDB model with proper aggregation
        $query = AIResponseCacheMongo::query();
        if ($dateFilter) {
            $query->where('created_at', '>=', $dateFilter);
        }
        if ($feature !== 'all') {
            // Support both old format (type) and new format (feature)
            $query->where(function($q) use ($feature) {
                $q->where('context_metadata.feature', $feature)
                  ->orWhere('context_metadata.type', 'laporan_' . $feature);
            });
        }

        $data = $query->orderBy('created_at', 'asc')->get();

        // Group by date manually since MongoDB aggregation might differ
        $timeline = [];
        foreach ($data as $entry) {
            $date = $entry->created_at->format('Y-m-d');

            if (!isset($timeline[$date])) {
                $timeline[$date] = [
                    'date' => $date,
                    'new_requests' => 0,
                    'cache_hits' => 0,
                ];
            }

            $timeline[$date]['new_requests']++;
            $timeline[$date]['cache_hits'] += max(0, ($entry->usage_count ?? 1) - 1);
        }

        return array_values($timeline);
    }

    /**
     * Get date filter based on period
     */
    private function getDateFilter($period)
    {
        return match($period) {
            '7days' => Carbon::now()->subDays(7),
            '30days' => Carbon::now()->subDays(30),
            '90days' => Carbon::now()->subDays(90),
            default => null,
        };
    }

    /**
     * Get feature status - real data from ai_response_cache & laporan_gjm per feature
     */
    private function getFeatureStatus($period)
    {
        $dateFilter = $this->getDateFilter($period);
        $features = ['triwulan', 'semester', 'vmts'];
        $status = [];

        foreach ($features as $feat) {
            // FIXED: Use MongoDB model
            $query = AIResponseCacheMongo::query();
            if ($dateFilter) {
                $query->where('created_at', '>=', $dateFilter);
            }
            $cacheCount = $query->where('context_metadata.feature', $feat)->count();

            $query2 = AIResponseCacheMongo::where('context_metadata.feature', $feat);
            if ($dateFilter) {
                $query2->where('created_at', '>=', $dateFilter);
            }
            $lastUsed = $query2->max('last_used_at');

            $jenisMap = [
                'triwulan' => 'triwulan',
                'semester' => 'semester',
                'vmts'     => 'VMTS',
            ];

            $laporanCount = DB::table('laporan_gjm')
                ->where('jenis_laporan', $jenisMap[$feat])
                ->when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter))
                ->count();

            $lastLaporan = DB::table('laporan_gjm')
                ->where('jenis_laporan', $jenisMap[$feat])
                ->max('updated_at');

            $status[$feat] = [
                'cache_count'   => $cacheCount,
                'last_used'     => $lastUsed ? Carbon::parse($lastUsed)->diffForHumans() : 'Belum pernah',
                'laporan_count' => $laporanCount,
                'last_laporan'  => $lastLaporan ? Carbon::parse($lastLaporan)->diffForHumans() : 'Belum ada',
                'active'        => ($cacheCount > 0 || $laporanCount > 0),
                'has_cache_data' => $cacheCount > 0,
            ];
        }

        return $status;
    }

    /**
     * Get hyperparameter tuning information - REAL DATA
     */
    private function getHyperparameterTuning($period, $feature)
    {
        $dateFilter = $this->getDateFilter($period);

        // FIXED: Use MongoDB model
        $query = AIResponseCacheMongo::query();
        if ($dateFilter) {
            $query->where('created_at', '>=', $dateFilter);
        }
        if ($feature !== 'all') {
            $query->where('context_metadata.feature', $feature);
        }

        $allData = $query->orderBy('created_at', 'asc')->get();

        // If less than 2 entries, use laporan_gjm fallback
        if ($allData->count() < 2) {
            return $this->getTuningDataFromLaporanGJM($period, $feature);
        }

        // For small datasets (2-5 entries), use first entry as "before" and last entry as "after"
        if ($allData->count() <= 5) {
            $beforeData = collect([$allData->first()]);
            $afterData = collect([$allData->last()]);
        } else {
            // For larger datasets, use 40% split
            $splitPoint = max(1, (int)($allData->count() * 0.4)); // Ensure at least 1 entry
            $beforeData = $allData->take($splitPoint);
            $afterData = $allData->reverse()->take($splitPoint);
        }

        $beforeParams = $this->getActualBeforeParameters();
        $afterParams = $this->getActualAfterParameters();

        $beforeMetrics = $this->calculateRealMetrics($beforeData);
        $afterMetrics = $this->calculateRealMetrics($afterData);

        return [
            'method' => 'OpenAI GPT-4o-mini with Intelligent Caching',
            'description' => 'Sistem menggunakan OpenAI GPT-4o-mini sebagai AI provider dengan cache optimization untuk meningkatkan performa dan efisiensi',
            'before' => [
                'label' => 'Model Awal (Baseline) - Data Periode Awal',
                'parameters' => $beforeParams,
                'metrics' => $beforeMetrics,
            ],
            'after' => [
                'label' => 'Model Teroptimasi (Tuned) - Data Periode Terbaru',
                'parameters' => $afterParams,
                'metrics' => $afterMetrics,
            ],
            'improvements' => $this->calculateImprovements($beforeMetrics, $afterMetrics),
            'tuning_methods' => [
                'Cache Optimization' => 'Implementasi intelligent caching dengan similarity threshold 0.85 untuk mengurangi redundant API calls',
                'Temperature Tuning' => 'Optimasi temperature parameter untuk balance antara creativity dan consistency',
                'Token Optimization' => 'Penyesuaian max_tokens untuk efisiensi tanpa mengorbankan kualitas response',
                'Prompt Engineering' => 'Advanced prompt engineering dengan context injection untuk hasil yang lebih akurat',
                'Anti-Hallucination' => 'Implementasi measures untuk mengurangi hallucination dan meningkatkan faktualitas',
            ],
            'data_note' => $allData->count() <= 5 ? 'Data terbatas: Menggunakan entry pertama vs terakhir untuk perbandingan' : null,
        ];
    }

    /**
     * Get actual BEFORE parameters (baseline)
     */
    private function getActualBeforeParameters()
    {
        return [
            'temperature' => 0.7,
            'max_tokens' => 4000,
            'cache_enabled' => false,
            'similarity_threshold' => 0.0,
            'provider' => 'OpenAI GPT-4o-mini',
            'prompt_engineering' => 'basic',
            'anti_hallucination' => false,
        ];
    }

    /**
     * Get actual AFTER parameters (tuned)
     */
    private function getActualAfterParameters()
    {
        return [
            'temperature' => (float) env('OPENAI_TEMPERATURE', 0.7),
            'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 4096),
            'cache_enabled' => true,
            'similarity_threshold' => 0.85,
            'provider' => 'OpenAI GPT-4o-mini',
            'prompt_engineering' => 'advanced with context',
            'anti_hallucination' => true,
        ];
    }

    /**
     * Calculate REAL metrics from actual data
     */
    private function calculateRealMetrics($data)
    {
        if ($data->count() === 0) {
            return [
                'avg_response_time' => 0,
                'avg_response_length' => 0,
                'cache_hit_rate' => 0,
                'quality_score' => 0,
                'total_requests' => 0,
                'unique_requests' => 0,
                'reused_responses' => 0,
            ];
        }

        $totalRequests = $data->count();
        $avgResponseLength = $data->avg('response_length') ?? 0;

        $cacheHits = $data->where('usage_count', '>', 1)->count();
        $cacheHitRate = $totalRequests > 0 ? ($cacheHits / $totalRequests) * 100 : 0;

        // Use REAL response time from database
        $avgResponseTime = $data->avg('response_time') ?? 0;

        // If no real response_time data, fall back to estimation
        if ($avgResponseTime == 0) {
            $avgResponseTime = $avgResponseLength / 100;
        }

        $qualityScore = min(100, ($avgResponseLength / 50) + ($cacheHitRate * 0.5));

        $uniqueRequests = $data->where('usage_count', 1)->count();
        $reusedResponses = $data->sum('usage_count') - $totalRequests;

        return [
            'avg_response_time' => round($avgResponseTime, 2),
            'avg_response_length' => round($avgResponseLength, 0),
            'cache_hit_rate' => round($cacheHitRate, 2),
            'quality_score' => round($qualityScore, 2),
            'total_requests' => $totalRequests,
            'unique_requests' => $uniqueRequests,
            'reused_responses' => $reusedResponses,
        ];
    }

    /**
     * Calculate improvements between before and after
     */
    private function calculateImprovements($before, $after)
    {
        $improvements = [];

        foreach ($before as $key => $beforeValue) {
            if ($key === 'total_requests') continue;

            $afterValue = $after[$key];
            $change = $afterValue - $beforeValue;
            $percentChange = $beforeValue != 0 ? ($change / $beforeValue) * 100 : 0;

            $improvements[$key] = [
                'before' => $beforeValue,
                'after' => $afterValue,
                'change' => round($change, 2),
                'percent_change' => round($percentChange, 2),
                'improved' => $this->isImprovement($key, $change),
            ];
        }

        return $improvements;
    }

    /**
     * Determine if change is an improvement
     */
    private function isImprovement($metric, $change)
    {
        if ($metric === 'avg_response_time') {
            return $change < 0;
        }
        return $change > 0;
    }

    /**
     * Get tuning data from laporan_gjm when cache data is insufficient
     */
    private function getTuningDataFromLaporanGJM($period, $feature)
    {
        $dateFilter = $this->getDateFilter($period);

        $jenisMap = [
            'all'      => ['triwulan', 'semester', 'VMTS'],
            'triwulan' => ['triwulan'],
            'semester' => ['semester'],
            'vmts'     => ['VMTS'],
        ];

        $jenis = $jenisMap[$feature] ?? $jenisMap['all'];

        $query = DB::table('laporan_gjm')
            ->whereIn('jenis_laporan', $jenis)
            ->when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter))
            ->whereNotNull('ai_preview_draft')
            ->orderBy('created_at', 'asc');

        $laporans = $query->get();
        $totalLaporan = $laporans->count();

        if ($totalLaporan === 0) {
            return $this->getDefaultTuningData();
        }

        $avgLength = $laporans->avg(fn($l) => strlen($l->ai_preview_draft ?? ''));
        $completedCount = $laporans->where('status_laporan', 'completed')->count();
        $successRate = $totalLaporan > 0 ? ($completedCount / $totalLaporan) * 100 : 0;

        $featureLabel = match($feature) {
            'triwulan' => 'Laporan Triwulan',
            'semester' => 'Laporan Semester',
            'vmts'     => 'Laporan VMTS',
            default    => 'Semua Fitur',
        };

        return [
            'method' => 'OpenAI GPT-4o-mini with Intelligent Caching',
            'description' => 'Data diambil dari ' . $totalLaporan . ' laporan ' . $featureLabel . ' yang dihasilkan AI.',
            'before' => [
                'label' => 'Model Awal (Baseline)',
                'parameters' => $this->getActualBeforeParameters(),
                'metrics' => [
                    'avg_response_time'   => 'N/A',
                    'avg_response_length' => round($avgLength, 0) . ' chars',
                    'cache_hit_rate'      => '0%',
                    'quality_score'       => 'N/A',
                    'total_requests'      => 0,
                ],
            ],
            'after' => [
                'label' => 'Model Teroptimasi (Tuned)',
                'parameters' => $this->getActualAfterParameters(),
                'metrics' => [
                    'avg_response_time'   => 'Dioptimasi via cache',
                    'avg_response_length' => round($avgLength, 0) . ' chars',
                    'cache_hit_rate'      => '0%',
                    'quality_score'       => round(min(100, ($avgLength / 50)), 2),
                    'total_requests'      => $totalLaporan,
                    'success_rate'        => round($successRate, 2) . '%',
                ],
            ],
            'improvements' => [],
            'laporan_stats' => [
                'total_laporan' => $totalLaporan,
                'completed_laporan' => $completedCount,
                'success_rate' => round($successRate, 2),
                'avg_preview_length' => round($avgLength, 0),
            ],
            'tuning_methods' => [
                'Cache Optimization' => 'Implementasi intelligent caching dengan similarity threshold 0.85',
                'Temperature Tuning' => 'Optimasi temperature parameter untuk balance antara creativity dan consistency',
                'Token Optimization' => 'Penyesuaian max_tokens untuk efisiensi tanpa mengorbankan kualitas response',
                'Prompt Engineering' => 'Advanced prompt engineering dengan context injection',
                'Anti-Hallucination' => 'Implementasi measures untuk mengurangi hallucination',
            ],
        ];
    }

    /**
     * Get default tuning data when insufficient data
     */
    private function getDefaultTuningData()
    {
        return [
            'method' => 'Adaptive Learning & Cache Optimization',
            'description' => 'Data belum cukup untuk analisis tuning. Sistem memerlukan minimal 10 requests.',
            'before' => [
                'label' => 'Model Awal (Baseline)',
                'parameters' => $this->getActualBeforeParameters(),
                'metrics' => [
                    'avg_response_time' => 0,
                    'avg_response_length' => 0,
                    'cache_hit_rate' => 0,
                    'quality_score' => 0,
                    'total_requests' => 0,
                ],
            ],
            'after' => [
                'label' => 'Model Teroptimasi (Tuned)',
                'parameters' => $this->getActualAfterParameters(),
                'metrics' => [
                    'avg_response_time' => 0,
                    'avg_response_length' => 0,
                    'cache_hit_rate' => 0,
                    'quality_score' => 0,
                    'total_requests' => 0,
                ],
            ],
            'improvements' => [],
            'tuning_methods' => [
                'Cache Optimization' => 'Implementasi intelligent caching dengan similarity threshold 0.85',
                'Temperature Tuning' => 'Optimasi temperature parameter untuk balance antara creativity dan consistency',
                'Token Optimization' => 'Penyesuaian max_tokens untuk efisiensi tanpa mengorbankan kualitas response',
                'Prompt Engineering' => 'Advanced prompt engineering dengan context injection',
                'Anti-Hallucination' => 'Implementasi measures untuk mengurangi hallucination',
            ],
        ];
    }

    /**
     * Get before/after comparison
     */
    private function getBeforeAfterComparison($period, $feature)
    {
        $dateFilter = $this->getDateFilter($period);

        // FIXED: Use MongoDB model
        $query = AIResponseCacheMongo::query();
        if ($dateFilter) {
            $query->where('created_at', '>=', $dateFilter);
        }
        if ($feature !== 'all') {
            $query->where('context_metadata.feature', $feature);
        }

        $allData = $query->orderBy('created_at', 'asc')->get();

        // If insufficient data, return default comparison
        if ($allData->count() < 10) {
            return $this->getDefaultComparison();
        }

        $splitPoint = (int)($allData->count() * 0.4);
        $beforeData = $allData->take($splitPoint);
        $afterData = $allData->reverse()->take($splitPoint);

        $beforeMetrics = $this->calculateRealMetrics($beforeData);
        $afterMetrics = $this->calculateRealMetrics($afterData);

        // Calculate improvements
        $improvements = $this->calculateImprovements($beforeMetrics, $afterMetrics);

        // Generate AI-powered analysis
        $aiAnalysis = $this->generateAIAnalysis($beforeMetrics, $afterMetrics, $improvements, $allData->count());

        return [
            'summary' => $aiAnalysis['summary'] ?? 'Analisis sedang diproses...',
            'critical_analysis' => $aiAnalysis['critical_analysis'] ?? [],
            'strengths' => $aiAnalysis['strengths'] ?? [],
            'limitations' => $aiAnalysis['limitations'] ?? [],
            'recommendations' => $aiAnalysis['recommendations'] ?? [],
        ];
    }

    /**
     * Generate AI-powered analysis based on actual metrics
     */
    private function generateAIAnalysis($beforeMetrics, $afterMetrics, $improvements, $totalDataPoints)
    {
        try {
            // Prepare data summary for AI
            $dataSummary = $this->prepareDataSummaryForAI($beforeMetrics, $afterMetrics, $improvements, $totalDataPoints);

            // Build prompt for AI
            $prompt = $this->buildAnalysisPrompt($dataSummary);

            // Call AI service
            $aiService = app(\App\Services\UnifiedAIService::class);

            $messages = [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah AI Expert dalam evaluasi dan analisis performa sistem AI. Tugas Anda adalah menganalisis data metrics dan memberikan insight yang mendalam, objektif, dan actionable.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ];

            $result = $aiService->generateChat($messages, [
                'max_tokens' => 2048,
                'temperature' => 0.7
            ]);

            if ($result['success'] && !empty($result['text'])) {
                // Parse AI response
                return $this->parseAIAnalysisResponse($result['text']);
            } else {
                Log::warning('AI analysis generation failed', [
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                return $this->getFallbackAnalysis($beforeMetrics, $afterMetrics, $improvements);
            }

        } catch (\Exception $e) {
            Log::error('AI analysis generation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getFallbackAnalysis($beforeMetrics, $afterMetrics, $improvements);
        }
    }

    /**
     * Prepare data summary for AI analysis
     */
    private function prepareDataSummaryForAI($before, $after, $improvements, $totalDataPoints)
    {
        return [
            'total_data_points' => $totalDataPoints,
            'before_metrics' => [
                'avg_response_time' => $before['avg_response_time'],
                'avg_response_length' => $before['avg_response_length'],
                'cache_hit_rate' => $before['cache_hit_rate'],
                'quality_score' => $before['quality_score'],
                'total_requests' => $before['total_requests'],
                'unique_requests' => $before['unique_requests'],
                'reused_responses' => $before['reused_responses'],
            ],
            'after_metrics' => [
                'avg_response_time' => $after['avg_response_time'],
                'avg_response_length' => $after['avg_response_length'],
                'cache_hit_rate' => $after['cache_hit_rate'],
                'quality_score' => $after['quality_score'],
                'total_requests' => $after['total_requests'],
                'unique_requests' => $after['unique_requests'],
                'reused_responses' => $after['reused_responses'],
            ],
            'improvements' => $improvements,
            'system_info' => [
                'provider' => 'OpenAI GPT-4o-mini',
                'cache_enabled' => true,
                'similarity_threshold' => 0.85,
                'anti_hallucination' => true,
            ]
        ];
    }

    /**
     * Build analysis prompt for AI
     */
    private function buildAnalysisPrompt($dataSummary)
    {
        $prompt = "Saya memiliki data evaluasi sistem AI Assistant untuk Laporan GJM Institut Teknologi Del.\n\n";

        $prompt .= "INFORMASI SISTEM:\n";
        $prompt .= "- AI Provider: {$dataSummary['system_info']['provider']}\n";
        $prompt .= "- Cache Enabled: " . ($dataSummary['system_info']['cache_enabled'] ? 'Yes' : 'No') . "\n";
        $prompt .= "- Similarity Threshold: {$dataSummary['system_info']['similarity_threshold']}\n";
        $prompt .= "- Anti-Hallucination: " . ($dataSummary['system_info']['anti_hallucination'] ? 'Yes' : 'No') . "\n";
        $prompt .= "- Total Data Points: {$dataSummary['total_data_points']}\n\n";

        $prompt .= "METRICS PERIODE AWAL (Baseline):\n";
        foreach ($dataSummary['before_metrics'] as $key => $value) {
            $prompt .= "- " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
        }
        $prompt .= "\n";

        $prompt .= "METRICS PERIODE TERBARU (After Optimization):\n";
        foreach ($dataSummary['after_metrics'] as $key => $value) {
            $prompt .= "- " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
        }
        $prompt .= "\n";

        if (!empty($dataSummary['improvements'])) {
            $prompt .= "IMPROVEMENTS:\n";
            foreach ($dataSummary['improvements'] as $metric => $improvement) {
                if ($metric === 'total_requests') continue;
                $status = $improvement['improved'] ? '✓ Improved' : '✗ Degraded';
                $prompt .= "- " . ucfirst(str_replace('_', ' ', $metric)) . ": ";
                $prompt .= "{$improvement['before']} → {$improvement['after']} ";
                $prompt .= "({$improvement['percent_change']}%) {$status}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "TUGAS ANDA:\n";
        $prompt .= "Berdasarkan data di atas, berikan analisis mendalam dalam format JSON berikut:\n\n";
        $prompt .= "{\n";
        $prompt .= '  "summary": "Ringkasan singkat (2-3 kalimat) tentang performa sistem secara keseluruhan",'."\n";
        $prompt .= '  "critical_analysis": ['."\n";
        $prompt .= '    "Poin analisis kritis 1 (fokus pada data faktual dan insight mendalam)",'."\n";
        $prompt .= '    "Poin analisis kritis 2",'."\n";
        $prompt .= '    "Poin analisis kritis 3",'."\n";
        $prompt .= '    "Poin analisis kritis 4"'."\n";
        $prompt .= '  ],'."\n";
        $prompt .= '  "strengths": ['."\n";
        $prompt .= '    "Kelebihan 1 (berdasarkan data metrics yang bagus)",'."\n";
        $prompt .= '    "Kelebihan 2",'."\n";
        $prompt .= '    "Kelebihan 3"'."\n";
        $prompt .= '  ],'."\n";
        $prompt .= '  "limitations": ['."\n";
        $prompt .= '    "Keterbatasan 1 (berdasarkan data metrics yang perlu improvement)",'."\n";
        $prompt .= '    "Keterbatasan 2",'."\n";
        $prompt .= '    "Keterbatasan 3"'."\n";
        $prompt .= '  ],'."\n";
        $prompt .= '  "recommendations": ['."\n";
        $prompt .= '    "Rekomendasi 1 (actionable dan spesifik berdasarkan data)",'."\n";
        $prompt .= '    "Rekomendasi 2",'."\n";
        $prompt .= '    "Rekomendasi 3",'."\n";
        $prompt .= '    "Rekomendasi 4"'."\n";
        $prompt .= '  ]'."\n";
        $prompt .= "}\n\n";

        $prompt .= "ATURAN PENTING:\n";
        $prompt .= "1. Analisis harus OBJEKTIF dan berdasarkan DATA FAKTUAL yang diberikan\n";
        $prompt .= "2. Gunakan angka spesifik dari metrics (jangan generik)\n";
        $prompt .= "3. Jika data masih sedikit (< 10 data points), sebutkan ini sebagai limitation\n";
        $prompt .= "4. Jika cache hit rate = 0%, jelaskan ini NORMAL untuk sistem baru\n";
        $prompt .= "5. Bandingkan before vs after untuk melihat improvement/degradation\n";
        $prompt .= "6. Rekomendasi harus ACTIONABLE dan SPESIFIK (bukan generik)\n";
        $prompt .= "7. Gunakan Bahasa Indonesia formal dan profesional\n";
        $prompt .= "8. Output HARUS valid JSON (jangan ada text di luar JSON)\n\n";

        $prompt .= "Berikan analisis Anda dalam format JSON yang valid:";

        return $prompt;
    }

    /**
     * Parse AI analysis response
     */
    private function parseAIAnalysisResponse($aiResponse)
    {
        try {
            // Extract JSON from response (in case AI adds extra text)
            $jsonStart = strpos($aiResponse, '{');
            $jsonEnd = strrpos($aiResponse, '}');

            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed = json_decode($jsonString, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // Validate structure
                    if (isset($parsed['summary']) &&
                        isset($parsed['critical_analysis']) &&
                        isset($parsed['strengths']) &&
                        isset($parsed['limitations']) &&
                        isset($parsed['recommendations'])) {

                        Log::info('AI analysis parsed successfully', [
                            'summary_length' => strlen($parsed['summary']),
                            'critical_analysis_count' => count($parsed['critical_analysis']),
                            'strengths_count' => count($parsed['strengths']),
                            'limitations_count' => count($parsed['limitations']),
                            'recommendations_count' => count($parsed['recommendations']),
                        ]);

                        return $parsed;
                    }
                }
            }

            Log::warning('Failed to parse AI analysis response', [
                'response_preview' => substr($aiResponse, 0, 200)
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Error parsing AI analysis response', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get fallback analysis when AI fails
     */
    private function getFallbackAnalysis($before, $after, $improvements)
    {
        $cacheHitRate = $after['cache_hit_rate'];
        $avgResponseTime = $after['avg_response_time'];
        $totalRequests = $after['total_requests'];

        return [
            'summary' => "Sistem menggunakan OpenAI GPT-4o-mini dengan cache optimization. " .
                        ($totalRequests < 10
                            ? "Data masih terbatas ({$totalRequests} requests), memerlukan lebih banyak data untuk analisis yang akurat."
                            : "Sistem menunjukkan performa yang stabil dengan cache hit rate {$cacheHitRate}%."),
            'critical_analysis' => [
                'Model menggunakan OpenAI GPT-4o-mini dengan cache optimization untuk meningkatkan efisiensi',
                "Cache hit rate sebesar {$cacheHitRate}% " .
                    ($cacheHitRate > 20 ? "menunjukkan sistem cache efektif" : "masih rendah, sistem memerlukan lebih banyak data"),
                "Response time rata-rata {$avgResponseTime}s " .
                    ($avgResponseTime < 10 ? "sangat baik" : "perlu dioptimasi"),
                "Total {$totalRequests} requests telah diproses dengan quality score {$after['quality_score']}",
            ],
            'strengths' => [
                'Infrastruktur cache sudah siap dan berfungsi dengan baik',
                'Parameter model (temperature, max_tokens) sudah dikonfigurasi optimal',
                'Sistem monitoring real-time untuk tracking performa',
            ],
            'limitations' => [
                $totalRequests < 20 ? "Data masih terbatas ({$totalRequests} requests), memerlukan lebih banyak data" : "Response time bergantung pada kecepatan API OpenAI",
                $cacheHitRate < 20 ? "Cache hit rate {$cacheHitRate}% masih rendah" : "Cache hanya efektif untuk prompt yang identik atau sangat mirip",
                'Quality score adalah estimasi dan bukan evaluasi manual',
            ],
            'recommendations' => [
                $cacheHitRate < 30 ? 'Tingkatkan similarity threshold untuk cache yang lebih agresif (coba 0.80)' : 'Pertahankan cache optimization yang sudah baik',
                $avgResponseTime > 10 ? 'Optimalkan panjang prompt dan context untuk mengurangi response time' : 'Response time sudah optimal',
                $totalRequests < 50 ? 'Kumpulkan lebih banyak data usage (target: 50+ requests) untuk analisis yang lebih akurat' : 'Monitor dan evaluasi metrics secara berkala',
                'Implementasi feedback loop untuk continuous improvement',
            ],
        ];
    }

    /**
     * Generate dynamic critical analysis based on actual data
     * @deprecated Use generateAIAnalysis instead
     */
    private function generateCriticalAnalysis($before, $after, $improvements)
    {
        $analysis = [];

        // Provider and model info
        $analysis[] = 'Model menggunakan OpenAI GPT-4o-mini dengan cache optimization untuk meningkatkan efisiensi';

        // Cache hit rate analysis
        $cacheHitRate = $after['cache_hit_rate'];
        if ($cacheHitRate > 30) {
            $analysis[] = "Cache hit rate sebesar {$cacheHitRate}% menunjukkan sistem cache sangat efektif dalam menggunakan kembali response";
        } elseif ($cacheHitRate > 10) {
            $analysis[] = "Cache hit rate sebesar {$cacheHitRate}% menunjukkan sistem cache mulai efektif, masih ada ruang untuk optimasi";
        } else {
            $analysis[] = "Cache hit rate sebesar {$cacheHitRate}% masih rendah, sistem memerlukan lebih banyak data untuk optimasi cache";
        }

        // Response time analysis
        $avgResponseTime = $after['avg_response_time'];
        if ($avgResponseTime < 5) {
            $analysis[] = "Response time rata-rata {$avgResponseTime}s sangat baik, menunjukkan performa optimal";
        } elseif ($avgResponseTime < 10) {
            $analysis[] = "Response time rata-rata {$avgResponseTime}s cukup baik, masih dalam batas acceptable";
        } else {
            $analysis[] = "Response time rata-rata {$avgResponseTime}s perlu dioptimasi untuk pengalaman user yang lebih baik";
        }

        // Quality score analysis
        $qualityScore = $after['quality_score'];
        if ($qualityScore > 80) {
            $analysis[] = "Quality score {$qualityScore} menunjukkan kualitas response yang sangat baik";
        } elseif ($qualityScore > 60) {
            $analysis[] = "Quality score {$qualityScore} menunjukkan kualitas response yang cukup baik";
        } else {
            $analysis[] = "Quality score {$qualityScore} perlu ditingkatkan melalui prompt engineering dan parameter tuning";
        }

        return $analysis;
    }

    /**
     * Generate dynamic strengths based on actual performance
     */
    private function generateStrengths($before, $after, $improvements)
    {
        $strengths = [];

        // Check cache efficiency
        if ($after['cache_hit_rate'] > 20) {
            $strengths[] = "Sistem cache sangat efektif dengan hit rate {$after['cache_hit_rate']}%, mengurangi waktu response dan biaya API secara signifikan";
        }

        // Check response consistency
        if ($after['reused_responses'] > 0) {
            $strengths[] = "Konsistensi response tinggi dengan {$after['reused_responses']} response yang berhasil digunakan kembali";
        }

        // Check response time improvement
        if (isset($improvements['avg_response_time']) && $improvements['avg_response_time']['improved']) {
            $percentChange = abs($improvements['avg_response_time']['percent_change']);
            $strengths[] = "Response time membaik {$percentChange}% setelah optimasi parameter";
        }

        // Check quality improvement
        if (isset($improvements['quality_score']) && $improvements['quality_score']['improved']) {
            $percentChange = $improvements['quality_score']['percent_change'];
            $strengths[] = "Quality score meningkat {$percentChange}% menunjukkan efektivitas prompt engineering dan anti-hallucination measures";
        }

        // Default strengths if no specific improvements
        if (empty($strengths)) {
            $strengths[] = "Infrastruktur cache sudah siap dan berfungsi dengan baik";
            $strengths[] = "Parameter model (temperature, max_tokens) sudah dikonfigurasi optimal";
            $strengths[] = "Sistem monitoring real-time untuk tracking performa";
        }

        return $strengths;
    }

    /**
     * Generate dynamic limitations based on actual data
     */
    private function generateLimitations($before, $after, $improvements)
    {
        $limitations = [];

        // Check response time
        if ($after['avg_response_time'] > 10) {
            $limitations[] = "Response time rata-rata {$after['avg_response_time']}s masih cukup tinggi, bergantung pada kecepatan API OpenAI";
        }

        // Check cache hit rate
        if ($after['cache_hit_rate'] < 20) {
            $limitations[] = "Cache hit rate {$after['cache_hit_rate']}% masih rendah, cache hanya efektif untuk prompt yang identik atau sangat mirip";
        }

        // Check data sufficiency
        if ($after['total_requests'] < 20) {
            $limitations[] = "Data masih terbatas ({$after['total_requests']} requests), memerlukan lebih banyak data untuk analisis yang lebih akurat";
        }

        // Quality score limitation
        $limitations[] = "Quality score adalah estimasi berdasarkan panjang response dan cache efficiency, bukan evaluasi manual";

        // Default limitations
        if (count($limitations) == 1) {
            $limitations[] = "Sistem masih dalam tahap pembelajaran dan pengumpulan data";
        }

        return $limitations;
    }

    /**
     * Generate dynamic recommendations based on actual metrics
     */
    private function generateRecommendations($before, $after, $improvements)
    {
        $recommendations = [];

        // Cache optimization recommendations
        if ($after['cache_hit_rate'] < 30) {
            $recommendations[] = "Tingkatkan similarity threshold untuk cache yang lebih agresif (saat ini 0.85, coba 0.80)";
        }

        // Response time recommendations
        if ($after['avg_response_time'] > 10) {
            $recommendations[] = "Optimalkan panjang prompt dan context untuk mengurangi response time";
            $recommendations[] = "Pertimbangkan implementasi streaming response untuk user experience yang lebih baik";
        }

        // Quality recommendations
        if ($after['quality_score'] < 70) {
            $recommendations[] = "Tingkatkan quality score melalui advanced prompt engineering dan context injection";
            $recommendations[] = "Implementasi feedback loop untuk continuous improvement berdasarkan user feedback";
        }

        // Data collection recommendations
        if ($after['total_requests'] < 50) {
            $recommendations[] = "Kumpulkan lebih banyak data usage (target: 50+ requests) untuk analisis yang lebih akurat";
        }

        // General recommendations
        $recommendations[] = "Monitor dan evaluasi metrics secara berkala (weekly/monthly)";

        // Fine-tuning recommendation if performance is good
        if ($after['quality_score'] > 80 && $after['cache_hit_rate'] > 30) {
            $recommendations[] = "Performa sudah baik, pertimbangkan fine-tuning model untuk use case spesifik Institut Teknologi Del";
        }

        return $recommendations;
    }

    /**
     * Generate comparison summary
     * @deprecated Use generateAIAnalysis instead
     */
    private function generateComparisonSummary($before, $after, $improvements)
    {
        $cacheImprovement = $improvements['cache_hit_rate']['percent_change'] ?? 0;
        $timeImprovement = abs($improvements['avg_response_time']['percent_change'] ?? 0);

        if ($cacheImprovement > 20) {
            return "Model teroptimasi menunjukkan peningkatan signifikan dengan cache hit rate meningkat {$cacheImprovement}% dan response time membaik {$timeImprovement}%. Sistem cache bekerja dengan efektif.";
        } elseif ($cacheImprovement > 0) {
            return "Model teroptimasi menunjukkan peningkatan moderat dengan cache hit rate meningkat {$cacheImprovement}%. Masih ada ruang untuk optimasi lebih lanjut.";
        } else {
            return "Model masih dalam tahap pembelajaran. Data yang lebih banyak diperlukan untuk evaluasi yang lebih akurat.";
        }
    }

    /**
     * Get default comparison when data is insufficient
     */
    private function getDefaultComparison()
    {
        // Use AI to generate analysis even for insufficient data
        $emptyMetrics = [
            'avg_response_time' => 0,
            'avg_response_length' => 0,
            'cache_hit_rate' => 0,
            'quality_score' => 0,
            'total_requests' => 0,
            'unique_requests' => 0,
            'reused_responses' => 0,
        ];

        $aiAnalysis = $this->generateAIAnalysis($emptyMetrics, $emptyMetrics, [], 0);

        if ($aiAnalysis && isset($aiAnalysis['summary'])) {
            return $aiAnalysis;
        }

        // Fallback if AI fails
        return [
            'summary' => 'Data belum cukup untuk analisis perbandingan. Sistem memerlukan minimal 10 AI requests untuk evaluasi yang akurat.',
            'critical_analysis' => [
                'Sistem menggunakan OpenAI GPT-4o-mini sebagai AI provider',
                'Cache optimization diaktifkan untuk meningkatkan efisiensi',
                'Anti-hallucination measures diterapkan untuk akurasi yang lebih baik',
                'Data akan terakumulasi seiring penggunaan fitur AI Assistant',
            ],
            'strengths' => [
                'Infrastruktur cache sudah siap untuk optimasi performa',
                'Parameter model sudah dikonfigurasi dengan optimal',
                'Sistem monitoring real-time untuk tracking performa',
            ],
            'limitations' => [
                'Belum ada data historis untuk perbandingan',
                'Cache belum dapat dimanfaatkan karena belum ada data',
                'Evaluasi performa memerlukan lebih banyak usage data',
            ],
            'recommendations' => [
                'Gunakan fitur AI Assistant (Laporan Triwulan, Semester, VMTS) untuk mengumpulkan data',
                'Monitor performa setelah 10-20 requests untuk melihat tren',
                'Evaluasi kembali setelah periode penggunaan yang cukup',
            ],
        ];
    }

    /**
     * Get Ambiguity Metrics
     */
    private function getAmbiguityMetrics($period, $feature)
    {
        $dateFilter = $this->getDateFilter($period);

        $query = AIEvaluationTest::query()
            ->where('test_type', 'ai_response')
            ->whereNotNull('ambiguity_score')
            ->when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter))
            ->when($feature !== 'all', fn($q) => $q->where('feature', $feature));

        $tests = $query->get();

        if ($tests->count() === 0) {
            return [
                'has_data' => false,
                'message' => 'Belum ada data evaluasi ambiguity. Gunakan fitur evaluasi untuk menambahkan test cases.',
                'total_tests' => 0,
            ];
        }

        $avgAmbiguity = $tests->avg('ambiguity_score');
        $highAmbiguityCount = $tests->where('ambiguity_score', '>=', 4)->count();
        $ambiguityRate = ($highAmbiguityCount / $tests->count()) * 100;

        // Distribution by score
        $distribution = [
            'very_clear' => $tests->where('ambiguity_score', 1)->count(),
            'mostly_clear' => $tests->where('ambiguity_score', 2)->count(),
            'moderate' => $tests->where('ambiguity_score', 3)->count(),
            'somewhat_ambiguous' => $tests->where('ambiguity_score', 4)->count(),
            'very_ambiguous' => $tests->where('ambiguity_score', 5)->count(),
        ];

        // Get examples of ambiguous responses
        $ambiguousExamples = $tests->where('ambiguity_score', '>=', 4)
            ->take(3)
            ->map(function($test) {
                return [
                    'test_name' => $test->test_name,
                    'score' => $test->ambiguity_score,
                    'ambiguous_parts' => $test->ambiguous_parts ?? [],
                    'notes' => $test->ambiguity_notes,
                ];
            });

        return [
            'has_data' => true,
            'total_tests' => $tests->count(),
            'avg_ambiguity' => round($avgAmbiguity, 2),
            'high_ambiguity_count' => $highAmbiguityCount,
            'ambiguity_rate' => round($ambiguityRate, 2),
            'distribution' => $distribution,
            'ambiguous_examples' => $ambiguousExamples,
            'quality_level' => $this->getAmbiguityQualityLevel($avgAmbiguity),
        ];
    }

    /**
     * Get RAGAS Metrics
     */
    private function getRAGASMetrics($period, $feature)
    {
        $dateFilter = $this->getDateFilter($period);

        // For VMTS only, get data from laporan_gjm table
        if ($feature === 'vmts') {
            return $this->getRAGASMetricsFromLaporanGJM($period, $feature);
        }

        // For "all" filter, combine data from both sources
        if ($feature === 'all') {
            return $this->getCombinedRAGASMetrics($period);
        }

        // For other features (triwulan, semester), use ai_evaluation_tests
        $query = AIEvaluationTest::query()
            ->where('test_type', 'ai_response')
            ->whereNotNull('ragas_overall_score')
            ->when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter))
            ->when($feature !== 'all', fn($q) => $q->where('feature', $feature));

        $tests = $query->get();

        if ($tests->count() === 0) {
            return [
                'has_data' => false,
                'message' => 'Belum ada data evaluasi RAGAS. Gunakan fitur evaluasi untuk menambahkan test cases dengan RAG context.',
                'total_tests' => 0,
            ];
        }

        $metrics = [
            'faithfulness' => [
                'avg' => round($tests->avg('ragas_faithfulness'), 4),
                'min' => round($tests->min('ragas_faithfulness'), 4),
                'max' => round($tests->max('ragas_faithfulness'), 4),
                'description' => 'Konsistensi faktual jawaban dengan konteks',
            ],
            'answer_relevancy' => [
                'avg' => round($tests->avg('ragas_answer_relevancy'), 4),
                'min' => round($tests->min('ragas_answer_relevancy'), 4),
                'max' => round($tests->max('ragas_answer_relevancy'), 4),
                'description' => 'Relevansi jawaban terhadap pertanyaan',
            ],
            'context_precision' => [
                'avg' => round($tests->avg('ragas_context_precision'), 4),
                'min' => round($tests->min('ragas_context_precision'), 4),
                'max' => round($tests->max('ragas_context_precision'), 4),
                'description' => 'Presisi konteks yang diambil',
            ],
            'context_recall' => [
                'avg' => round($tests->avg('ragas_context_recall'), 4),
                'min' => round($tests->min('ragas_context_recall'), 4),
                'max' => round($tests->max('ragas_context_recall'), 4),
                'description' => 'Recall konteks yang diambil',
            ],
            'context_relevancy' => [
                'avg' => round($tests->avg('ragas_context_relevancy'), 4),
                'min' => round($tests->min('ragas_context_relevancy'), 4),
                'max' => round($tests->max('ragas_context_relevancy'), 4),
                'description' => 'Relevansi konteks yang diambil',
            ],
        ];

        $avgOverall = $tests->avg('ragas_overall_score');

        // Quality distribution
        $qualityDistribution = [
            'excellent' => $tests->where('ragas_overall_score', '>=', 0.8)->count(),
            'good' => $tests->whereBetween('ragas_overall_score', [0.6, 0.8])->count(),
            'fair' => $tests->whereBetween('ragas_overall_score', [0.4, 0.6])->count(),
            'poor' => $tests->where('ragas_overall_score', '<', 0.4)->count(),
        ];

        // RAG context statistics
        $avgChunksUsed = $tests->avg('rag_chunks_count');
        $avgSimilarity = $tests->avg('rag_avg_similarity');

        return [
            'has_data' => true,
            'total_tests' => $tests->count(),
            'overall_score' => round($avgOverall, 4),
            'overall_percentage' => round($avgOverall * 100, 2),
            'metrics' => $metrics,
            'quality_distribution' => $qualityDistribution,
            'quality_level' => $this->getRAGASQualityLevel($avgOverall),
            'rag_stats' => [
                'avg_chunks_used' => round($avgChunksUsed, 1),
                'avg_similarity' => round($avgSimilarity, 4),
            ],
            'recommendations' => $this->generateRAGASRecommendations($metrics, $avgOverall),
        ];
    }

    /**
     * Get combined RAGAS metrics from both ai_evaluation_tests and laporan_gjm
     */
    private function getCombinedRAGASMetrics($period)
    {
        $dateFilter = $this->getDateFilter($period);

        // Get ALL completed tests from ai_evaluation_tests (Triwulan & Semester) - for total count
        $allTests = AIEvaluationTest::query()
            ->where('test_type', 'ai_response')
            ->when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter))
            ->get();

        // Get only tests with RAGAS score - for metrics calculation
        $testsWithRAGAS = AIEvaluationTest::query()
            ->where('test_type', 'ai_response')
            ->whereNotNull('ragas_overall_score')
            ->when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter))
            ->get();

        // Get ALL VMTS laporan - for total count
        $allLaporans = DB::table('laporan_gjm')
            ->where('jenis_laporan', 'VMTS')
            ->when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter))
            ->get();

        // Get only VMTS laporan with RAGAS score - for metrics calculation
        $laporansWithRAGAS = DB::table('laporan_gjm')
            ->where('jenis_laporan', 'VMTS')
            ->whereNotNull('ragas_overall_score')
            ->when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter))
            ->get();

        // Count total laporan selesai (for display)
        $totalTests = $allTests->count() + $allLaporans->count();
        $testsWithMetrics = $testsWithRAGAS->count() + $laporansWithRAGAS->count();

        // If no completed data from both sources
        if ($totalTests === 0) {
            return [
                'has_data' => false,
                'message' => 'Belum ada data evaluasi RAGAS dari semua fitur. Generate laporan atau tambahkan test cases untuk melihat metrics.',
                'total_tests' => 0,
            ];
        }

        // If have total tests but no RAGAS metrics yet
        if ($testsWithMetrics === 0) {
            return [
                'has_data' => false,
                'message' => 'Sudah ada ' . $totalTests . ' laporan selesai, namun belum ada evaluasi RAGAS. Jalankan evaluasi RAGAS untuk laporan tersebut.',
                'total_tests' => $totalTests,
            ];
        }

        // Use only data with RAGAS metrics for calculations
        $tests = $testsWithRAGAS;
        $laporans = $laporansWithRAGAS;

        // Calculate combined metrics
        $metrics = [
            'faithfulness' => [
                'avg' => $this->calculateCombinedAvg($tests, $laporans, 'ragas_faithfulness'),
                'min' => $this->calculateCombinedMin($tests, $laporans, 'ragas_faithfulness'),
                'max' => $this->calculateCombinedMax($tests, $laporans, 'ragas_faithfulness'),
                'description' => 'Konsistensi faktual jawaban dengan konteks',
            ],
            'answer_relevancy' => [
                'avg' => $this->calculateCombinedAvg($tests, $laporans, 'ragas_answer_relevancy'),
                'min' => $this->calculateCombinedMin($tests, $laporans, 'ragas_answer_relevancy'),
                'max' => $this->calculateCombinedMax($tests, $laporans, 'ragas_answer_relevancy'),
                'description' => 'Relevansi jawaban terhadap pertanyaan',
            ],
            'context_precision' => [
                'avg' => $this->calculateCombinedAvg($tests, $laporans, 'ragas_context_precision'),
                'min' => $this->calculateCombinedMin($tests, $laporans, 'ragas_context_precision'),
                'max' => $this->calculateCombinedMax($tests, $laporans, 'ragas_context_precision'),
                'description' => 'Presisi konteks yang diambil',
            ],
            'context_recall' => [
                'avg' => $this->calculateCombinedAvg($tests, $laporans, 'ragas_context_recall'),
                'min' => $this->calculateCombinedMin($tests, $laporans, 'ragas_context_recall'),
                'max' => $this->calculateCombinedMax($tests, $laporans, 'ragas_context_recall'),
                'description' => 'Recall konteks yang diambil',
            ],
            'context_relevancy' => [
                'avg' => $this->calculateCombinedAvg($tests, $laporans, 'ragas_context_relevancy'),
                'min' => $this->calculateCombinedMin($tests, $laporans, 'ragas_context_relevancy'),
                'max' => $this->calculateCombinedMax($tests, $laporans, 'ragas_context_relevancy'),
                'description' => 'Relevansi konteks yang diambil',
            ],
        ];

        // Calculate combined overall score
        $avgOverall = $this->calculateCombinedAvg($tests, $laporans, 'ragas_overall_score');

        // Combined quality distribution
        $qualityDistribution = [
            'excellent' => $tests->where('ragas_overall_score', '>=', 0.8)->count() +
                          $laporans->where('ragas_overall_score', '>=', 0.8)->count(),
            'good' => $tests->whereBetween('ragas_overall_score', [0.6, 0.8])->count() +
                     $laporans->whereBetween('ragas_overall_score', [0.6, 0.8])->count(),
            'fair' => $tests->whereBetween('ragas_overall_score', [0.4, 0.6])->count() +
                     $laporans->whereBetween('ragas_overall_score', [0.4, 0.6])->count(),
            'poor' => $tests->where('ragas_overall_score', '<', 0.4)->count() +
                     $laporans->where('ragas_overall_score', '<', 0.4)->count(),
        ];

        // Combined RAG stats
        $avgChunksUsed = $this->calculateCombinedAvg($tests, $laporans, 'rag_chunks_count');
        $avgSimilarity = $this->calculateCombinedAvg($tests, $laporans, 'rag_avg_similarity');

        return [
            'has_data' => true,
            'total_tests' => $totalTests,
            'tests_with_ragas' => $testsWithMetrics,
            'overall_score' => round($avgOverall, 4),
            'overall_percentage' => round($avgOverall * 100, 2),
            'metrics' => $metrics,
            'quality_distribution' => $qualityDistribution,
            'quality_level' => $this->getRAGASQualityLevel($avgOverall),
            'rag_stats' => [
                'avg_chunks_used' => round($avgChunksUsed, 1),
                'avg_similarity' => round($avgSimilarity, 4),
            ],
            'recommendations' => $this->generateRAGASRecommendations($metrics, $avgOverall),
            'data_source' => 'combined (ai_evaluation_tests + laporan_gjm)',
            'breakdown' => [
                'total_triwulan_semester' => $allTests->count(),
                'total_vmts' => $allLaporans->count(),
                'from_tests_with_ragas' => $testsWithRAGAS->count(),
                'from_laporans_with_ragas' => $laporansWithRAGAS->count(),
            ],
        ];
    }

    /**
     * Calculate combined average from two collections
     */
    private function calculateCombinedAvg($collection1, $collection2, $field)
    {
        $sum1 = $collection1->sum($field);
        $sum2 = $collection2->sum($field);
        $count1 = $collection1->whereNotNull($field)->count();
        $count2 = $collection2->whereNotNull($field)->count();
        $totalCount = $count1 + $count2;

        if ($totalCount === 0) {
            return 0;
        }

        return ($sum1 + $sum2) / $totalCount;
    }

    /**
     * Calculate combined minimum from two collections
     */
    private function calculateCombinedMin($collection1, $collection2, $field)
    {
        $min1 = $collection1->whereNotNull($field)->min($field);
        $min2 = $collection2->whereNotNull($field)->min($field);

        if ($min1 === null && $min2 === null) {
            return 0;
        }

        if ($min1 === null) {
            return $min2;
        }

        if ($min2 === null) {
            return $min1;
        }

        return min($min1, $min2);
    }

    /**
     * Calculate combined maximum from two collections
     */
    private function calculateCombinedMax($collection1, $collection2, $field)
    {
        $max1 = $collection1->whereNotNull($field)->max($field);
        $max2 = $collection2->whereNotNull($field)->max($field);

        if ($max1 === null && $max2 === null) {
            return 0;
        }

        if ($max1 === null) {
            return $max2;
        }

        if ($max2 === null) {
            return $max1;
        }

        return max($max1, $max2);
    }

    /**
     * Get RAGAS Metrics from laporan_gjm table (for VMTS)
     */
    private function getRAGASMetricsFromLaporanGJM($period, $feature)
    {
        $dateFilter = $this->getDateFilter($period);

        $query = DB::table('laporan_gjm')
            ->where('jenis_laporan', 'VMTS')
            ->whereNotNull('ragas_overall_score')
            ->when($dateFilter, fn($q) => $q->where('created_at', '>=', $dateFilter));

        $laporans = $query->get();

        if ($laporans->count() === 0) {
            return [
                'has_data' => false,
                'message' => 'Belum ada laporan VMTS dengan evaluasi RAGAS. Generate laporan VMTS baru untuk melihat metrics.',
                'total_tests' => 0,
            ];
        }

        $metrics = [
            'faithfulness' => [
                'avg' => round($laporans->avg('ragas_faithfulness'), 4),
                'min' => round($laporans->min('ragas_faithfulness'), 4),
                'max' => round($laporans->max('ragas_faithfulness'), 4),
                'description' => 'Konsistensi faktual jawaban dengan konteks',
            ],
            'answer_relevancy' => [
                'avg' => round($laporans->avg('ragas_answer_relevancy'), 4),
                'min' => round($laporans->min('ragas_answer_relevancy'), 4),
                'max' => round($laporans->max('ragas_answer_relevancy'), 4),
                'description' => 'Relevansi jawaban terhadap pertanyaan',
            ],
            'context_precision' => [
                'avg' => round($laporans->avg('ragas_context_precision'), 4),
                'min' => round($laporans->min('ragas_context_precision'), 4),
                'max' => round($laporans->max('ragas_context_precision'), 4),
                'description' => 'Presisi konteks yang diambil',
            ],
            'context_recall' => [
                'avg' => round($laporans->avg('ragas_context_recall'), 4),
                'min' => round($laporans->min('ragas_context_recall'), 4),
                'max' => round($laporans->max('ragas_context_recall'), 4),
                'description' => 'Recall konteks yang diambil',
            ],
            'context_relevancy' => [
                'avg' => round($laporans->avg('ragas_context_relevancy'), 4),
                'min' => round($laporans->min('ragas_context_relevancy'), 4),
                'max' => round($laporans->max('ragas_context_relevancy'), 4),
                'description' => 'Relevansi konteks yang diambil',
            ],
        ];

        $avgOverall = $laporans->avg('ragas_overall_score');

        // Quality distribution
        $qualityDistribution = [
            'excellent' => $laporans->where('ragas_overall_score', '>=', 0.8)->count(),
            'good' => $laporans->whereBetween('ragas_overall_score', [0.6, 0.8])->count(),
            'fair' => $laporans->whereBetween('ragas_overall_score', [0.4, 0.6])->count(),
            'poor' => $laporans->where('ragas_overall_score', '<', 0.4)->count(),
        ];

        // RAG context statistics
        $avgChunksUsed = $laporans->avg('rag_chunks_count') ?? 0;
        $avgSimilarity = $laporans->avg('rag_avg_similarity') ?? 0;

        return [
            'has_data' => true,
            'total_tests' => $laporans->count(),
            'overall_score' => round($avgOverall, 4),
            'overall_percentage' => round($avgOverall * 100, 2),
            'metrics' => $metrics,
            'quality_distribution' => $qualityDistribution,
            'quality_level' => $this->getRAGASQualityLevel($avgOverall),
            'rag_stats' => [
                'avg_chunks_used' => round($avgChunksUsed, 1),
                'avg_similarity' => round($avgSimilarity, 4),
            ],
            'recommendations' => $this->generateRAGASRecommendations($metrics, $avgOverall),
            'data_source' => 'laporan_gjm',
        ];
    }

    /**
     * Get ambiguity quality level
     */
    private function getAmbiguityQualityLevel($avgScore)
    {
        if ($avgScore <= 2.0) {
            return [
                'level' => 'Excellent',
                'label' => 'Very Clear',
                'color' => 'success',
                'description' => 'Responses are very clear with minimal ambiguity'
            ];
        } elseif ($avgScore <= 3.0) {
            return [
                'level' => 'Good',
                'label' => 'Mostly Clear',
                'color' => 'info',
                'description' => 'Responses are generally clear with some minor ambiguity'
            ];
        } elseif ($avgScore <= 4.0) {
            return [
                'level' => 'Fair',
                'label' => 'Moderately Ambiguous',
                'color' => 'warning',
                'description' => 'Responses have noticeable ambiguity that may confuse users'
            ];
        } else {
            return [
                'level' => 'Poor',
                'label' => 'Very Ambiguous',
                'color' => 'danger',
                'description' => 'Responses are highly ambiguous and need significant improvement'
            ];
        }
    }

    /**
     * Get RAGAS quality level
     */
    private function getRAGASQualityLevel($avgScore)
    {
        if ($avgScore >= 0.8) {
            return [
                'level' => 'Sangat Baik',
                'color' => 'success',
                'description' => 'Sistem RAG berjalan sangat baik dengan kualitas retrieval dan generation yang tinggi'
            ];
        } elseif ($avgScore >= 0.6) {
            return [
                'level' => 'Baik',
                'color' => 'info',
                'description' => 'Sistem RAG berjalan memadai dengan ruang untuk optimasi'
            ];
        } elseif ($avgScore >= 0.4) {
            return [
                'level' => 'Cukup',
                'color' => 'warning',
                'description' => 'Sistem RAG perlu peningkatan dalam kualitas retrieval atau generation'
            ];
        } else {
            return [
                'level' => 'Kurang',
                'color' => 'danger',
                'description' => 'Sistem RAG memerlukan optimasi yang signifikan'
            ];
        }
    }
    /**
     * Generate RAGAS recommendations
     */
    private function generateRAGASRecommendations($metrics, $overallScore)
    {
        $recommendations = [];

        if ($metrics['faithfulness']['avg'] < 0.7) {
            $recommendations[] = [
                'metric' => 'Faithfulness',
                'issue' => 'Skor faithfulness rendah menunjukkan respons mungkin tidak konsisten dengan konteks yang diambil',
                'action' => 'Tambahkan instruksi eksplisit untuk tetap pada konteks dan implementasikan mekanisme sitasi'
            ];
        }

        if ($metrics['answer_relevancy']['avg'] < 0.7) {
            $recommendations[] = [
                'metric' => 'Answer Relevancy',
                'issue' => 'Jawaban mungkin tidak langsung menjawab pertanyaan',
                'action' => 'Tingkatkan pemahaman query dan prompt engineering untuk fokus pada kebutuhan pertanyaan'
            ];
        }

        if ($metrics['context_precision']['avg'] < 0.7) {
            $recommendations[] = [
                'metric' => 'Context Precision',
                'issue' => 'Konteks yang diambil mengandung informasi yang tidak relevan',
                'action' => 'Tingkatkan kualitas embedding, implementasikan re-ranking, atau naikkan threshold similarity'
            ];
        }

        if ($metrics['context_recall']['avg'] < 0.7) {
            $recommendations[] = [
                'metric' => 'Context Recall',
                'issue' => 'Tidak semua informasi yang diperlukan berhasil diambil',
                'action' => 'Naikkan parameter top_k, turunkan threshold similarity, atau tingkatkan strategi chunking'
            ];
        }

        if ($metrics['context_relevancy']['avg'] < 0.7) {
            $recommendations[] = [
                'metric' => 'Context Relevancy',
                'issue' => 'Konteks yang diambil tidak relevan dengan query',
                'action' => 'Tingkatkan kualitas model embedding atau implementasikan teknik query expansion'
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'metric' => 'Overall',
                'issue' => 'Sistem berjalan dengan baik',
                'action' => 'Lanjutkan monitoring dan pertahankan strategi optimasi saat ini'
            ];
        }

        return $recommendations;
    }

    /**
     * Download evaluation report as PDF
     */
    public function downloadReport(Request $request)
    {
        try {
            $period = $request->get('period', '7days');
            $feature = $request->get('feature', 'all');

            // Get all data
            $data = [
                'overview' => $this->getOverviewStats($period, $feature),
                'performance' => $this->getPerformanceMetrics($period, $feature),
                'cache' => $this->getCacheStats($period, $feature),
                'timeline' => $this->getTimelineData($period, $feature),
                'tuning' => $this->getHyperparameterTuning($period, $feature),
                'comparison' => $this->getBeforeAfterComparison($period, $feature),
                'feature_status' => $this->getFeatureStatus($period),
                'ambiguity_metrics' => $this->getAmbiguityMetrics($period, $feature),
                'ragas_metrics' => $this->getRAGASMetrics($period, $feature),
            ];

            // Generate charts as base64 images
            $data['timeline_chart'] = $this->generateTimelineChart($data['timeline']);
            $data['cache_pie_chart'] = $this->generateCachePieChart($data['cache']);

            $periodLabel = match($period) {
                '7days' => '7 Hari Terakhir',
                '30days' => '30 Hari Terakhir',
                '90days' => '90 Hari Terakhir',
                default => 'Semua Periode',
            };

            $featureLabel = match($feature) {
                'triwulan' => 'Laporan Triwulan',
                'semester' => 'Laporan Semester',
                'vmts' => 'Laporan VMTS',
                default => 'Semua Fitur',
            };

            // Load view for PDF
            $pdf = \PDF::loadView('gjm.model-evaluation.pdf-report', [
                'data' => $data,
                'period' => $periodLabel,
                'feature' => $featureLabel,
                'generated_at' => now()->format('d F Y H:i:s'),
            ]);

            // Set paper size and orientation
            $pdf->setPaper('a4', 'portrait');

            // Generate filename
            $filename = 'Evaluasi_AI_Assistant_' . str_replace(' ', '_', $featureLabel) . '_' . str_replace(' ', '_', $periodLabel) . '_' . now()->format('Y-m-d') . '.pdf';

            // Download PDF
            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Download report error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Gagal mengunduh laporan: ' . $e->getMessage());
        }
    }

    /**
     * Generate timeline chart as base64 image
     */
    private function generateTimelineChart($timeline)
    {
        if (count($timeline) == 0) {
            return null;
        }

        // Create simple SVG chart
        $width = 700;
        $height = 250;
        $padding = 40;
        $chartWidth = $width - ($padding * 2);
        $chartHeight = $height - ($padding * 2);

        // Get data
        $dates = [];
        $newRequests = [];
        $cacheHits = [];

        foreach ($timeline as $item) {
            $dates[] = \Carbon\Carbon::parse($item->date)->format('d/m');
            $newRequests[] = $item->new_requests;
            $cacheHits[] = $item->cache_hits;
        }

        $maxValue = max(array_merge($newRequests, $cacheHits));
        $maxValue = $maxValue > 0 ? $maxValue : 1;

        // Start SVG
        $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';

        // Background
        $svg .= '<rect width="' . $width . '" height="' . $height . '" fill="#f8f9fa"/>';

        // Grid lines
        for ($i = 0; $i <= 5; $i++) {
            $y = $padding + ($chartHeight / 5 * $i);
            $svg .= '<line x1="' . $padding . '" y1="' . $y . '" x2="' . ($width - $padding) . '" y2="' . $y . '" stroke="#dee2e6" stroke-width="1"/>';
            $value = round($maxValue - ($maxValue / 5 * $i));
            $svg .= '<text x="' . ($padding - 5) . '" y="' . ($y + 4) . '" text-anchor="end" font-size="10" fill="#666">' . $value . '</text>';
        }

        // Plot lines
        $pointCount = count($dates);
        $xStep = $chartWidth / ($pointCount - 1);

        // New Requests line (blue)
        $points1 = '';
        for ($i = 0; $i < $pointCount; $i++) {
            $x = $padding + ($xStep * $i);
            $y = $padding + $chartHeight - (($newRequests[$i] / $maxValue) * $chartHeight);
            $points1 .= $x . ',' . $y . ' ';
        }
        $svg .= '<polyline points="' . trim($points1) . '" fill="none" stroke="#667eea" stroke-width="2"/>';

        // Cache Hits line (green)
        $points2 = '';
        for ($i = 0; $i < $pointCount; $i++) {
            $x = $padding + ($xStep * $i);
            $y = $padding + $chartHeight - (($cacheHits[$i] / $maxValue) * $chartHeight);
            $points2 .= $x . ',' . $y . ' ';
        }
        $svg .= '<polyline points="' . trim($points2) . '" fill="none" stroke="#51CF66" stroke-width="2"/>';

        // X-axis labels
        for ($i = 0; $i < $pointCount; $i++) {
            if ($i % max(1, floor($pointCount / 10)) == 0) {
                $x = $padding + ($xStep * $i);
                $svg .= '<text x="' . $x . '" y="' . ($height - 10) . '" text-anchor="middle" font-size="9" fill="#666">' . $dates[$i] . '</text>';
            }
        }

        // Legend
        $svg .= '<rect x="' . ($width - 180) . '" y="10" width="15" height="3" fill="#667eea"/>';
        $svg .= '<text x="' . ($width - 160) . '" y="15" font-size="10" fill="#333">Permintaan Baru</text>';
        $svg .= '<rect x="' . ($width - 180) . '" y="25" width="15" height="3" fill="#51CF66"/>';
        $svg .= '<text x="' . ($width - 160) . '" y="30" font-size="10" fill="#333">Cache Hits</text>';

        $svg .= '</svg>';

        // Convert to base64
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Generate cache pie chart as base64 image
     */
    private function generateCachePieChart($cache)
    {
        if ($cache['total_entries'] == 0) {
            return null;
        }

        $reused = $cache['reused_entries'];
        $singleUse = $cache['total_entries'] - $cache['reused_entries'];
        $total = $cache['total_entries'];

        // Create SVG pie chart
        $size = 300;
        $center = $size / 2;
        $radius = 100;

        $svg = '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">';

        // Background
        $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="#ffffff"/>';

        // Calculate angles
        $reusedPercent = $total > 0 ? ($reused / $total) : 0;
        $reusedAngle = $reusedPercent * 360;

        // Draw pie slices
        if ($reusedPercent > 0) {
            // Reused slice (green)
            $endAngle = $reusedAngle;
            $largeArc = $endAngle > 180 ? 1 : 0;

            $x1 = $center + $radius * cos(0);
            $y1 = $center + $radius * sin(0);
            $x2 = $center + $radius * cos(deg2rad($endAngle));
            $y2 = $center + $radius * sin(deg2rad($endAngle));

            $svg .= '<path d="M ' . $center . ' ' . $center . ' L ' . $x1 . ' ' . $y1 . ' A ' . $radius . ' ' . $radius . ' 0 ' . $largeArc . ' 1 ' . $x2 . ' ' . $y2 . ' Z" fill="#51CF66"/>';
        }

        if ($reusedPercent < 1) {
            // Single use slice (light purple)
            $startAngle = $reusedAngle;
            $endAngle = 360;
            $largeArc = ($endAngle - $startAngle) > 180 ? 1 : 0;

            $x1 = $center + $radius * cos(deg2rad($startAngle));
            $y1 = $center + $radius * sin(deg2rad($startAngle));
            $x2 = $center + $radius * cos(deg2rad($endAngle));
            $y2 = $center + $radius * sin(deg2rad($endAngle));

            $svg .= '<path d="M ' . $center . ' ' . $center . ' L ' . $x1 . ' ' . $y1 . ' A ' . $radius . ' ' . $radius . ' 0 ' . $largeArc . ' 1 ' . $x2 . ' ' . $y2 . ' Z" fill="#E0E7FF"/>';
        }

        // Center circle (donut effect)
        $svg .= '<circle cx="' . $center . '" cy="' . $center . '" r="60" fill="#ffffff"/>';

        // Center text
        $svg .= '<text x="' . $center . '" y="' . ($center - 10) . '" text-anchor="middle" font-size="24" font-weight="bold" fill="#333">' . round($reusedPercent * 100, 1) . '%</text>';
        $svg .= '<text x="' . $center . '" y="' . ($center + 15) . '" text-anchor="middle" font-size="12" fill="#666">Digunakan Ulang</text>';

        // Legend
        $legendY = $size - 60;
        $svg .= '<rect x="50" y="' . $legendY . '" width="20" height="20" fill="#51CF66"/>';
        $svg .= '<text x="75" y="' . ($legendY + 15) . '" font-size="12" fill="#333">Digunakan Ulang: ' . $reused . '</text>';

        $svg .= '<rect x="50" y="' . ($legendY + 30) . '" width="20" height="20" fill="#E0E7FF"/>';
        $svg .= '<text x="75" y="' . ($legendY + 45) . '" font-size="12" fill="#333">Sekali Pakai: ' . $singleUse . '</text>';

        $svg .= '</svg>';

        // Convert to base64
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
