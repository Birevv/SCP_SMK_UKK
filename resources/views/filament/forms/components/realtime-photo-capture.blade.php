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

    <style>
        .rtc-box {
            width: 100%;
            max-width: 580px;
            margin: 0 auto;
            border-radius: 0.75rem;
            border: 1px dashed #cbd5e1;
            background-color: #f8fafc;
            padding: 1.25rem 1rem;
            text-align: center;
            box-sizing: border-box;
            transition: background-color 0.2s, border-color 0.2s;
        }
        html.dark .rtc-box, .dark .rtc-box {
            border-color: #334155;
            background-color: #0f172a;
        }

        .rtc-screen {
            width: 100%;
            max-width: 540px;
            aspect-ratio: 16 / 9;
            max-height: 315px;
            margin: 0 auto;
            border-radius: 0.5rem;
            overflow: hidden;
            position: relative;
            background-color: #000000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .rtc-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transform: scaleX(-1);
        }

        .rtc-preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .rtc-badge-live {
            position: absolute;
            top: 8px;
            left: 8px;
            z-index: 10;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 9999px;
            background-color: rgba(220, 38, 38, 0.9);
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .rtc-badge-success {
            position: absolute;
            top: 8px;
            left: 8px;
            z-index: 10;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 9999px;
            background-color: rgba(16, 185, 129, 0.95);
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .rtc-pulse-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #ffffff;
            animation: rtcPulse 1.2s infinite;
        }

        @keyframes rtcPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        .rtc-actions {
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .rtc-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            line-height: 1.4;
            box-sizing: border-box;
        }

        .rtc-btn-shutter {
            background-color: #2563eb;
            color: #ffffff;
            border: 1px solid #1d4ed8;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.25);
        }
        .rtc-btn-shutter:hover:not(:disabled) {
            background-color: #1d4ed8;
        }
        .rtc-btn-shutter:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .rtc-btn-retake {
            background-color: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .rtc-btn-retake:hover {
            background-color: #f1f5f9;
        }
        html.dark .rtc-btn-retake, .dark .rtc-btn-retake {
            background-color: #1e293b;
            color: #f1f5f9;
            border-color: #475569;
        }
        html.dark .rtc-btn-retake:hover, .dark .rtc-btn-retake:hover {
            background-color: #334155;
        }

        .rtc-btn-remove {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .rtc-btn-remove:hover {
            background-color: #fee2e2;
        }
        html.dark .rtc-btn-remove, .dark .rtc-btn-remove {
            background-color: rgba(127, 29, 29, 0.3);
            color: #fca5a5;
            border-color: rgba(185, 28, 28, 0.5);
        }
        html.dark .rtc-btn-remove:hover, .dark .rtc-btn-remove:hover {
            background-color: rgba(127, 29, 29, 0.5);
        }

        .rtc-empty-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px auto;
            background-color: #e2e8f0;
            color: #64748b;
        }
        html.dark .rtc-empty-icon, .dark .rtc-empty-icon {
            background-color: #1e293b;
            color: #94a3b8;
        }

        .rtc-title {
            font-size: 13px;
            font-weight: 600;
            margin: 0 0 4px 0;
            color: #1e293b;
        }
        html.dark .rtc-title, .dark .rtc-title {
            color: #f8fafc;
        }

        .rtc-desc {
            font-size: 11.5px;
            color: #64748b;
            margin: 0 auto 10px auto;
            max-width: 320px;
            line-height: 1.4;
        }
        html.dark .rtc-desc, .dark .rtc-desc {
            color: #94a3b8;
        }

        .rtc-alert {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11px;
            text-align: left;
            margin: 0 auto 10px auto;
            max-width: 360px;
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }
        html.dark .rtc-alert, .dark .rtc-alert {
            background-color: rgba(120, 53, 15, 0.25);
            border-color: rgba(180, 83, 9, 0.4);
            color: #fcd34d;
        }
    </style>

    <div
        x-data="realtimePhotoCapture({
            statePath: @js($statePath),
            initialValue: @js(is_string($initialState) ? $initialState : null),
            initialUrl: @js($initialUrl),
            uploadUrl: @js(route('admin.journals.upload-realtime-photo')),
            csrfToken: @js(csrf_token()),
        })"
        x-init="init()"
        style="width: 100%; display: flex; justify-content: center;"
    >
        {{-- Hidden input for form sync --}}
        <input type="hidden" name="{{ $statePath }}" :value="photoValue" />

        {{-- Main Container --}}
        <div class="rtc-box">
            {{-- 1. KONDISI SUDAH ADA FOTO (PREVIEW) --}}
            <div x-show="hasPhoto && !isCameraActive" x-cloak>
                <div class="rtc-screen">
                    <template x-if="photoUrl">
                        <img :src="photoUrl" alt="Bukti Foto Realtime" class="rtc-preview-img" />
                    </template>
                    <div class="rtc-badge-success">
                        <svg style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>Foto Realtime Terlampir</span>
                    </div>
                </div>

                <div class="rtc-actions">
                    <button type="button" @click="retakePhoto()" class="rtc-btn rtc-btn-retake">
                        <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Ambil Ulang (Kamera)</span>
                    </button>

                    <button type="button" @click="removePhoto()" class="rtc-btn rtc-btn-remove">
                        <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        <span>Hapus</span>
                    </button>
                </div>
            </div>

            {{-- 2. KONDISI KAMERA AKTIF (LIVE STREAM PC) --}}
            <div x-show="isCameraActive && !hasPhoto" x-cloak>
                <div class="rtc-screen">
                    <video
                        x-ref="videoElement"
                        autoplay
                        playsinline
                        muted
                        class="rtc-video"
                    ></video>

                    {{-- Live Indicator --}}
                    <div class="rtc-badge-live">
                        <span class="rtc-pulse-dot"></span>
                        <span>LIVE KAMERA REALTIME</span>
                    </div>
                </div>

                {{-- Action Shutter Button --}}
                <div class="rtc-actions">
                    <button
                        type="button"
                        @click="takeSnapshot()"
                        :disabled="isUploading"
                        class="rtc-btn rtc-btn-shutter"
                    >
                        <template x-if="!isUploading">
                            <span style="display: inline-flex; align-items: center; gap: 6px;">
                                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                                </svg>
                                <span>Ambil Foto Realtime</span>
                            </span>
                        </template>
                        <template x-if="isUploading">
                            <span style="display: inline-flex; align-items: center; gap: 6px;">
                                <span>Menyimpan Foto...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>

            {{-- 3. KONDISI KAMERA BELUM AKTIF / PERLU IZIN / ERROR --}}
            <div x-show="!isCameraActive && !hasPhoto">
                {{-- Clean Circular Icon --}}
                <div class="rtc-empty-icon">
                    <template x-if="streamError">
                        <svg style="width: 22px; height: 22px; color: #d97706;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </template>
                    <template x-if="!streamError">
                        <svg style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                        </svg>
                    </template>
                </div>

                {{-- Heading Text --}}
                <p class="rtc-title" x-text="streamError ? 'Kamera Belum Dapat Diakses' : 'Kamera Realtime (Web Browser)'"></p>

                {{-- Informative Help / Error Banner --}}
                <div>
                    <template x-if="streamError">
                        <div class="rtc-alert">
                            <svg style="width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <span x-text="streamError"></span>
                        </div>
                    </template>
                    <template x-if="!streamError">
                        <p class="rtc-desc">
                            Nyalakan kamera web browser PC/laptop Anda untuk mengambil foto bukti kelas secara realtime.
                        </p>
                    </template>
                </div>

                {{-- Action Area --}}
                <div class="rtc-actions">
                    <button type="button" @click="startCamera()" class="rtc-btn rtc-btn-shutter">
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
