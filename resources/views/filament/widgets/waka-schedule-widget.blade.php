<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-funnel" class="h-5 w-5 text-primary-500" />
                <span>Monitoring Jadwal Mengajar per Jurusan & Tingkat (Waka Kurikulum)</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Filter jadwal mengajar guru berdasarkan program keahlian (Jurusan), jenjang kelas, dan hari KBM.
        </x-slot>

        {{-- Filter Bar --}}
        <div class="space-y-4 mb-6">
            {{-- Filter Jurusan Tabs/Pills --}}
            <div>
                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5 block">
                    Pilih Jurusan:
                </label>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        type="button"
                        wire:click="setMajor('ALL')"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $selectedMajor === 'ALL' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                    >
                        Semua Jurusan
                    </button>
                    @foreach ($majors as $m)
                        <button
                            type="button"
                            wire:click="setMajor('{{ $m->code }}')"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5 {{ $selectedMajor === $m->code ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                        >
                            <span>{{ $m->code }}</span>
                            <span class="text-[10px] opacity-80">({{ $m->name }})</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Filter Jurusan & Tingkat & Hari & Putaran --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2 border-t border-gray-200 dark:border-gray-800">
                {{-- Jurusan --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 block">
                        Jurusan:
                    </label>
                    <select
                        wire:model.live="selectedMajor"
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="ALL">Semua Jurusan</option>
                        @foreach ($majors as $m)
                            <option value="{{ $m->code }}">{{ $m->code }} - {{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tingkat Kelas --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 block">
                        Tingkat / Kelas:
                    </label>
                    <select
                        wire:model.live="selectedGrade"
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="ALL">Semua Tingkat (X, XI, XII)</option>
                        @foreach ($grades as $g)
                            <option value="{{ $g->name }}">Kelas {{ $g->name }} ({{ $g->description }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Hari --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 block">
                        Hari KBM:
                    </label>
                    <select
                        wire:model.live="selectedDay"
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500"
                    >
                        @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d)
                            <option value="{{ $d }}">{{ $d }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Putaran --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 block">
                        Siklus Putaran:
                    </label>
                    <select
                        wire:model.live="selectedCycle"
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="Putaran 1">Putaran 1</option>
                        <option value="Putaran 2">Putaran 2</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Summary Stats Pills --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-5">
            <div class="bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-800 rounded-lg p-2.5 flex items-center justify-between">
                <div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Total Jadwal</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $totalSchedules }}</div>
                </div>
                <x-filament::badge color="info">{{ $selectedMajor === 'ALL' ? 'Semua' : $selectedMajor }}</x-filament::badge>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-800 rounded-lg p-2.5 flex items-center justify-between">
                <div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Jurnal Terisi</div>
                    <div class="text-lg font-bold text-success-600 dark:text-success-400">{{ $filledCount }}</div>
                </div>
                <x-filament::badge color="success">Sudah</x-filament::badge>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-800 rounded-lg p-2.5 flex items-center justify-between">
                <div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Belum Terisi</div>
                    <div class="text-lg font-bold text-warning-600 dark:text-warning-400">{{ $unfilledCount }}</div>
                </div>
                <x-filament::badge color="warning">Pending</x-filament::badge>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-800 rounded-lg p-2.5 flex items-center justify-between">
                <div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Terlambat >45m</div>
                    <div class="text-lg font-bold text-danger-600 dark:text-danger-400">{{ $overdueCount }}</div>
                </div>
                <x-filament::badge color="danger">Alert</x-filament::badge>
            </div>
        </div>

        {{-- Table Schedules --}}
        @if ($schedules->isEmpty())
            <div class="text-center py-8 border border-dashed border-gray-300 dark:border-gray-700 rounded-xl">
                <x-filament::icon icon="heroicon-o-calendar-days" class="mx-auto h-10 w-10 text-gray-400 mb-2" />
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tidak Ada Jadwal Mengajar Ditemukan</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Tidak ada jadwal untuk Jurusan <strong>{{ $selectedMajor }}</strong>, Tingkat <strong>{{ $selectedGrade }}</strong> pada hari <strong>{{ $selectedDay }}</strong> ({{ $selectedCycle }}).
                </p>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800 font-semibold">
                        <tr>
                            <th class="py-2.5 px-3">Waktu</th>
                            <th class="py-2.5 px-3">Jurusan / Kelas</th>
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
                                    <div class="flex items-center gap-1.5">
                                        <x-filament::badge color="info" size="sm">
                                            {{ $s['classroom_name'] }}
                                        </x-filament::badge>
                                        @if ($s['major_code'] !== '-')
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">({{ $s['major_code'] }})</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2 px-3 font-medium text-gray-900 dark:text-white">
                                    {{ $s['subject_name'] }}
                                </td>
                                <td class="py-2 px-3 text-gray-700 dark:text-gray-200">
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
