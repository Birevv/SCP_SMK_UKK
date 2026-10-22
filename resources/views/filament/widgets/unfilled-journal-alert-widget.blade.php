<x-filament-widgets::widget>
    @if ($unfilledSchedules->isNotEmpty())
        <div class="scp-alert-container">
            
            {{-- Header Alert --}}
            <div class="scp-alert-header">
                <div class="scp-alert-icon-title">
                    <div class="scp-alert-icon-box">
                        <x-filament::icon
                            icon="heroicon-o-exclamation-triangle"
                            class="scp-alert-icon"
                        />
                    </div>
                    <div>
                        <h3 class="scp-alert-heading">
                            Peringatan: Jurnal Mengajar Belum Diisi!
                        </h3>
                        <p class="scp-alert-subheading">
                            Terdapat jadwal yang telah selesai lebih dari 45 menit lalu namun belum memiliki jurnal harian.
                        </p>
                    </div>
                </div>
                <x-filament::badge color="danger" size="sm">
                    {{ $unfilledSchedules->count() }} Jadwal Tertunda
                </x-filament::badge>
            </div>

            {{-- List Jadwal yang Tertunda --}}
            <div class="scp-alert-list">
                @foreach ($unfilledSchedules as $schedule)
                    <div class="scp-alert-card">
                        <div class="scp-alert-card-info">
                            <div class="scp-alert-card-badges">
                                <span class="scp-alert-card-subject">
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
                            <div class="scp-alert-card-time">
                                Jam Pembelajaran: <strong>{{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}</strong> ({{ $schedule->day_of_week }}, {{ $schedule->cycle_type }})
                            </div>
                        </div>

                        <div class="scp-alert-card-action">
                            <span class="scp-alert-card-due">
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