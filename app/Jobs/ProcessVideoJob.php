<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Video $video;
    public int $timeout = 3600; // 1 hour max processing time
    public int $tries = 1; // Don't retry on failure, let user re-upload

    // Quality presets for HLS transcoding
    private array $qualityPresets = [
        '360p' => [
            'video_bitrate' => '800k',
            'audio_bitrate' => '96k',
            'resolution' => '640:360',
            'framerate' => 24,
        ],
        '720p' => [
            'video_bitrate' => '2500k',
            'audio_bitrate' => '128k',
            'resolution' => '1280:720',
            'framerate' => 30,
        ],
        '1080p' => [
            'video_bitrate' => '5000k',
            'audio_bitrate' => '192k',
            'resolution' => '1920:1080',
            'framerate' => 30,
        ],
    ];

    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    public function handle(): void
    {
        $this->video->update([
            'status' => 'processing',
            'processing_metadata' => [
                'started_at' => now()->toIso8601String(),
                'qualities_to_generate' => array_keys($this->qualityPresets),
            ],
        ]);

        try {
            $disk = Storage::disk($this->video->disk);
            $inputPath = $disk->path($this->video->file_path);

            if (!file_exists($inputPath)) {
                throw new RuntimeException("Input file not found: {$inputPath}");
            }

            // Verify FFmpeg is available
            $ffmpegPath = $this->findFfmpeg();
            $ffprobePath = $this->findFfprobe();

            // Get video duration
            $duration = $this->getVideoDuration($ffprobePath, $inputPath);
            $this->video->update(['duration' => (int) $duration]);

            // Generate thumbnail
            $thumbnailPath = $this->generateThumbnail($ffmpegPath, $inputPath, $disk);
            $this->video->update(['thumbnail_path' => $thumbnailPath]);

            // Create HLS output directory
            $hlsBaseDir = 'hls/' . $this->video->id;
            $disk->makeDirectory($hlsBaseDir);

            // Transcode to multiple qualities and generate HLS playlists
            $generatedQualities = [];
            $variantPlaylists = [];

            foreach ($this->qualityPresets as $qualityName => $preset) {
                $qualityDir = "{$hlsBaseDir}/{$qualityName}";
                $disk->makeDirectory($qualityDir);

                $outputPattern = $disk->path("{$qualityDir}/segment_%03d.ts");
                $playlistPath = $disk->path("{$qualityDir}/playlist.m3u8");

                $this->transcodeToHls(
                    $ffmpegPath,
                    $inputPath,
                    $outputPattern,
                    $playlistPath,
                    $preset
                );

                $generatedQualities[] = $qualityName;

                // Build variant playlist entry
                $bandwidth = $this->getBandwidth($qualityName, $preset);
                $resolution = $preset['resolution'];
                $variantPlaylists[] = "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$resolution},NAME=\"{$qualityName}\"\n{$qualityName}/playlist.m3u8";
            }

            // Generate master playlist
            $masterPlaylist = "#EXTM3U\n#EXT-X-VERSION:3\n";
            $masterPlaylist .= implode("\n", $variantPlaylists);

            $masterPlaylistPath = "{$hlsBaseDir}/master.m3u8";
            $disk->put($masterPlaylistPath, $masterPlaylist);

            // Update video record
            $this->video->update([
                'status' => 'ready',
                'hls_path' => $masterPlaylistPath,
                'qualities' => $generatedQualities,
                'processing_metadata' => array_merge(
                    $this->video->processing_metadata ?? [],
                    [
                        'completed_at' => now()->toIso8601String(),
                        'qualities_generated' => $generatedQualities,
                    ]
                ),
            ]);

            // Clean up original file to save space (optional - can be kept for re-processing)
            // $disk->delete($this->video->file_path);

            Log::info("Video {$this->video->id} processed successfully with qualities: " . implode(', ', $generatedQualities));

        } catch (\Exception $e) {
            Log::error("Video processing failed for video {$this->video->id}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            $this->video->update([
                'status' => 'processing_failed',
                'error_message' => $e->getMessage(),
                'processing_metadata' => array_merge(
                    $this->video->processing_metadata ?? [],
                    [
                        'failed_at' => now()->toIso8601String(),
                        'error' => $e->getMessage(),
                    ]
                ),
            ]);

            throw $e;
        }
    }

    private function findFfmpeg(): string
    {
        $paths = [
            'ffmpeg',
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
        ];

        foreach ($paths as $path) {
            $output = shell_exec("where {$path} 2>NUL || which {$path} 2>/dev/null");
            if ($output && trim($output)) {
                return trim($output);
            }
        }

        throw new RuntimeException('FFmpeg not found. Please install FFmpeg on your system.');
    }

    private function findFfprobe(): string
    {
        $paths = [
            'ffprobe',
            '/usr/bin/ffprobe',
            '/usr/local/bin/ffprobe',
            'C:\\ffmpeg\\bin\\ffprobe.exe',
            'C:\\Program Files\\ffmpeg\\bin\\ffprobe.exe',
        ];

        foreach ($paths as $path) {
            $output = shell_exec("where {$path} 2>NUL || which {$path} 2>/dev/null");
            if ($output && trim($output)) {
                return trim($output);
            }
        }

        throw new RuntimeException('FFprobe not found. Please install FFmpeg on your system.');
    }

    private function getVideoDuration(string $ffprobe, string $inputPath): float
    {
        $cmd = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellcmd($ffprobe),
            escapeshellarg($inputPath)
        );

        $output = shell_exec($cmd);
        if (!$output) {
            throw new RuntimeException('Failed to get video duration');
        }

        return (float) trim($output);
    }

    private function generateThumbnail(string $ffmpeg, string $inputPath, $disk): string
    {
        $thumbnailRelPath = 'thumbnails/' . $this->video->id . '.jpg';
        $thumbnailFullPath = $disk->path($thumbnailRelPath);

        $thumbDir = dirname($thumbnailFullPath);
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        // Extract frame at 10% into the video
        $duration = $this->video->duration ?: 10;
        $seekTime = min($duration * 0.1, 30); // At most 30 seconds in

        $cmd = sprintf(
            '%s -ss %s -i %s -vframes 1 -q:v 2 -y %s 2>&1',
            escapeshellcmd($ffmpeg),
            escapeshellarg((string) $seekTime),
            escapeshellarg($inputPath),
            escapeshellarg($thumbnailFullPath)
        );

        shell_exec($cmd);

        if (!file_exists($thumbnailFullPath)) {
            // Try at 1 second if first attempt failed
            $cmd = sprintf(
                '%s -ss 1 -i %s -vframes 1 -q:v 2 -y %s 2>&1',
                escapeshellcmd($ffmpeg),
                escapeshellarg($inputPath),
                escapeshellarg($thumbnailFullPath)
            );
            shell_exec($cmd);
        }

        return $thumbnailRelPath;
    }

    private function transcodeToHls(
        string $ffmpeg,
        string $inputPath,
        string $outputPattern,
        string $playlistPath,
        array $preset
    ): void {
        $cmd = sprintf(
            '%s -i %s -vf "scale=%s" -c:v libx264 -preset medium -b:v %s -maxrate %s -bufsize %s '
            . '-c:a aac -b:a %s -ar 48000 -ac 2 '
            . '-f hls -hls_time 6 -hls_list_size 0 -hls_segment_filename %s '
            . '-hls_playlist_type vod -progress pipe:1 -y %s 2>&1',
            escapeshellcmd($ffmpeg),
            escapeshellarg($inputPath),
            escapeshellarg($preset['resolution']),
            escapeshellarg($preset['video_bitrate']),
            escapeshellarg($preset['video_bitrate']),
            escapeshellarg((int) $preset['video_bitrate'] * 2 . 'k'),
            escapeshellarg($preset['audio_bitrate']),
            escapeshellarg($outputPattern),
            escapeshellarg($playlistPath)
        );

        $output = shell_exec($cmd);

        if (!file_exists($playlistPath)) {
            throw new RuntimeException(
                "HLS transcoding failed for quality {$preset['resolution']}. FFmpeg output: " . ($output ?? 'No output')
            );
        }
    }

    private function getBandwidth(string $qualityName, array $preset): int
    {
        // Calculate approximate bandwidth from bitrates
        $videoBps = (int) $preset['video_bitrate'] * 1000;
        $audioBps = (int) $preset['audio_bitrate'] * 1000;
        return $videoBps + $audioBps;
    }
}