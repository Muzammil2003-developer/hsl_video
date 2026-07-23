<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $liveStream->title }}
                </h2>
                <div class="flex items-center space-x-3 mt-1">
                    @if($liveStream->status === 'live')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5 animate-pulse"></span>
                            LIVE
                        </span>
                        <span class="text-sm text-red-600" id="viewer-count">{{ $liveStream->viewer_count }} watching</span>
                    @elseif($liveStream->status === 'scheduled')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Scheduled
                        </span>
                        @if($liveStream->scheduled_at)
                            <span class="text-sm text-gray-500">{{ $liveStream->scheduled_at->format('F j, Y g:i A') }}</span>
                        @endif
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            {{ ucfirst($liveStream->status) }}
                        </span>
                        @if($liveStream->ended_at)
                            <span class="text-sm text-gray-500">Ended {{ $liveStream->ended_at->diffForHumans() }}</span>
                        @endif
                    @endif
                </div>
            </div>
            <div class="flex items-center space-x-3">
                @if($liveStream->status === 'scheduled')
                    <form action="{{ route('live.start', $liveStream) }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                            Go Live
                        </button>
                    </form>
                @elseif($liveStream->status === 'live')
                    <a href="{{ route('live.obs-config', $liveStream) }}" class="inline-flex items-center px-3 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        OBS Config
                    </a>
                    <form action="{{ route('live.stop', $liveStream) }}" method="POST" onsubmit="return confirm('End this stream?');">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                            End Stream
                        </button>
                    </form>
                @endif
                <a href="{{ route('live.edit', $liveStream) }}" class="inline-flex items-center px-3 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Video / Streaming Area -->
            <div class="bg-black rounded-lg overflow-hidden shadow-xl">
                <div class="relative aspect-video">
                    @if($liveStream->status === 'live')
                        {{-- HLS Live Player --}}
                        <video id="live-player" class="w-full h-full" controls autoplay>
                            <p class="text-white p-4">Your browser does not support HLS streaming.</p>
                        </video>

                        <!-- Live indicator overlay -->
                        <div class="absolute top-4 left-4 flex items-center space-x-2 bg-black bg-opacity-60 rounded-full px-3 py-1">
                            <span class="flex h-2.5 w-2.5 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                            </span>
                            <span class="text-white text-xs font-medium">LIVE</span>
                        </div>

                        <!-- Viewer count overlay -->
                        <div class="absolute top-4 right-4 bg-black bg-opacity-60 rounded-full px-3 py-1">
                            <span class="text-white text-xs" id="live-viewers">{{ $liveStream->viewer_count }} watching</span>
                        </div>

                    @elseif($liveStream->status === 'scheduled')
                        <div class="flex items-center justify-center h-full bg-gradient-to-br from-gray-900 to-gray-800">
                            <div class="text-center">
                                <svg class="mx-auto h-20 w-20 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <p class="mt-4 text-xl font-semibold text-white">Stream Scheduled</p>
                                @if($liveStream->scheduled_at)
                                    <p class="mt-2 text-gray-400">{{ $liveStream->scheduled_at->format('F j, Y g:i A') }}</p>
                                @endif
                                <p class="mt-1 text-sm text-gray-500">Check back when the stream goes live.</p>
                            </div>
                        </div>

                    @else
                        <div class="flex items-center justify-center h-full bg-gradient-to-br from-gray-900 to-gray-800">
                            <div class="text-center">
                                <svg class="mx-auto h-20 w-20 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14"/>
                                </svg>
                                <p class="mt-4 text-xl font-semibold text-white">Stream Ended</p>
                                @if($liveStream->ended_at)
                                    <p class="mt-2 text-gray-400">Ended {{ $liveStream->ended_at->diffForHumans() }}</p>
                                @endif
                                @if($liveStream->peak_viewer_count > 0)
                                    <p class="mt-1 text-sm text-gray-500">Peak viewers: {{ $liveStream->peak_viewer_count }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Stream Details -->
            <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $liveStream->title }}</h3>
                        <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                            @if($liveStream->category)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">{{ $liveStream->category }}</span>
                            @endif
                            @if($liveStream->started_at)
                                <span>Started {{ $liveStream->started_at->diffForHumans() }}</span>
                            @endif
                        </div>
                        @if($liveStream->description)
                            <div class="mt-4 text-sm text-gray-700 whitespace-pre-wrap">
                                {{ $liveStream->description }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Stats Sidebar -->
                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</h4>
                        <p class="mt-1 text-lg font-medium text-gray-900 capitalize">{{ $liveStream->status }}</p>
                    </div>

                    @if($liveStream->status === 'live' || $liveStream->status === 'ended')
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Viewers</h4>
                        <p class="mt-1 text-lg font-medium text-gray-900">{{ $liveStream->viewer_count }} current</p>
                        @if($liveStream->peak_viewer_count > 0)
                            <p class="text-sm text-gray-500">Peak: {{ $liveStream->peak_viewer_count }}</p>
                        @endif
                    </div>
                    @endif

                    @if($liveStream->status === 'scheduled')
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Stream Key</h4>
                        <div class="mt-1 flex items-center space-x-2">
                            <code class="text-sm bg-gray-100 px-2 py-1 rounded flex-1 truncate font-mono">{{ $liveStream->stream_key }}</code>
                            <button onclick="copyToClipboard('{{ $liveStream->stream_key }}')" class="text-indigo-600 hover:text-indigo-800 text-xs">Copy</button>
                        </div>
                        <p class="mt-2">
                            <a href="{{ route('live.obs-config', $liveStream) }}" class="text-sm text-indigo-600 hover:text-indigo-800">View OBS Configuration →</a>
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
        (function() {
            // Copy to clipboard helper
            window.copyToClipboard = function(text) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('Copied to clipboard!');
                });
            };

            @if($liveStream->status === 'live')
            // Initialize HLS live player
            const video = document.getElementById('live-player');
            const hlsUrl = '{{ $liveStream->getHlsUrl() }}';

            if (Hls.isSupported()) {
                const hls = new Hls({
                    enableWorker: true,
                    lowLatencyMode: true,
                    liveDurationInfinity: true,
                });

                hls.loadSource(hlsUrl);
                hls.attachMedia(video);

                hls.on(Hls.Events.MANIFEST_PARSED, function() {
                    video.play().catch(() => {});
                });

                hls.on(Hls.Events.ERROR, function(event, data) {
                    if (data.fatal) {
                        switch (data.type) {
                            case Hls.ErrorTypes.NETWORK_ERROR:
                                console.log('HLS network error, recovering...');
                                hls.startLoad();
                                break;
                            case Hls.ErrorTypes.MEDIA_ERROR:
                                console.log('HLS media error, recovering...');
                                hls.recoverMediaError();
                                break;
                        }
                    }
                });

            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                // Native HLS (Safari)
                video.src = hlsUrl;
            }

            // Poll status every 30 seconds
            setInterval(function() {
                fetch('{{ route("live.check-status", $liveStream) }}')
                    .then(res => res.json())
                    .then(data => {
                        const viewerEl = document.getElementById('live-viewers');
                        const viewerCountEl = document.getElementById('viewer-count');
                        if (viewerEl) viewerEl.textContent = data.viewer_count + ' watching';
                        if (viewerCountEl) viewerCountEl.textContent = data.viewer_count + ' watching';

                        if (!data.is_live) {
                            // Stream ended — reload page to show ended state
                            location.reload();
                        }
                    })
                    .catch(() => {});
            }, 30000);
            @endif
        })();
    </script>
    @endpush

    @stack('scripts')
</x-app-layout>