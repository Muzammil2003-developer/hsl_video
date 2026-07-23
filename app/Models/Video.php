<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    /** @use HasFactory<\Database\Factories\VideoFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'original_filename',
        'file_path',
        'disk',
        'file_size',
        'mime_type',
        'duration',
        'thumbnail_path',
        'hls_path',
        'qualities',
        'processing_metadata',
        'status',
        'error_message',
        'upload_progress',
        'upload_session_id',
    ];

    protected function casts(): array
    {
        return [
            'qualities' => 'array',
            'processing_metadata' => 'array',
            'file_size' => 'integer',
            'duration' => 'integer',
            'upload_progress' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function getFileSizeForHumans(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDurationForHumans(): string
    {
        if (!$this->duration) {
            return 'N/A';
        }
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getStreamUrl(): ?string
    {
        if (!$this->hls_path) {
            return null;
        }
        return route('video.stream', $this->id);
    }

    public function getThumbnailUrl(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }
        return Storage::disk($this->disk)->url($this->thumbnail_path);
    }

    public function getQualities(): array
    {
        return $this->qualities ?? [];
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
}