<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('OBS Studio Configuration') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $liveStream->title }}</h3>
                        <p class="text-sm text-gray-500 mt-1">Use these settings in OBS Studio to start your live stream.</p>
                    </div>

                    <!-- Stream Configuration Cards -->
                    <div class="space-y-6">
                        <!-- Server URL -->
                        <div class="bg-gray-50 rounded-lg p-5 border border-gray-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">RTMP Server URL</label>
                            <div class="flex items-center space-x-2">
                                <code id="rtmp-server" class="flex-1 block bg-white border border-gray-300 rounded-lg px-4 py-3 text-sm font-mono text-gray-800 select-all">{{ $obsConfig['server'] }}</code>
                                <button onclick="copyToClipboard('rtmp-server')" class="inline-flex items-center px-3 py-3 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    Copy
                                </button>
                            </div>
                        </div>

                        <!-- Stream Key -->
                        <div class="bg-yellow-50 rounded-lg p-5 border border-yellow-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Stream Key</label>
                            <div class="flex items-center space-x-2">
                                <code id="stream-key" class="flex-1 block bg-white border border-yellow-300 rounded-lg px-4 py-3 text-sm font-mono text-gray-800 select-all break-all">{{ $obsConfig['stream_key'] }}</code>
                                <button onclick="copyToClipboard('stream-key')" class="inline-flex items-center px-3 py-3 bg-yellow-600 text-white text-sm rounded-lg hover:bg-yellow-700 transition-colors whitespace-nowrap">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    Copy Key
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-yellow-700">⚠️ Keep this key private. Anyone with this key can stream to your channel.</p>
                        </div>

                        <!-- HLS URL -->
                        <div class="bg-gray-50 rounded-lg p-5 border border-gray-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">HLS Playlist URL (for player)</label>
                            <div class="flex items-center space-x-2">
                                <code id="hls-url" class="flex-1 block bg-white border border-gray-300 rounded-lg px-4 py-3 text-sm font-mono text-gray-800 select-all">{{ $hlsUrl }}</code>
                                <button onclick="copyToClipboard('hls-url')" class="inline-flex items-center px-3 py-3 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- OBS Setup Instructions -->
                    <div class="mt-8 bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-700">How to configure OBS Studio</h4>
                        </div>
                        <div class="p-5">
                            <ol class="list-decimal list-inside space-y-3 text-sm text-gray-700">
                                <li>
                                    <strong>Open OBS Studio</strong> on your computer.
                                    <p class="ml-5 text-xs text-gray-500">Download from <a href="https://obsproject.com" target="_blank" class="text-indigo-600 hover:text-indigo-800">obsproject.com</a> if you don't have it.</p>
                                </li>
                                <li>
                                    Go to <strong>Settings → Stream</strong>.
                                </li>
                                <li>
                                    Set <strong>Service</strong> to <code class="bg-gray-100 px-1 py-0.5 rounded text-xs font-mono">Custom...</code>
                                </li>
                                <li>
                                    Paste the <strong>RTMP Server URL</strong> from above into the <strong>Server</strong> field.
                                </li>
                                <li>
                                    Paste the <strong>Stream Key</strong> from above into the <strong>Stream Key</strong> field.
                                </li>
                                <li>
                                    Click <strong>OK</strong> to save settings.
                                </li>
                                <li>
                                    Go to <strong>Settings → Output</strong> and set:
                                    <ul class="ml-8 mt-1 list-disc space-y-1 text-xs text-gray-500">
                                        <li><strong>Video Bitrate</strong>: 2500 - 6000 Kbps (adjust based on your upload speed)</li>
                                        <li><strong>Audio Bitrate</strong>: 160 - 320 Kbps</li>
                                        <li><strong>Encoder</strong>: Hardware (NVENC/AMF) if available, else x264</li>
                                    </ul>
                                </li>
                                <li>
                                    Go to <strong>Settings → Video</strong> and set:
                                    <ul class="ml-8 mt-1 list-disc space-y-1 text-xs text-gray-500">
                                        <li><strong>Output Resolution</strong>: 1920x1080 or 1280x720</li>
                                        <li><strong>FPS</strong>: 30 or 60</li>
                                    </ul>
                                </li>
                                <li>
                                    Click <strong>Start Streaming</strong> in OBS to go live!
                                </li>
                                <li>
                                    Viewers can watch at your stream page.
                                </li>
                            </ol>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 flex items-center justify-between">
                        <a href="{{ route('live.show', $liveStream) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                            Back to Stream
                        </a>
                        <div class="flex space-x-3">
                            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Print Config
                            </button>
                            <form action="{{ route('live.start', $liveStream) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                                    Regenerate Stream Key
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyToClipboard(elementId) {
            const el = document.getElementById(elementId);
            const text = el.textContent;
            navigator.clipboard.writeText(text).then(() => {
                const btn = el.nextElementSibling;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Copied!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            });
        }
    </script>
    @endpush

    @stack('scripts')
</x-app-layout>