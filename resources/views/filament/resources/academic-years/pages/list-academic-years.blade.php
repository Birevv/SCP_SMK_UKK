<x-filament-panels::page>
    <style>
        /* Pill Kategori Interaktif */
        .scp-legend-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            border: 1px solid;
            user-select: none;
        }
        .scp-legend-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 9999px;
            flex-shrink: 0;
        }

        .scp-pill-libur_nasional { background-color: #fee2e2; color: #991b1b; border-color: #fca5a5; }
        .scp-pill-libur_nasional .scp-legend-dot { background-color: #dc2626; }

        .scp-pill-libur_sekolah { background-color: #fef3c7; color: #92400e; border-color: #fcd34d; }
        .scp-pill-libur_sekolah .scp-legend-dot { background-color: #d97706; }

        .scp-pill-upacara { background-color: #ffe4e6; color: #9f1239; border-color: #fda4af; }
        .scp-pill-upacara .scp-legend-dot { background-color: #e11d48; }

        .scp-pill-ujian { background-color: #e0f2fe; color: #075985; border-color: #7dd3fc; }
        .scp-pill-ujian .scp-legend-dot { background-color: #0284c7; }

        .scp-pill-pkl { background-color: #ccfbf1; color: #115e59; border-color: #5eead4; }
        .scp-pill-pkl .scp-legend-dot { background-color: #0d9488; }

        .scp-pill-ukk { background-color: #f3e8ff; color: #6b21a8; border-color: #d8b4fe; }
        .scp-pill-ukk .scp-legend-dot { background-color: #9333ea; }

        .scp-pill-kegiatan_siswa { background-color: #d1fae5; color: #065f46; border-color: #6ee7b7; }
        .scp-pill-kegiatan_siswa .scp-legend-dot { background-color: #10b981; }

        .scp-pill-rapat { background-color: #e0e7ff; color: #3730a3; border-color: #a5b4fc; }
        .scp-pill-rapat .scp-legend-dot { background-color: #4f46e5; }

        .scp-pill-lainnya { background-color: #f3f4f6; color: #374151; border-color: #d1d5db; }
        .scp-pill-lainnya .scp-legend-dot { background-color: #6b7280; }

        /* Dark mode overrides for category pills */
        .dark .scp-pill-libur_nasional { background-color: rgba(239, 68, 68, 0.2); color: #fca5a5; border-color: rgba(239, 68, 68, 0.4); }
        .dark .scp-pill-libur_sekolah { background-color: rgba(245, 158, 11, 0.2); color: #fde68a; border-color: rgba(245, 158, 11, 0.4); }
        .dark .scp-pill-upacara { background-color: rgba(244, 63, 94, 0.2); color: #fda4af; border-color: rgba(244, 63, 94, 0.4); }
        .dark .scp-pill-ujian { background-color: rgba(14, 165, 233, 0.2); color: #7dd3fc; border-color: rgba(14, 165, 233, 0.4); }
        .dark .scp-pill-pkl { background-color: rgba(20, 184, 166, 0.2); color: #5eead4; border-color: rgba(20, 184, 166, 0.4); }
        .dark .scp-pill-ukk { background-color: rgba(168, 85, 247, 0.2); color: #d8b4fe; border-color: rgba(168, 85, 247, 0.4); }
        .dark .scp-pill-kegiatan_siswa { background-color: rgba(16, 185, 129, 0.2); color: #6ee7b7; border-color: rgba(16, 185, 129, 0.4); }
        .dark .scp-pill-rapat { background-color: rgba(99, 102, 241, 0.2); color: #a5b4fc; border-color: rgba(99, 102, 241, 0.4); }
        .dark .scp-pill-lainnya { background-color: rgba(107, 114, 128, 0.2); color: #d1d5db; border-color: rgba(107, 114, 128, 0.4); }

        /* Agenda 1 Hari: Color Dot */
        .scp-dot-libur_nasional { background-color: #dc2626; }
        .scp-dot-libur_sekolah  { background-color: #d97706; }
        .scp-dot-upacara        { background-color: #e11d48; }
        .scp-dot-ujian          { background-color: #0284c7; }
        .scp-dot-pkl            { background-color: #0d9488; }
        .scp-dot-ukk            { background-color: #9333ea; }
        .scp-dot-kegiatan_siswa { background-color: #10b981; }
        .scp-dot-rapat          { background-color: #4f46e5; }
        .scp-dot-lainnya        { background-color: #6b7280; }

        /* Agenda Multi-Hari: Solid Colored Bar dengan Teks Putih */
        .scp-solid-libur_nasional { background-color: #dc2626; color: #ffffff !important; }
        .scp-solid-libur_sekolah  { background-color: #d97706; color: #ffffff !important; }
        .scp-solid-upacara        { background-color: #e11d48; color: #ffffff !important; }
        .scp-solid-ujian          { background-color: #0284c7; color: #ffffff !important; }
        .scp-solid-pkl            { background-color: #0d9488; color: #ffffff !important; }
        .scp-solid-ukk            { background-color: #9333ea; color: #ffffff !important; }
        .scp-solid-kegiatan_siswa { background-color: #10b981; color: #ffffff !important; }
        .scp-solid-rapat          { background-color: #4f46e5; color: #ffffff !important; }
        .scp-solid-lainnya        { background-color: #6b7280; color: #ffffff !important; }

        /* Print Settings */
        @media print {
            body * {
                visibility: hidden !important;
            }
            #scp-calendar-print-area, #scp-calendar-print-area * {
                visibility: visible !important;
            }
            #scp-calendar-print-area {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                width: 100vw !important;
                height: 100vh !important;
                margin: 0 !important;
                padding: 12px !important;
                background: #ffffff !important;
                color: #000000 !important;
                z-index: 999999 !important;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: landscape;
                margin: 6mm;
            }
        }
    </style>

    <div class="space-y-4">
        
        <!-- 1. COMPACT HEADER & TABS (SATU BARIS RINGKAS) -->
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap;" class="no-print">
            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background-color: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="w-5 h-5" />
                    </div>
                    <h1 style="font-size: 1.15rem; font-weight: 700; color: #111827; margin: 0;">
                        Kalender Akademik
                    </h1>
                </div>

                <!-- Navigasi Tab (gap-2 dan px-3 py-1.5) -->
                <div style="display: inline-flex; align-items: center; padding: 4px; gap: 8px; background: #f3f4f6; border-radius: 12px;">
                    <button
                        type="button"
                        wire:click="switchTab('calendar')"
                        style="padding: 6px 12px; font-size: 0.8125rem; font-weight: 600; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.15s; {{ $currentViewTab === 'calendar' ? 'background: #ffffff; color: #2563eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05);' : 'background: transparent; color: #6b7280;' }}">
                        <x-filament::icon icon="heroicon-o-calendar" class="w-4 h-4" />
                        <span>Kalender Akademik</span>
                    </button>

                    <button
                        type="button"
                        wire:click="switchTab('years')"
                        style="padding: 6px 12px; font-size: 0.8125rem; font-weight: 600; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.15s; {{ $currentViewTab === 'years' ? 'background: #ffffff; color: #2563eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05);' : 'background: transparent; color: #6b7280;' }}">
                        <x-filament::icon icon="heroicon-o-table-cells" class="w-4 h-4" />
                        <span>Data Tahun Ajaran</span>
                        <span style="padding: 1px 6px; font-size: 0.65rem; border-radius: 9999px; background: #e5e7eb; color: #374151; font-weight: 700;">
                            {{ $this->academicYears->count() }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Sisi Kanan Header: Action Button / Badge Status Periode Aktif -->
            <div style="display: flex; align-items: center; gap: 8px;">
                @if ($currentViewTab === 'years')
                    <a
                        href="{{ \App\Filament\Resources\AcademicYears\AcademicYearResource::getUrl('create') }}"
                        style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; border-radius: 8px; background-color: #2563eb; color: #ffffff; font-size: 0.875rem; font-weight: 600; text-decoration: none;">
                        <x-filament::icon icon="heroicon-m-plus" class="w-4 h-4" />
                        <span>Buat Tahun Ajaran</span>
                    </a>
                @endif

                @if ($this->selectedYear)
                    <div style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.8125rem; font-weight: 500; color: #4b5563; background: #f9fafb; padding: 6px 12px; border-radius: 8px; border: 1px solid #e5e7eb;">
                        <span style="width: 8px; height: 8px; border-radius: 9999px; background-color: {{ $this->selectedYear->is_active ? '#10b981' : '#9ca3af' }};"></span>
                        <span>Tahun Ajaran Aktif: <strong style="color: #111827;">{{ $this->selectedYear->name }}</strong></span>
                        @if ($this->selectedYear->start_date && $this->selectedYear->end_date)
                            <span style="color: #9ca3af;">({{ $this->selectedYear->start_date->format('d M Y') }} - {{ $this->selectedYear->end_date->format('d M Y') }})</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @if ($currentViewTab === 'years')
            <!-- TAB 2: DATA TAHUN AJARAN (TABEL RESMI FILAMENT) -->
            <div class="mt-2">
                {{ $this->table }}
            </div>
        @else
            <!-- TAB 1: KALENDER AKADEMIK -->
            <div class="space-y-4">
                
                <!-- 2. SATU BARIS TOOLBAR (FILTER DI KIRI & TOMBOL AKSI DI KANAN) -->
                <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 10px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);" class="no-print">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                        <!-- Sisi Kiri: Filter Dropdowns -->
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <!-- Dropdown Tahun Ajaran / Semester (Tanpa Bintang) -->
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">
                                    Tahun Ajaran / Semester:
                                </label>
                                <select
                                    wire:model.live="selectedYearId"
                                    style="border: 1px solid #d1d5db; border-radius: 8px; padding: 6px 12px; font-size: 0.875rem; font-weight: 600; color: #111827; background: #ffffff; outline: none;">
                                    @foreach ($this->academicYears as $year)
                                        <option value="{{ $year->id }}">
                                            {{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Dropdown Target Tingkat Siswa -->
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">
                                    Target Tingkat Siswa:
                                </label>
                                <select
                                    wire:model.live="selectedGrade"
                                    style="border: 1px solid #d1d5db; border-radius: 8px; padding: 6px 12px; font-size: 0.875rem; font-weight: 500; color: #111827; background: #ffffff; outline: none;">
                                    <option value="">Semua Tingkat</option>
                                    <option value="X">Khusus Kelas X</option>
                                    <option value="XI">Khusus Kelas XI</option>
                                    <option value="XII">Khusus Kelas XII</option>
                                </select>
                            </div>
                        </div>

                        <!-- Sisi Kanan: Action Buttons (dengan padding dan gaya eksplisit) -->
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            @if ($this->canManage())
                                <button
                                    type="button"
                                    wire:click="openSyncModal"
                                    style="padding: 8px 14px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; border-radius: 8px; border: 1px solid #d1d5db; background: #ffffff; color: #374151; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                                    <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4 text-primary-500" />
                                    <span>Tarik Libur Nasional</span>
                                </button>
                            @endif

                            <button
                                type="button"
                                onclick="window.print()"
                                style="padding: 8px 14px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; border-radius: 8px; border: 1px solid #d1d5db; background: #ffffff; color: #374151; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                                <x-filament::icon icon="heroicon-o-printer" class="w-4 h-4 text-gray-500" />
                                <span>Export / Cetak PDF</span>
                            </button>

                            @if ($this->canManage())
                                <button
                                    type="button"
                                    wire:click="openCreateEventModal"
                                    style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; border-radius: 8px; background-color: #2563eb; color: #ffffff; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer;">
                                    <x-filament::icon icon="heroicon-m-plus" class="w-4 h-4" />
                                    <span>Tambah Agenda</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 3. CONTAINER CARD PUTIH MENYATU (FULLCALENDAR CLEAN CARD LAYOUT) -->
                <div id="scp-calendar-print-area" style="background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);" class="space-y-4">
                    
                    <!-- 1. TOOLBAR NAVIGASI BULAN SEJAJAR 1 BARIS (KIRI - TENGAH - KANAN) -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px;" class="no-print">
                        
                        <!-- Kiri: Grup tombol < > dan Hari Ini -->
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="display: inline-flex; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; background: #ffffff;">
                                <button
                                    type="button"
                                    wire:click="prevMonth"
                                    title="Bulan Sebelumnya"
                                    style="padding: 6px 12px; border: none; border-right: 1px solid #d1d5db; background: transparent; cursor: pointer; display: flex; align-items: center;">
                                    <x-filament::icon icon="heroicon-m-chevron-left" class="w-4 h-4 text-gray-600" />
                                </button>
                                <button
                                    type="button"
                                    wire:click="nextMonth"
                                    title="Bulan Berikutnya"
                                    style="padding: 6px 12px; border: none; background: transparent; cursor: pointer; display: flex; align-items: center;">
                                    <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4 text-gray-600" />
                                </button>
                            </div>

                            <button
                                type="button"
                                wire:click="goToToday"
                                style="padding: 6px 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #ffffff; font-size: 0.875rem; font-weight: 600; color: #374151; cursor: pointer;">
                                Hari Ini
                            </button>
                        </div>

                        <!-- Tengah: Judul Bulan dan Tahun -->
                        <div style="text-align: center;">
                            <h2 style="font-size: 1.35rem; font-weight: 700; color: #1f2937; margin: 0;">
                                {{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->locale('id')->translatedFormat('F Y') }}
                            </h2>
                            @if ($this->isOutOfPeriod)
                                <div style="margin-top: 4px; display: inline-flex; align-items: center; gap: 6px; padding: 2px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d;">
                                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-3.5 h-3.5 text-amber-600 shrink-0" />
                                    <span>Di luar rentang semester</span>
                                    <button type="button" wire:click="resetToActivePeriod" style="text-decoration: underline; font-weight: 700; color: #78350f; background: none; border: none; cursor: pointer; padding: 0;">
                                        Kembali ke Periode Aktif
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Kanan: Input Pencarian dan Switcher -->
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <!-- Input Cari agenda... -->
                            <div style="position: relative; display: flex; align-items: center;">
                                <input
                                    type="text"
                                    wire:model.live.debounce.250ms="searchQuery"
                                    placeholder="Cari agenda..."
                                    style="border: 1px solid #d1d5db; border-radius: 8px; padding: 6px 12px; font-size: 0.875rem; width: 170px; outline: none; background: #ffffff;" />
                                @if (filled($searchQuery))
                                    <button
                                        type="button"
                                        wire:click="$set('searchQuery', '')"
                                        style="position: absolute; right: 8px; background: none; border: none; cursor: pointer; color: #9ca3af; display: flex; align-items: center;">
                                        <x-filament::icon icon="heroicon-m-x-mark" class="w-4 h-4" />
                                    </button>
                                @endif
                            </div>

                            <!-- Switcher Bulan | Minggu | Daftar Agenda -->
                            <div style="display: inline-flex; border: 1px solid #d1d5db; border-radius: 8px; padding: 2px; background: #f3f4f6;">
                                <button
                                    type="button"
                                    wire:click="$set('viewMode', 'month')"
                                    style="padding: 4px 12px; font-size: 0.75rem; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; {{ $viewMode === 'month' ? 'background: #2563eb; color: #ffffff;' : 'background: transparent; color: #4b5563;' }}">
                                    Bulan
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('viewMode', 'week')"
                                    style="padding: 4px 12px; font-size: 0.75rem; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; {{ $viewMode === 'week' ? 'background: #2563eb; color: #ffffff;' : 'background: transparent; color: #4b5563;' }}">
                                    Minggu
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('viewMode', 'list')"
                                    style="padding: 4px 12px; font-size: 0.75rem; font-weight: 600; border-radius: 6px; border: none; cursor: pointer; {{ $viewMode === 'list' ? 'background: #2563eb; color: #ffffff;' : 'background: transparent; color: #4b5563;' }}">
                                    Daftar Agenda
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- LEGENDA FILTER KATEGORI (PILL KECIL SATU BARIS DENGAN TITIK WARNA DAN NAMA SINGKAT) -->
                    <div style="display: flex; align-items: center; gap: 6px; overflow-x: auto; padding: 6px 0; border-top: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6;" class="no-print">
                        <span style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-right: 4px; flex-shrink: 0;">
                            Kategori:
                        </span>
                        @foreach (\App\Enums\AcademicEventCategory::cases() as $cat)
                            @php
                                $isActive = empty($selectedCategories) || in_array($cat->value, $selectedCategories, true);
                                $count = $this->categoryCounts[$cat->value] ?? 0;
                            @endphp
                            <button
                                type="button"
                                wire:click="toggleCategoryFilter('{{ $cat->value }}')"
                                title="Klik untuk filter {{ $cat->getLabel() }}"
                                class="scp-legend-pill scp-pill-{{ $cat->value }} {{ $isActive ? 'opacity-100 ring-1 ring-primary-500/70 shadow-2xs' : 'opacity-35 grayscale-[25%] hover:opacity-75' }}">
                                <span class="scp-legend-dot"></span>
                                <span class="whitespace-nowrap">{{ $cat->getShortLabel() }}</span>
                                <span style="padding: 1px 6px; border-radius: 9999px; font-size: 0.65rem; background: rgba(255,255,255,0.8); font-weight: 700; margin-left: 2px;">
                                    {{ $count }}
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <!-- 5. TAMPILAN UTAMA KALENDER (BULAN / MINGGU / DAFTAR AGENDA) -->
                    @if ($viewMode === 'list')
                        <!-- LIST VIEW: DAFTAR AGENDA BULAN INI -->
                        <div style="border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px;" class="space-y-3">
                            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f3f4f6; padding-bottom: 8px;">
                                <h3 style="font-size: 0.875rem; font-weight: 700; color: #111827; margin: 0;">
                                    Agenda Bulan {{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->locale('id')->translatedFormat('F Y') }}
                                    <span style="font-size: 0.75rem; color: #6b7280; font-weight: 400;">({{ $this->monthEvents->count() }} agenda ditemukan)</span>
                                </h3>
                            </div>

                            @if ($this->monthEvents->isEmpty())
                                <div style="text-align: center; padding: 48px 16px; color: #9ca3af;">
                                    <x-filament::icon icon="heroicon-o-calendar" class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                                    <p style="font-size: 0.875rem; font-weight: 500;">Tidak ada agenda akademik yang sesuai dengan filter bulan ini.</p>
                                </div>
                            @else
                                <div class="space-y-2">
                                    @foreach ($this->monthEvents as $item)
                                        @php
                                            $isMultiDay = $item->start_date->ne($item->end_date);
                                        @endphp
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb; background: #fafafa;">
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 48px; text-align: center; padding: 6px; background: #ffffff; border-radius: 8px; border: 1px solid #d1d5db; flex-shrink: 0;">
                                                    <div style="font-size: 0.625rem; font-weight: 700; color: #9ca3af; text-transform: uppercase;">{{ $item->start_date->translatedFormat('M') }}</div>
                                                    <div style="font-size: 1.125rem; font-weight: 800; color: #111827; line-height: 1.2;">{{ $item->start_date->day }}</div>
                                                </div>

                                                <div class="space-y-1">
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold scp-solid-{{ $item->category instanceof \App\Enums\AcademicEventCategory ? $item->category->value : $item->category }}">
                                                            {{ $item->category instanceof \App\Enums\AcademicEventCategory ? $item->category->getShortLabel() : $item->category }}
                                                        </span>
                                                        @if ($item->target_grades)
                                                            <span style="padding: 2px 8px; font-size: 0.625rem; font-weight: 600; background: #e5e7eb; color: #374151; border-radius: 4px;">
                                                                Kelas {{ implode(', ', $item->target_grades) }}
                                                            </span>
                                                        @endif
                                                        <span style="font-size: 0.75rem; color: #6b7280;">
                                                            {{ $item->start_date->format('d M Y') }}
                                                            @if ($isMultiDay)
                                                                s/d {{ $item->end_date->format('d M Y') }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <h4 style="font-size: 0.875rem; font-weight: 700; color: #111827; margin: 0;">
                                                        {{ $item->title }}
                                                    </h4>
                                                    @if ($item->description)
                                                        <p style="font-size: 0.75rem; color: #6b7280; margin: 0;">
                                                            {{ $item->description }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($this->canManage())
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <button
                                                        type="button"
                                                        wire:click="openEditEventModal({{ $item->id }})"
                                                        style="padding: 6px; border: none; background: transparent; color: #4b5563; cursor: pointer; border-radius: 6px;"
                                                        title="Edit Agenda">
                                                        <x-filament::icon icon="heroicon-m-pencil-square" class="w-4 h-4" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        wire:click="deleteEvent({{ $item->id }})"
                                                        wire:confirm="Apakah Anda yakin ingin menghapus agenda ini?"
                                                        style="padding: 6px; border: none; background: transparent; color: #ef4444; cursor: pointer; border-radius: 6px;"
                                                        title="Hapus Agenda">
                                                        <x-filament::icon icon="heroicon-m-trash" class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <!-- 2. TABEL GRID KALENDER (BORDER EKSPLISIT & TINGGI 105PX, TANPA TANDA + APAPUN) -->
                        <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
                            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                                <thead>
                                    <tr style="background-color: #f9fafb;">
                                        <th style="border: 1px solid #e5e7eb; height: auto; vertical-align: middle; padding: 10px; text-align: center; font-size: 0.8125rem; font-weight: 700; color: #374151; width: 14.285%;">Senin</th>
                                        <th style="border: 1px solid #e5e7eb; height: auto; vertical-align: middle; padding: 10px; text-align: center; font-size: 0.8125rem; font-weight: 700; color: #374151; width: 14.285%;">Selasa</th>
                                        <th style="border: 1px solid #e5e7eb; height: auto; vertical-align: middle; padding: 10px; text-align: center; font-size: 0.8125rem; font-weight: 700; color: #374151; width: 14.285%;">Rabu</th>
                                        <th style="border: 1px solid #e5e7eb; height: auto; vertical-align: middle; padding: 10px; text-align: center; font-size: 0.8125rem; font-weight: 700; color: #374151; width: 14.285%;">Kamis</th>
                                        <th style="border: 1px solid #e5e7eb; height: auto; vertical-align: middle; padding: 10px; text-align: center; font-size: 0.8125rem; font-weight: 700; color: #374151; width: 14.285%;">Jumat</th>
                                        <th style="border: 1px solid #e5e7eb; height: auto; vertical-align: middle; padding: 10px; text-align: center; font-size: 0.8125rem; font-weight: 700; color: #374151; width: 14.285%;">Sabtu</th>
                                        <th style="border: 1px solid #e5e7eb; height: auto; vertical-align: middle; padding: 10px; text-align: center; font-size: 0.8125rem; font-weight: 700; color: #ef4444; width: 14.285%;">Minggu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->calendarWeeks as $week)
                                        <tr>
                                            @foreach ($week['days'] as $day)
                                                @php
                                                    $cellBgStyle = 'background-color: #ffffff;';
                                                    if ($day['is_today']) {
                                                        $cellBgStyle = 'background-color: #eff6ff;'; // Soft blue highlight untuk Hari Ini
                                                    } elseif (! $day['is_current_month']) {
                                                        $cellBgStyle = 'background-color: #f9fafb;'; // Redup untuk tanggal di luar bulan
                                                    }
                                                @endphp
                                                <td
                                                    style="border: 1px solid #e5e7eb; height: 105px; min-height: 105px; vertical-align: top; padding: 8px 10px; position: relative; {{ $cellBgStyle }}"
                                                    @if ($this->canManage())
                                                        wire:click="openCreateEventModal('{{ $day['date'] }}')"
                                                    @endif
                                                    class="align-top transition duration-150 hover:bg-blue-50/40 cursor-pointer">
                                                    
                                                    <!-- Top Row: Angka Tanggal Saja di Pojok Kanan Atas (Tanda + SUDAH DIHAPUS SEPENUHNYA DARI KODE) -->
                                                    <div style="display: flex; justify-content: flex-end; margin-bottom: 6px; pointer-events: none;">
                                                        <span style="font-size: 0.875rem; font-weight: 600; {{ $day['is_today'] ? 'color: #2563eb; font-weight: 800;' : ($day['is_sunday'] ? 'color: #ef4444; font-weight: 800;' : ($day['is_current_month'] ? 'color: #1f2937;' : 'color: #9ca3af;')) }}">
                                                            {{ $day['day_number'] }}
                                                        </span>
                                                    </div>

                                                    <!-- Container Daftar Agenda Sel -->
                                                    <div style="display: flex; flex-direction: column; gap: 4px; overflow-y: auto; max-height: 72px; scrollbar-width: none;">
                                                        @foreach ($day['events'] as $event)
                                                            @php
                                                                $isMultiDay = $event->start_date->ne($event->end_date);
                                                            @endphp
                                                            <div
                                                                x-data="{ showPopover: false }"
                                                                @mouseenter="showPopover = true"
                                                                @mouseleave="showPopover = false"
                                                                style="position: relative;"
                                                                class="pointer-events-auto">
                                                                
                                                                @if ($isMultiDay)
                                                                    <!-- Multi-day Event: Pita / Bar Berwarna Solid Sesuai Kategori dengan Teks Putih -->
                                                                    <button
                                                                        type="button"
                                                                        wire:click.stop="openEditEventModal({{ $event->id }})"
                                                                        class="w-full text-left rounded px-2 py-0.5 text-xs font-medium text-white truncate block transition shadow-2xs hover:brightness-95 scp-solid-{{ $event->category instanceof \App\Enums\AcademicEventCategory ? $event->category->value : $event->category }}">
                                                                        @if ($event->target_grades)
                                                                            <span style="font-weight: 700; font-size: 0.625rem; opacity: 0.9;">[{{ implode(',', $event->target_grades) }}]</span>
                                                                        @endif
                                                                        <span>{{ $event->title }}</span>
                                                                    </button>
                                                                @else
                                                                    <!-- 1-day Event: Titik Warna Kategori (Dot) Diikuti Teks Singkat Satu Baris -->
                                                                    <button
                                                                        type="button"
                                                                        wire:click.stop="openEditEventModal({{ $event->id }})"
                                                                        style="width: 100%; display: flex; align-items: center; gap: 6px; text-align: left; font-size: 0.75rem; font-weight: 500; color: #1f2937; padding: 2px 4px; border-radius: 4px; border: none; background: transparent; cursor: pointer;">
                                                                        <span class="w-2 h-2 rounded-full shrink-0 scp-dot-{{ $event->category instanceof \App\Enums\AcademicEventCategory ? $event->category->value : $event->category }}"></span>
                                                                        @if ($event->target_grades)
                                                                            <span style="font-size: 0.625rem; font-weight: 700; color: #6b7280; flex-shrink: 0;">[{{ implode(',', $event->target_grades) }}]</span>
                                                                        @endif
                                                                        <span class="truncate">{{ $event->title }}</span>
                                                                    </button>
                                                                @endif

                                                                <!-- Popover Tooltip Saat Hover -->
                                                                <div
                                                                    x-show="showPopover"
                                                                    x-transition:enter="transition ease-out duration-150"
                                                                    x-transition:enter-start="opacity-0 scale-95"
                                                                    x-transition:enter-end="opacity-100 scale-100"
                                                                    x-transition:leave="transition ease-in duration-100"
                                                                    x-transition:leave-start="opacity-100 scale-100"
                                                                    x-transition:leave-end="opacity-0 scale-95"
                                                                    x-cloak
                                                                    style="position: absolute; z-index: 50; left: 50%; transform: translateX(-50%); bottom: calc(100% + 6px); width: 250px; padding: 12px; background: #ffffff; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; font-size: 0.75rem; color: #1f2937; pointer-events: none;"
                                                                    class="no-print">
                                                                    <div style="font-weight: 700; font-size: 0.875rem; color: #111827; margin-bottom: 4px; line-height: 1.25;">
                                                                        {{ $event->title }}
                                                                    </div>
                                                                    <div style="display: flex; align-items: center; gap: 6px; font-size: 0.6875rem; color: #6b7280; margin-bottom: 8px;">
                                                                        <x-filament::icon icon="heroicon-o-calendar" class="w-3.5 h-3.5 text-primary-500" />
                                                                        <span>
                                                                            {{ $event->start_date->format('d M Y') }}
                                                                            @if ($isMultiDay)
                                                                                s/d {{ $event->end_date->format('d M Y') }}
                                                                            @endif
                                                                        </span>
                                                                    </div>
                                                                    <div style="display: flex; flex-direction: column; gap: 4px; font-size: 0.6875rem;">
                                                                        <div>
                                                                            <span style="color: #6b7280;">Kategori:</span>
                                                                            <span style="font-weight: 600;">{{ $event->category instanceof \App\Enums\AcademicEventCategory ? $event->category->getLabel() : $event->category }}</span>
                                                                        </div>
                                                                        @if ($event->target_grades)
                                                                            <div>
                                                                                <span style="color: #6b7280;">Target Siswa:</span>
                                                                                <span style="font-weight: 600;">Kelas {{ implode(', ', $event->target_grades) }}</span>
                                                                            </div>
                                                                        @endif
                                                                        <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px; padding-top: 4px; border-top: 1px solid #f3f4f6;">
                                                                            @if ($event->is_holiday)
                                                                                <span style="padding: 2px 6px; border-radius: 4px; font-size: 0.625rem; background: #fee2e2; color: #b91c1c; font-weight: 700;">Libur</span>
                                                                            @endif
                                                                            @if ($event->disable_kbm)
                                                                                <span style="padding: 2px 6px; border-radius: 4px; font-size: 0.625rem; background: #fef3c7; color: #b45309; font-weight: 700;">KBM Nonaktif</span>
                                                                            @endif
                                                                            @if ($event->requires_attendance)
                                                                                <span style="padding: 2px 6px; border-radius: 4px; font-size: 0.625rem; background: #dbeafe; color: #1d4ed8; font-weight: 700;">Wajib Presensi</span>
                                                                            @endif
                                                                        </div>
                                                                        @if ($event->description)
                                                                            <div style="margin-top: 6px; color: #4b5563; font-style: italic; font-size: 0.6875rem;">
                                                                                "{{ Str::limit($event->description, 90) }}"
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- 3, 4, 5. MODAL POPUP TAMBAH / EDIT AGENDA AKADEMIK (TELEPORT KE BODY, BORDER EKSPLISIT) -->
        @if ($showEventModal)
            <div x-teleport="body">
                <div
                    style="position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 16px;"
                    class="no-print">
                    <div
                        style="background: #ffffff; border-radius: 12px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); display: flex; flex-direction: column;">
                        
                        <!-- Header Modal -->
                        <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 16px; border-bottom: 1px solid #e5e7eb; margin-bottom: 20px;">
                            <h3 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px;">
                                <x-filament::icon icon="heroicon-o-calendar" class="w-5 h-5 text-primary-500" />
                                <span>{{ $editingEventId ? 'Edit Agenda Akademik' : 'Tambah Agenda Akademik Baru' }}</span>
                            </h3>
                            <button
                                type="button"
                                wire:click="closeEventModal"
                                style="border: none; background: transparent; color: #9ca3af; cursor: pointer; display: flex; align-items: center;">
                                <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Isi Form Modal (Input dengan border eksplisit) -->
                        <div style="display: flex; flex-direction: column; gap: 16px; flex: 1;">
                            <!-- Judul Agenda -->
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #374151;">
                                    Judul Agenda <span style="color: #ef4444;">*</span>
                                </label>
                                <input
                                    type="text"
                                    wire:model="eventTitle"
                                    placeholder="Contoh: Praktik Kerja Lapangan (PKL) Gelombang 1"
                                    style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 0.875rem; margin-top: 4px; outline: none; background: #ffffff; color: #111827; box-sizing: border-box;" />
                                @error('eventTitle')
                                    <p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Kategori Agenda -->
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #374151;">
                                    Kategori Agenda <span style="color: #ef4444;">*</span>
                                </label>
                                <select
                                    wire:model.live="eventCategory"
                                    style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 0.875rem; margin-top: 4px; outline: none; background: #ffffff; color: #111827; box-sizing: border-box;">
                                    @foreach (\App\Enums\AcademicEventCategory::cases() as $cat)
                                        <option value="{{ $cat->value }}">{{ $cat->getLabel() }}</option>
                                    @endforeach
                                </select>
                                @error('eventCategory')
                                    <p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Target Tingkat Siswa -->
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #374151;">
                                    Target Tingkat Siswa (Kosongkan jika berlaku untuk semua kelas)
                                </label>
                                <div style="display: flex; align-items: center; gap: 16px; margin-top: 8px;">
                                    @foreach (['X', 'XI', 'XII'] as $grade)
                                        <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.8125rem; font-weight: 600; color: #374151; cursor: pointer;">
                                            <input
                                                type="checkbox"
                                                value="{{ $grade }}"
                                                wire:model="eventTargetGrades"
                                                style="border-radius: 4px; border: 1px solid #d1d5db;" />
                                            <span>Kelas {{ $grade }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- 4. Rentang Tanggal (Grid 1fr 1fr dengan gap 16px) -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #374151;">
                                        Tanggal Mulai <span style="color: #ef4444;">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        wire:model="eventStartDate"
                                        style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 0.875rem; margin-top: 4px; outline: none; background: #ffffff; color: #111827; box-sizing: border-box;" />
                                    @error('eventStartDate')
                                        <p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #374151;">
                                        Tanggal Selesai <span style="color: #ef4444;">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        wire:model="eventEndDate"
                                        style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 0.875rem; margin-top: 4px; outline: none; background: #ffffff; color: #111827; box-sizing: border-box;" />
                                    @error('eventEndDate')
                                        <p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Pengaturan Status & Dampak Operasional -->
                            <div style="background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb; display: flex; flex-direction: column; gap: 8px;">
                                <label style="display: block; font-size: 0.6875rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                                    Dampak Operasional Sekolah:
                                </label>
                                
                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8125rem; font-weight: 500; color: #1f2937; cursor: pointer;">
                                    <input
                                        type="checkbox"
                                        wire:model="eventIsHoliday" />
                                    <span>Tanggal Merah / Libur Resmi</span>
                                </label>

                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8125rem; font-weight: 500; color: #1f2937; cursor: pointer;">
                                    <input
                                        type="checkbox"
                                        wire:model="eventDisableKbm" />
                                    <span>Nonaktifkan KBM (Kegiatan Belajar Mengajar Ditiadakan)</span>
                                </label>

                                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8125rem; font-weight: 500; color: #1f2937; cursor: pointer;">
                                    <input
                                        type="checkbox"
                                        wire:model="eventRequiresAttendance" />
                                    <span>Wajib Presensi Kehadiran Siswa</span>
                                </label>
                            </div>

                            <!-- Deskripsi Agenda -->
                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #374151;">
                                    Deskripsi / Catatan Tambahan
                                </label>
                                <textarea
                                    wire:model="eventDescription"
                                    rows="3"
                                    placeholder="Tuliskan keterangan detail terkait agenda..."
                                    style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 0.875rem; margin-top: 4px; outline: none; background: #ffffff; color: #111827; box-sizing: border-box;"></textarea>
                            </div>
                        </div>

                        <!-- 5. Footer Action Modal (Padding tombol Simpan Agenda eksplisit) -->
                        <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 16px; border-top: 1px solid #e5e7eb; margin-top: 20px;">
                            <div>
                                @if ($editingEventId)
                                    <button
                                        type="button"
                                        wire:click="deleteEvent"
                                        wire:confirm="Hapus agenda akademik ini?"
                                        style="padding: 8px 14px; border: none; background: transparent; color: #ef4444; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                                        Hapus
                                    </button>
                                @endif
                            </div>

                            <div style="display: flex; align-items: center; gap: 10px;">
                                <button
                                    type="button"
                                    wire:click="closeEventModal"
                                    style="padding: 8px 16px; border: 1px solid #d1d5db; border-radius: 8px; background: #ffffff; color: #374151; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    wire:click="saveEvent"
                                    style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; border-radius: 8px; background-color: #2563eb; color: #ffffff; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer;">
                                    <span>{{ $editingEventId ? 'Perbarui Agenda' : 'Simpan Agenda' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- 7. MODAL TARIK LIBUR NASIONAL (TELEPORT KE BODY) -->
        @if ($showSyncModal)
            <div x-teleport="body">
                <div
                    style="position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 16px;"
                    class="no-print"
                    x-data>
                    <div style="background: #ffffff; border-radius: 12px; width: 100%; max-width: 450px; overflow: hidden; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
                        <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 16px; border-bottom: 1px solid #e5e7eb; margin-bottom: 16px;">
                            <h3 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px;">
                                <x-filament::icon icon="heroicon-o-arrow-path" class="w-5 h-5 text-primary-500" />
                                <span>Tarik Libur Nasional Otomatis</span>
                            </h3>
                            <button
                                type="button"
                                wire:click="closeSyncModal"
                                style="border: none; background: transparent; color: #9ca3af; cursor: pointer; display: flex; align-items: center;">
                                <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5" />
                            </button>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
                            <div style="padding: 12px; background: #eff6ff; color: #1e40af; border-radius: 8px; font-size: 0.8125rem; display: flex; align-items: flex-start; gap: 10px; border: 1px solid #bfdbfe;">
                                <x-filament::icon icon="heroicon-o-information-circle" class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                                <div class="space-y-1">
                                    <p style="font-weight: 700; margin: 0;">Sinkronisasi Resmi SKB 3 Menteri</p>
                                    <p style="font-size: 0.75rem; margin: 0; line-height: 1.4;">
                                        Sistem akan mengambil data hari libur nasional resmi dari API Pemerintah Republik Indonesia dan menyinkronkannya khusus untuk rentang semester aktif terpilih.
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #374151;">
                                    Tahun Kalender Sinkronisasi
                                </label>
                                <input
                                    type="number"
                                    wire:model="syncYear"
                                    style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 0.875rem; margin-top: 4px; outline: none; background: #ffffff; color: #111827; box-sizing: border-box;" />
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                            <button
                                type="button"
                                wire:click="closeSyncModal"
                                style="padding: 8px 16px; border: 1px solid #d1d5db; border-radius: 8px; background: #ffffff; color: #374151; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                                Batal
                            </button>
                            <button
                                type="button"
                                wire:click="syncNationalHolidays"
                                wire:loading.attr="disabled"
                                style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; border-radius: 8px; background-color: #2563eb; color: #ffffff; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer;">
                                <span wire:loading.remove wire:target="syncNationalHolidays">Mulai Tarik Data</span>
                                <span wire:loading wire:target="syncNationalHolidays" class="flex items-center gap-1">
                                    Sedang Menyinkronkan...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
