<x-filament-widgets::widget>
    @if (isset($stats) && $stats !== null)
        <div class="space-y-6">

            {{-- 1. HEADER HERO CARD --}}
            <div class="scp-student-hero">
                <div class="scp-student-hero-content">
                    <div class="scp-student-meta-row">
                        <span class="scp-student-meta-pill">
                            <x-filament::icon icon="heroicon-m-academic-cap" class="h-3.5 w-3.5 shrink-0" />
                            <span>Kelas {{ $stats['classroom_name'] }}</span>
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-600" aria-hidden="true">•</span>
                        <span class="scp-student-cycle-text">{{ $stats['active_cycle'] }}</span>
                    </div>
                    <h2 class="scp-student-hero-title">
                        Selamat Datang, {{ $stats['student_name'] }}
                    </h2>
                    <p class="scp-student-hero-date">
                        <x-filament::icon icon="heroicon-m-calendar" class="h-4 w-4 shrink-0" />
                        <span>{{ $stats['day_display'] }}</span>
                    </p>
                </div>

                @if (!empty($stats['create_leave_url']))
                    <div>
                        <a
                            href="{{ $stats['create_leave_url'] }}"
                            class="scp-student-hero-btn"
                        >
                            <x-filament::icon icon="heroicon-m-plus" class="h-4 w-4 shrink-0" />
                            <span>Ajukan Surat Izin / Sakit</span>
                        </a>
                    </div>
                @endif
            </div>

            {{-- 2. RINGKASAN KEHADIRAN SEMESTER INI --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex flex-wrap items-center justify-between gap-2 w-full">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5 text-primary-500" />
                            <span class="text-base font-bold text-gray-900 dark:text-white">Ringkasan Kehadiran Semester Ini</span>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Total Sesi Pelajaran: {{ $stats['total'] }}
                        </span>
                    </div>
                </x-slot>

                <div class="space-y-4">
                    {{-- Metrik Utama & Badge Status --}}
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <div class="flex items-baseline gap-2.5">
                            <span class="text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                                {{ $stats['percentage'] }}%
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                tingkat kehadiran saat ini
                            </span>
                        </div>
                        <div>
                            <x-filament::badge :color="$stats['percentage'] >= 75 ? 'success' : 'danger'" size="md">
                                <div class="flex items-center gap-1.5">
                                    <x-filament::icon icon="{{ $stats['percentage'] >= 75 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle' }}" class="h-4 w-4" />
                                    <span>{{ $stats['percentage'] >= 75 ? 'Memenuhi Syarat (≥ 75%)' : 'Di Bawah Batas Minimal (< 75%)' }}</span>
                                </div>
                            </x-filament::badge>
                        </div>
                    </div>

                    {{-- Progress Bar (CSS Solid, Reliable & Accessible) --}}
                    <div class="scp-student-progress-wrapper">
                        <div
                            class="scp-student-progress-track"
                            role="progressbar"
                            aria-valuenow="{{ $stats['percentage'] }}"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            {{-- Penanda Target Minimal 75% --}}
                            <div
                                class="scp-student-progress-marker"
                                role="img"
                                aria-label="Target minimal kehadiran 75%"
                                title="Target Minimal: 75%"
                            ></div>

                            {{-- Bar Pengisi Kehadiran --}}
                            <div
                                class="scp-student-progress-bar {{ $stats['percentage'] >= 75 ? 'scp-student-progress-success' : 'scp-student-progress-danger' }}"
                                style="width: {{ min(100, max(0, $stats['percentage'])) }}%;"
                            ></div>
                        </div>

                        <div class="scp-student-progress-labels">
                            <span>0%</span>
                            <span class="font-semibold" style="color: {{ $stats['percentage'] >= 75 ? '#059669' : '#e11d48' }};">
                                Syarat Minimal: 75%
                            </span>
                            <span>100%</span>
                        </div>
                    </div>

                    {{-- Rincian 4 Status Kehadiran --}}
                    <div class="scp-student-stats-grid">
                        <div class="scp-student-stat-card scp-student-stat-hadir">
                            <span class="scp-student-stat-label">Hadir</span>
                            <div class="scp-student-stat-val-row">
                                <span class="scp-student-stat-val">{{ $stats['hadir'] }}</span>
                                <span class="scp-student-stat-unit">sesi</span>
                            </div>
                        </div>

                        <div class="scp-student-stat-card scp-student-stat-sakit">
                            <span class="scp-student-stat-label">Sakit</span>
                            <div class="scp-student-stat-val-row">
                                <span class="scp-student-stat-val">{{ $stats['sakit'] }}</span>
                                <span class="scp-student-stat-unit">sesi</span>
                            </div>
                        </div>

                        <div class="scp-student-stat-card scp-student-stat-izin">
                            <span class="scp-student-stat-label">Izin</span>
                            <div class="scp-student-stat-val-row">
                                <span class="scp-student-stat-val">{{ $stats['izin'] }}</span>
                                <span class="scp-student-stat-unit">sesi</span>
                            </div>
                        </div>

                        <div class="scp-student-stat-card scp-student-stat-alpa">
                            <span class="scp-student-stat-label">Alpa</span>
                            <div class="scp-student-stat-val-row">
                                <span class="scp-student-stat-val">{{ $stats['alpa'] }}</span>
                                <span class="scp-student-stat-unit">sesi</span>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            {{-- 3. JADWAL PELAJARAN HARI INI --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-book-open" class="h-5 w-5 text-primary-500" />
                        <span class="text-base font-bold text-gray-900 dark:text-white">Jadwal Pelajaran Hari Ini</span>
                    </div>
                </x-slot>

                @if ($stats['today_schedules']->isEmpty())
                    <div class="text-center py-8 px-4 rounded-lg border border-dashed border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/40">
                        <x-filament::icon icon="heroicon-o-calendar" class="mx-auto h-8 w-8 text-gray-400 dark:text-gray-500 mb-2" />
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tidak ada kegiatan belajar mengajar terjadwal pada hari ini.</p>
                    </div>
                @else
                    <div class="scp-student-schedule-grid">
                        @foreach ($stats['today_schedules'] as $item)
                            <div class="scp-student-schedule-card">
                                <div>
                                    <div class="scp-student-schedule-top">
                                        <span class="scp-student-time-pill">
                                            {{ substr($item->start_time, 0, 5) }} - {{ substr($item->end_time, 0, 5) }}
                                        </span>
                                        <span class="scp-student-room-pill">
                                            {{ $item->room?->name ?? 'Ruang KBM' }}
                                        </span>
                                    </div>
                                    <h4 class="scp-student-subject-name" title="{{ $item->subject?->name }}">
                                        {{ $item->subject?->name }}
                                    </h4>
                                </div>
                                <div class="scp-student-teacher-row">
                                    <x-filament::icon icon="heroicon-o-user" class="h-3.5 w-3.5 shrink-0" />
                                    <span class="truncate">{{ $item->teacher?->full_name }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>

            {{-- 4. RIWAYAT PENGAJUAN SURAT IZIN / SAKIT --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-envelope" class="h-5 w-5 text-amber-500" />
                            <span class="text-base font-bold text-gray-900 dark:text-white">Riwayat Pengajuan Surat Izin / Sakit</span>
                        </div>
                        <a
                            href="{{ \App\Filament\Resources\LeaveRequests\LeaveRequestResource::getUrl('index') }}"
                            class="scp-student-link"
                        >
                            <span>Semua Pengajuan</span>
                            <x-filament::icon icon="heroicon-m-arrow-right" class="h-3.5 w-3.5" />
                        </a>
                    </div>
                </x-slot>

                @if ($stats['recent_leaves']->isEmpty())
                    <div class="text-center py-8 px-4 rounded-lg border border-dashed border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/40">
                        <x-filament::icon icon="heroicon-o-document-check" class="mx-auto h-8 w-8 text-gray-400 dark:text-gray-500 mb-2" />
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Belum ada pengajuan surat izin atau sakit yang dibuat.</p>
                    </div>
                @else
                    <div class="scp-student-table-box">
                        <table class="scp-student-table">
                            <thead>
                                <tr>
                                    <th>Tanggal Periode</th>
                                    <th>Jenis Alasan</th>
                                    <th>Keterangan</th>
                                    <th class="text-center">Status Verifikasi</th>
                                    <th class="text-center">Lampiran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stats['recent_leaves'] as $leave)
                                    <tr>
                                        <td class="font-medium whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($leave->start_date)->isoFormat('DD MMM') }}
                                            @if ($leave->end_date && $leave->end_date !== $leave->start_date)
                                                - {{ \Carbon\Carbon::parse($leave->end_date)->isoFormat('DD MMM YYYY') }}
                                            @else
                                                {{ \Carbon\Carbon::parse($leave->start_date)->isoFormat('YYYY') }}
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $reasonLabel = match (strtolower(str_replace(' ', '_', (string) $leave->reason_type))) {
                                                    'sakit' => 'Sakit',
                                                    'izin' => 'Izin',
                                                    'pulang_cepat' => 'Pulang Cepat',
                                                    'kegiatan_luar' => 'Kegiatan Luar',
                                                    default => (string) $leave->reason_type,
                                                };
                                                $reasonColor = match (strtolower(str_replace(' ', '_', (string) $leave->reason_type))) {
                                                    'sakit' => 'warning',
                                                    'izin' => 'info',
                                                    'pulang_cepat' => 'danger',
                                                    default => 'primary',
                                                };
                                            @endphp
                                            <x-filament::badge :color="$reasonColor" size="sm">
                                                {{ $reasonLabel }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="max-w-xs truncate">
                                            <div>{{ $leave->description }}</div>
                                            @if ($leave->status === 'Rejected' && $leave->rejection_note)
                                                <div class="text-xs text-rose-600 dark:text-rose-400 mt-1 italic">
                                                    Alasan ditolak: {{ $leave->rejection_note }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center whitespace-nowrap">
                                            @php
                                                $statusColor = match ($leave->status) {
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    default => 'warning',
                                                };
                                                $statusLabel = match ($leave->status) {
                                                    'Approved' => 'Disetujui',
                                                    'Rejected' => 'Ditolak',
                                                    default => 'Menunggu',
                                                };
                                            @endphp
                                            <x-filament::badge :color="$statusColor" size="sm">
                                                {{ $statusLabel }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="text-center whitespace-nowrap">
                                            @if ($leave->attachment_photo)
                                                <a
                                                    href="{{ asset('storage/' . ltrim($leave->attachment_photo, '/')) }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    aria-label="Buka lampiran berkas surat izin (membuka tab baru)"
                                                    class="scp-student-link"
                                                >
                                                    <x-filament::icon icon="heroicon-m-paper-clip" class="h-3.5 w-3.5" />
                                                    <span>Lihat Berkas</span>
                                                </a>
                                            @else
                                                <span class="text-gray-400 text-xs">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>

            {{-- 5. RIWAYAT PRESENSI PELAJARAN TERBARU --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-5 w-5 text-emerald-500" />
                        <span class="text-base font-bold text-gray-900 dark:text-white">Riwayat Presensi Pelajaran Terbaru</span>
                    </div>
                </x-slot>

                <div class="scp-student-table-box">
                    <table class="scp-student-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Hari</th>
                                <th>Mata Pelajaran</th>
                                <th>Status Kehadiran</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (isset($stats['history']) && count($stats['history']) > 0)
                                @foreach ($stats['history'] as $item)
                                    @php
                                        $itemDate = $item->date ?? $item->journal?->date ?? $item->created_at;
                                        $carbonDate = $itemDate ? \Carbon\Carbon::parse($itemDate) : null;
                                    @endphp
                                    <tr>
                                        <td class="font-medium whitespace-nowrap">
                                            {{ $carbonDate ? $carbonDate->isoFormat('DD MMM YYYY') : '-' }}
                                        </td>
                                        <td class="text-gray-600 dark:text-gray-400">
                                            {{ $carbonDate ? $carbonDate->locale('id')->isoFormat('dddd') : '-' }}
                                        </td>
                                        <td class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $item->journal?->schedule?->subject?->name ?? $item->journal?->schedule?->subject_name ?? '-' }}
                                        </td>
                                        <td>
                                            @php
                                                $attColor = match ($item->status) {
                                                    'Hadir' => 'success',
                                                    'Sakit' => 'warning',
                                                    'Izin' => 'info',
                                                    default => 'danger',
                                                };
                                            @endphp
                                            <x-filament::badge :color="$attColor" size="sm">
                                                {{ $item->status }}
                                            </x-filament::badge>
                                        </td>
                                        <td class="text-gray-600 dark:text-gray-400">
                                            {{ $item->notes ?? ($item->status === 'Hadir' ? 'Tepat waktu' : '-') }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-400 dark:text-gray-500">
                                        Belum ada riwayat catatan presensi.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @else
        <x-filament::section>
            <div class="text-center py-10 px-4">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 dark:bg-blue-950/60 text-primary-600 dark:text-primary-400 mb-3">
                    <x-filament::icon icon="heroicon-o-information-circle" class="h-6 w-6" />
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Data Kehadiran Belum Tersedia</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Data ringkasan kehadiran belum dapat ditampilkan untuk akun ini. Pastikan Anda telah terdaftar aktif dalam rombongan belajar (kelas) semester ini, atau hubungi Wali Kelas / Guru BK Anda.
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
