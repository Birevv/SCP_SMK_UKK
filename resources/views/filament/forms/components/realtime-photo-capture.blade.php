<x-filament-forms::field-wrapper :field="$field">
    @php
        $statePath = $getStatePath();
        $initialState = $getState();
        $initialUrl = null;
        if ($initialState) {
            $initialUrl = str_starts_with($initialState, 'http') || str_starts_with($initialState, '/')
                ? $initialState
                : asset('storage/' . ltrim($initialState, '/'));
        }
    @endphp

    <div
        x-data="realtimePhotoCapture({
            statePath: @js($statePath),
            initialValue: @js($initialState),
            initialUrl: @js($initialUrl),
            uploadUrl: @js(route('admin.journals.upload-realtime-photo')),
            csrfToken: @js(csrf_token()),
        })"
        x-init="init()"
        class="w-full space-y-3"
    >
        {{-- Hidden input sync with Livewire --}}
        <input type="hidden" name="{{ $statePath }}" :value="state" />

        {{-- 1. PREVIEW JIKA FOTO SUDAH TERSEDIA / TERSIMPAN --}}
        <div x-show="state && !isCameraOpen" x-cloak class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="relative h-24 w-28 flex-shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100 shadow-inner dark:border-gray-700 dark:bg-gray-800">
                        <template x-if="previewUrl">
                            <img :src="previewUrl" alt="Bukti Foto Jurnal" class="h-full w-full object-cover" />
                        </template>
                    </div>
                    <div class="min-w-0">
                        <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Foto Realtime Terlampir</span>
                        </div>
                        <p class="mt-1.5 truncate text-xs font-mono text-gray-500 dark:text-gray-400" x-text="photoFilename || state"></p>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500" x-text="capturedTimestamp ? 'Diambil: ' + capturedTimestamp : 'Tersimpan di sistem'"></p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="openCamera()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-750"
                    >
                        <svg class="h-4 w-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                        </svg>
                        <span>Foto Ulang (Kamera)</span>
                    </button>

                    <button
                        type="button"
                        @click="removePhoto()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50/60 px-3 py-2 text-xs font-medium text-red-600 transition hover:bg-red-100 focus:outline-none dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-400 dark:hover:bg-red-950"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        <span>Hapus</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- 2. LIVE CAMERA VIEWFINDER & SHUTTER --}}
        <div x-show="isCameraOpen" x-cloak class="relative overflow-hidden rounded-2xl border border-gray-700 bg-gray-950 text-white shadow-2xl">
            {{-- Top control bar --}}
            <div class="flex items-center justify-between border-b border-gray-800 bg-gray-900/90 px-4 py-2.5 backdrop-blur-sm">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                    </span>
                    <span class="text-xs font-semibold tracking-wide uppercase text-gray-200">Kamera Realtime Kelas</span>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Switch Camera (Front/Back) --}}
                    <button
                        type="button"
                        x-show="videoDevices.length > 1"
                        @click="flipCamera()"
                        class="inline-flex items-center gap-1 rounded-lg bg-gray-800 px-2.5 py-1 text-xs text-gray-300 transition hover:bg-gray-700"
                        title="Ganti Kamera (Depan/Belakang)"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Putar</span>
                    </button>

                    {{-- Close Camera --}}
                    <button
                        type="button"
                        @click="closeCamera()"
                        class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-800 hover:text-white"
                        title="Tutup Kamera"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Camera Viewport --}}
            <div class="relative flex min-h-[320px] items-center justify-center bg-black sm:min-h-[380px]">
                {{-- Live Video Stream --}}
                <video
                    x-ref="videoElement"
                    autoplay
                    playsinline
                    muted
                    x-show="!capturedImage && isStreaming"
                    class="max-h-[420px] w-full object-cover transition-transform"
                    :class="{ 'scale-x-[-1]': isFrontCamera }"
                ></video>

                {{-- Frozen Snapshot Review --}}
                <template x-if="capturedImage">
                    <div class="relative w-full">
                        <img :src="capturedImage" alt="Snapshot" class="max-h-[420px] w-full object-cover" />
                        <div class="absolute top-3 left-3 rounded-md bg-black/60 px-2 py-1 text-[11px] font-medium text-emerald-300 backdrop-blur-sm">
                            ✓ Pratinjau Foto Ditangkap
                        </div>
                    </div>
                </template>

                {{-- Loading / Connecting Indicator --}}
                <div x-show="!isStreaming && !cameraError && !capturedImage" class="flex flex-col items-center gap-3 p-6 text-center text-gray-400">
                    <svg class="h-8 w-8 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <p class="text-sm">Menghubungkan ke kamera perangkat...</p>
                </div>

                {{-- Camera Error Message --}}
                <div x-show="cameraError" x-cloak class="p-6 text-center text-red-400">
                    <svg class="mx-auto h-10 w-10 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <p class="mt-2 text-sm font-medium" x-text="cameraError"></p>
                    <p class="mt-1 text-xs text-gray-400">Pastikan izin kamera diizinkan pada browser Anda, atau gunakan unggah foto perangkat di bawah.</p>
                </div>

                {{-- Flash Animation Overlay --}}
                <div
                    x-show="isFlashing"
                    x-transition:enter="transition-opacity duration-75"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-90"
                    x-transition:leave="transition-opacity duration-150"
                    x-transition:leave-start="opacity-90"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-none absolute inset-0 bg-white"
                ></div>

                {{-- Hidden Canvas for processing snapshot & watermark --}}
                <canvas x-ref="canvasElement" class="hidden"></canvas>
            </div>

            {{-- Bottom Control Bar --}}
            <div class="flex items-center justify-center gap-4 border-t border-gray-800 bg-gray-900/90 p-4">
                {{-- Mode 1: Live Stream -> Shutter Button --}}
                <template x-if="!capturedImage && isStreaming">
                    <div class="flex items-center gap-4">
                        <button
                            type="button"
                            @click="captureSnapshot()"
                            class="group relative inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-red-600 to-rose-600 px-6 py-3 font-semibold text-white shadow-lg transition hover:scale-105 hover:from-red-500 hover:to-rose-500 active:scale-95 focus:outline-none"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <circle cx="12" cy="12" r="3" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            </svg>
                            <span>Ambil Foto Sekarang</span>
                        </button>
                    </div>
                </template>

                {{-- Mode 2: Captured -> Retake / Confirm Buttons --}}
                <template x-if="capturedImage">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <button
                            type="button"
                            @click="retakeSnapshot()"
                            :disabled="isUploading"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-gray-700 bg-gray-800 px-4 py-2.5 text-xs font-semibold text-gray-200 transition hover:bg-gray-700 focus:outline-none disabled:opacity-50"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span>Foto Ulang</span>
                        </button>

                        <button
                            type="button"
                            @click="confirmAndSave()"
                            :disabled="isUploading"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-green-600 px-5 py-2.5 text-xs font-semibold text-white shadow-md transition hover:from-emerald-500 hover:to-green-500 focus:outline-none disabled:opacity-60"
                        >
                            <template x-if="isUploading">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                            </template>
                            <template x-if="!isUploading">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </template>
                            <span x-text="isUploading ? 'Menyimpan Foto...' : 'Gunakan Foto Ini'"></span>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- 3. TOMBOL UTAMA BUKA KAMERA & FALLBACK UPLOAD (Ketika belum ada foto & kamera tertutup) --}}
        <div x-show="!state && !isCameraOpen" x-cloak class="rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50/70 p-6 text-center transition hover:border-primary-400 dark:border-gray-700 dark:bg-gray-900/40">
            <div class="mx-auto flex max-w-md flex-col items-center">
                {{-- Camera Icon Circle --}}
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-100 text-primary-600 shadow-sm dark:bg-primary-950/60 dark:text-primary-400">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                    </svg>
                </div>

                <h4 class="mt-3 text-base font-semibold text-gray-900 dark:text-gray-100">
                    Bukti Foto Kegiatan Belajar Mengajar
                </h4>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Ambil foto suasana kelas secara langsung (realtime) dengan kamera atau webcam untuk menjamin keaslian kehadiran.
                </p>

                {{-- Action: Buka Kamera Realtime --}}
                <div class="mt-4 flex flex-col gap-2.5 sm:flex-row">
                    <button
                        type="button"
                        @click="openCamera()"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus:outline-none dark:bg-primary-500 dark:hover:bg-primary-400"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                        </svg>
                        <span>Buka Kamera Realtime</span>
                    </button>

                    {{-- Action: Upload File / Kamera Perangkat Langsung --}}
                    <label
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-750"
                    >
                        <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        <span>Pilih / Foto Perangkat</span>
                        <input
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                            capture="environment"
                            @change="handleFileUpload($event)"
                            class="hidden"
                        />
                    </label>
                </div>

                <p class="mt-3 text-[11px] text-gray-400 dark:text-gray-500">
                    💡 Pada perangkat smartphone, tombol di atas dapat langsung membuka kamera belakang secara otomatis.
                </p>
            </div>
        </div>
    </div>

    <script>
        function realtimePhotoCapture(config) {
            return {
                statePath: config.statePath,
                state: config.initialValue || null,
                previewUrl: config.initialUrl || null,
                photoFilename: null,
                capturedTimestamp: null,

                uploadUrl: config.uploadUrl,
                csrfToken: config.csrfToken,

                isCameraOpen: false,
                isStreaming: false,
                cameraError: null,
                isFlashing: false,
                isUploading: false,
                capturedImage: null,

                stream: null,
                videoDevices: [],
                currentDeviceIndex: 0,
                isFrontCamera: false,

                init() {
                    if (this.state && !this.previewUrl) {
                        this.previewUrl = this.state.startsWith('http') || this.state.startsWith('/')
                            ? this.state
                            : '/storage/' + this.state.replace(/^\/+/, '');
                    }
                    if (this.state) {
                        const parts = this.state.split('/');
                        this.photoFilename = parts[parts.length - 1];
                    }

                    // Sinkronisasi jika Livewire mengupdate state
                    if (window.Livewire) {
                        this.$watch('state', (value) => {
                            this.$wire.set(this.statePath, value);
                        });
                    }
                },

                async openCamera() {
                    this.isCameraOpen = true;
                    this.cameraError = null;
                    this.capturedImage = null;
                    this.isStreaming = false;

                    try {
                        // Cek device kamera yang tersedia
                        if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
                            const devices = await navigator.mediaDevices.enumerateDevices();
                            this.videoDevices = devices.filter(d => d.kind === 'videoinput');
                        }

                        await this.startStream();
                    } catch (err) {
                        console.error('Gagal mengakses kamera:', err);
                        this.cameraError = 'Tidak dapat mengakses kamera: ' + (err.message || 'Izin ditolak.');
                    }
                },

                async startStream() {
                    this.stopStream();

                    const constraints = {
                        audio: false,
                        video: {
                            width: { ideal: 1280 },
                            height: { ideal: 720 },
                            facingMode: { ideal: 'environment' }
                        }
                    };

                    if (this.videoDevices.length > 0 && this.videoDevices[this.currentDeviceIndex]) {
                        constraints.video.deviceId = { exact: this.videoDevices[this.currentDeviceIndex].deviceId };
                    }

                    const stream = await navigator.mediaDevices.getUserMedia(constraints);
                    this.stream = stream;

                    const video = this.$refs.videoElement;
                    if (video) {
                        video.srcObject = stream;
                        await video.play();
                        this.isStreaming = true;

                        // Deteksi kamera depan untuk efek mirror
                        const track = stream.getVideoTracks()[0];
                        const settings = track.getSettings ? track.getSettings() : {};
                        this.isFrontCamera = settings.facingMode === 'user';
                    }
                },

                stopStream() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(track => track.stop());
                        this.stream = null;
                    }
                    this.isStreaming = false;
                },

                closeCamera() {
                    this.stopStream();
                    this.isCameraOpen = false;
                    this.capturedImage = null;
                    this.cameraError = null;
                },

                async flipCamera() {
                    if (this.videoDevices.length <= 1) return;
                    this.currentDeviceIndex = (this.currentDeviceIndex + 1) % this.videoDevices.length;
                    await this.startStream();
                },

                captureSnapshot() {
                    const video = this.$refs.videoElement;
                    const canvas = this.$refs.canvasElement;
                    if (!video || !canvas) return;

                    // Efek flash
                    this.isFlashing = true;
                    setTimeout(() => { this.isFlashing = false; }, 150);

                    const width = video.videoWidth || 1280;
                    const height = video.videoHeight || 720;
                    canvas.width = width;
                    canvas.height = height;

                    const ctx = canvas.getContext('2d');

                    // Jika kamera depan, gambar secara mirrored
                    if (this.isFrontCamera) {
                        ctx.translate(width, 0);
                        ctx.scale(-1, 1);
                    }
                    ctx.drawImage(video, 0, 0, width, height);

                    if (this.isFrontCamera) {
                        ctx.setTransform(1, 0, 0, 1, 0, 0); // Reset transform untuk teks watermark
                    }

                    // TAMPILKAN WATERMARK REALTIME PADA FOTO
                    this.drawRealtimeWatermark(ctx, width, height);

                    // Konversi ke base64 JPEG
                    this.capturedImage = canvas.toDataURL('image/jpeg', 0.9);
                    this.stopStream();
                },

                drawRealtimeWatermark(ctx, width, height) {
                    const now = new Date();
                    const options = {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false
                    };
                    const dateStr = now.toLocaleDateString('id-ID', options) + ' WIB';
                    this.capturedTimestamp = dateStr;

                    const barHeight = Math.max(64, Math.round(height * 0.1));
                    const y = height - barHeight;

                    // Background bar semi transparan
                    ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
                    ctx.fillRect(0, y, width, barHeight);

                    // Border aksen hijau di atas bar
                    ctx.fillStyle = '#10b981';
                    ctx.fillRect(0, y, width, 3);

                    // Teks Watermark
                    const fontSize = Math.max(13, Math.round(barHeight * 0.28));
                    ctx.font = 'bold ' + fontSize + 'px sans-serif';
                    ctx.fillStyle = '#ffffff';
                    ctx.shadowColor = 'rgba(0, 0, 0, 0.8)';
                    ctx.shadowBlur = 4;
                    ctx.fillText('🏫 SCP SMK - Presensi & Jurnal Mengajar', 16, y + (barHeight * 0.42));

                    ctx.font = '500 ' + Math.max(11, Math.round(fontSize * 0.85)) + 'px monospace';
                    ctx.fillStyle = '#34d399';
                    ctx.fillText('📅 ' + dateStr + ' | BUKTI REALTIME', 16, y + (barHeight * 0.78));

                    ctx.shadowBlur = 0; // Reset shadow
                },

                retakeSnapshot() {
                    this.capturedImage = null;
                    this.startStream();
                },

                async confirmAndSave() {
                    if (!this.capturedImage) return;

                    this.isUploading = true;
                    try {
                        const response = await fetch(this.uploadUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                photo_base64: this.capturedImage
                            })
                        });

                        const result = await response.json();
                        if (result.success) {
                            this.state = result.path;
                            this.previewUrl = result.url;
                            this.photoFilename = result.filename;
                            this.capturedTimestamp = result.captured_at || this.capturedTimestamp;

                            // Update Livewire form state
                            if (this.$wire) {
                                this.$wire.set(this.statePath, result.path);
                            }

                            this.closeCamera();
                        } else {
                            alert('Gagal menyimpan foto: ' + (result.message || 'Terjadi kesalahan'));
                        }
                    } catch (err) {
                        console.error('Upload foto gagal:', err);
                        alert('Gagal mengunggah foto: ' + (err.message || 'Koneksi terputus.'));
                    } finally {
                        this.isUploading = false;
                    }
                },

                async handleFileUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('photo', file);

                    try {
                        const response = await fetch(this.uploadUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: formData
                        });

                        const result = await response.json();
                        if (result.success) {
                            this.state = result.path;
                            this.previewUrl = result.url;
                            this.photoFilename = result.filename;
                            this.capturedTimestamp = result.captured_at;

                            if (this.$wire) {
                                this.$wire.set(this.statePath, result.path);
                            }
                        } else {
                            alert('Gagal mengunggah foto: ' + (result.message || 'Terjadi kesalahan'));
                        }
                    } catch (err) {
                        console.error('Upload file gagal:', err);
                        alert('Gagal mengunggah file foto: ' + err.message);
                    }
                },

                removePhoto() {
                    if (confirm('Apakah Anda yakin ingin menghapus foto bukti ini?')) {
                        this.state = null;
                        this.previewUrl = null;
                        this.photoFilename = null;
                        this.capturedTimestamp = null;
                        if (this.$wire) {
                            this.$wire.set(this.statePath, null);
                        }
                    }
                }
            };
        }
    </script>
</x-filament-forms::field-wrapper>
