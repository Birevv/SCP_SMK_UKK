<x-filament-forms::field-wrapper :field="$field">
    @php
        $statePath = $getStatePath();
        $initialState = $getState();
        if (is_array($initialState)) {
            $initialState = reset($initialState);
        }
        $initialUrl = null;
        if (is_string($initialState) && ! empty($initialState)) {
            if (str_starts_with($initialState, 'http://') || str_starts_with($initialState, 'https://')) {
                $initialUrl = $initialState;
            } elseif (str_starts_with($initialState, '/storage/')) {
                $initialUrl = asset(ltrim($initialState, '/'));
            } else {
                $initialUrl = asset('storage/' . ltrim($initialState, '/'));
            }
        }

        $isPdf = $initialState && (str_ends_with(strtolower($initialState), '.pdf') || str_contains(strtolower($initialState), '.pdf'));
        $directory = method_exists($field, 'getDirectory') ? $field->getDirectory() : 'journal_photos';
        $maxSizeKb = method_exists($field, 'getMaxSize') ? $field->getMaxSize() : 5120;
        $enableWatermark = method_exists($field, 'getEnableWatermark') ? $field->getEnableWatermark() : true;
        $defaultFacing = method_exists($field, 'getDefaultFacingMode') ? $field->getDefaultFacingMode() : 'environment';
        $captureType = method_exists($field, 'getCaptureType') ? $field->getCaptureType() : 'journal';
        $isDisabled = $isDisabled();
    @endphp

    <div
        x-data="scpInlineCameraComponent({
            wire: $wire,
            statePath: @js($statePath),
            initialValue: @js(is_string($initialState) ? $initialState : null),
            initialUrl: @js($initialUrl),
            uploadUrl: @js(route('admin.journals.upload-realtime-photo')),
            csrfToken: @js(csrf_token()),
            directory: @js($directory),
            maxSizeKb: @js($maxSizeKb),
            enableWatermark: @js($enableWatermark),
            defaultFacingMode: @js($defaultFacing),
            captureType: @js($captureType),
            isDisabled: @js($isDisabled),
        })"
        x-init="initComponent()"
        class="scp-cam-wrapper"
    >
        {{-- Hidden Input for Livewire Form Submission --}}
        <input type="hidden" name="{{ $statePath }}" wire:model.defer="{{ $statePath }}" :value="photoValue" />

        {{-- Hidden Canvas for Snapshot Capture & Watermarking --}}
        <canvas x-ref="snapshotCanvasRef" style="display: none;"></canvas>

        {{-- Main Inline Card (Directly embedded in form, no modal) --}}
        <div class="scp-cam-inline-card">

            {{-- 1. KONDISI SUDAH ADA FOTO / LAMPIRAN (PREVIEW HASIL FOTO REALTIME ATAU DOKUMEN) --}}
            <div x-show="hasItem" @if(! $initialUrl) x-cloak @endif>
                <div class="scp-cam-inline-header">
                    <div class="scp-cam-inline-title">
                        <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $captureType === 'leave_request' ? 'Lampiran Bukti Perizinan' : 'Bukti Foto Kegiatan Pembelajaran' }}</span>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-mono" x-show="capturedTimestamp" x-text="capturedTimestamp"></span>
                </div>

                {{-- Preview Display Screen --}}
                <div class="scp-cam-inline-viewport" style="aspect-ratio: 16 / 9; max-height: 380px;">
                    @if($isPdf)
                        <div class="flex flex-col items-center justify-center h-full p-6 text-center bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <svg class="w-14 h-14 text-rose-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            <span class="font-semibold text-sm text-gray-800 dark:text-gray-200">Berkas Dokumen Lampiran (PDF)</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-3">{{ basename($initialState) }}</span>
                            <a href="{{ $initialUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-xs font-medium shadow-sm transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                Buka / Unduh Dokumen PDF
                            </a>
                        </div>
                    @else
                        <img
                            :src="itemUrl"
                            @if($initialUrl) src="{{ $initialUrl }}" @endif
                            alt="Bukti Foto Realtime"
                            class="scp-cam-inline-video"
                            style="object-fit: contain; background-color: #020617;"
                        />
                    @endif
                </div>

                {{-- Action / Info Links & Buttons --}}
                <div class="mt-3 flex items-center justify-between gap-2 flex-wrap">
                    @if($initialUrl && ! $isPdf)
                        <a
                            href="{{ $initialUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 inline-flex items-center gap-1"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                            <span>Lihat Foto Ukuran Penuh</span>
                        </a>
                    @else
                        <span></span>
                    @endif

                    <div class="scp-cam-inline-controls" x-show="!isDisabled">
                        <button
                            type="button"
                            @click="retakePhoto()"
                            class="scp-btn-cam-secondary"
                        >
                            <svg class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span>Ambil Ulang Foto</span>
                        </button>

                        <button
                            type="button"
                            @click="removeItem()"
                            class="scp-btn-cam-danger"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- KONDISI READONLY / DISABLED DAN BELUM ADA FOTO --}}
            @if($isDisabled && ! $initialUrl)
                <div class="p-4 text-center rounded-lg bg-gray-50 dark:bg-gray-900 border border-dashed border-gray-300 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Tidak ada bukti foto / dokumen perizinan yang dilampirkan.</p>
                </div>
            @endif

            {{-- 2. KONDISI KAMERA LANGSUNG DITAMPILKAN DI HALAMAN (HANYA SAAT FORM EDIT/CREATE DAN TIDAK ADA FOTO) --}}
            @if(! $isDisabled)
            <div x-show="!hasItem" @if($initialUrl) x-cloak @endif>
                <div class="scp-cam-inline-header">
                    <div class="scp-cam-inline-title">
                        <svg class="h-4 w-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                        </svg>
                        <span x-text="captureType === 'leave_request' ? 'Foto Surat Izin / Sakit (Realtime)' : 'Bukti Foto Kegiatan Pembelajaran (Realtime)'"></span>
                    </div>

                    {{-- Info mode kamera saat streaming --}}
                    <div class="flex items-center gap-2" x-show="isStreaming">
                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 font-medium">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span x-text="currentFacingMode === 'environment' ? 'Kamera Belakang' : 'Kamera Depan'"></span>
                        </span>
                    </div>
                </div>

                {{-- Alert Error jika izin kamera ditolak / tidak tersedia --}}
                <template x-if="cameraError">
                    <div class="mb-3 p-3 rounded-lg text-xs bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 flex items-start gap-2.5">
                        <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <div class="flex-1">
                            <p x-text="cameraError" class="font-medium"></p>
                            <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-1">
                                Pastikan kamera tidak digunakan oleh aplikasi lain dan browser memiliki izin akses kamera.
                            </p>
                        </div>
                    </div>
                </template>

                {{-- Viewport Kamera Langsung di Halaman --}}
                <div class="scp-cam-inline-viewport">
                    {{-- 1. Video Stream --}}
                    <video
                        x-ref="videoRef"
                        autoplay
                        playsinline
                        muted
                        class="scp-cam-inline-video"
                        :class="{ 'is-mirrored': currentFacingMode === 'user' }"
                        x-show="isStreaming && !isUploading"
                    ></video>

                    {{-- 2. Standby Box saat kamera belum aktif --}}
                    <div
                        x-show="!isStreaming && !isCameraLoading && !isUploading"
                        x-cloak
                        class="scp-cam-standby-box"
                    >
                        <div class="scp-cam-standby-icon">
                            <svg style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                            </svg>
                        </div>
                        <span style="font-size: 0.8125rem; font-weight: 600; color: #f8fafc;">Kamera Belum Aktif</span>
                        <span style="font-size: 0.6875rem; color: #94a3b8; max-width: 280px; margin: 0 auto; line-height: 1.3;">
                            Klik tombol "Nyalakan Kamera" untuk mulai mengambil foto langsung.
                        </span>
                    </div>

                    {{-- 3. Indicator Loading Kamera --}}
                    <div
                        x-show="isCameraLoading"
                        x-cloak
                        class="scp-cam-loading-overlay"
                    >
                        <div class="scp-cam-spinner"></div>
                        <p class="scp-cam-loading-text">Menghubungkan Kamera...</p>
                        <p class="scp-cam-loading-subtext">Mohon izinkan akses kamera jika diminta browser</p>
                    </div>

                    {{-- 4. Indicator Menyimpan Foto --}}
                    <div
                        x-show="isUploading"
                        x-cloak
                        class="scp-cam-loading-overlay"
                    >
                        <div class="scp-cam-spinner scp-cam-spinner-success"></div>
                        <p class="scp-cam-loading-text">Menyimpan Foto Realtime...</p>
                        <p class="scp-cam-loading-subtext">Menerapkan watermark & verifikasi tanggal</p>
                    </div>
                </div>

                {{-- Toolbar Kontrol Kamera di Bawah Video --}}
                <div class="scp-cam-inline-controls" x-show="!isDisabled">

                    {{-- A. Saat Kamera Sedang Aktif --}}
                    <template x-if="isStreaming">
                        <div class="flex items-center justify-center gap-2.5 flex-wrap w-full">
                            {{-- Tombol Ambil Foto Utama --}}
                            <button
                                type="button"
                                @click="takeSnapshot()"
                                :disabled="!isStreaming || isCameraLoading || isUploading"
                                class="scp-btn-cam-primary"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                                </svg>
                                <span>Ambil Foto</span>
                            </button>

                            {{-- Tombol Putar Kamera Depan / Belakang --}}
                            <button
                                type="button"
                                @click="toggleFacingMode()"
                                :disabled="isCameraLoading || isUploading"
                                class="scp-btn-cam-secondary"
                                title="Beralih antara Kamera Depan dan Belakang"
                            >
                                <svg class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <span x-text="currentFacingMode === 'environment' ? 'Putar ke Kamera Depan' : 'Putar ke Kamera Belakang'"></span>
                            </button>

                            {{-- Dropdown Pilihan Perangkat jika > 1 kamera terdeteksi --}}
                            <div x-show="availableDevices.length > 1">
                                <select
                                    x-model="selectedDeviceId"
                                    @change="onDeviceSelected()"
                                    class="scp-cam-select-device"
                                >
                                    <template x-for="(dev, idx) in availableDevices" :key="dev.deviceId">
                                        <option :value="dev.deviceId" x-text="dev.label || ('Kamera ' + (idx + 1))"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Tombol Matikan Kamera Sementara --}}
                            <button
                                type="button"
                                @click="stopCameraStream()"
                                class="text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 px-3 py-2"
                            >
                                Matikan Kamera
                            </button>
                        </div>
                    </template>

                    {{-- B. Saat Kamera Belum Aktif --}}
                    <template x-if="!isStreaming">
                        <div class="flex items-center justify-center gap-2.5 flex-wrap w-full">
                            <button
                                type="button"
                                @click="startStream()"
                                :disabled="isCameraLoading"
                                class="scp-btn-cam-primary"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                                </svg>
                                <span>Nyalakan Kamera</span>
                            </button>

                            <button
                                type="button"
                                @click="toggleFacingMode()"
                                class="scp-btn-cam-secondary"
                            >
                                <svg class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <span x-text="currentFacingMode === 'environment' ? 'Mode: Kamera Belakang' : 'Mode: Kamera Depan'"></span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
            @endif
        </div>
    </div>

    <script>
        if (typeof window.scpInlineCameraComponent === 'undefined') {
            window.scpInlineCameraComponent = function(cfg) {
            return {
                statePath: cfg.statePath,
                wireInstance: cfg.wire,
                photoValue: cfg.initialValue,
                itemUrl: cfg.initialUrl,
                uploadUrl: cfg.uploadUrl,
                csrfToken: cfg.csrfToken,
                directory: cfg.directory,
                maxSizeKb: cfg.maxSizeKb,
                enableWatermark: Boolean(cfg.enableWatermark),
                defaultFacingMode: cfg.defaultFacingMode || 'environment',
                captureType: cfg.captureType,
                isDisabled: Boolean(cfg.isDisabled),

                hasItem: Boolean(cfg.initialValue),
                isStreaming: false,
                isCameraLoading: false,
                isUploading: false,
                cameraError: null,
                capturedTimestamp: null,

                mediaStream: null,
                currentFacingMode: cfg.defaultFacingMode || 'environment',
                availableDevices: [],
                selectedDeviceId: null,

                initComponent() {
                    window.addEventListener('beforeunload', () => this.stopCameraStream());
                    window.addEventListener('pagehide', () => this.stopCameraStream());

                    // Auto-start camera jika belum ada foto dan form dapat diedit
                    if (!this.hasItem && !this.isDisabled) {
                        this.$nextTick(() => {
                            this.loadAvailableDevices().then(() => {
                                this.startStream();
                            });
                        });
                    }
                },

                // Contract alias for testing compatibility
                closeCameraModal() {
                    this.stopCameraStream();
                },

                async loadAvailableDevices() {
                    try {
                        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
                            return;
                        }
                        const devices = await navigator.mediaDevices.enumerateDevices();
                        this.availableDevices = devices.filter(d => d.kind === 'videoinput');
                        if (this.availableDevices.length > 0 && !this.selectedDeviceId) {
                            this.selectedDeviceId = this.availableDevices[0].deviceId;
                        }
                    } catch (e) {
                        console.warn('Gagal mendeteksi daftar kamera:', e);
                    }
                },

                async startStream() {
                    if (this.isDisabled) return;
                    this.isCameraLoading = true;
                    this.cameraError = null;
                    this.stopCameraStream();

                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        this.cameraError = 'Browser ini tidak mendukung akses kamera perangkat secara langsung.';
                        this.isCameraLoading = false;
                        return;
                    }

                    try {
                        let constraints;
                        if (this.selectedDeviceId) {
                            constraints = {
                                video: {
                                    deviceId: { exact: this.selectedDeviceId },
                                    width: { ideal: 1280 },
                                    height: { ideal: 720 },
                                },
                                audio: false,
                            };
                        } else {
                            constraints = {
                                video: {
                                    facingMode: this.currentFacingMode === 'user' ? 'user' : { ideal: 'environment' },
                                    width: { ideal: 1280 },
                                    height: { ideal: 720 },
                                },
                                audio: false,
                            };
                        }

                        let stream;
                        try {
                            stream = await navigator.mediaDevices.getUserMedia(constraints);
                        } catch (firstErr) {
                            stream = await navigator.mediaDevices.getUserMedia({
                                video: true,
                                audio: false,
                            });
                        }

                        this.mediaStream = stream;
                        this.isStreaming = true;
                        this.isCameraLoading = false;

                        this.$nextTick(() => {
                            if (this.$refs.videoRef) {
                                this.$refs.videoRef.srcObject = stream;
                                this.$refs.videoRef.play().catch(e => console.warn('Video play error:', e));
                            }
                        });

                        this.loadAvailableDevices();
                    } catch (err) {
                        console.warn('Kamera gagal dimulai:', err);
                        this.isCameraLoading = false;
                        this.isStreaming = false;
                        this.stopCameraStream();

                        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                            this.cameraError = 'Izin kamera ditolak. Silakan izinkan akses kamera di ikon gembok pada address bar browser Anda.';
                        } else if (err.name === 'NotReadableError' || (err.message && err.message.toLowerCase().includes('could not start video source'))) {
                            this.cameraError = 'Kamera sedang digunakan oleh aplikasi lain atau driver sibuk. Tutup aplikasi tersebut lalu coba lagi.';
                        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                            this.cameraError = 'Tidak ada perangkat kamera yang terdeteksi pada perangkat ini.';
                        } else {
                            this.cameraError = 'Kendala akses kamera: ' + (err.message || err.name);
                        }
                    }
                },

                stopCameraStream() {
                    if (this.mediaStream) {
                        this.mediaStream.getTracks().forEach(track => {
                            try { track.stop(); } catch (e) {}
                        });
                        this.mediaStream = null;
                    }
                    if (this.$refs.videoRef) {
                        this.$refs.videoRef.srcObject = null;
                    }
                    this.isStreaming = false;
                },

                async switchFacingMode(mode) {
                    if (this.currentFacingMode === mode) return;
                    this.currentFacingMode = mode;
                    this.selectedDeviceId = null;
                    await this.startStream();
                },

                async toggleFacingMode() {
                    const nextMode = this.currentFacingMode === 'user' ? 'environment' : 'user';
                    this.currentFacingMode = nextMode;
                    this.selectedDeviceId = null;
                    await this.startStream();
                },

                async onDeviceSelected() {
                    await this.startStream();
                },

                async takeSnapshot() {
                    if (!this.mediaStream || !this.$refs.videoRef) return;
                    const video = this.$refs.videoRef;
                    const canvas = this.$refs.snapshotCanvasRef;

                    if (!video.videoWidth || !video.videoHeight) {
                        alert('Kamera sedang memuat, silakan tunggu sejenak lalu tekan kembali.');
                        return;
                    }

                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');

                    ctx.save();
                    if (this.currentFacingMode === 'user') {
                        ctx.translate(canvas.width, 0);
                        ctx.scale(-1, 1);
                    }
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    ctx.restore();

                    if (this.enableWatermark) {
                        this.applyWatermark(ctx, canvas.width, canvas.height);
                    }

                    const dataUrl = canvas.toDataURL('image/jpeg', 0.88);

                    // Hentikan video stream setelah foto diambil
                    this.stopCameraStream();

                    // Simpan ke backend via upload endpoint
                    await this.uploadCapturedPhoto(dataUrl);
                },

                applyWatermark(ctx, width, height) {
                    const barHeight = Math.max(34, Math.round(height * 0.08));
                    const y = height - barHeight;

                    ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
                    ctx.fillRect(0, y, width, barHeight);

                    ctx.fillStyle = '#10b981';
                    ctx.fillRect(0, y, width, 3);

                    const now = new Date();
                    const dateStr = now.toLocaleDateString('id-ID', {
                        day: '2-digit', month: 'short', year: 'numeric'
                    }) + ' ' + now.toLocaleTimeString('id-ID');

                    const fontSize = Math.max(12, Math.round(barHeight * 0.32));
                    ctx.font = 'bold ' + fontSize + 'px sans-serif';
                    ctx.fillStyle = '#ffffff';
                    ctx.fillText('SCP SMK - BUKTI REALTIME', 14, y + (barHeight * 0.45));

                    ctx.font = '500 ' + Math.max(10, Math.round(fontSize * 0.85)) + 'px monospace';
                    ctx.fillStyle = '#34d399';
                    ctx.fillText(dateStr, 14, y + (barHeight * 0.82));
                },

                async uploadCapturedPhoto(dataUrl) {
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
                                photo_base64: dataUrl,
                                folder: this.directory,
                            })
                        });

                        const result = await response.json();
                        if (result.success) {
                            this.photoValue = result.path;
                            this.itemUrl = result.url;
                            this.hasItem = true;

                            const now = new Date();
                            this.capturedTimestamp = now.toLocaleDateString('id-ID', {
                                day: '2-digit', month: 'short', year: 'numeric'
                            }) + ' ' + now.toLocaleTimeString('id-ID');

                            this.syncLivewire(result.path);
                        } else {
                            alert('Gagal menyimpan foto: ' + (result.message || 'Terjadi kesalahan'));
                            // Jika gagal, nyalakan kembali stream kamera
                            this.startStream();
                        }
                    } catch (e) {
                        console.error('Upload camera snapshot gagal:', e);
                        alert('Gagal mengunggah foto: ' + e.message);
                        this.startStream();
                    } finally {
                        this.isUploading = false;
                    }
                },

                async retakePhoto() {
                    this.photoValue = null;
                    this.itemUrl = null;
                    this.hasItem = false;
                    this.capturedTimestamp = null;
                    this.syncLivewire(null);
                    await this.startStream();
                },

                removeItem() {
                    if (confirm('Apakah Anda yakin ingin menghapus foto bukti ini dan mengambil foto baru?')) {
                        this.photoValue = null;
                        this.itemUrl = null;
                        this.hasItem = false;
                        this.capturedTimestamp = null;
                        this.syncLivewire(null);
                        this.startStream();
                    }
                },

                syncLivewire(value) {
                    this.photoValue = value;
                    const wire = this.wireInstance || (typeof this.$wire !== 'undefined' ? this.$wire : null) || (window.Livewire && this.$el ? window.Livewire.find(this.$el.closest('[wire\\:id]')?.getAttribute('wire:id')) : null);
                    if (wire) {
                        if (typeof wire.set === 'function') {
                            wire.set(this.statePath, value);
                        } else if (typeof wire.$set === 'function') {
                            wire.$set(this.statePath, value);
                        }
                    }
                }
            };
        };
        }
    </script>
</x-filament-forms::field-wrapper>
