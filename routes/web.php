<?php

use App\Http\Controllers\Clipper\AuthController;
use App\Http\Controllers\Clipper\ClipController;
use App\Http\Controllers\Clipper\DashboardController;
use App\Http\Controllers\Clipper\HealthController;
use App\Http\Controllers\Clipper\StreamController;
use App\Http\Controllers\Clipper\StreamLogController;
use App\Http\Controllers\Clipper\UploadQueueController;
use App\Http\Controllers\Clipper\YouTubeSettingsController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| GTA VI Auto Clipper Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('clipper')->name('clipper.')->group(function () {
    // Guest Admin Auth
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Protected Admin Routes
    Route::middleware(['web', EnsureUserIsAdmin::class])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Streams
        Route::prefix('streams')->name('streams.')->group(function () {
            Route::get('/', [StreamController::class, 'index'])->name('index');
            Route::get('create', [StreamController::class, 'create'])->name('create');
            Route::post('/', [StreamController::class, 'store'])->name('store');
            Route::get('{id}', [StreamController::class, 'show'])->name('show');
            Route::post('{id}/toggle', [StreamController::class, 'toggle'])->name('toggle');
            Route::post('{id}/capture-highlight', [StreamController::class, 'captureHighlight'])->name('captureHighlight');
            Route::delete('{id}', [StreamController::class, 'destroy'])->name('destroy');
            Route::get('{stream}/logs', [StreamLogController::class, 'index'])->name('logs');
        });

        // Clips Library
        Route::prefix('clips')->name('clips.')->group(function () {
            Route::get('/', [ClipController::class, 'index'])->name('index');
            Route::post('clear-static', [ClipController::class, 'clearStatic'])->name('clearStatic');
            Route::get('{id}', [ClipController::class, 'show'])->name('show');
            Route::put('{id}', [ClipController::class, 'update'])->name('update');
            Route::delete('{id}', [ClipController::class, 'destroy'])->name('destroy');
            Route::post('{id}/approval', [ClipController::class, 'updateApproval'])->name('approval');
        });

        // Upload Queue
        Route::prefix('uploads')->name('uploads.')->group(function () {
            Route::get('/', [UploadQueueController::class, 'index'])->name('index');
            Route::post('{id}/retry', [UploadQueueController::class, 'retry'])->name('retry');
            Route::post('{id}/publish', [UploadQueueController::class, 'publishNow'])->name('publish');
        });

        // YouTube Settings & OAuth
        Route::prefix('youtube')->name('youtube.')->group(function () {
            Route::get('/', [YouTubeSettingsController::class, 'index'])->name('index');
            Route::get('connect', [YouTubeSettingsController::class, 'connect'])->name('connect');
            Route::post('/', [YouTubeSettingsController::class, 'update'])->name('update');
            Route::post('disconnect', [YouTubeSettingsController::class, 'disconnect'])->name('disconnect');
            Route::get('callback', [YouTubeSettingsController::class, 'callback'])->name('callback');
        });

        // Health & Telemetry
        Route::get('health', [HealthController::class, 'index'])->name('health.index');
        Route::post('health/test-ping', [HealthController::class, 'testPing'])->name('health.testPing');

        // General System Logs
        Route::get('logs', [StreamLogController::class, 'index'])->name('logs.index');
    });
});
