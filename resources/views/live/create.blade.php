<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Live Stream') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('live.store') }}" method="POST">
                        @csrf

                        <div class="mb-6">
                            <x-input-label for="title" :value="__('Stream Title')" />
                            <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title')" required autofocus placeholder="Enter a title for your stream" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="What's your stream about?">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <x-input-label for="category" :value="__('Category')" />
                                <select id="category" name="category" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select a category</option>
                                    <option value="Gaming" {{ old('category') === 'Gaming' ? 'selected' : '' }}>Gaming</option>
                                    <option value="Music" {{ old('category') === 'Music' ? 'selected' : '' }}>Music</option>
                                    <option value="Education" {{ old('category') === 'Education' ? 'selected' : '' }}>Education</option>
                                    <option value="Tech" {{ old('category') === 'Tech' ? 'selected' : '' }}>Tech</option>
                                    <option value="Entertainment" {{ old('category') === 'Entertainment' ? 'selected' : '' }}>Entertainment</option>
                                    <option value="Sports" {{ old('category') === 'Sports' ? 'selected' : '' }}>Sports</option>
                                    <option value="News" {{ old('category') === 'News' ? 'selected' : '' }}>News</option>
                                    <option value="Other" {{ old('category') === 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <x-input-error :messages="$errors->get('category')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="scheduled_at" :value="__('Schedule (optional)')" />
                                <x-text-input id="scheduled_at" class="block mt-1 w-full" type="datetime-local" name="scheduled_at" :value="old('scheduled_at')" />
                                <p class="mt-1 text-xs text-gray-500">Leave empty to start immediately when ready.</p>
                                <x-input-error :messages="$errors->get('scheduled_at')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                            <div class="flex items-start">
                                <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-sm text-blue-700">
                                    <p class="font-medium">After creating your stream:</p>
                                    <ol class="mt-1 list-decimal list-inside space-y-1">
                                        <li>You'll get a unique <strong>RTMP URL</strong> and <strong>Stream Key</strong></li>
                                        <li>Enter these in <strong>OBS Studio</strong> → Settings → Stream</li>
                                        <li>Click <strong>"Go Live"</strong> in the dashboard when you're ready to broadcast</li>
                                        <li>Viewers can watch via the HLS player on your stream page</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('live.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                Cancel
                            </a>
                            <x-primary-button>
                                {{ __('Create Stream') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>