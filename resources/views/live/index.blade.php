<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Live Streams') }}
            </h2>
            <a href="{{ route('live.create') }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                + Create Stream
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if($liveStreams->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8l4 4m0 0l4-4m-4 4V4"/>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No live streams yet</h3>
                        <p class="mt-1 text-sm text-gray-500">Create your first live stream and start broadcasting with OBS Studio.</p>
                        <a href="{{ route('live.create') }}" class="mt-6 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                            Create Your First Stream
                        </a>
                    </div>
                </div>
            @else
                <!-- Currently Live Banner -->
                @php $activeLive = $liveStreams->firstWhere('status', 'live'); @endphp
                @if($activeLive)
                    <div class="mb-6 bg-gradient-to-r from-red-600 to-red-800 rounded-lg shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <span class="flex h-3 w-3 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-300 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                                </span>
                                <div>
                                    <p class="text-sm font-medium opacity-90">You're Live!</p>
                                    <p class="text-lg font-bold">{{ $activeLive->title }}</p>
                                </div>
                            </div>
                            <a href="{{ route('live.show', $activeLive) }}" class="inline-flex items-center px-4 py-2 bg-white text-red-700 rounded-md font-semibold text-sm hover:bg-red-50 transition-colors">
                                Go to Stream
                            </a>
                        </div>
                    </div>
                @endif

                <div class="space-y-4">
                    @foreach($liveStreams as $stream)
                        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                            <div class="p-5">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-3">
                                            <h3 class="text-base font-semibold text-gray-900 truncate">
                                                <a href="{{ route('live.show', $stream) }}" class="hover:text-red-600">{{ $stream->title }}</a>
                                            </h3>
                                            @if($stream->status === 'live')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5 animate-pulse"></span>
                                                    LIVE
                                                </span>
                                            @elseif($stream->status === 'scheduled')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Scheduled
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    {{ ucfirst($stream->status) }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                            @if($stream->category)
                                                <span>{{ $stream->category }}</span>
                                            @endif
                                            @if($stream->scheduled_at && $stream->status === 'scheduled')
                                                <span>Scheduled: {{ $stream->scheduled_at->format('M j, Y g:i A') }}</span>
                                            @endif
                                            @if($stream->status === 'live')
                                                <span class="text-red-500">{{ $stream->viewer_count }} watching</span>
                                            @endif
                                            @if($stream->started_at)
                                                <span>Started: {{ $stream->started_at->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="ml-4 flex items-center space-x-2">
                                        <a href="{{ route('live.show', $stream) }}" class="text-sm text-indigo-600 hover:text-indigo-900">View</a>
                                        @if($stream->status === 'scheduled')
                                            <form action="{{ route('live.start', $stream) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-sm text-green-600 hover:text-green-900">Go Live</button>
                                            </form>
                                        @endif
                                        @if($stream->status === 'live')
                                            <form action="{{ route('live.stop', $stream) }}" method="POST" class="inline" onsubmit="return confirm('End this stream?');">
                                                @csrf
                                                <button type="submit" class="text-sm text-red-600 hover:text-red-900">End</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('live.edit', $stream) }}" class="text-sm text-gray-600 hover:text-gray-900">Edit</a>
                                        <form action="{{ route('live.destroy', $stream) }}" method="POST" class="inline" onsubmit="return confirm('Delete this stream?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-500 hover:text-red-700">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $liveStreams->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>