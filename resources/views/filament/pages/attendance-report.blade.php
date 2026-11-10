<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Area Form Filter -->
        <x-filament::section>
            <form wire:submit.prevent="$refresh" class="space-y-4">
                {{ $this->form }}

                <div class="pt-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between flex-wrap gap-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Pilih rentang tanggal dan kelas untuk memperbarui rekap kehadiran siswa.
                    </span>

                    <div class="flex items-center gap-2.5 flex-wrap">
                        <x-filament::button
                            tag="a"
                            href="{{ route('attendance-report.export-excel', ['classroom_id' => $this->classroom_id, 'start_date' => $this->start_date, 'end_date' => $this->end_date]) }}"
                            target="_blank"
                            icon="heroicon-o-document-arrow-down"
                            color="primary"
                            size="sm"
                        >
                            Ekspor Excel (.xlsx)
                        </x-filament::button>

                        <x-filament::button
                            tag="a"
                            href="{{ route('attendance-report.print', ['classroom_id' => $this->classroom_id, 'start_date' => $this->start_date, 'end_date' => $this->end_date]) }}"
                            target="_blank"
                            icon="heroicon-o-printer"
                            color="success"
                            size="sm"
                        >
                            Cetak Laporan (PDF / Print)
                        </x-filament::button>
                    </div>
                </div>
            </form>
        </x-filament::section>

        <!-- Area Preview Laporan di Halaman Web -->
        @php
            $data = $this->reportData;
            $students = $data['students'] ?? [];
            $totalStudents = count($students);
            $totalHadir = array_sum(array_column($students, 'hadir'));
            $totalSakit = array_sum(array_column($students, 'sakit'));
            $totalIzin = array_sum(array_column($students, 'izin'));
            $totalAlpa = array_sum(array_column($students, 'alpa'));
            $avgPercentage = $totalStudents > 0
                ? round(array_sum(array_column($students, 'percentage')) / $totalStudents, 1)
                : 0;
            $underTargetCount = count(array_filter($students, fn ($s) => $s['percentage'] < 75));
        @endphp

        @if (!empty($data) && $totalStudents > 0)
            {{-- 1. KPI SUMMARY STATS CARDS --}}
            <div class="scp-kpi-grid">
                <!-- Card 1: Total Siswa -->
                <div class="scp-kpi-card">
                    <div class="scp-kpi-header">
                        <span class="scp-kpi-title">Total Siswa Terdaftar</span>
                        <div class="scp-kpi-icon scp-kpi-icon-blue">
                            <x-filament::icon icon="heroicon-m-user-group" class="h-4 w-4" />
                        </div>
                    </div>
                    <div>
                        <div class="scp-kpi-val">
                            {{ $totalStudents }} <span style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">siswa</span>
                        </div>
                        <div class="scp-kpi-sub">
                            <x-filament::icon icon="heroicon-m-academic-cap" class="h-3.5 w-3.5 text-gray-400" />
                            <span>Kelas {{ $data['classroom']?->name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Rata-rata Kehadiran -->
                <div class="scp-kpi-card">
                    <div class="scp-kpi-header">
                        <span class="scp-kpi-title">Rata-rata Kehadiran Kelas</span>
                        <div class="scp-kpi-icon scp-kpi-icon-emerald">
                            <x-filament::icon icon="heroicon-m-chart-bar" class="h-4 w-4" />
                        </div>
                    </div>
                    <div>
                        <div class="scp-kpi-val {{ $avgPercentage >= 75 ? 'scp-text-emerald' : 'scp-text-rose' }}">
                            {{ $avgPercentage }}%
                        </div>
                        <div class="scp-kpi-sub {{ $avgPercentage >= 75 ? 'scp-text-emerald' : 'scp-text-rose' }}" style="font-weight: 600;">
                            <span>{{ $avgPercentage >= 75 ? '✓ Memenuhi standar ≥75%' : '⚠ Perlu perhatian khusus' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Perhatian Khusus -->
                <div class="scp-kpi-card">
                    <div class="scp-kpi-header">
                        <span class="scp-kpi-title">Perhatian Khusus (&lt;75%)</span>
                        <div class="scp-kpi-icon scp-kpi-icon-rose">
                            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-4 w-4" />
                        </div>
                    </div>
                    <div>
                        <div class="scp-kpi-val scp-text-rose">
                            {{ $underTargetCount }} <span style="font-size: 0.8125rem; font-weight: 500; color: #64748b;">siswa</span>
                        </div>
                        <div class="scp-kpi-sub">
                            <span>Berisiko kehadiran rendah</span>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Akumulasi Absensi -->
                <div class="scp-kpi-card">
                    <div class="scp-kpi-header">
                        <span class="scp-kpi-title">Akumulasi Absensi</span>
                        <div class="scp-kpi-icon scp-kpi-icon-purple">
                            <x-filament::icon icon="heroicon-m-calendar-days" class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="scp-kpi-breakdown">
                        <div class="scp-kpi-row">
                            <span class="scp-text-emerald" style="font-weight: 600;">Hadir:</span>
                            <span style="font-weight: 700;">{{ $totalHadir }}</span>
                        </div>
                        <div class="scp-kpi-row">
                            <span class="scp-text-amber" style="font-weight: 600;">Sakit / Izin:</span>
                            <span style="font-weight: 700;">{{ $totalSakit + $totalIzin }}</span>
                        </div>
                        <div class="scp-kpi-row">
                            <span class="scp-text-rose" style="font-weight: 600;">Alpa:</span>
                            <span style="font-weight: 700;">{{ $totalAlpa }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. REPORT TABLE & CARD VIEW --}}
            <x-filament::section>
                <!-- Kop Laporan -->
                <div class="border-b border-gray-200 dark:border-gray-800 pb-4 mb-4 text-center">
                    <h2 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white uppercase tracking-tight">
                        LAPORAN REKAPITULASI KEHADIRAN SISWA
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        SMK NEGERI 1 BANTUL — SCHOOL CLASS PROGRESS
                    </p>
                    <div class="scp-report-info-bar">
                        <span>KELAS: {{ strtoupper($data['classroom']?->name ?? '-') }}</span>
                        <span>PERIODE: {{ $data['start_date'] }} s/d {{ $data['end_date'] }}</span>
                    </div>
                </div>

                <!-- A. Tabel Data (Desktop & Tablet) -->
                <div class="hidden sm:block scp-report-table-box">
                    <table class="scp-report-table">
                        <thead>
                            <tr>
                                <th style="text-align: center; width: 48px;">No</th>
                                <th>NIS / Username</th>
                                <th>Nama Siswa</th>
                                <th style="text-align: center;" class="scp-text-emerald">Hadir</th>
                                <th style="text-align: center;" class="scp-text-amber">Sakit</th>
                                <th style="text-align: center;" class="scp-text-blue">Izin</th>
                                <th style="text-align: center;" class="scp-text-rose">Alpa</th>
                                <th style="text-align: center;">Total</th>
                                <th style="text-align: center;">% Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $index => $student)
                                <tr>
                                    <td style="text-align: center; color: #94a3b8; font-weight: 500;">{{ $index + 1 }}</td>
                                    <td style="font-family: monospace; font-size: 0.75rem;">{{ $student['username'] }}</td>
                                    <td style="font-weight: 600;">{{ $student['full_name'] }}</td>
                                    <td style="text-align: center; font-weight: 700;" class="scp-text-emerald">{{ $student['hadir'] }}</td>
                                    <td style="text-align: center; font-weight: 700;" class="scp-text-amber">{{ $student['sakit'] }}</td>
                                    <td style="text-align: center; font-weight: 700;" class="scp-text-blue">{{ $student['izin'] }}</td>
                                    <td style="text-align: center; font-weight: 700;" class="scp-text-rose">{{ $student['alpa'] }}</td>
                                    <td style="text-align: center; font-weight: 600;">{{ $student['total'] }}</td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <span class="scp-pct-badge {{ $student['percentage'] >= 75 ? 'scp-pct-success' : 'scp-pct-danger' }}">
                                            {{ $student['percentage'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- B. Card View Adaptif (Smartphone) -->
                <div class="block sm:hidden space-y-2.5">
                    @foreach ($students as $index => $student)
                        <div class="scp-mobile-card">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-400">#{{ $index + 1 }}</span>
                                    <span class="font-semibold text-xs text-gray-900 dark:text-white">{{ $student['full_name'] }}</span>
                                </div>
                                <span class="scp-pct-badge {{ $student['percentage'] >= 75 ? 'scp-pct-success' : 'scp-pct-danger' }}">
                                    {{ $student['percentage'] }}%
                                </span>
                            </div>
                            <div class="text-[11px] text-gray-500 font-mono mt-0.5">
                                NIS: {{ $student['username'] }} • Total: {{ $student['total'] }} sesi
                            </div>
                            <div class="scp-mobile-stat-grid">
                                <div class="scp-mobile-stat-box scp-mobile-stat-box-emerald">
                                    <div class="scp-mobile-stat-lbl scp-text-emerald">Hadir</div>
                                    <div class="scp-mobile-stat-val scp-text-emerald">{{ $student['hadir'] }}</div>
                                </div>
                                <div class="scp-mobile-stat-box scp-mobile-stat-box-amber">
                                    <div class="scp-mobile-stat-lbl scp-text-amber">Sakit</div>
                                    <div class="scp-mobile-stat-val scp-text-amber">{{ $student['sakit'] }}</div>
                                </div>
                                <div class="scp-mobile-stat-box scp-mobile-stat-box-blue">
                                    <div class="scp-mobile-stat-lbl scp-text-blue">Izin</div>
                                    <div class="scp-mobile-stat-val scp-text-blue">{{ $student['izin'] }}</div>
                                </div>
                                <div class="scp-mobile-stat-box scp-mobile-stat-box-rose">
                                    <div class="scp-mobile-stat-lbl scp-text-rose">Alpa</div>
                                    <div class="scp-mobile-stat-val scp-text-rose">{{ $student['alpa'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
                    <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="mx-auto h-10 w-10 text-gray-400 mb-2" />
                    <p class="font-medium">Tidak ada data presensi yang ditemukan untuk kriteria filter ini.</p>
                    <p class="text-xs text-gray-400 mt-1">Silakan sesuaikan pilihan kelas atau periode tanggal di atas.</p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
