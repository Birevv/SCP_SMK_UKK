<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-5 w-5 text-warning-500" />
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
                    class="px-2.5 py-1 text-xs font-medium rounded-lg transition-colors {{ $selectedGrade === 'ALL' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                >
                    Semua
                </button>
                @foreach ($grades as $g)
                    <button
                        type="button"
                        wire:click="setGrade('{{ $g->name }}')"
                        class="px-2.5 py-1 text-xs font-medium rounded-lg transition-colors {{ $selectedGrade === $g->name ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
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

        {{-- Table List Schedules --}}
        @if ($schedules->isEmpty())
            <div class="text-center py-6 border border-dashed border-gray-300 dark:border-gray-700 rounded-xl">
                <x-filament::icon icon="heroicon-o-academic-cap" class="mx-auto h-8 w-8 text-gray-400 mb-1.5" />
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tidak Ada Jadwal Kelas</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Tidak ditemukan jadwal pelajaran pada kelas dan hari yang dipilih.
                </p>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-semibold">
                        <tr>
                            <th class="py-2.5 px-3">Waktu</th>
                            <th class="py-2.5 px-3">Kelas</th>
                            <th class="py-2.5 px-3">Mata Pelajaran</th>
                            <th class="py-2.5 px-3">Guru Pengajar</th>
                            <th class="py-2.5 px-3">Ruangan</th>
                            <th class="py-2.5 px-3 text-center">Status Jurnal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($schedules as $s)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors">
                                <td class="py-2 px-3 whitespace-nowrap font-mono text-xs text-gray-700 dark:text-gray-300">
                                    {{ $s['start_time'] }} - {{ $s['end_time'] }}
                                </td>
                                <td class="py-2 px-3 whitespace-nowrap">
                                    <x-filament::badge color="info" size="sm">
                                        {{ $s['classroom_name'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="py-2 px-3 font-medium text-gray-900 dark:text-white">
                                    {{ $s['subject_name'] }}
                                </td>
                                <td class="py-2 px-3 text-gray-700 dark:text-gray-300">
                                    {{ $s['teacher_name'] }}
                                </td>
                                <td class="py-2 px-3 text-gray-500 dark:text-gray-400">
                                    {{ $s['room_name'] }}
                                </td>
                                <td class="py-2 px-3 text-center whitespace-nowrap">
                                    @if ($s['has_journal'])
                                        <x-filament::badge color="success" size="sm" icon="heroicon-o-check-circle">
                                            Sudah Diisi
                                        </x-filament::badge>
                                    @elseif ($s['is_overdue'])
                                        <x-filament::badge color="danger" size="sm" icon="heroicon-o-exclamation-triangle">
                                            Terlambat >45m
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
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
