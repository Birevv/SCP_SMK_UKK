<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Area Form Filter -->
        <x-filament::section>
            <form wire:submit.prevent="$refresh" class="space-y-4">
                {{ $this->form }}

                <div style="margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid rgba(156, 163, 175, 0.3); display: flex; justify-content: flex-end; clear: both;">
                    <x-filament::button
                        tag="a"
                        href="{{ route('attendance-report.print', ['classroom_id' => $this->classroom_id, 'start_date' => $this->start_date, 'end_date' => $this->end_date]) }}"
                        target="_blank"
                        icon="heroicon-o-printer"
                        color="success">
                        Cetak Laporan (PDF / Print)
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <!-- Area Preview Laporan di Halaman Web -->
        @php
            $data = $this->reportData;
        @endphp

        @if (!empty($data) && count($data['students']) > 0)
            <div style="margin-top: 1.75rem;">
                <x-filament::section>
                <!-- Kop Laporan -->
                <div style="border-bottom: 2px solid #374151; padding-bottom: 1rem; margin-bottom: 1.5rem; text-align: center;">
                    <h1 style="font-size: 1.5rem; font-weight: 700; text-transform: uppercase;">
                        SCHOOL CLASS PROGRESS
                    </h1>
                    <p style="font-size: 0.875rem; font-weight: 500; opacity: 0.8;">
                        LAPORAN REKAPITULASI KEHADIRAN SISWA
                    </p>
                    <div style="margin-top: 0.75rem; display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600;">
                        <span>KELAS: {{ strtoupper($data['classroom']?->name ?? '-') }}</span>
                        <span>PERIODE: {{ $data['start_date'] }} s/d {{ $data['end_date'] }}</span>
                    </div>
                </div>

                <!-- Tabel Data -->
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid #4b5563; background-color: rgba(156, 163, 175, 0.1);">
                                <th style="padding: 10px; border: 1px solid #6b7280; text-align: center; width: 40px;">No</th>
                                <th style="padding: 10px; border: 1px solid #6b7280;">NIS / Username</th>
                                <th style="padding: 10px; border: 1px solid #6b7280;">Nama Siswa</th>
                                <th style="padding: 10px; border: 1px solid #6b7280; text-align: center;">Hadir</th>
                                <th style="padding: 10px; border: 1px solid #6b7280; text-align: center;">Sakit</th>
                                <th style="padding: 10px; border: 1px solid #6b7280; text-align: center;">Izin</th>
                                <th style="padding: 10px; border: 1px solid #6b7280; text-align: center;">Alpa</th>
                                <th style="padding: 10px; border: 1px solid #6b7280; text-align: center;">Total</th>
                                <th style="padding: 10px; border: 1px solid #6b7280; text-align: center;">% Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['students'] as $index => $student)
                                <tr style="border-bottom: 1px solid #4b5563;">
                                    <td style="padding: 8px; border: 1px solid #6b7280; text-align: center;">{{ $index + 1 }}</td>
                                    <td style="padding: 8px; border: 1px solid #6b7280;">{{ $student['username'] }}</td>
                                    <td style="padding: 8px; border: 1px solid #6b7280; font-weight: 500;">{{ $student['full_name'] }}</td>
                                    <td style="padding: 8px; border: 1px solid #6b7280; text-align: center; color: #16a34a; font-weight: 600;">{{ $student['hadir'] }}</td>
                                    <td style="padding: 8px; border: 1px solid #6b7280; text-align: center; color: #ca8a04; font-weight: 600;">{{ $student['sakit'] }}</td>
                                    <td style="padding: 8px; border: 1px solid #6b7280; text-align: center; color: #2563eb; font-weight: 600;">{{ $student['izin'] }}</td>
                                    <td style="padding: 8px; border: 1px solid #6b7280; text-align: center; color: #dc2626; font-weight: 600;">{{ $student['alpa'] }}</td>
                                    <td style="padding: 8px; border: 1px solid #6b7280; text-align: center; font-weight: 700;">{{ $student['total'] }}</td>
                                    <td style="padding: 8px; border: 1px solid #6b7280; text-align: center; font-weight: 700; color: {{ $student['percentage'] < 75 ? '#dc2626' : '#16a34a' }};">
                                        {{ $student['percentage'] }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </x-filament::section>
            </div>
        @else
            <div style="margin-top: 1.75rem;">
                <x-filament::section>
                    <div style="text-align: center; padding: 2rem; opacity: 0.7;">
                        Tidak ada data presensi yang ditemukan untuk kriteria filter ini.
                    </div>
                </x-filament::section>
            </div>
        @endif
    </div>
</x-filament-panels::page>
