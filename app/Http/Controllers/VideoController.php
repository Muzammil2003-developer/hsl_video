<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $videos = Video::forUser(auth()->user())
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return view('videos.index', compact('videos'));
    }

    public function create()
    {
        return view('videos.upload');
    }

    public function show(Video $video)
    {
        if ($video->user_id !== auth()->id()) {
            abort(403);
        }

        if ($video->status !== 'ready') {
            return redirect()->route('videos.index')
                ->with('error', 'This video is still being processed. Please check back later.');
        }

        return view('videos.show', compact('video'));
    }

    public function edit(Video $video)
    {
        if ($video->user_id !== auth()->id()) {
            abort(403);
        }
        return view('videos.edit', compact('video'));
    }

    public function update(Request $request, Video $video)
    {
        if ($video->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);

        $video->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return redirect()->route('videos.show', $video)
            ->with('success', 'Video details updated successfully.');
    }

    public function destroy(Video $video)
    {
        if ($video->user_id !== auth()->id()) {
            abort(403);
        }

        // Delete files from storage
        $disk = Storage::disk($video->disk);
        $basePath = dirname($video->file_path);

        // Remove processed files
        if ($video->hls_path) {
            $hlsDir = dirname($video->hls_path);
            $disk->deleteDirectory($hlsDir);
        }

        // Remove original file
        if ($disk->exists($video->file_path)) {
            $disk->delete($video->file_path);
        }

        // Remove thumbnail
        if ($video->thumbnail_path && $disk->exists($video->thumbnail_path)) {
            $disk->delete($video->thumbnail_path);
        }

        // Clean up empty directories
        $disk->deleteDirectory($basePath);

        $video->update(['status' => 'deleted']);
        $video->delete();

        return redirect()->route('videos.index')
            ->with('success', 'Video deleted successfully.');
    }

    /**
     * Stream HLS playlist files.
     */
    public function stream(Video $video, $path = '')
    {
        if ($video->user_id !== auth()->id()) {
            abort(403);
        }

        if ($video->status !== 'ready' || !$video->hls_path) {
            abort(404);
        }

        $baseDir = dirname($video->hls_path);
        $filePath = $baseDir . '/' . $path;

        $disk = Storage::disk($video->disk);

        if (!$disk->exists($filePath)) {
            abort(404);
        }

        $mimeTypes = [
            '.m3u8' => 'application/vnd.apple.mpegurl',
            '.ts' => 'video/MP2T',
            '.m4s' => 'video/iso.segment',
            '.mp4' => 'video/mp4',
            '.jpg' => 'image/jpeg',
            '.png' => 'image/png',
            '.vtt' => 'text/vtt',
            '.key' => 'application/octet-stream',
        ];

        $extension = strrchr($path, '.');
        $mime = $mimeTypes[$extension] ?? 'application/octet-stream';

        return response()->stream(function () use ($disk, $filePath) {
            $stream = $disk->readStream($filePath);
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Length' => $disk->size($filePath),
            'Cache-Control' => 'public, max-age=86400',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Serve the master playlist for HLS streaming.
     */
    public function masterPlaylist(Video $video)
    {
        if ($video->user_id !== auth()->id()) {
            abort(403);
        }

        if ($video->status !== 'ready' || !$video->hls_path) {
            abort(404);
        }

        $disk = Storage::disk($video->disk);

        if (!$disk->exists($video->hls_path)) {
            abort(404);
        }

        $content = $disk->get($video->hls_path);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}