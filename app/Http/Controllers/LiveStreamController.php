<?php

namespace App\Http\Controllers;

use App\Models\LiveStream;
use App\Jobs\CheckLiveStreamStatusJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LiveStreamController extends Controller
{
    /**
     * Display a listing of the user's live streams.
     */
    public function index()
    {
        $liveStreams = LiveStream::forUser(auth()->user())
            ->orderByRaw("FIELD(status, 'live', 'scheduled', 'ended', 'archived')")
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('live.index', compact('liveStreams'));
    }

    /**
     * Show the form for creating a new live stream.
     */
    public function create()
    {
        return view('live.create');
    }

    /**
     * Store a newly created live stream.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'scheduled_at' => 'nullable|date|after:now',
            'category' => 'nullable|string|max:100',
        ]);

        $streamKey = LiveStream::generateStreamKey();

        $liveStream = LiveStream::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'stream_key' => $streamKey,
            'rtmp_url' => (new LiveStream())->getRtmpIngestUrl(),
            'status' => 'scheduled',
            'scheduled_at' => $request->scheduled_at,
            'category' => $request->category,
        ]);

        return redirect()->route('live.show', $liveStream)
            ->with('success', 'Live stream created successfully. Configure your streaming software with the provided settings.');
    }

    /**
     * Display the live stream with player for live or details for ended/scheduled.
     */
    public function show(LiveStream $liveStream)
    {
        if ($liveStream->user_id !== auth()->id()) {
            abort(403);
        }

        return view('live.show', compact('liveStream'));
    }

    /**
     * Show the form for editing the live stream.
     */
    public function edit(LiveStream $liveStream)
    {
        if ($liveStream->user_id !== auth()->id()) {
            abort(403);
        }

        return view('live.edit', compact('liveStream'));
    }

    /**
     * Update the live stream.
     */
    public function update(Request $request, LiveStream $liveStream)
    {
        if ($liveStream->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'scheduled_at' => 'nullable|date',
            'category' => 'nullable|string|max:100',
        ]);

        $liveStream->update([
            'title' => $request->title,
            'description' => $request->description,
            'scheduled_at' => $request->scheduled_at,
            'category' => $request->category,
        ]);

        return redirect()->route('live.show', $liveStream)
            ->with('success', 'Live stream updated successfully.');
    }

    /**
     * Delete the live stream.
     */
    public function destroy(LiveStream $liveStream)
    {
        if ($liveStream->user_id !== auth()->id()) {
            abort(403);
        }

        // Clean up thumbnail if exists
        if ($liveStream->thumbnail_path && Storage::disk('public')->exists($liveStream->thumbnail_path)) {
            Storage::disk('public')->delete($liveStream->thumbnail_path);
        }

        $liveStream->delete();

        return redirect()->route('live.index')
            ->with('success', 'Live stream deleted successfully.');
    }

    /**
     * Show OBS Studio configuration for this stream.
     */
    public function obsConfig(LiveStream $liveStream)
    {
        if ($liveStream->user_id !== auth()->id()) {
            abort(403);
        }

        $obsConfig = $liveStream->getOBSConfiguration();
        $hlsUrl = $liveStream->getHlsUrl();

        return view('live.obs-config', compact('liveStream', 'obsConfig', 'hlsUrl'));
    }

    /**
     * Manually start a scheduled stream.
     */
    public function start(LiveStream $liveStream)
    {
        if ($liveStream->user_id !== auth()->id()) {
            abort(403);
        }

        if (!in_array($liveStream->status, ['scheduled', 'ended', 'archived'])) {
            return back()->with('error', 'Stream cannot be started from its current state.');
        }

        // Regenerate stream key for fresh start
        $newKey = LiveStream::generateStreamKey();
        $liveStream->update([
            'stream_key' => $newKey,
            'status' => 'scheduled',
            'started_at' => null,
            'ended_at' => null,
            'hls_playlist_url' => null,
            'viewer_count' => 0,
            'peak_viewer_count' => 0,
        ]);

        // Dispatch status check job (will poll the HLS endpoint)
        CheckLiveStreamStatusJob::dispatch($liveStream);

        return redirect()->route('live.obs-config', $liveStream)
            ->with('success', 'Stream is ready. Use the configuration below in OBS Studio to start streaming.');
    }

    /**
     * Manually stop a live stream.
     */
    public function stop(LiveStream $liveStream)
    {
        if ($liveStream->user_id !== auth()->id()) {
            abort(403);
        }

        if ($liveStream->status !== 'live') {
            return back()->with('error', 'Stream is not currently live.');
        }

        $liveStream->markAsEnded();

        return back()->with('success', 'Stream ended successfully.');
    }

    /**
     * Webhook receiver for the RTMP/HLS streaming server.
     * The streaming server (Nginx RTMP with notify_method or MediaMTX webhook)
     * should POST to this endpoint when stream events occur.
     *
     * Expected payload:
     * {
     *   "action": "publish_start | publish_end | update",
     *   "stream_key": "abc123...",
     *   "client_id": "...",
     *   "hls_playlist_url": "http://..."
     * }
     */
    public function webhook(Request $request)
    {
        // Verify webhook secret for security
        $webhookSecret = config('services.streaming.webhook_secret');
        $providedSecret = $request->header('X-Webhook-Secret') ?? $request->input('secret');

        if ($webhookSecret && $providedSecret !== $webhookSecret) {
            return response()->json(['error' => 'Invalid webhook secret'], 401);
        }

        $action = $request->input('action');
        $streamKey = $request->input('stream_key');
        $hlsUrl = $request->input('hls_playlist_url');

        $liveStream = LiveStream::where('stream_key', $streamKey)->first();

        if (!$liveStream) {
            return response()->json(['error' => 'Stream not found'], 404);
        }

        switch ($action) {
            case 'publish_start':
            case 'stream_start':
                $liveStream->markAsLive();
                if ($hlsUrl) {
                    $liveStream->update(['hls_playlist_url' => $hlsUrl]);
                }
                break;

            case 'publish_end':
            case 'stream_end':
                $liveStream->markAsEnded();
                break;

            case 'update':
                if ($hlsUrl) {
                    $liveStream->update(['hls_playlist_url' => $hlsUrl]);
                }
                if ($request->has('viewer_count')) {
                    $liveStream->update([
                        'viewer_count' => $request->viewer_count,
                        'peak_viewer_count' => max($liveStream->peak_viewer_count, $request->viewer_count),
                    ]);
                }
                break;

            default:
                return response()->json(['error' => 'Unknown action'], 400);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Check and update stream status manually (ajax endpoint for polling).
     */
    public function checkStatus(LiveStream $liveStream)
    {
        if ($liveStream->user_id !== auth()->id()) {
            abort(403);
        }

        $isLive = $liveStream->checkIfLive();

        if ($isLive && $liveStream->status === 'scheduled') {
            $liveStream->markAsLive();
        } elseif (!$isLive && $liveStream->status === 'live') {
            // Don't instantly mark as ended — give a grace period
            // The stream might just be between segments
            $liveStream->markAsEnded();
        }

        return response()->json([
            'status' => $liveStream->fresh()->status,
            'viewer_count' => $liveStream->viewer_count,
            'is_live' => $isLive,
        ]);
    }
}