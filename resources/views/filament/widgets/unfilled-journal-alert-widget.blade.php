<x-filament-widgets::widget>
    @if(count($unfilledSchedules) > 0)
        <div class="p-4 bg-danger-500/10 border border-danger-500 rounded-xl">
            <div class="flex items-center gap-3 mb-2 text-danger-600 dark:text-danger-400 font-bold text-lg">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6 animate-pulse" />
                <span>Peringatan Jurnal Belum Diisi!</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                Anda memiliki jadwal mengajar hari ini yang jam pengajarannya telah selesai (lebih dari 30 menit) namun jurnalnya belum dibuat:
            </p>
            <ul class="space-y-2">
                @foreach($unfilledSchedules as $schedule)
                    <li class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                        <div>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $schedule->subject_name }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 block">
                                {{ $schedule->classroom?->name ?? 'Kelas -' }} | {{ $schedule->room?->name ?? 'Ruangan -' }} | {{ $schedule->start_time }} - {{ $schedule->end_time }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-filament-widgets::widget>
