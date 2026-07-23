<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Videos') }}
            </h2>
            <a href="{{ route('videos.create') }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                + Upload Video
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

            @if($videos->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-400 mb-4">
                            <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No videos yet</h3>
                        <p class="text-gray-500 mb-6">Upload your first video to get started.</p>
                        <a href="{{ route('videos.create') }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                            Upload Your First Video
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($videos as $video)
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            <a href="{{ $video->status === 'ready' ? route('videos.show', $video) : '#' }}" class="block">
                                <div class="relative aspect-video bg-gray-900">
                                    @if($video->thumbnail_path && $video->status === 'ready')
                                        <img src="{{ Storage::url($video->thumbnail_path) }}" alt="{{ $video->title }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="flex items-center justify-center h-full">
                                            @if($video->status === 'processing')
                                                <div class="text-center">
                                                    <svg class="animate-spin h-8 w-8 text-white mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <span class="text-white text-xs">Processing</span>
                                                </div>
                                            @elseif($video->status === 'uploading')
                                                <div class="text-center">
                                                    <div class="w-12 h-12 border-2 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                                                    <span class="text-white text-xs">Uploading... {{ $video->upload_progress }}%</span>
                                                </div>
                                            @elseif($video->status === 'processing_failed')
                                                <div class="text-center">
                                                    <svg class="h-8 w-8 text-red-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                    </svg>
                                                    <span class="text-red-400 text-xs">Processing Failed</span>
                                                </div>
                                            @else
                                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- Duration badge -->
                                    @if($video->duration && $video->status === 'ready')
                                        <span class="absolute bottom-2 right-2 bg-black bg-opacity-75 text-white text-xs px-1.5 py-0.5 rounded">
                                            {{ $video->getDurationForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </a>

                            <div class="p-4">
                                <a href="{{ $video->status === 'ready' ? route('videos.show', $video) : '#' }}">
                                    <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $video->title }}</h3>
                                </a>
                                <p class="text-xs text-gray-500 mt-1">{{ $video->created_at->diffForHumans() }}</p>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-xs text-gray-400">{{ $video->getFileSizeForHumans() }}</span>
                                    <div class="flex space-x-2">
                                        @if($video->status === 'ready')
                                            <span class="text-xs text-green-600 flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                                Ready
                                            </span>
                                        @elseif($video->status === 'processing')
                                            <span class="text-xs text-yellow-600">Processing</span>
                                        @elseif($video->status === 'uploading')
                                            <span class="text-xs text-blue-600">{{ $video->upload_progress }}%</span>
                                        @elseif($video->status === 'processing_failed')
                                            <span class="text-xs text-red-600">Failed</span>
                                        @endif

                                        <form action="{{ route('videos.destroy', $video) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this video?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $videos->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>