<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoUploadController extends Controller
{
    /**
     * Initialize a chunked upload session.
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'filename' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1|max:10737418240', // 10GB max
            'mime_type' => 'nullable|string|max:255',
        ]);

        $uploadSessionId = (string) Str::uuid();
        $extension = pathinfo($request->filename, PATHINFO_EXTENSION);
        $safeFilename = Str::slug(pathinfo($request->filename, PATHINFO_FILENAME)) . '.' . $extension;

        // Create the video record
        $video = Video::create([
            'user_id' => $request->user()->id,
            'title' => pathinfo($request->filename, PATHINFO_FILENAME),
            'original_filename' => $safeFilename,
            'file_path' => "uploads/{$uploadSessionId}/{$safeFilename}",
            'disk' => 'local',
            'file_size' => $request->file_size,
            'mime_type' => $request->mime_type,
            'status' => 'uploading',
            'upload_progress' => 0,
            'upload_session_id' => $uploadSessionId,
        ]);

        // Create the temporary chunk directory
        $chunkDir = Storage::disk('local')->path("chunks/{$uploadSessionId}");
        if (!is_dir($chunkDir)) {
            mkdir($chunkDir, 0755, true);
        }

        return response()->json([
            'upload_session_id' => $uploadSessionId,
            'video_id' => $video->id,
            'chunk_size' => 5 * 1024 * 1024, // 5MB chunks
            'total_chunks' => (int) ceil($request->file_size / (5 * 1024 * 1024)),
        ]);
    }

    /**
     * Upload a single chunk.
     */
    public function chunk(Request $request)
    {
        $request->validate([
            'upload_session_id' => 'required|string|exists:videos,upload_session_id',
            'chunk_index' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
            'chunk' => 'required|file|max:10485760', // 10MB per chunk
        ]);

        $video = Video::where('upload_session_id', $request->upload_session_id)->firstOrFail();
        $chunkDir = Storage::disk('local')->path("chunks/{$request->upload_session_id}");
        $chunkPath = "{$chunkDir}/chunk_{$request->chunk_index}";

        // Save the chunk
        $request->file('chunk')->move($chunkDir, "chunk_{$request->chunk_index}");

        // Calculate progress
        $progress = (int) (($request->chunk_index + 1) / $request->total_chunks * 100);
        $video->update(['upload_progress' => $progress]);

        return response()->json([
            'success' => true,
            'chunk_index' => $request->chunk_index,
            'progress' => $progress,
            'received' => $request->chunk_index + 1,
            'total' => $request->total_chunks,
        ]);
    }

    /**
     * Finalize the upload by assembling all chunks.
     */
    public function finalize(Request $request)
    {
        $request->validate([
            'upload_session_id' => 'required|string|exists:videos,upload_session_id',
            'total_chunks' => 'required|integer|min:1',
        ]);

        $video = Video::where('upload_session_id', $request->upload_session_id)->firstOrFail();
        $chunkDir = Storage::disk('local')->path("chunks/{$request->upload_session_id}");
        $finalPath = Storage::disk('local')->path($video->file_path);

        // Ensure upload directory exists
        $finalDir = dirname($finalPath);
        if (!is_dir($finalDir)) {
            mkdir($finalDir, 0755, true);
        }

        // Assemble chunks
        $finalFile = fopen($finalPath, 'wb');
        if (!$finalFile) {
            return response()->json(['error' => 'Failed to create final file'], 500);
        }

        for ($i = 0; $i < $request->total_chunks; $i++) {
            $chunkFile = "{$chunkDir}/chunk_{$i}";
            if (!file_exists($chunkFile)) {
                fclose($finalFile);
                return response()->json(['error' => "Missing chunk {$i}"], 400);
            }
            $chunk = fopen($chunkFile, 'rb');
            stream_copy_to_stream($chunk, $finalFile);
            fclose($chunk);
            unlink($chunkFile); // Clean up chunk
        }

        fclose($finalFile);

        // Clean up chunk directory
        rmdir($chunkDir);

        // Verify file integrity
        $actualSize = filesize($finalPath);
        if ($actualSize !== (int) $video->file_size) {
            $video->update([
                'status' => 'uploading',
                'error_message' => "File size mismatch: expected {$video->file_size}, got {$actualSize}",
            ]);
            return response()->json(['error' => 'File size mismatch. Upload may be corrupted.'], 400);
        }

        // Update video record
        $video->update([
            'status' => 'uploaded',
            'upload_progress' => 100,
            'mime_type' => mime_content_type($finalPath) ?: $video->mime_type,
        ]);

        // Dispatch processing job
        \App\Jobs\ProcessVideoJob::dispatch($video);

        return response()->json([
            'success' => true,
            'video_id' => $video->id,
            'message' => 'Upload complete. Video processing has started.',
        ]);
    }

    /**
     * Get upload progress for a session.
     */
    public function progress(Request $request, $uploadSessionId)
    {
        $video = Video::where('upload_session_id', $uploadSessionId)->firstOrFail();
        return response()->json([
            'upload_progress' => $video->upload_progress,
            'status' => $video->status,
        ]);
    }
}