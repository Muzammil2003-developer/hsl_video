<?php

namespace App\Jobs;

use App\Models\LiveStream;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckLiveStreamStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public LiveStream $liveStream;
    public int $timeout = 30;
    public int $tries = 1;

    /**
     * How many times to poll before giving up (5 minutes total with 30s intervals).
     */
    private int $maxAttempts = 10;

    public function __construct(LiveStream $liveStream)
    {
        $this->liveStream = $liveStream;
    }

    public function handle(): void
    {
        $attempts = $this->liveStream->stream_metadata['status_check_attempts'] ?? 0;

        if ($attempts >= $this->maxAttempts) {
            Log::info("Live stream {$this->liveStream->id} status check exhausted after {$attempts} attempts.");
            return;
        }

        $isLive = $this->liveStream->checkIfLive();

        if ($isLive) {
            $this->liveStream->markAsLive();
            Log::info("Live stream {$this->liveStream->id} detected as LIVE.");
            return;
        }

        // Not live yet — schedule another check
        $this->liveStream->update([
            'stream_metadata' => array_merge(
                $this->liveStream->stream_metadata ?? [],
                ['status_check_attempts' => $attempts + 1]
            ),
        ]);

        // Re-dispatch in 30 seconds
        if ($attempts < $this->maxAttempts - 1) {
            static::dispatch($this->liveStream)
                ->delay(now()->addSeconds(30));
        }
    }
}