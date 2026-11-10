<x-filament-widgets::widget>
    @if (isset($stats) && $stats !== null)
        <div class="space-y-6">

            {{-- 1. HEADER HERO BANNER --}}
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 bg-white/15 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-xs mb-2">
                        <span class="inline-flex items-center gap-1">
                            <x-filament::icon icon="heroicon-m-academic-cap" class="h-3.5 w-3.5 inline" />
                            <span>Kelas {{ $stats['classroom_name'] }}</span>
                        </span>
                        <span>•</span>
                        <span>{{ $stats['active_cycle'] }}</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight">
                        Halo, {{ $stats['student_name'] }}!
                    </h2>
                    <p class="text-xs sm:text-sm text-primary-100 mt-1 flex items-center gap-1.5">
                        <x-filament::icon icon="heroicon-m-calendar" class="h-4 w-4 text-primary-200" />
                        <span>{{ $stats['day_display'] }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    @if (!empty($stats['create_leave_url']))
                        <a
                            href="{{ $stats['create_leave_url'] }}"
                            class="inline-flex items-center gap-2 bg-white text-primary-700 hover:bg-primary-50 font-semibold text-xs sm:text-sm px-4 py-2.5 rounded-xl shadow-xs transition-all active:scale-95"
                        >
                            <x-filament::icon icon="heroicon-m-camera" class="h-4 w-4 text-primary-600" />
                            <span>Ajukan Surat Izin / Sakit</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- 2. METRICS & ATTENDANCE SUMMARY --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Percentage Card --}}
                <x-filament::section class="md:col-span-1">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Persentase Kehadiran
                        </span>
                        <x-filament::badge :color="$stats['percentage'] >= 75 ? 'success' : 'danger'" size="sm">
                            {{ $stats['percentage'] >= 75 ? 'Aman (≥75%)' : 'Di Bawah Target' }}
                        </x-filament::badge>
                    </div>

                    <div class="flex items-baseline gap-2 mb-3">
                        <span class="text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                            {{ $stats['percentage'] }}%
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            dari total {{ $stats['total'] }} sesi
                        </span>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2.5 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-500 {{ $stats['percentage'] >= 75 ? 'bg-emerald-500' : 'bg-rose-500' }}"
                            style="width: {{ min(100, max(0, $stats['percentage'])) }}%;"
                        ></div>
                    </div>
                    <div class="flex justify-between items-center text-[11px] text-gray-400 dark:text-gray-500 mt-1.5">
                        <span>0%</span>
                        <span>Syarat Minimal: 75%</span>
                        <span>100%</span>
                    </div>
                </x-filament::section>

                {{-- Counter Badges --}}
                <x-filament::section class="md:col-span-2">
                    <div class="flex items-center justify-between mb-3 border-b border-gray-100 dark:border-gray-800 pb-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Rincian Kehadiran Semester Ini
                        </span>
                        <span class="text-xs text-gray-400">Total Sesi: {{ $stats['total'] }}</span>
                    </div>

                    <div class="scp-stat-mini-grid">
                        <div class="scp-stat-mini-card scp-stat-mini-emerald">
                            <span class="scp-stat-mini-title scp-text-emerald">Hadir</span>
                            <div class="scp-stat-mini-val scp-text-emerald">{{ $stats['hadir'] }}</div>
                        </div>

                        <div class="scp-stat-mini-card scp-stat-mini-amber">
                            <span class="scp-stat-mini-title scp-text-amber">Sakit</span>
                            <div class="scp-stat-mini-val scp-text-amber">{{ $stats['sakit'] }}</div>
                        </div>

                        <div class="scp-stat-mini-card scp-stat-mini-blue">
                            <span class="scp-stat-mini-title scp-text-blue">Izin</span>
                            <div class="scp-stat-mini-val scp-text-blue">{{ $stats['izin'] }}</div>
                        </div>

                        <div class="scp-stat-mini-card scp-stat-mini-rose">
                            <span class="scp-stat-mini-title scp-text-rose">Alpa</span>
                            <div class="scp-stat-mini-val scp-text-rose">{{ $stats['alpa'] }}</div>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- 3. JADWAL PELAJARAN HARI INI --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-book-open" class="h-5 w-5 text-primary-500" />
                        <span>Jadwal Pelajaran Hari Ini</span>
                    </div>
                </x-slot>

                @if ($stats['today_schedules']->isEmpty())
                    <div class="text-center py-6 border border-dashed border-gray-200 dark:border-gray-800 rounded-xl">
                        <x-filament::icon icon="heroicon-o-calendar" class="mx-auto h-8 w-8 text-gray-400 mb-1" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Tidak ada kegiatan belajar mengajar pada hari ini.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($stats['today_schedules'] as $item)
                            <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-3.5 bg-white dark:bg-gray-900/60 shadow-2xs hover:border-primary-400 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-mono text-xs font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/60 px-2 py-0.5 rounded-md">
                                        {{ substr($item->start_time, 0, 5) }} - {{ substr($item->end_time, 0, 5) }}
                                    </span>
                                    <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-md">
                                        {{ $item->room?->name ?? 'Ruang KBM' }}
                                    </span>
                                </div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white line-clamp-1 mb-1">
                                    {{ $item->subject?->name }}
                                </h4>
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-o-user" class="h-3.5 w-3.5 text-gray-400" />
                                    <span class="truncate">{{ $item->teacher?->full_name }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>

            {{-- 4. RIWAYAT PENGAJUAN PERIZINAN TERBARU --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-envelope" class="h-5 w-5 text-amber-500" />
                            <span>Riwayat Pengajuan Surat Izin / Sakit</span>
                        </div>
                        <a
                            href="{{ \App\Filament\Resources\LeaveRequests\LeaveRequestResource::getUrl('index') }}"
                            class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium inline-flex items-center gap-1"
                        >
                            <span>Semua Pengajuan</span>
                            <x-filament::icon icon="heroicon-m-arrow-right" class="h-3 w-3" />
                        </a>
                    </div>
                </x-slot>

                @if ($stats['recent_leaves']->isEmpty())
                    <div class="text-center py-6 border border-dashed border-gray-200 dark:border-gray-800 rounded-xl">
                        <x-filament::icon icon="heroicon-o-document-check" class="mx-auto h-8 w-8 text-gray-400 mb-1" />
                        <p class="text-xs text-gray-500 dark:text-gray-400">Belum ada pengajuan izin atau surat sakit yang dibuat.</p>
                    </div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-semibold">
                                <tr>
                                    <th class="py-2.5 px-3">Tanggal Periode</th>
                                    <th class="py-2.5 px-3">Jenis Alasan</th>
                                    <th class="py-2.5 px-3">Keterangan</th>
                                    <th class="py-2.5 px-3 text-center">Status Verifikasi</th>
                                    <th class="py-2.5 px-3 text-center">Lampiran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                @foreach ($stats['recent_leaves'] as $leave)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors">
                                        <td class="py-2 px-3 whitespace-nowrap font-medium text-gray-800 dark:text-gray-200">
                                            {{ \Carbon\Carbon::parse($leave->start_date)->isoFormat('DD MMM') }}
                                            @if ($leave->end_date && $leave->end_date !== $leave->start_date)
                                                - {{ \Carbon\Carbon::parse($leave->end_date)->isoFormat('DD MMM YYYY') }}
                                            @else
                                                {{ \Carbon\Carbon::parse($leave->start_date)->isoFormat('YYYY') }}
                                            @endif
                                        </td>
                                        <td class="py-2 px-3">
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
                                        <td class="py-2 px-3 text-gray-600 dark:text-gray-300 max-w-xs truncate">
                                            {{ $leave->description }}
                                            @if ($leave->status === 'Rejected' && $leave->rejection_note)
                                                <div class="text-[11px] text-rose-600 dark:text-rose-400 mt-0.5 italic">
                                                    Alasan ditolak: {{ $leave->rejection_note }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="py-2 px-3 text-center whitespace-nowrap">
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
                                        <td class="py-2 px-3 text-center whitespace-nowrap">
                                            @if ($leave->attachment_photo)
                                                <a
                                                    href="{{ asset('storage/' . ltrim($leave->attachment_photo, '/')) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:underline"
                                                >
                                                    <x-filament::icon icon="heroicon-m-paper-clip" class="h-3.5 w-3.5" />
                                                    <span>Lihat Berkas</span>
                                                </a>
                                            @else
                                                <span class="text-gray-400 text-[11px]">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>

            {{-- 5. RIWAYAT PRESENSI KELAS --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-5 w-5 text-emerald-500" />
                        <span>Riwayat Presensi Pelajaran Terbaru</span>
                    </div>
                </x-slot>

                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-semibold">
                            <tr>
                                <th class="py-2.5 px-3">Tanggal</th>
                                <th class="py-2.5 px-3">Hari</th>
                                <th class="py-2.5 px-3">Mata Pelajaran</th>
                                <th class="py-2.5 px-3">Status Kehadiran</th>
                                <th class="py-2.5 px-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @if (isset($stats['history']) && count($stats['history']) > 0)
                                @foreach ($stats['history'] as $item)
                                    @php
                                        $itemDate = $item->date ?? $item->journal?->date ?? $item->created_at;
                                        $carbonDate = $itemDate ? \Carbon\Carbon::parse($itemDate) : null;
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors">
                                        <td class="py-2 px-3 whitespace-nowrap font-medium text-gray-800 dark:text-gray-200">
                                            {{ $carbonDate ? $carbonDate->isoFormat('DD MMM YYYY') : '-' }}
                                        </td>
                                        <td class="py-2 px-3 text-gray-500 dark:text-gray-400">
                                            {{ $carbonDate ? $carbonDate->locale('id')->isoFormat('dddd') : '-' }}
                                        </td>
                                        <td class="py-2 px-3 font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $item->journal?->schedule?->subject?->name ?? $item->journal?->schedule?->subject_name ?? '-' }}
                                        </td>
                                        <td class="py-2 px-3">
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
                                        <td class="py-2 px-3 text-gray-500 dark:text-gray-400 text-[11px]">
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
            <div class="text-center py-6 text-gray-500 dark:text-gray-400 text-sm">
                Data ringkasan kehadiran siswa belum tersedia.
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
