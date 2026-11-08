<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between gap-2 w-full">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-5 w-5 text-gray-500" />
                    <span>Monitoring Jadwal & Jurnal Mengajar</span>
                </div>
            </div>
        </x-slot>

        <x-slot name="description">
            Filter jadwal dan status pengisian jurnal mengajar berdasarkan tanggal, jurusan, tingkat, dan kelas.
        </x-slot>

        <style>
            /* ==========================================================================
               Waka Schedule Monitoring Widget (Unslop Human-Designed UI)
               ========================================================================== */
            .scp-waka-widget {
                font-family: inherit;
            }
            .scp-filter-section {
                margin-bottom: 1.25rem;
            }
            .scp-filter-label {
                font-size: 0.75rem;
                font-weight: 500;
                color: #64748b;
                margin-bottom: 0.5rem;
                display: block;
            }
            .scp-major-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 0.375rem;
                margin-bottom: 1rem;
            }
            .scp-major-tab {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.35rem 0.75rem;
                font-size: 0.75rem;
                font-weight: 500;
                border-radius: 0.375rem;
                cursor: pointer;
                transition: all 0.15s ease-in-out;
                border: 1px solid #e2e8f0;
                background-color: #f8fafc;
                color: #475569;
                line-height: 1.25;
            }
            .scp-major-tab:hover {
                background-color: #f1f5f9;
                color: #1e293b;
                border-color: #cbd5e1;
            }
            .scp-major-tab.scp-tab-active {
                background-color: #0f172a;
                color: #ffffff;
                border-color: #0f172a;
            }
            .scp-major-subtext {
                font-size: 0.6875rem;
                opacity: 0.75;
            }
            .scp-filter-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 0.75rem;
                padding-top: 0.75rem;
                border-top: 1px solid #e2e8f0;
            }
            @media (max-width: 1024px) {
                .scp-filter-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }
            @media (max-width: 640px) {
                .scp-filter-grid {
                    grid-template-columns: 1fr;
                }
            }
            .scp-select {
                width: 100%;
                font-size: 0.8125rem;
                border-radius: 0.5rem;
                border: 1px solid #d1d5db;
                background-color: #ffffff;
                color: #0f172a;
                padding: 0.45rem 0.75rem;
                line-height: 1.25;
            }
            .scp-select:focus {
                outline: none;
                border-color: #2563eb;
                box-shadow: 0 0 0 1px #2563eb;
            }
            .scp-metrics-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 0.75rem;
                margin-bottom: 1.25rem;
            }
            @media (max-width: 900px) {
                .scp-metrics-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }
            @media (max-width: 480px) {
                .scp-metrics-grid {
                    grid-template-columns: 1fr;
                }
            }
            .scp-metric-card {
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 0.5rem;
                padding: 0.85rem 1rem;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            }
            .scp-metric-label {
                font-size: 0.75rem;
                font-weight: 500;
                color: #64748b;
                margin-bottom: 0.35rem;
            }
            .scp-metric-val-row {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 0.5rem;
            }
            .scp-metric-number {
                font-size: 1.5rem;
                font-weight: 600;
                letter-spacing: -0.02em;
                font-variant-numeric: tabular-nums;
                color: #0f172a;
                line-height: 1;
            }
            .scp-metric-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                font-size: 0.6875rem;
                font-weight: 500;
            }
            .scp-dot {
                width: 6px;
                height: 6px;
                border-radius: 9999px;
                flex-shrink: 0;
            }
            .scp-dot-neutral { background-color: #94a3b8; }
            .scp-dot-success { background-color: #10b981; }
            .scp-dot-warning { background-color: #f59e0b; }
            .scp-dot-danger { background-color: #f43f5e; }

            .scp-table-container {
                border-radius: 0.5rem;
                border: 1px solid #e2e8f0;
                overflow-x: auto;
                background-color: #ffffff;
            }
            .scp-table {
                width: 100%;
                text-align: left;
                border-collapse: collapse;
                font-size: 0.8125rem;
            }
            .scp-table thead {
                background-color: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
            }
            .scp-table th {
                padding: 0.65rem 0.85rem;
                font-size: 0.6875rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #64748b;
            }
            .scp-table td {
                padding: 0.65rem 0.85rem;
                border-bottom: 1px solid #f1f5f9;
                color: #334155;
                vertical-align: middle;
            }
            .scp-table tbody tr:last-child td {
                border-bottom: none;
            }
            .scp-table tbody tr:hover td {
                background-color: #f8fafc;
            }
            .scp-time-val {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
                font-size: 0.75rem;
                font-weight: 500;
                letter-spacing: 0.02em;
                color: #475569;
                white-space: nowrap;
            }
            .scp-class-title {
                font-weight: 600;
                color: #0f172a;
            }
            .scp-major-pill {
                display: inline-block;
                padding: 0.1rem 0.4rem;
                font-size: 0.6875rem;
                font-weight: 500;
                border-radius: 0.25rem;
                background-color: #f1f5f9;
                color: #475569;
                border: 1px solid #e2e8f0;
                margin-left: 0.35rem;
            }
            .scp-subject-title {
                font-weight: 500;
                color: #0f172a;
            }
            .scp-teacher-name {
                color: #334155;
            }
            .scp-room-val {
                color: #64748b;
                font-size: 0.75rem;
            }
            .scp-status-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.2rem 0.55rem;
                border-radius: 0.375rem;
                font-size: 0.75rem;
                font-weight: 500;
                white-space: nowrap;
            }
            .scp-status-success {
                background-color: #f0fdf4;
                color: #15803d;
                border: 1px solid #bbf7d0;
            }
            .scp-status-warning {
                background-color: #fefce8;
                color: #854d0e;
                border: 1px solid #fef08a;
            }
            .scp-status-danger {
                background-color: #fff1f2;
                color: #be123c;
                border: 1px solid #fecdd3;
            }
            .scp-empty-state {
                text-align: center;
                padding: 2.5rem 1rem;
                border: 1px dashed #cbd5e1;
                border-radius: 0.5rem;
                background-color: #f8fafc;
            }
            .scp-empty-title {
                font-size: 0.875rem;
                font-weight: 600;
                color: #334155;
                margin-top: 0.5rem;
            }
            .scp-empty-desc {
                font-size: 0.75rem;
                color: #64748b;
                margin-top: 0.25rem;
            }

            /* DARK MODE OVERRIDES */
            html.dark .scp-major-tab,
            .dark .scp-major-tab {
                background-color: rgba(255, 255, 255, 0.04);
                border-color: rgba(255, 255, 255, 0.08);
                color: #94a3b8;
            }
            html.dark .scp-major-tab:hover,
            .dark .scp-major-tab:hover {
                background-color: rgba(255, 255, 255, 0.08);
                color: #f1f5f9;
            }
            html.dark .scp-major-tab.scp-tab-active,
            .dark .scp-major-tab.scp-tab-active {
                background-color: #f8fafc;
                color: #0f172a;
                border-color: #f8fafc;
            }
            html.dark .scp-filter-grid,
            .dark .scp-filter-grid {
                border-top-color: rgba(255, 255, 255, 0.08);
            }
            html.dark .scp-select,
            .dark .scp-select {
                background-color: #111827;
                border-color: rgba(255, 255, 255, 0.12);
                color: #f1f5f9;
            }
            html.dark .scp-metric-card,
            .dark .scp-metric-card {
                background-color: rgba(255, 255, 255, 0.02);
                border-color: rgba(255, 255, 255, 0.07);
            }
            html.dark .scp-metric-label,
            .dark .scp-metric-label {
                color: #94a3b8;
            }
            html.dark .scp-metric-number,
            .dark .scp-metric-number {
                color: #f8fafc;
            }
            html.dark .scp-table-container,
            .dark .scp-table-container {
                background-color: #111827;
                border-color: rgba(255, 255, 255, 0.08);
            }
            html.dark .scp-table thead,
            .dark .scp-table thead {
                background-color: rgba(255, 255, 255, 0.03);
                border-bottom-color: rgba(255, 255, 255, 0.08);
            }
            html.dark .scp-table th,
            .dark .scp-table th {
                color: #94a3b8;
            }
            html.dark .scp-table td,
            .dark .scp-table td {
                border-bottom-color: rgba(255, 255, 255, 0.04);
                color: #cbd5e1;
            }
            html.dark .scp-table tbody tr:hover td,
            .dark .scp-table tbody tr:hover td {
                background-color: rgba(255, 255, 255, 0.03);
            }
            html.dark .scp-class-title,
            .dark .scp-class-title,
            html.dark .scp-subject-title,
            .dark .scp-subject-title {
                color: #f8fafc;
            }
            html.dark .scp-teacher-name,
            .dark .scp-teacher-name {
                color: #e2e8f0;
            }
            html.dark .scp-major-pill,
            .dark .scp-major-pill {
                background-color: rgba(255, 255, 255, 0.05);
                border-color: rgba(255, 255, 255, 0.1);
                color: #cbd5e1;
            }
            html.dark .scp-time-val,
            .dark .scp-time-val,
            html.dark .scp-room-val,
            .dark .scp-room-val {
                color: #94a3b8;
            }
            html.dark .scp-status-success,
            .dark .scp-status-success {
                background-color: rgba(16, 185, 129, 0.12);
                color: #6ee7b7;
                border-color: rgba(16, 185, 129, 0.25);
            }
            html.dark .scp-status-warning,
            .dark .scp-status-warning {
                background-color: rgba(245, 158, 11, 0.12);
                color: #fcd34d;
                border-color: rgba(245, 158, 11, 0.25);
            }
            html.dark .scp-status-danger,
            .dark .scp-status-danger {
                background-color: rgba(244, 63, 94, 0.12);
                color: #fda4af;
                border-color: rgba(244, 63, 94, 0.25);
            }
            html.dark .scp-empty-state,
            .dark .scp-empty-state {
                background-color: rgba(255, 255, 255, 0.02);
                border-color: rgba(255, 255, 255, 0.08);
            }
            html.dark .scp-empty-title,
            .dark .scp-empty-title {
                color: #e2e8f0;
            }
            html.dark .scp-empty-desc,
            .dark .scp-empty-desc {
                color: #94a3b8;
            }
        </style>

        <div class="scp-waka-widget">
            {{-- Filter Section --}}
            <div class="scp-filter-section">
                {{-- Major Tabs/Pills --}}
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <label class="scp-filter-label" style="margin-bottom: 0;">
                            Program keahlian (jurusan)
                        </label>
                        <button
                            type="button"
                            wire:click="resetFilters"
                            class="scp-major-tab"
                            style="padding: 0.25rem 0.65rem; font-size: 0.7rem;"
                            title="Reset semua filter ke default hari ini"
                        >
                            Reset Filter
                        </button>
                    </div>
                    <div class="scp-major-tabs">
                        <button
                            type="button"
                            wire:click="setMajor('ALL')"
                            class="scp-major-tab {{ $selectedMajor === 'ALL' ? 'scp-tab-active' : '' }}"
                        >
                            Semua Jurusan
                        </button>
                        @foreach ($majors as $m)
                            <button
                                type="button"
                                wire:click="setMajor('{{ $m->code }}')"
                                class="scp-major-tab {{ $selectedMajor === $m->code ? 'scp-tab-active' : '' }}"
                            >
                                <span>{{ $m->code }}</span>
                                <span class="scp-major-subtext">({{ $m->name }})</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Secondary Filters: Tahun Ajaran, Tanggal, Tingkat, Kelas, Hari, Putaran, Status Jurnal --}}
                <div class="scp-filter-grid">
                    {{-- Filter Tahun Ajaran --}}
                    <div>
                        <label class="scp-filter-label">
                            Tahun ajaran
                        </label>
                        <select wire:model.live="selectedAcademicYearId" class="scp-select">
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}">
                                    {{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Tanggal --}}
                    <div>
                        <label class="scp-filter-label">
                            Tanggal jurnal
                        </label>
                        <input
                            type="date"
                            wire:model.live="selectedDate"
                            min="{{ $minDate }}"
                            max="{{ $maxDate }}"
                            class="scp-select"
                        />
                        @if ($minDate && $maxDate)
                            <div style="font-size: 0.6875rem; color: #64748b; margin-top: 0.2rem;" class="dark:text-gray-400">
                                Periode: {{ \Carbon\Carbon::parse($minDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($maxDate)->format('d/m/Y') }}
                            </div>
                        @endif
                    </div>

                    {{-- Tingkat Kelas --}}
                    <div>
                        <label class="scp-filter-label">
                            Tingkat / jenjang
                        </label>
                        <select wire:model.live="selectedGrade" class="scp-select">
                            <option value="ALL">Semua Tingkat (X, XI, XII)</option>
                            @foreach ($grades as $g)
                                <option value="{{ $g->name }}">Kelas {{ $g->name }} ({{ $g->description }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Kelas Spesifik --}}
                    <div>
                        <label class="scp-filter-label">
                            Kelas
                        </label>
                        <select wire:model.live="selectedClassroom" class="scp-select">
                            <option value="ALL">Semua Kelas</option>
                            @foreach ($classrooms as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Hari KBM --}}
                    <div>
                        <label class="scp-filter-label">
                            Hari KBM
                        </label>
                        <select wire:model.live="selectedDay" class="scp-select">
                            @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Siklus Putaran --}}
                    <div>
                        <label class="scp-filter-label">
                            Siklus putaran
                        </label>
                        <select wire:model.live="selectedCycle" class="scp-select">
                            <option value="Putaran 1">Putaran 1</option>
                            <option value="Putaran 2">Putaran 2</option>
                        </select>
                    </div>

                    {{-- Filter Status Jurnal --}}
                    <div>
                        <label class="scp-filter-label">
                            Status jurnal
                        </label>
                        <select wire:model.live="selectedStatus" class="scp-select">
                            <option value="ALL">Semua Status Jurnal</option>
                            <option value="filled">Sudah Diisi</option>
                            <option value="unfilled">Belum Diisi</option>
                            <option value="not_filled">Tidak Mengisi Jurnal</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Summary Metrics --}}
            <div class="scp-metrics-grid">
                <div class="scp-metric-card">
                    <div class="scp-metric-label">Total Jadwal</div>
                    <div class="scp-metric-val-row">
                        <div class="scp-metric-number">{{ $totalSchedules }}</div>
                        <div class="scp-metric-badge">
                            <span class="scp-dot scp-dot-neutral"></span>
                            <span class="text-gray-500 dark:text-gray-400 font-medium">{{ $selectedMajor === 'ALL' ? 'Semua' : $selectedMajor }}</span>
                        </div>
                    </div>
                </div>

                <div class="scp-metric-card">
                    <div class="scp-metric-label">Jurnal Terisi</div>
                    <div class="scp-metric-val-row">
                        <div class="scp-metric-number">{{ $filledCount }}</div>
                        <div class="scp-metric-badge">
                            <span class="scp-dot scp-dot-success"></span>
                            <span class="text-emerald-700 dark:text-emerald-400 font-medium">Selesai</span>
                        </div>
                    </div>
                </div>

                <div class="scp-metric-card">
                    <div class="scp-metric-label">Belum Diisi</div>
                    <div class="scp-metric-val-row">
                        <div class="scp-metric-number">{{ $unfilledCount }}</div>
                        <div class="scp-metric-badge">
                            <span class="scp-dot scp-dot-warning"></span>
                            <span class="text-amber-700 dark:text-amber-400 font-medium">Tenggat 1 Hari</span>
                        </div>
                    </div>
                </div>

                <div class="scp-metric-card">
                    <div class="scp-metric-label">Tidak Mengisi Jurnal</div>
                    <div class="scp-metric-val-row">
                        <div class="scp-metric-number {{ $notFilledCount > 0 ? 'text-rose-600 dark:text-rose-400' : '' }}">{{ $notFilledCount }}</div>
                        <div class="scp-metric-badge">
                            <span class="scp-dot {{ $notFilledCount > 0 ? 'scp-dot-danger' : 'scp-dot-neutral' }}"></span>
                            <span class="{{ $notFilledCount > 0 ? 'text-rose-700 dark:text-rose-400 font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $notFilledCount > 0 ? 'Perlu tindakan' : 'Tertib' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table Schedules --}}
            @if ($schedules->isEmpty())
                <div class="scp-empty-state">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="mx-auto h-8 w-8 text-gray-400" />
                    <div class="scp-empty-title">Tidak Ada Jadwal Mengajar Ditemukan</div>
                    <div class="scp-empty-desc">
                        Tidak ada jadwal untuk Tahun Ajaran <strong>{{ $selectedYear?->name ?? '-' }}</strong>, Jurusan <strong>{{ $selectedMajor }}</strong>, Tingkat <strong>{{ $selectedGrade }}</strong> pada hari <strong>{{ $selectedDay }}</strong> ({{ $selectedCycle }}).
                    </div>
                </div>
            @else
                <div class="scp-table-container">
                    <table class="scp-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Jurusan / Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th>Guru Pengajar</th>
                                <th>Ruangan</th>
                                <th style="text-align: center;">Status Jurnal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schedules as $s)
                                <tr>
                                    <td>
                                        <span class="scp-time-val">{{ $s['start_time'] }} - {{ $s['end_time'] }}</span>
                                        @if (! empty($s['session_label']))
                                            <div style="font-size: 0.6875rem; color: #6b7280; margin-top: 1px;" class="dark:text-gray-400 font-medium">
                                                {{ $s['session_label'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="scp-class-title">{{ $s['classroom_name'] }}</span>
                                        @if ($s['major_code'] !== '-')
                                            <span class="scp-major-pill">{{ $s['major_code'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="scp-subject-title">{{ $s['subject_name'] }}</span>
                                    </td>
                                    <td>
                                        <span class="scp-teacher-name">{{ $s['teacher_name'] }}</span>
                                    </td>
                                    <td>
                                        <span class="scp-room-val">{{ $s['room_name'] }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        @if ($s['has_journal'])
                                            <span class="scp-status-pill scp-status-success">
                                                <span class="scp-dot scp-dot-success"></span>
                                                Sudah diisi
                                            </span>
                                        @elseif ($s['is_expired'] ?? $s['is_overdue'])
                                            <span class="scp-status-pill scp-status-danger">
                                                <span class="scp-dot scp-dot-danger"></span>
                                                Tidak mengisi jurnal
                                            </span>
                                        @else
                                            <span class="scp-status-pill scp-status-warning">
                                                <span class="scp-dot scp-dot-warning"></span>
                                                Belum diisi
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
