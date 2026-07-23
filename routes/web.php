<?php

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
    return view('dashboard', compact('videosCount', 'readyCount', 'processingCount', 'totalSize'));
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
});

require __DIR__.'/auth.php';