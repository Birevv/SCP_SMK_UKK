<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                    <span>Jadwal Belajar Kelas (Wali Kelas & Guru BK)</span>
                </div>
                <x-filament::badge color="info">
                    {{ $activeCycle }}
                </x-filament::badge>
            </div>
        </x-slot>

        <x-slot name="description">
            Pantau jadwal pelajaran dan keterisian jurnal mengajar guru untuk kelas binaan Anda.
        </x-slot>

        {{-- Filter Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5 pb-3 border-b border-gray-200 dark:border-gray-800">
            {{-- Filter Tingkat Kelas --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mr-1">Tingkat:</span>
                <button
                    type="button"
                    wire:click="setGrade('ALL')"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg transition-colors {{ $selectedGrade === 'ALL' ? 'bg-primary-600 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                >
                    Semua
                </button>
                @foreach ($grades as $g)
                    <button
                        type="button"
                        wire:click="setGrade('{{ $g->name }}')"
                        class="px-2.5 py-1 text-xs font-medium rounded-lg transition-colors {{ $selectedGrade === $g->name ? 'bg-primary-600 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                    >
                        Kelas {{ $g->name }}
                    </button>
                @endforeach
            </div>

            {{-- Select Classroom & Hari --}}
            <div class="flex flex-wrap items-center gap-2">
                @if ($availableClassrooms->isNotEmpty())
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Kelas:</span>
                        <select
                            wire:model.live="selectedClassroomId"
                            class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500 py-1"
                        >
                            <option value="">Semua Rombel</option>
                            @foreach ($availableClassrooms as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Hari:</span>
                    <select
                        wire:model.live="selectedDay"
                        class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500 py-1"
                    >
                        @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d)
                            <option value="{{ $d }}">{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Table / Cards of Schedules --}}
        @if ($schedules->isEmpty())
            <div class="text-center py-6 border border-dashed border-gray-300 dark:border-gray-700 rounded-xl">
                <x-filament::icon icon="heroicon-o-academic-cap" class="mx-auto h-8 w-8 text-gray-400 mb-1.5" />
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tidak Ada Jadwal Kelas</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Tidak ditemukan jadwal pelajaran pada kelas dan hari yang dipilih.
                </p>
            </div>
        @else
            {{-- A. TAMPILAN DESKTOP & TABLET: TABEL LENGKAP --}}
            <div class="scp-homeroom-desktop-table overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="scp-homeroom-table w-full text-left text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-semibold">
                        <tr>
                            <th class="py-3 px-4 first:pl-5">Waktu</th>
                            <th class="py-3 px-4">Kelas</th>
                            <th class="py-3 px-4">Mata Pelajaran</th>
                            <th class="py-3 px-4">Guru Pengajar</th>
                            <th class="py-3 px-4">Ruangan</th>
                            <th class="py-3 px-4 last:pr-5 text-center">Status Jurnal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($schedules as $s)
                            <tr class="{{ $s['is_current'] ? 'bg-primary-50/70 dark:bg-primary-950/40 font-medium' : 'hover:bg-gray-50/50 dark:hover:bg-gray-800/40' }} transition-colors">
                                <td class="py-3 px-4 first:pl-5 whitespace-nowrap font-mono text-xs text-gray-700 dark:text-gray-300">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span>{{ $s['start_time'] }} - {{ $s['end_time'] }}</span>
                                        @if ($s['is_current'])
                                            <x-filament::badge color="primary" size="sm" icon="heroicon-m-clock">
                                                Saat Ini
                                            </x-filament::badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <x-filament::badge color="info" size="sm">
                                        {{ $s['classroom_name'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">
                                    {{ $s['subject_name'] }}
                                </td>
                                <td class="py-3 px-4 text-gray-700 dark:text-gray-200">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span>{{ $s['teacher_name'] }}</span>
                                        @if ($s['is_user_teacher'])
                                            <x-filament::badge color="success" size="sm">
                                                Anda
                                            </x-filament::badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">
                                    {{ $s['room_name'] }}
                                </td>
                                <td class="py-3 px-4 last:pr-5 text-center whitespace-nowrap">
                                    @if ($s['has_journal'])
                                        <x-filament::badge color="success" size="sm" icon="heroicon-o-check-circle">
                                            Sudah Diisi
                                        </x-filament::badge>
                                    @elseif ($s['is_expired'] ?? $s['is_overdue'])
                                        <x-filament::badge color="danger" size="sm" icon="heroicon-o-x-circle">
                                            Tidak Mengisi Jurnal
                                        </x-filament::badge>
                                    @else
                                        <x-filament::badge color="warning" size="sm">
                                            Belum Diisi
                                        </x-filament::badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- B. TAMPILAN SMARTPHONE: ADAPTIVE CARD LIST --}}
            <div class="scp-homeroom-mobile-cards space-y-2.5">
                @foreach ($schedules as $s)
                    <div class="p-3 bg-white dark:bg-gray-900/70 border {{ $s['is_current'] ? 'border-primary-500 ring-1 ring-primary-500/30' : 'border-gray-200 dark:border-gray-800' }} rounded-xl shadow-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-mono text-xs font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/60 px-2 py-0.5 rounded-md">
                                    {{ $s['start_time'] }} - {{ $s['end_time'] }}
                                </span>
                                @if ($s['is_current'])
                                    <x-filament::badge color="primary" size="sm" icon="heroicon-m-clock">
                                        Saat Ini
                                    </x-filament::badge>
                                @endif
                            </div>
                            <x-filament::badge color="info" size="sm">
                                {{ $s['classroom_name'] }}
                            </x-filament::badge>
                        </div>
                        <div>
                            <div class="font-semibold text-xs text-gray-900 dark:text-gray-100">
                                {{ $s['subject_name'] }}
                            </div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center justify-between mt-1">
                                <span class="inline-flex items-center gap-1 text-gray-600 dark:text-gray-300">
                                    <x-filament::icon icon="heroicon-m-user" class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                    <span>{{ $s['teacher_name'] }}</span>
                                    @if ($s['is_user_teacher'])
                                        <x-filament::badge color="success" size="sm" class="ml-0.5">
                                            Anda
                                        </x-filament::badge>
                                    @endif
                                </span>
                                <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-m-map-pin" class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                    <span>{{ $s['room_name'] }}</span>
                                </span>
                            </div>
                        </div>
                        <div class="pt-1.5 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between">
                            <span class="text-[10px] text-gray-400">Status Jurnal:</span>
                            @if ($s['has_journal'])
                                <x-filament::badge color="success" size="sm" icon="heroicon-o-check-circle">
                                    Sudah Diisi
                                </x-filament::badge>
                            @elseif ($s['is_expired'] ?? $s['is_overdue'])
                                <x-filament::badge color="danger" size="sm" icon="heroicon-o-x-circle">
                                    Tidak Mengisi Jurnal
                                </x-filament::badge>
                            @else
                                <x-filament::badge color="warning" size="sm">
                                    Belum Diisi
                                </x-filament::badge>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
