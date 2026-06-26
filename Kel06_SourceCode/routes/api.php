<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\N8nCallbackController;
use App\Http\Controllers\GJM\LaporanTriwulanController;
use App\Http\Controllers\GJM\LaporanSemesterController;
use App\Http\Controllers\API\GKM\DashboardApiController as GKMDashboardApiController;
use App\Http\Controllers\API\GJM\DashboardApiController as GJMDashboardApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ─── AUTH ────────────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'apiLogin']);
Route::post('/logout', [AuthController::class, 'apiLogout']);

// ─── N8N CALLBACK ────────────────────────────────────────────────────────────
Route::post('/n8n/callback', [N8nCallbackController::class, 'handleCallback'])
    ->name('api.n8n.callback');

// ─── AUTHENTICATED ROUTES ────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {

    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

});

// ─── SESSION-BASED AUTH ROUTES ───────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // ── GKM Dashboard ──────────────────────────────────────────────────────
    Route::prefix('gkm')->name('api.gkm.')->group(function () {
        Route::get('/dashboard', [GKMDashboardApiController::class, 'index'])
            ->name('dashboard');
        Route::get('/dashboard/analytics', [GKMDashboardApiController::class, 'analytics'])
            ->name('dashboard.analytics');
    });

    // ── GJM Dashboard ──────────────────────────────────────────────────────
    Route::prefix('gjm')->name('api.gjm.')->group(function () {
        Route::get('/dashboard', [GJMDashboardApiController::class, 'index'])
            ->name('dashboard');
        Route::post('/dashboard/clear-cache', [GJMDashboardApiController::class, 'clearCache'])
            ->name('dashboard.clear-cache');

        // AI Prompt Assistant
        Route::post('/ai-prompt/triwulan', [LaporanTriwulanController::class, 'aiPrompt'])
            ->name('ai-prompt.triwulan');
        Route::post('/ai-prompt/semester', [LaporanSemesterController::class, 'aiPrompt'])
            ->name('ai-prompt.semester');
        Route::post('/ai-assistant/semester', [LaporanSemesterController::class, 'aiAssistant'])
            ->name('ai-assistant.semester');
    });

});