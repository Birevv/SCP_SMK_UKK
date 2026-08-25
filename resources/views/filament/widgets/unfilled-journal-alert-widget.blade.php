<x-filament::widget>
    @if ($unfilledSchedules->isNotEmpty())
        <div class="p-4 rounded-xl bg-danger-50 border border-danger-200 dark:bg-danger-950 dark:border-danger-800">
            <div class="flex items-center gap-3 mb-3">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                <h3 class="text-lg font-bold text-danger-800 dark:text-danger-200">
                    Peringatan: Jurnal Mengajar Belum Diisi! (>30 Menit Selesai)
                </h3>
            </div>
            <div class="space-y-2">
                @foreach ($unfilledSchedules as $schedule)
                    <div class="flex justify-between items-center bg-white dark:bg-gray-900 p-3 rounded-lg border shadow-sm">
                        <div>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $schedule->subject?->name ?? $schedule->subject_name }}</span>
                            <span class="text-sm text-gray-500">({{ $schedule->classroom?->name }} - {{ $schedule->room?->name }})</span>
                        </div>
                        <div class="text-sm text-danger-600 font-medium">
                            Jam Selesai: {{ $schedule->end_time }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament::widget>