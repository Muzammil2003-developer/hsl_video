<?php

use App\Http\Controllers\LiveStreamController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('videos.index');
    }
    return view('welcome');
});

Route::get('/dashboard', function () {
    $videosCount = auth()->user()->videos()->count();
    $readyCount = auth()->user()->videos()->where('status', 'ready')->count();
    $processingCount = auth()->user()->videos()->where('status', 'processing')->count();
    $totalSize = auth()->user()->videos()->sum('file_size');

    $liveStreamsCount = auth()->user()->liveStreams()->count();
    $currentlyLive = auth()->user()->liveStreams()->where('status', 'live')->count();

    return view('dashboard', compact('videosCount', 'readyCount', 'processingCount', 'totalSize', 'liveStreamsCount', 'currentlyLive'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Video management
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/upload', [VideoController::class, 'create'])->name('videos.create');
    Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
    Route::get('/videos/{video}/edit', [VideoController::class, 'edit'])->name('videos.edit');
    Route::put('/videos/{video}', [VideoController::class, 'update'])->name('videos.update');
    Route::delete('/videos/{video}', [VideoController::class, 'destroy'])->name('videos.destroy');

    // Chunked upload endpoints
    Route::post('/upload/initiate', [VideoUploadController::class, 'initiate'])->name('upload.initiate');
    Route::post('/upload/chunk', [VideoUploadController::class, 'chunk'])->name('upload.chunk');
    Route::post('/upload/finalize', [VideoUploadController::class, 'finalize'])->name('upload.finalize');
    Route::get('/upload/progress/{uploadSessionId}', [VideoUploadController::class, 'progress'])->name('upload.progress');

    // HLS streaming
    Route::get('/videos/{video}/master.m3u8', [VideoController::class, 'masterPlaylist'])->name('video.master');
    Route::get('/videos/{video}/stream/{path}', [VideoController::class, 'stream'])
        ->where('path', '.*')
        ->name('video.stream');

    // Live Stream management
    Route::get('/live', [LiveStreamController::class, 'index'])->name('live.index');
    Route::get('/live/create', [LiveStreamController::class, 'create'])->name('live.create');
    Route::post('/live', [LiveStreamController::class, 'store'])->name('live.store');
    Route::get('/live/{liveStream}', [LiveStreamController::class, 'show'])->name('live.show');
    Route::get('/live/{liveStream}/edit', [LiveStreamController::class, 'edit'])->name('live.edit');
    Route::put('/live/{liveStream}', [LiveStreamController::class, 'update'])->name('live.update');
    Route::delete('/live/{liveStream}', [LiveStreamController::class, 'destroy'])->name('live.destroy');

    // Live Stream actions
    Route::get('/live/{liveStream}/obs-config', [LiveStreamController::class, 'obsConfig'])->name('live.obs-config');
    Route::post('/live/{liveStream}/start', [LiveStreamController::class, 'start'])->name('live.start');
    Route::post('/live/{liveStream}/stop', [LiveStreamController::class, 'stop'])->name('live.stop');
    Route::get('/live/{liveStream}/check-status', [LiveStreamController::class, 'checkStatus'])->name('live.check-status');
});

// Streaming server webhook (no auth - authenticated via webhook secret)
Route::post('/streaming/webhook', [LiveStreamController::class, 'webhook'])->name('streaming.webhook');

require __DIR__.'/auth.php';