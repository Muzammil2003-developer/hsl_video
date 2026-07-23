<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Live Stream') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('live.update', $liveStream) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-6">
                            <x-input-label for="title" :value="__('Stream Title')" />
                            <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title', $liveStream->title)" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $liveStream->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <x-input-label for="category" :value="__('Category')" />
                                <select id="category" name="category" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select a category</option>
                                    @foreach(['Gaming', 'Music', 'Education', 'Tech', 'Entertainment', 'Sports', 'News', 'Other'] as $cat)
                                        <option value="{{ $cat }}" {{ old('category', $liveStream->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('category')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="scheduled_at" :value="__('Schedule')" />
                                <x-text-input id="scheduled_at" class="block mt-1 w-full" type="datetime-local" name="scheduled_at"
                                    :value="old('scheduled_at', $liveStream->scheduled_at ? $liveStream->scheduled_at->format('Y-m-d\TH:i') : '')" />
                                <x-input-error :messages="$errors->get('scheduled_at')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Stream Information</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Status:</span>
                                    <span class="ml-2 text-gray-900 capitalize">{{ $liveStream->status }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Category:</span>
                                    <span class="ml-2 text-gray-900">{{ $liveStream->category ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Stream Key:</span>
                                    <span class="ml-2 text-gray-900 font-mono text-xs">{{ substr($liveStream->stream_key, 0, 16) }}...</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Viewers:</span>
                                    <span class="ml-2 text-gray-900">{{ $liveStream->viewer_count }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('live.show', $liveStream) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                Cancel
                            </a>
                            <x-primary-button>
                                {{ __('Update Stream') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>