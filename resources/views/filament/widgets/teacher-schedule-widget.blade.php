<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5 text-primary-500" />
                    <span>Jadwal Mengajar Saya (Filter Tingkat Kelas)</span>
                </div>
                <x-filament::badge color="primary">
                    {{ $activeCycle }}
                </x-filament::badge>
            </div>
        </x-slot>

        <x-slot name="description">
            Filter jadwal mengajar Anda berdasarkan jenjang Kelas (X, XI, XII) untuk memudahkan fokus pembelajaran.
        </x-slot>

        {{-- Filter Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5 pb-3 border-b border-gray-200 dark:border-gray-800">
            {{-- Pills Filter Kelas --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mr-1">Filter Kelas:</span>
                <button
                    type="button"
                    wire:click="setGrade('ALL')"
                    class="px-3 py-1 text-xs font-medium rounded-lg transition-colors {{ $selectedGrade === 'ALL' ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                >
                    Semua Kelas
                </button>
                @foreach ($grades as $g)
                    <button
                        type="button"
                        wire:click="setGrade('{{ $g->name }}')"
                        class="px-3 py-1 text-xs font-medium rounded-lg transition-colors {{ $selectedGrade === $g->name ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}"
                    >
                        Kelas {{ $g->name }}
                    </button>
                @endforeach
            </div>

            {{-- Filter Jurusan & Hari --}}
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Jurusan:</span>
                    <select
                        wire:model.live="selectedMajor"
                        class="text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 focus:ring-primary-500 focus:border-primary-500 py-1"
                    >
                        <option value="ALL">Semua Jurusan</option>
                        @foreach ($majors as $m)
                            <option value="{{ $m->code }}">{{ $m->code }}</option>
                        @endforeach
                    </select>
                </div>

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

        {{-- Cards / List of Schedules --}}
        @if ($schedules->isEmpty())
            <div class="text-center py-6 border border-dashed border-gray-300 dark:border-gray-700 rounded-xl">
                <x-filament::icon icon="heroicon-o-academic-cap" class="mx-auto h-8 w-8 text-gray-400 mb-1.5" />
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tidak Ada Jadwal Mengajar</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Anda tidak memiliki jadwal mengajar pada {{ $selectedGrade !== 'ALL' ? 'Kelas '.$selectedGrade : 'semua kelas' }} di hari {{ $selectedDay }}.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($schedules as $s)
                    <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-3.5 bg-white dark:bg-gray-900/70 shadow-xs flex flex-col justify-between hover:border-primary-400 dark:hover:border-primary-600 transition-colors">
                        <div>
                            {{-- Header Card --}}
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-mono text-xs font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/50 px-2 py-0.5 rounded-md">
                                    {{ $s['start_time'] }} - {{ $s['end_time'] }}
                                </span>
                                <x-filament::badge color="info" size="sm">
                                    {{ $s['classroom_name'] }}
                                </x-filament::badge>
                            </div>

                            {{-- Mapel & Ruang --}}
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white line-clamp-1 mb-1">
                                {{ $s['subject_name'] }}
                            </h4>
                            <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 mb-3">
                                <x-filament::icon icon="heroicon-o-map-pin" class="h-3.5 w-3.5 text-gray-400" />
                                <span>{{ $s['room_name'] }}</span>
                            </div>
                        </div>

                        {{-- Footer Card: Status Jurnal & Action --}}
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            @if ($s['has_journal'])
                                <x-filament::badge color="success" size="sm" icon="heroicon-o-check-circle">
                                    Jurnal Selesai
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

                            <a
                                href="{{ url('/admin/journals/create?schedule_id='.$s['id']) }}"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline"
                            >
                                <span>{{ $s['has_journal'] ? 'Lihat Jurnal' : 'Isi Jurnal' }}</span>
                                <x-filament::icon icon="heroicon-m-arrow-right" class="h-3 w-3" />
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
