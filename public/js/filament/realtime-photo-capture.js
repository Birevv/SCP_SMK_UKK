/**
 * Realtime Photo Capture Component for Filament Forms
 * Supports inline video streaming, snapshot capture, watermarking, retake, and preview.
 */
function scpInlineCameraComponent(cfg) {
    return {
        statePath: cfg.statePath,
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
            if (this.$wire) {
                this.$wire.set(this.statePath, value);
            }
        }
    };
}

window.scpInlineCameraComponent = scpInlineCameraComponent;

