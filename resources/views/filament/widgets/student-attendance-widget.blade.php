<x-filament-widgets::widget>
    @if (isset($stats) && $stats !== null)
        <x-filament::section>
            <!-- Header Ringkasan -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(156, 163, 175, 0.2);">
                <div>
                    <h3 style="font-size: 1.125rem; font-weight: 700;">Ringkasan Kehadiran Siswa</h3>
                    <p style="font-size: 0.875rem; opacity: 0.7; margin-top: 2px;">
                        Nama: <strong style="color: #2563eb;">{{ $stats['student_name'] }}</strong>
                    </p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 1.75rem; font-weight: 900; color: {{ $stats['percentage'] < 75 ? '#dc2626' : '#16a34a' }};">
                        {{ $stats['percentage'] }}%
                    </div>
                    <div style="font-size: 0.75rem; opacity: 0.7;">Persentase Kehadiran</div>
                </div>
            </div>

            <!-- Grid 4 Kartu Statistik -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem;">
                <!-- Hadir -->
                <div style="background-color: rgba(22, 163, 74, 0.1); border: 1px solid rgba(22, 163, 74, 0.3); border-radius: 0.75rem; padding: 1rem; text-align: center;">
                    <div style="font-size: 1.75rem; font-weight: 800; color: #16a34a;">{{ $stats['hadir'] }}</div>
                    <div style="font-size: 0.875rem; font-weight: 600; color: #15803d; margin-top: 4px;">Hadir</div>
                </div>

                <!-- Sakit -->
                <div style="background-color: rgba(202, 138, 4, 0.1); border: 1px solid rgba(202, 138, 4, 0.3); border-radius: 0.75rem; padding: 1rem; text-align: center;">
                    <div style="font-size: 1.75rem; font-weight: 800; color: #ca8a04;">{{ $stats['sakit'] }}</div>
                    <div style="font-size: 0.875rem; font-weight: 600; color: #a16207; margin-top: 4px;">Sakit</div>
                </div>

                <!-- Izin -->
                <div style="background-color: rgba(37, 99, 235, 0.1); border: 1px solid rgba(37, 99, 235, 0.3); border-radius: 0.75rem; padding: 1rem; text-align: center;">
                    <div style="font-size: 1.75rem; font-weight: 800; color: #2563eb;">{{ $stats['izin'] }}</div>
                    <div style="font-size: 0.875rem; font-weight: 600; color: #1d4ed8; margin-top: 4px;">Izin</div>
                </div>

                <!-- Alpa -->
                <div style="background-color: rgba(220, 38, 38, 0.1); border: 1px solid rgba(220, 38, 38, 0.3); border-radius: 0.75rem; padding: 1rem; text-align: center;">
                    <div style="font-size: 1.75rem; font-weight: 800; color: #dc2626;">{{ $stats['alpa'] }}</div>
                    <div style="font-size: 0.875rem; font-weight: 600; color: #b91c1c; margin-top: 4px;">Alpa</div>
                </div>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div style="text-align: center; padding: 1.5rem; opacity: 0.7;">
                Data statistik kehadiran belum tersedia.
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
