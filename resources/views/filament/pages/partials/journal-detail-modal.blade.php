<div class="space-y-5 py-2">
    {{-- Header Info Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
        <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-900/60">
            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">Mata Pelajaran</span>
            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">
                {{ $journal->schedule?->subject?->name ?? '-' }}
            </p>
        </div>

        <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-900/60">
            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">Kelas & Ruang</span>
            <div class="flex items-center gap-1.5 mt-0.5">
                <span class="font-bold text-sm text-primary-600 dark:text-primary-400">
                    {{ $journal->schedule?->classroom?->name ?? '-' }}
                </span>
                @if ($journal->schedule?->room?->name)
                    <span class="text-xs text-gray-400 dark:text-gray-500">•</span>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                        {{ $journal->schedule->room->name }}
                    </span>
                @endif
            </div>
        </div>

        <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-900/60">
            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">Tanggal & Sesi</span>
            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">
                {{ $journal->date ? $journal->date->translatedFormat('d M Y') : '-' }}
            </p>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                @if ($journal->schedule?->start_session && $journal->schedule?->end_session)
                    Jam ke {{ $journal->schedule->start_session }}-{{ $journal->schedule->end_session }}
                @endif
                ({{ substr((string) ($journal->schedule?->start_time ?? ''), 0, 5) }} - {{ substr((string) ($journal->schedule?->end_time ?? ''), 0, 5) }})
            </span>
        </div>

        <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-900/60">
            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider">Guru Pengajar</span>
            <p class="text-sm font-bold text-gray-900 dark:text-white mt-0.5 line-clamp-1">
                {{ $journal->teacher?->full_name ?? '-' }}
            </p>
            <span class="text-[11px] text-gray-400 dark:text-gray-500">
                Waktu Isi: {{ $journal->created_at ? $journal->created_at->translatedFormat('d M Y H:i') : '-' }}
            </span>
        </div>
    </div>

    {{-- Materi Pembelajaran & Catatan --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <div class="flex items-center gap-2 mb-2">
                <x-filament::icon icon="heroicon-o-book-open" class="h-4 w-4 text-primary-500" />
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                    Materi & Ringkasan Pembelajaran
                </h4>
            </div>
            <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line leading-relaxed">
                {{ $journal->teaching_material ?: 'Tidak ada materi yang dicantumkan.' }}
            </p>
        </div>

        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <div class="flex items-center gap-2 mb-2">
                <x-filament::icon icon="heroicon-o-chat-bubble-bottom-center-text" class="h-4 w-4 text-amber-500" />
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                    Catatan Tambahan / Kendala KBM
                </h4>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line leading-relaxed">
                {{ $journal->notes ?: 'Tidak ada catatan tambahan atau kendala yang dilaporkan.' }}
            </p>
        </div>
    </div>

    {{-- Bukti Foto Kegiatan Realtime --}}
    @if ($journal->activity_photo)
        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <div class="flex items-center gap-2 mb-3">
                <x-filament::icon icon="heroicon-o-camera" class="h-4 w-4 text-primary-500" />
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                    Bukti Foto Kegiatan (Realtime)
                </h4>
            </div>
            <div class="flex items-center gap-4">
                <a
                    href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($journal->activity_photo) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="block rounded-lg overflow-hidden border border-gray-200 dark:border-gray-800 hover:opacity-90 transition-opacity"
                >
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($journal->activity_photo) }}"
                        alt="Bukti Foto KBM"
                        class="h-36 w-auto object-cover rounded-lg"
                    />
                </a>
                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                    <p class="font-medium text-gray-700 dark:text-gray-300">Foto diambil langsung via kamera perangkat</p>
                    <p>Klik foto untuk melihat tampilan ukuran penuh.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Rekap & Daftar Presensi Siswa --}}
    @php
        $attendances = $journal->attendances->sortBy(fn($a) => $a->student?->full_name ?? '');
        $hadirCount = $attendances->where('status', 'Hadir')->count();
        $sakitCount = $attendances->where('status', 'Sakit')->count();
        $izinCount = $attendances->where('status', 'Izin')->count();
        $alpaCount = $attendances->where('status', 'Alpa')->count();
        $totalStudents = $attendances->count();
    @endphp

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        {{-- Section Header & Quick Stats --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-user-group" class="h-5 w-5 text-primary-500" />
                <div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">Daftar Presensi Siswa</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total terdaftar: {{ $totalStudents }} siswa</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                    Hadir: {{ $hadirCount }}
                </span>
                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                    Sakit: {{ $sakitCount }}
                </span>
                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                    Izin: {{ $izinCount }}
                </span>
                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                    Alpa: {{ $alpaCount }}
                </span>
            </div>
        </div>

        {{-- Tabel Siswa --}}
        @if ($attendances->isEmpty())
            <div class="py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                Tidak ada data presensi yang tercatat pada jurnal ini.
            </div>
        @else
            <div class="max-h-72 overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50/70 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300 uppercase text-[10px] tracking-wider sticky top-0 z-10 border-b border-gray-100 dark:border-gray-800">
                        <tr>
                            <th class="py-2.5 px-4 w-12 text-center">No</th>
                            <th class="py-2.5 px-4">Nama Siswa</th>
                            <th class="py-2.5 px-4 w-32 text-center">Status</th>
                            <th class="py-2.5 px-4">Catatan / Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($attendances as $idx => $att)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="py-2 px-4 text-center text-gray-400">{{ $loop->iteration }}</td>
                                <td class="py-2 px-4 font-medium text-gray-900 dark:text-white">
                                    {{ $att->student?->full_name ?? '-' }}
                                </td>
                                <td class="py-2 px-4 text-center">
                                    @switch($att->status)
                                        @case('Hadir')
                                            <span class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                                Hadir
                                            </span>
                                            @break
                                        @case('Sakit')
                                            <span class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400">
                                                Sakit
                                            </span>
                                            @break
                                        @case('Izin')
                                            <span class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400">
                                                Izin
                                            </span>
                                            @break
                                        @case('Alpa')
                                            <span class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400">
                                                Alpa
                                            </span>
                                            @break
                                        @default
                                            <span class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                {{ $att->status }}
                                            </span>
                                    @endswitch
                                </td>
                                <td class="py-2 px-4 text-gray-600 dark:text-gray-400">
                                    {{ $att->notes ?: '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

