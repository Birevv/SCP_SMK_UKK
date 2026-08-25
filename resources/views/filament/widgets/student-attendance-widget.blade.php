<x-filament-widgets::widget>
    @if (isset($stats) && $stats !== null)
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Header Section -->
            <div style="padding-bottom: 0.75rem; border-bottom: 1px solid rgba(156, 163, 175, 0.2);">
                <h2 style="font-size: 1.25rem; font-weight: 700; letter-spacing: -0.02em;">
                    Dashboard Kehadiran
                </h2>
                <p style="font-size: 0.875rem; opacity: 0.7; margin-top: 4px;">
                    Ringkasan kehadiran dan aktivitas untuk <strong>{{ $stats['student_name'] }}</strong>.
                </p>
            </div>

            <!-- Overview Area: Grid 2 Kolom -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                
                <!-- Main Card: Kehadiran Bulan Ini -->
                <x-filament::section style="grid-column: span 2 / span 2;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.7;">
                            Kehadiran Bulan Ini
                        </span>
                        <x-filament::badge :color="$stats['percentage'] >= 75 ? 'success' : 'danger'" size="sm">
                            {{ $stats['percentage'] >= 75 ? 'Sangat Baik' : 'Perlu Perhatian' }}
                        </x-filament::badge>
                    </div>

                    <div style="margin-bottom: 1.5rem; display: flex; align-items: baseline; gap: 0.75rem;">
                        <span style="font-size: 2.25rem; font-weight: 800; letter-spacing: -0.03em;">
                            {{ $stats['percentage'] }}%
                        </span>
                        <span style="font-size: 0.875rem; opacity: 0.7;">
                            {{ $stats['hadir'] }} dari {{ $stats['total'] }} hari pertemuan
                        </span>
                    </div>

                    <!-- Progress Bar Sederhana -->
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <div style="width: 100%; background-color: rgba(156, 163, 175, 0.2); border-radius: 9999px; height: 8px; overflow: hidden;">
                            <div @style([
                                'background-color: #10b981',
                                'height: 100%',
                                'border-radius: 9999px',
                                'transition: width 0.5s ease',
                                'width: ' . min(100, max(0, $stats['percentage'])) . '%',
                            ])></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; opacity: 0.6;">
                            <span>0%</span>
                            <span>Target Minimum: 75%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </x-filament::section>

                <!-- Side Card: Ringkasan Rincian -->
                <x-filament::section>
                    <h3 style="font-size: 0.875rem; font-weight: 600; padding-bottom: 0.75rem; margin-bottom: 0.75rem; border-bottom: 1px solid rgba(156, 163, 175, 0.2);">
                        Rincian Kehadiran
                    </h3>

                    <div style="display: flex; flex-direction: column; font-size: 0.875rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid rgba(156, 163, 175, 0.1);">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background-color: #10b981; display: inline-block;"></span>
                                <span style="opacity: 0.8;">Hadir</span>
                            </div>
                            <span style="font-weight: 700;">{{ $stats['hadir'] }}</span>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid rgba(156, 163, 175, 0.1);">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background-color: #f59e0b; display: inline-block;"></span>
                                <span style="opacity: 0.8;">Sakit</span>
                            </div>
                            <span style="font-weight: 700;">{{ $stats['sakit'] }}</span>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid rgba(156, 163, 175, 0.1);">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background-color: #0284c7; display: inline-block;"></span>
                                <span style="opacity: 0.8;">Izin</span>
                            </div>
                            <span style="font-weight: 700;">{{ $stats['izin'] }}</span>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background-color: #ef4444; display: inline-block;"></span>
                                <span style="opacity: 0.8;">Alpa</span>
                            </div>
                            <span style="font-weight: 700;">{{ $stats['alpa'] }}</span>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            <!-- Table Section: Riwayat Kehadiran -->
            <x-filament::section>
                <x-slot name="heading">
                    Riwayat Kehadiran Terbaru
                </x-slot>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(156, 163, 175, 0.3); opacity: 0.8; font-size: 0.75rem; text-transform: uppercase;">
                                <th style="padding: 10px 12px;">Tanggal</th>
                                <th style="padding: 10px 12px;">Hari</th>
                                <th style="padding: 10px 12px;">Mata Pelajaran</th>
                                <th style="padding: 10px 12px;">Status</th>
                                <th style="padding: 10px 12px;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (isset($stats['history']) && count($stats['history']) > 0)
                                @foreach ($stats['history'] as $item)
                                    @php
                                        $itemDate = $item->date ?? $item->journal?->date ?? $item->created_at;
                                        $carbonDate = $itemDate ? \Carbon\Carbon::parse($itemDate) : null;
                                    @endphp
                                    <tr style="border-bottom: 1px solid rgba(156, 163, 175, 0.15);">
                                        <td style="padding: 12px; font-weight: 500;">
                                            {{ $carbonDate ? $carbonDate->isoFormat('DD MMM YYYY') : '-' }}
                                        </td>
                                        <td style="padding: 12px; opacity: 0.8;">
                                            {{ $carbonDate ? $carbonDate->locale('id')->isoFormat('dddd') : '-' }}
                                        </td>
                                        <td style="padding: 12px; font-weight: 500;">
                                            {{ $item->journal?->schedule?->subject?->name ?? $item->journal?->schedule?->subject_name ?? '-' }}
                                        </td>
                                        <td style="padding: 12px;">
                                            @if ($item->status === 'Hadir')
                                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background-color: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">
                                                    <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #10b981;"></span>
                                                    Hadir
                                                </span>
                                            @elseif ($item->status === 'Sakit')
                                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background-color: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">
                                                    <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #f59e0b;"></span>
                                                    Sakit
                                                </span>
                                            @elseif ($item->status === 'Izin')
                                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background-color: rgba(2, 132, 199, 0.15); color: #0284c7; border: 1px solid rgba(2, 132, 199, 0.3);">
                                                    <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #0284c7;"></span>
                                                    Izin
                                                </span>
                                            @else
                                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background-color: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                                                    <span style="width: 6px; height: 6px; border-radius: 50%; background-color: #ef4444;"></span>
                                                    Alpa
                                                </span>
                                            @endif
                                        </td>
                                        <td style="padding: 12px; font-size: 0.75rem; opacity: 0.7;">
                                            {{ $item->notes ?? ($item->status === 'Hadir' ? 'Tepat waktu' : '-') }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" style="padding: 2rem; text-align: center; opacity: 0.7;">
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
            <div style="text-align: center; padding: 1.5rem; opacity: 0.7;">
                Data ringkasan kehadiran belum tersedia.
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
