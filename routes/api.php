<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\N8nCallbackController;
use App\Http\Controllers\GJM\LaporanTriwulanController;
use App\Http\Controllers\GJM\LaporanSemesterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// LOGIN API
Route::post('/login', [AuthController::class, 'login']);

// N8n Callback
Route::post('/n8n/callback', [N8nCallbackController::class, 'handleCallback'])
    ->name('api.n8n.callback');

// AI Prompt Assistant - Triwulan & Semester
Route::middleware(['auth'])->group(function () {
    Route::post('/gjm/ai-prompt/triwulan', [LaporanTriwulanController::class, 'aiPrompt'])
        ->name('api.gjm.ai-prompt.triwulan');

    Route::post('/gjm/ai-prompt/semester', [LaporanSemesterController::class, 'aiPrompt'])
        ->name('api.gjm.ai-prompt.semester');

    Route::post('/ai-assistant/semester', [LaporanSemesterController::class, 'aiAssistant'])
        ->name('api.ai-assistant.semester');
});