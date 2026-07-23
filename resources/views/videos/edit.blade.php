<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Video') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('videos.update', $video) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Title -->
                        <div class="mb-6">
                            <x-input-label for="title" :value="__('Title')" />
                            <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title', $video->title)" required autofocus />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="5" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $video->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <!-- Video Info (read-only) -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Video Information</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Filename:</span>
                                    <span class="ml-2 text-gray-900">{{ $video->original_filename }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Size:</span>
                                    <span class="ml-2 text-gray-900">{{ $video->getFileSizeForHumans() }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Duration:</span>
                                    <span class="ml-2 text-gray-900">{{ $video->getDurationForHumans() }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Status:</span>
                                    <span class="ml-2 text-gray-900 capitalize">{{ $video->status }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Uploaded:</span>
                                    <span class="ml-2 text-gray-900">{{ $video->created_at->format('M j, Y g:i A') }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Qualities:</span>
                                    <span class="ml-2 text-gray-900">
                                        @if($video->qualities)
                                            {{ implode(', ', $video->qualities) }}
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('videos.show', $video) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                Cancel
                            </a>
                            <x-primary-button>
                                {{ __('Update Video') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>