<x-filament-widgets::widget>
    @if ($unfilledSchedules->isNotEmpty())
        <div style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(220, 38, 38, 0.03) 100%); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 1rem; padding: 1.25rem; margin-bottom: 1.5rem; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.08);">
            
            {{-- Header Alert --}}
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background-color: rgba(239, 68, 68, 0.15); padding: 0.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                        <x-filament::icon
                            icon="heroicon-o-exclamation-triangle"
                            style="width: 24px; height: 24px; color: #dc2626;"
                        />
                    </div>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 700; color: #b91c1c; margin: 0;">
                            Peringatan: Jurnal Mengajar Belum Diisi!
                        </h3>
                        <p style="font-size: 0.8rem; color: #ef4444; margin: 0.15rem 0 0 0; font-weight: 500;">
                            Terdapat jadwal yang telah selesai lebih dari 45 menit lalu namun belum memiliki jurnal harian.
                        </p>
                    </div>
                </div>
                <x-filament::badge color="danger" size="sm">
                    {{ $unfilledSchedules->count() }} Jadwal Tertunda
                </x-filament::badge>
            </div>

            {{-- List Jadwal yang Tertunda --}}
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                @foreach ($unfilledSchedules as $schedule)
                    <div style="display: flex; justify-content: space-between; align-items: center; background-color: rgba(255, 255, 255, 0.95); border: 1px solid rgba(239, 68, 68, 0.2); padding: 0.85rem 1.15rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 0.75rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <span style="font-weight: 700; font-size: 0.95rem; color: #1f2937;">
                                    {{ $schedule->subject?->name ?? $schedule->subject_name }}
                                </span>
                                <x-filament::badge color="info" size="sm">
                                    {{ $schedule->classroom?->name ?? 'Kelas' }}
                                </x-filament::badge>
                                <x-filament::badge color="gray" size="sm">
                                    {{ $schedule->room?->name ?? 'Ruang' }}
                                </x-filament::badge>
                                @if ($schedule->teacher)
                                    <x-filament::badge color="warning" size="sm">
                                        {{ $schedule->teacher->full_name }}
                                    </x-filament::badge>
                                @endif
                            </div>
                            <div style="font-size: 0.8rem; color: #6b7280;">
                                Jam Pembelajaran: <strong>{{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}</strong> ({{ $schedule->day_of_week }}, {{ $schedule->cycle_type }})
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-size: 0.8rem; font-weight: 600; color: #dc2626;">
                                Selesai Pukul {{ substr($schedule->end_time, 0, 5) }}
                            </span>
                            @if (class_exists(\App\Filament\Resources\Journals\JournalResource::class))
                                <x-filament::button
                                    tag="a"
                                    href="{{ \App\Filament\Resources\Journals\JournalResource::getUrl('create', ['schedule_id' => $schedule->id]) }}"
                                    size="xs"
                                    color="danger"
                                    icon="heroicon-o-pencil-square">
                                    Isi Jurnal
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    @endif
</x-filament-widgets::widget>