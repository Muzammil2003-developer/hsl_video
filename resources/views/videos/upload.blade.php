<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upload Video') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Upload Drop Zone -->
                    <div id="upload-zone" class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-red-400 transition-colors duration-300 cursor-pointer"
                         @dragover.prevent="active=true"
                         @dragleave.prevent="active=false"
                         @drop.prevent="handleDrop">
                        <div id="upload-prompt">
                            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="mt-4 text-lg text-gray-600">Drag and drop your video file here, or <span class="text-red-600 font-medium">browse</span></p>
                            <p class="mt-2 text-sm text-gray-500">Supports MP4, AVI, MOV, MKV, WebM up to 10GB</p>
                            <input type="file" id="file-input" accept="video/*" class="hidden">
                        </div>

                        <!-- Upload Progress -->
                        <div id="upload-progress" class="hidden">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <svg class="h-10 w-10 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm0 2h12v10H4V5z" clip-rule="evenodd"/>
                                        <path d="M7 8a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z"/>
                                    </svg>
                                    <div class="text-left">
                                        <p id="file-name" class="text-sm font-medium text-gray-900"></p>
                                        <p id="file-size" class="text-xs text-gray-500"></p>
                                    </div>
                                </div>
                                <button id="cancel-upload" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                                <div id="progress-bar" class="bg-red-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span id="progress-text">0%</span>
                                <span id="upload-status">Preparing...</span>
                            </div>

                            <!-- Chunk Details -->
                            <div id="chunk-details" class="mt-2 text-xs text-gray-400 hidden">
                                <span id="chunk-info"></span>
                            </div>
                        </div>

                        <!-- Upload Complete / Processing -->
                        <div id="upload-complete" class="hidden">
                            <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="mt-4 text-lg font-medium text-gray-900">Upload Complete!</p>
                            <p class="mt-1 text-sm text-gray-500">Your video is now being processed in the background.</p>
                            <div class="mt-4 flex justify-center space-x-4">
                                <a href="{{ route('videos.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                    Go to My Videos
                                </a>
                                <button id="upload-another" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                                    Upload Another
                                </button>
                            </div>
                        </div>

                        <!-- Upload Error -->
                        <div id="upload-error" class="hidden">
                            <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <p class="mt-4 text-lg font-medium text-red-900">Upload Failed</p>
                            <p id="error-message" class="mt-1 text-sm text-red-500"></p>
                            <button id="retry-upload" class="mt-4 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                                Try Again
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function() {
            const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB chunks
            let uploadSessionId = null;
            let videoId = null;
            let currentFile = null;
            let totalChunks = 0;
            let uploadedChunks = new Set();
            let uploading = false;
            let cancelled = false;

            const uploadZone = document.getElementById('upload-zone');
            const fileInput = document.getElementById('file-input');
            const uploadPrompt = document.getElementById('upload-prompt');
            const uploadProgress = document.getElementById('upload-progress');
            const uploadComplete = document.getElementById('upload-complete');
            const uploadError = document.getElementById('upload-error');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const uploadStatus = document.getElementById('upload-status');
            const fileName = document.getElementById('file-name');
            const fileSize = document.getElementById('file-size');
            const errorMessage = document.getElementById('error-message');
            const chunkInfo = document.getElementById('chunk-info');
            const chunkDetails = document.getElementById('chunk-details');
            const cancelBtn = document.getElementById('cancel-upload');
            const retryBtn = document.getElementById('retry-upload');
            const uploadAnotherBtn = document.getElementById('upload-another');

            // Event listeners
            uploadZone.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    startUpload(e.target.files[0]);
                }
            });

            uploadZone.addEventListener('dragover', () => {
                uploadZone.classList.add('border-red-400', 'bg-red-50');
            });
            uploadZone.addEventListener('dragleave', () => {
                uploadZone.classList.remove('border-red-400', 'bg-red-50');
            });
            uploadZone.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadZone.classList.remove('border-red-400', 'bg-red-50');
                if (e.dataTransfer.files.length > 0) {
                    startUpload(e.dataTransfer.files[0]);
                }
            });

            cancelBtn.addEventListener('click', cancelUpload);
            retryBtn.addEventListener('click', () => {
                if (currentFile) startUpload(currentFile);
            });
            uploadAnotherBtn.addEventListener('click', resetUpload);

            function formatFileSize(bytes) {
                const units = ['B', 'KB', 'MB', 'GB'];
                let i = 0;
                while (bytes >= 1024 && i < units.length - 1) {
                    bytes /= 1024;
                    i++;
                }
                return bytes.toFixed(2) + ' ' + units[i];
            }

            function updateProgress(percent, statusText) {
                progressBar.style.width = percent + '%';
                progressText.textContent = Math.round(percent) + '%';
                if (statusText) uploadStatus.textContent = statusText;
            }

            function resetUpload() {
                uploadPrompt.classList.remove('hidden');
                uploadProgress.classList.add('hidden');
                uploadComplete.classList.add('hidden');
                uploadError.classList.add('hidden');
                progressBar.style.width = '0%';
                progressText.textContent = '0%';
                uploading = false;
                cancelled = false;
                currentFile = null;
                uploadSessionId = null;
                videoId = null;
                fileInput.value = '';
                uploadedChunks.clear();
            }

            function cancelUpload() {
                cancelled = true;
                uploading = false;
                resetUpload();
            }

            async function startUpload(file) {
                // Validate file type
                const validTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-matroska', 'video/webm', 'video/x-msvideo', 'video/mpeg'];
                if (!validTypes.includes(file.type) && file.type.startsWith('video/') === false) {
                    showError('Unsupported file type. Please upload a video file.');
                    return;
                }

                // Validate file size (10GB max)
                const maxSize = 10 * 1024 * 1024 * 1024; // 10GB
                if (file.size > maxSize) {
                    showError('File is too large. Maximum file size is 10GB.');
                    return;
                }

                currentFile = file;
                cancelled = false;
                uploading = true;

                // Show progress UI
                uploadPrompt.classList.add('hidden');
                uploadProgress.classList.remove('hidden');
                uploadComplete.classList.add('hidden');
                uploadError.classList.add('hidden');

                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);

                totalChunks = Math.ceil(file.size / CHUNK_SIZE);
                updateProgress(0, 'Initializing...');

                try {
                    // Step 1: Initiate upload session
                    const initiateRes = await fetch('{{ route("upload.initiate") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            filename: file.name,
                            file_size: file.size,
                            mime_type: file.type,
                        }),
                    });

                    if (!initiateRes.ok) {
                        const err = await initiateRes.json();
                        throw new Error(err.error || err.message || 'Failed to initialize upload');
                    }

                    const initData = await initiateRes.json();
                    uploadSessionId = initData.upload_session_id;
                    videoId = initData.video_id;

                    // Step 2: Upload chunks with resumable support
                    for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                        if (cancelled) return;

                        // Check if chunk was already uploaded (resumable)
                        if (uploadedChunks.has(chunkIndex)) {
                            continue;
                        }

                        const start = chunkIndex * CHUNK_SIZE;
                        const end = Math.min(start + CHUNK_SIZE, file.size);
                        const chunk = file.slice(start, end);

                        const formData = new FormData();
                        formData.append('upload_session_id', uploadSessionId);
                        formData.append('chunk_index', chunkIndex);
                        formData.append('total_chunks', totalChunks);
                        formData.append('chunk', chunk, `chunk_${chunkIndex}`);

                        chunkDetails.classList.remove('hidden');
                        chunkInfo.textContent = `Chunk ${chunkIndex + 1} of ${totalChunks} (${formatFileSize(end - start)})`;

                        const chunkRes = await fetch('{{ route("upload.chunk") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        if (!chunkRes.ok) {
                            const err = await chunkRes.json();
                            throw new Error(err.error || err.message || `Failed to upload chunk ${chunkIndex + 1}`);
                        }

                        const chunkData = await chunkRes.json();
                        uploadedChunks.add(chunkIndex);

                        const percent = ((chunkIndex + 1) / totalChunks) * 100;
                        updateProgress(percent, `Uploading chunk ${chunkIndex + 1} of ${totalChunks}`);
                    }

                    // Step 3: Finalize upload
                    if (cancelled) return;
                    updateProgress(100, 'Finalizing...');

                    const finalizeRes = await fetch('{{ route("upload.finalize") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            upload_session_id: uploadSessionId,
                            total_chunks: totalChunks,
                        }),
                    });

                    if (!finalizeRes.ok) {
                        const err = await finalizeRes.json();
                        throw new Error(err.error || err.message || 'Failed to finalize upload');
                    }

                    const finalData = await finalizeRes.json();

                    // Show complete UI
                    uploadProgress.classList.add('hidden');
                    uploadComplete.classList.remove('hidden');
                    uploading = false;

                } catch (error) {
                    console.error('Upload error:', error);
                    showError(error.message || 'An unexpected error occurred during upload.');
                }
            }

            function showError(message) {
                uploadProgress.classList.add('hidden');
                uploadComplete.classList.add('hidden');
                uploadError.classList.remove('hidden');
                errorMessage.textContent = message;
                uploading = false;
            }
        })();
    </script>
    @endpush

    @stack('scripts')
</x-app-layout>