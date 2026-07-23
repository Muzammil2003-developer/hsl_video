<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LiveStream extends Model
{
    /** @use HasFactory<\Database\Factories\LiveStreamFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'stream_key',
        'rtmp_url',
        'hls_playlist_url',
        'thumbnail_path',
        'status',
        'viewer_count',
        'peak_viewer_count',
        'scheduled_at',
        'started_at',
        'ended_at',
        'category',
        'stream_metadata',
        'auto_detect_status',
        'chat_embed_url',
    ];

    protected function casts(): array
    {
        return [
            'stream_metadata' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'viewer_count' => 'integer',
            'peak_viewer_count' => 'integer',
            'auto_detect_status' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a unique RTMP stream key.
     */
    public static function generateStreamKey(): string
    {
        do {
            $key = Str::random(32);
        } while (static::where('stream_key', $key)->exists());

        return $key;
    }

    /**
     * Build the RTMP ingest URL for OBS Studio.
     * The streaming server (Nginx RTMP / MediaMTX) will be configured post-deploy.
     */
    public function getRtmpIngestUrl(): string
    {
        // Default RTMP server - configure this via environment variable
        $rtmpServer = config('services.streaming.rtmp_server', 'rtmp://localhost:1935');
        $appName = config('services.streaming.rtmp_app', 'live');

        return "{$rtmpServer}/{$appName}";
    }

    /**
     * Build the full RTMP URL with stream key for OBS.
     */
    public function getOBSConfiguration(): array
    {
        return [
            'server' => $this->rtmp_url ?? $this->getRtmpIngestUrl(),
            'stream_key' => $this->stream_key,
            'protocol' => 'RTMP',
        ];
    }

    /**
     * The HLS URL for the live player.
     */
    public function getHlsUrl(): ?string
    {
        if ($this->hls_playlist_url) {
            return $this->hls_playlist_url;
        }

        // Build default HLS URL based on convention
        $hlsBase = config('services.streaming.hls_base_url', 'http://localhost:8888');
        $streamKey = $this->stream_key;

        return "{$hlsBase}/live/{$streamKey}.m3u8";
    }

    /**
     * Check if the stream is currently live by probing the HLS playlist.
     * This is used for auto-detection without requiring a webhook.
     */
    public function checkIfLive(): bool
    {
        if ($this->status !== 'live' && $this->status !== 'scheduled') {
            return false;
        }

        $hlsUrl = $this->getHlsUrl();
        if (!$hlsUrl) {
            return false;
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET',
                ],
            ]);

            $response = @file_get_contents($hlsUrl, false, $context);

            if ($response !== false) {
                // Check if the playlist contains #EXTINF (has segments) = stream is live
                return str_contains($response, '#EXTINF');
            }
        } catch (\Exception $e) {
            // Unable to reach streaming server - stream might be offline
        }

        return false;
    }

    /**
     * Mark the stream as live.
     */
    public function markAsLive(): void
    {
        $this->update([
            'status' => 'live',
            'started_at' => $this->started_at ?? now(),
        ]);
    }

    /**
     * Mark the stream as ended.
     */
    public function markAsEnded(): void
    {
        $this->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);
    }

    /**
     * Increment viewer count (called via WebSocket or polling).
     */
    public function incrementViewers(int $count = 1): void
    {
        $newCount = $this->viewer_count + $count;
        $this->update([
            'viewer_count' => max(0, $newCount),
            'peak_viewer_count' => max($this->peak_viewer_count, $newCount),
        ]);
    }

    /**
     * Scope: Currently live streams.
     */
    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    /**
     * Scope: Scheduled/upcoming streams.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope: Ended/archived streams.
     */
    public function scopeEnded($query)
    {
        return $query->whereIn('status', ['ended', 'archived']);
    }

    /**
     * Scope: Streams for a specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
}