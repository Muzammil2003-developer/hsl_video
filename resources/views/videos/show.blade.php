<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $video->title }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $video->getDurationForHumans() }} &bull; {{ $video->getFileSizeForHumans() }}
                    @if($video->qualities)
                        &bull; {{ count($video->qualities) }} quality(ies)
                    @endif
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('videos.edit', $video) }}" class="inline-flex items-center px-3 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Edit
                </a>
                <form action="{{ route('videos.destroy', $video) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this video?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <!-- Video Player -->
            <div class="bg-black rounded-lg overflow-hidden shadow-xl">
                <div class="relative aspect-video">
                    <video id="hls-player"
                           class="w-full h-full"
                           controls
                           autoplay
                           preload="auto"
                           poster="{{ $video->thumbnail_path ? Storage::url($video->thumbnail_path) : '' }}">
                        <p class="text-white p-4">Your browser does not support HTML5 video.</p>
                    </video>

                    <!-- Quality Selector Overlay -->
                    <div class="absolute bottom-16 right-4" id="quality-selector-container">
                        <button id="quality-btn"
                                class="bg-black bg-opacity-80 text-white text-xs px-3 py-1.5 rounded hover:bg-opacity-90 transition-opacity hidden"
                                onclick="toggleQualityMenu()">
                            <span id="current-quality">Auto</span>
                            <svg class="w-3 h-3 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="quality-menu"
                             class="absolute bottom-full right-0 mb-2 bg-black bg-opacity-90 rounded-lg overflow-hidden hidden">
                            <div class="py-1">
                                <button class="quality-option block w-full text-left px-4 py-2 text-xs text-white hover:bg-gray-700 transition-colors"
                                        data-quality="auto"
                                        onclick="setQuality('auto')">Auto</button>
                                @if($video->qualities)
                                    @foreach($video->qualities as $quality)
                                        <button class="quality-option block w-full text-left px-4 py-2 text-xs text-white hover:bg-gray-700 transition-colors"
                                                data-quality="{{ $quality }}"
                                                onclick="setQuality('{{ $quality }}')">{{ $quality }}</button>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Video Details -->
            <div class="mt-6 bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $video->title }}</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Uploaded {{ $video->created_at->format('F j, Y') }}
                        </p>
                        @if($video->description)
                            <div class="mt-4 text-gray-700 text-sm whitespace-pre-wrap">
                                {{ $video->description }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Video Info Cards -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</h4>
                    <p class="mt-1 text-lg font-medium text-gray-900">{{ $video->getDurationForHumans() }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">File Size</h4>
                    <p class="mt-1 text-lg font-medium text-gray-900">{{ $video->getFileSizeForHumans() }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Available Qualities</h4>
                    <p class="mt-1 text-lg font-medium text-gray-900">
                        @if($video->qualities)
                            {{ implode(', ', $video->qualities) }}
                        @else
                            N/A
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- HLS.js library --}}
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
        (function() {
            const video = document.getElementById('hls-player');
            const masterPlaylistUrl = '{{ route("video.master", $video) }}';
            const qualityBtn = document.getElementById('quality-btn');
            const qualityMenu = document.getElementById('quality-menu');
            const currentQualitySpan = document.getElementById('current-quality');

            let hls = null;
            let currentLevel = -1; // -1 = auto

            function initPlayer() {
                if (Hls.isSupported()) {
                    hls = new Hls({
                        enableWorker: true,
                        lowLatencyMode: true,
                        backbufferLength: 30,
                    });

                    hls.loadSource(masterPlaylistUrl);
                    hls.attachMedia(video);

                    hls.on(Hls.Events.MANIFEST_PARSED, function() {
                        qualityBtn.classList.remove('hidden');
                        video.play().catch(() => {});
                    });

                    hls.on(Hls.Events.LEVEL_SWITCHED, function(event, data) {
                        const level = hls.levels[data.level];
                        if (level) {
                            const height = level.height;
                            const qualityMap = {
                                360: '360p',
                                720: '720p',
                                1080: '1080p',
                            };
                            const qualityName = qualityMap[height] || height + 'p';
                            currentQualitySpan.textContent = qualityName;
                        }
                    });

                    hls.on(Hls.Events.ERROR, function(event, data) {
                        if (data.fatal) {
                            switch (data.type) {
                                case Hls.ErrorTypes.NETWORK_ERROR:
                                    console.warn('HLS network error, trying to recover...');
                                    hls.startLoad();
                                    break;
                                case Hls.ErrorTypes.MEDIA_ERROR:
                                    console.warn('HLS media error, trying to recover...');
                                    hls.recoverMediaError();
                                    break;
                                default:
                                    console.error('Fatal HLS error:', data);
                                    break;
                            }
                        }
                    });

                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    // Native HLS support (Safari)
                    video.src = masterPlaylistUrl;
                    qualityBtn.classList.remove('hidden');
                } else {
                    video.innerHTML = '<p class="text-white p-4">HLS streaming is not supported in your browser. Please use a modern browser like Chrome, Firefox, or Safari.</p>';
                }
            }

            // Quality switching
            window.setQuality = function(quality) {
                if (!hls) return;

                if (quality === 'auto') {
                    hls.currentLevel = -1;
                    currentQualitySpan.textContent = 'Auto';
                } else {
                    const heightMap = {
                        '360p': 360,
                        '720p': 720,
                        '1080p': 1080,
                    };

                    const targetHeight = heightMap[quality];
                    if (!targetHeight) return;

                    // Find the level index matching the height
                    let levelIndex = -1;
                    for (let i = 0; i < hls.levels.length; i++) {
                        if (hls.levels[i].height === targetHeight) {
                            levelIndex = i;
                            break;
                        }
                    }

                    if (levelIndex >= 0) {
                        hls.currentLevel = levelIndex;
                        currentQualitySpan.textContent = quality;
                    }
                }

                // Close menu
                qualityMenu.classList.add('hidden');
            };

            window.toggleQualityMenu = function() {
                qualityMenu.classList.toggle('hidden');
            };

            // Close quality menu when clicking outside
            document.addEventListener('click', function(event) {
                const container = document.getElementById('quality-selector-container');
                if (container && !container.contains(event.target)) {
                    qualityMenu.classList.add('hidden');
                }
            });

            // Initialize player when DOM is ready
            if (document.readyState === 'complete') {
                initPlayer();
            } else {
                window.addEventListener('load', initPlayer);
            }
        })();
    </script>
    @endpush

    @stack('scripts')
</x-app-layout>