<x-filament-forms::field-wrapper :field="$field">
    @php
        $statePath = $getStatePath();
        $initialState = $getState();
        if (is_array($initialState)) {
            $initialState = reset($initialState);
        }
        $initialUrl = null;
        if (is_string($initialState) && ! empty($initialState)) {
            $initialUrl = str_starts_with($initialState, 'http') || str_starts_with($initialState, '/')
                ? $initialState
                : asset('storage/' . ltrim($initialState, '/'));
        }
    @endphp

    <div
        x-data="realtimePhotoCapture({
            statePath: @js($statePath),
            initialValue: @js(is_string($initialState) ? $initialState : null),
            initialUrl: @js($initialUrl),
            uploadUrl: @js(route('admin.journals.upload-realtime-photo')),
            csrfToken: @js(csrf_token()),
        })"
        x-init="init()"
        class="w-full"
    >
        {{-- Hidden input for form sync --}}
        <input type="hidden" name="{{ $statePath }}" :value="photoValue" />

        {{-- Main Container --}}
        <div
            style="border: 1px dashed #d1d5db; background-color: #fafafa; border-radius: 0.75rem; padding: 1.5rem 1rem; text-align: center; position: relative;"
            class="dark:!border-gray-700 dark:!bg-gray-900/40"
        >
            {{-- 1. KONDISI SUDAH ADA FOTO (PREVIEW) --}}
            <div x-show="hasPhoto && !isCameraActive" x-cloak style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem;">
                <div
                    style="max-width: 440px; width: 100%; border-radius: 0.5rem; overflow: hidden; position: relative; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
                    class="dark:!border-gray-700"
                >
                    <template x-if="photoUrl">
                        <img :src="photoUrl" alt="Bukti Foto Realtime" style="width: 100%; height: 240px; object-fit: cover; display: block;" />
                    </template>
                    <div
                        style="position: absolute; top: 0.5rem; left: 0.5rem; display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; background-color: rgba(16, 185, 129, 0.95); color: #ffffff; font-size: 0.6875rem; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.1);"
                    >
                        <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>Foto Realtime Terlampir</span>
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.25rem;">
                    <button
                        type="button"
                        @click="retakePhoto()"
                        style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; border: 1px solid #d1d5db; background-color: #ffffff; color: #374151; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"
                        class="dark:!bg-gray-800 dark:!text-gray-200 dark:!border-gray-700"
                    >
                        <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Ambil Ulang (Kamera)</span>
                    </button>

                    <button
                        type="button"
                        @click="removePhoto()"
                        style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; border: 1px solid #fecaca; background-color: #fef2f2; color: #dc2626; cursor: pointer;"
                        class="dark:!bg-red-950/40 dark:!text-red-400 dark:!border-red-900/50"
                    >
                        <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        <span>Hapus</span>
                    </button>
                </div>
            </div>

            {{-- 2. KONDISI KAMERA AKTIF (LIVE STREAM PC) --}}
            <div x-show="isCameraActive && !hasPhoto" x-cloak style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem;">
                <div
                    style="max-width: 440px; width: 100%; border-radius: 0.5rem; overflow: hidden; position: relative; background-color: #000000; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);"
                >
                    <video
                        x-ref="videoElement"
                        autoplay
                        playsinline
                        muted
                        style="width: 100%; height: 240px; object-fit: cover; display: block; transform: scaleX(-1);"
                    ></video>

                    {{-- Live Indicator --}}
                    <div
                        style="position: absolute; top: 0.5rem; left: 0.5rem; display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; background-color: rgba(220, 38, 38, 0.9); color: #ffffff; font-size: 0.6875rem; font-weight: 600;"
                    >
                        <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #ffffff;"></span>
                        <span>LIVE KAMERA REALTIME</span>
                    </div>
                </div>

                {{-- Action Shutter Button --}}
                <div style="margin-top: 0.25rem;">
                    <button
                        type="button"
                        @click="takeSnapshot()"
                        :disabled="isUploading"
                        style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.5rem 1.25rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 600; background-color: #2563eb; color: #ffffff; border: none; cursor: pointer; box-shadow: 0 2px 4px rgba(37,99,235,0.2);"
                    >
                        <template x-if="!isUploading">
                            <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                                </svg>
                                <span>Ambil Foto Realtime</span>
                            </span>
                        </template>
                        <template x-if="isUploading">
                            <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                <span>Menyimpan Foto...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>

            {{-- 3. KONDISI KAMERA BELUM AKTIF / PERLU IZIN / ERROR --}}
            <div x-show="!isCameraActive && !hasPhoto" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0.5rem 0;">
                
                {{-- Clean Circular Icon --}}
                <div
                    style="width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; background-color: #f3f4f6;"
                    class="dark:!bg-gray-800"
                >
                    <template x-if="streamError">
                        <svg style="width: 24px; height: 24px; color: #d97706;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </template>
                    <template x-if="!streamError">
                        <svg style="width: 24px; height: 24px; color: #6b7280;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                        </svg>
                    </template>
                </div>

                {{-- Heading Text --}}
                <p
                    style="font-size: 0.875rem; font-weight: 600; color: #1f2937; margin: 0 0 0.25rem 0;"
                    class="dark:!text-gray-100"
                    x-text="streamError ? 'Kamera Belum Dapat Diakses' : 'Kamera Realtime (Web Browser)'"
                ></p>

                {{-- Informative Help / Error Banner --}}
                <div style="max-width: 440px; width: 100%; margin: 0 auto 0.875rem auto;">
                    <template x-if="streamError">
                        <div
                            style="display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background-color: #fffbeb; border: 1px solid #fef3c7; color: #92400e; font-size: 0.75rem; text-align: left; line-height: 1.4;"
                            class="dark:!bg-amber-950/40 dark:!border-amber-900/50 dark:!text-amber-300"
                        >
                            <svg style="width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <span x-text="streamError"></span>
                        </div>
                    </template>
                    <template x-if="!streamError">
                        <p
                            style="font-size: 0.75rem; color: #6b7280; line-height: 1.4; margin: 0;"
                            class="dark:!text-gray-400"
                        >
                            Nyalakan kamera web browser PC/laptop Anda untuk mengambil foto bukti kelas secara realtime.
                        </p>
                    </template>
                </div>

                {{-- Action Area --}}
                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.625rem; width: 100%;">
                    {{-- Primary Button to request permission & open camera --}}
                    <button
                        type="button"
                        @click="startCamera()"
                        style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.5rem 1.25rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 500; background-color: #2563eb; color: #ffffff; border: none; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"
                    >
                        <svg style="width: 15px; height: 15px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                        </svg>
                        <span x-text="streamError ? 'Coba Hubungkan Ulang Kamera' : 'Buka Kamera Realtime'"></span>
                    </button>
                </div>
            </div>

            {{-- Hidden Canvas for snapshot --}}
            <canvas x-ref="photoCanvas" style="display: none;"></canvas>
        </div>
    </div>

    <script>
        function realtimePhotoCapture(config) {
            return {
                statePath: config.statePath,
                photoValue: config.initialValue,
                photoUrl: config.initialUrl,
                uploadUrl: config.uploadUrl,
                csrfToken: config.csrfToken,

                hasPhoto: Boolean(config.initialValue),
                isCameraActive: false,
                isUploading: false,
                mediaStream: null,
                streamError: null,

                init() {
                    if (!this.hasPhoto) {
                        this.startCamera();
                    }
                },

                async startCamera() {
                    this.streamError = null;

                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        this.streamError = 'Browser ini tidak mendukung akses kamera langsung.';
                        this.isCameraActive = false;
                        return;
                    }

                    try {
                        this.stopCameraStream();

                        // Mulai dengan constraint yang paling universal dan ramah perangkat PC
                        let stream;
                        try {
                            stream = await navigator.mediaDevices.getUserMedia({
                                video: true,
                                audio: false,
                            });
                        } catch (firstErr) {
                            // Fallback jika video default gagal
                            stream = await navigator.mediaDevices.getUserMedia({
                                video: { width: { ideal: 1280 }, height: { ideal: 720 } },
                                audio: false,
                            });
                        }

                        this.mediaStream = stream;
                        this.isCameraActive = true;
                        this.streamError = null;

                        this.$nextTick(() => {
                            if (this.$refs.videoElement) {
                                this.$refs.videoElement.srcObject = stream;
                                this.$refs.videoElement.play().catch(e => console.warn('Video play error:', e));
                            }
                        });
                    } catch (err) {
                        console.warn('Gagal akses kamera:', err);
                        this.isCameraActive = false;
                        this.stopCameraStream();

                        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                            this.streamError = 'Izin kamera ditolak. Klik ikon gembok/kamera di sebelah URL browser, pilih "Izinkan", lalu coba lagi.';
                        } else if (err.name === 'NotReadableError' || (err.message && err.message.toLowerCase().includes('could not start video source'))) {
                            this.streamError = 'Kamera tidak dapat dimulai karena sedang digunakan aplikasi lain (Zoom/Teams/Camera) atau driver sibuk. Tutup aplikasi tersebut lalu coba lagi.';
                        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                            this.streamError = 'Perangkat kamera tidak terdeteksi pada PC / laptop ini. Pastikan kamera terhubung.';
                        } else {
                            this.streamError = 'Kendala akses kamera (' + (err.message || err.name) + '). Silakan periksa koneksi kamera lalu coba kembali.';
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
                    if (this.$refs.videoElement) {
                        this.$refs.videoElement.srcObject = null;
                    }
                },

                takeSnapshot() {
                    if (!this.mediaStream || !this.$refs.videoElement) return;

                    const video = this.$refs.videoElement;
                    const canvas = this.$refs.photoCanvas;
                    if (!video.videoWidth || !video.videoHeight) {
                        alert('Kamera sedang memuat, silakan tunggu sebentar.');
                        return;
                    }

                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    const ctx = canvas.getContext('2d');

                    // Flip horizontal mirror
                    ctx.save();
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    ctx.restore();

                    // Watermark realtime
                    this.applyWatermark(ctx, canvas.width, canvas.height);

                    const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
                    this.photoUrl = dataUrl;
                    this.hasPhoto = true;
                    this.isCameraActive = false;
                    this.stopCameraStream();

                    this.uploadBase64(dataUrl);
                },

                applyWatermark(ctx, width, height) {
                    const barHeight = Math.max(34, Math.round(height * 0.08));
                    const y = height - barHeight;

                    // Bar transparan gelap
                    ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
                    ctx.fillRect(0, y, width, barHeight);

                    // Garis penanda
                    ctx.fillStyle = '#10b981';
                    ctx.fillRect(0, y, width, 3);

                    // Teks Watermark
                    const now = new Date();
                    const dateStr = now.toLocaleDateString('id-ID', {
                        day: '2-digit', month: 'short', year: 'numeric'
                    }) + ' ' + now.toLocaleTimeString('id-ID');

                    const fontSize = Math.max(12, Math.round(barHeight * 0.32));
                    ctx.font = 'bold ' + fontSize + 'px sans-serif';
                    ctx.fillStyle = '#ffffff';
                    ctx.fillText('🏫 SCP SMK - BUKTI REALTIME', 14, y + (barHeight * 0.45));

                    ctx.font = '500 ' + Math.max(10, Math.round(fontSize * 0.85)) + 'px monospace';
                    ctx.fillStyle = '#34d399';
                    ctx.fillText('📅 ' + dateStr, 14, y + (barHeight * 0.82));
                },

                async uploadBase64(base64Image) {
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
                                photo_base64: base64Image
                            })
                        });

                        const result = await response.json();
                        if (result.success) {
                            this.photoValue = result.path;
                            this.photoUrl = result.url;
                            this.syncLivewire(result.path);
                        } else {
                            alert('Gagal menyimpan foto: ' + (result.message || 'Terjadi kesalahan.'));
                        }
                    } catch (e) {
                        console.error('Upload foto gagal:', e);
                        alert('Gagal mengunggah foto: ' + e.message);
                    } finally {
                        this.isUploading = false;
                    }
                },

                retakePhoto() {
                    this.photoValue = null;
                    this.photoUrl = null;
                    this.hasPhoto = false;
                    this.syncLivewire(null);
                    this.startCamera();
                },

                removePhoto() {
                    this.photoValue = null;
                    this.photoUrl = null;
                    this.hasPhoto = false;
                    this.isCameraActive = false;
                    this.stopCameraStream();
                    this.syncLivewire(null);
                },

                syncLivewire(value) {
                    if (this.$wire) {
                        this.$wire.set(this.statePath, value);
                    }
                }
            };
        }
    </script>
</x-filament-forms::field-wrapper>
