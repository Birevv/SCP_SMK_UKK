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

        /* Agenda Multi-Hari: Badge Colors (Soft background, dark readable text, subtle border) */
        .scp-solid-libur_nasional { background-color: #fee2e2; color: #991b1b !important; border-color: #fca5a5 !important; }
        .scp-solid-libur_sekolah  { background-color: #fef3c7; color: #92400e !important; border-color: #fcd34d !important; }
        .scp-solid-upacara        { background-color: #ffe4e6; color: #9f1239 !important; border-color: #fda4af !important; }
        .scp-solid-ujian          { background-color: #e0f2fe; color: #075985 !important; border-color: #7dd3fc !important; }
        .scp-solid-pkl            { background-color: #ccfbf1; color: #115e59 !important; border-color: #5eead4 !important; }
        .scp-solid-ukk            { background-color: #f3e8ff; color: #6b21a8 !important; border-color: #d8b4fe !important; }
        .scp-solid-kegiatan_siswa { background-color: #d1fae5; color: #065f46 !important; border-color: #6ee7b7 !important; }
        .scp-solid-rapat          { background-color: #e0e7ff; color: #3730a3 !important; border-color: #a5b4fc !important; }
        .scp-solid-lainnya        { background-color: #f3f4f6; color: #374151 !important; border-color: #d1d5db !important; }

        /* Dark mode overrides for badge colors */
        .dark .scp-solid-libur_nasional { background-color: rgba(239, 68, 68, 0.2); color: #fca5a5 !important; border-color: rgba(239, 68, 68, 0.4) !important; }
        .dark .scp-solid-libur_sekolah  { background-color: rgba(245, 158, 11, 0.2); color: #fde68a !important; border-color: rgba(245, 158, 11, 0.4) !important; }
        .dark .scp-solid-upacara        { background-color: rgba(244, 63, 94, 0.2); color: #fda4af !important; border-color: rgba(244, 63, 94, 0.4) !important; }
        .dark .scp-solid-ujian          { background-color: rgba(14, 165, 233, 0.2); color: #7dd3fc !important; border-color: rgba(14, 165, 233, 0.4) !important; }
        .dark .scp-solid-pkl            { background-color: rgba(20, 184, 166, 0.2); color: #5eead4 !important; border-color: rgba(20, 184, 166, 0.4) !important; }
        .dark .scp-solid-ukk            { background-color: rgba(168, 85, 247, 0.2); color: #d8b4fe !important; border-color: rgba(168, 85, 247, 0.4) !important; }
        .dark .scp-solid-kegiatan_siswa { background-color: rgba(16, 185, 129, 0.2); color: #6ee7b7 !important; border-color: rgba(16, 185, 129, 0.4) !important; }
        .dark .scp-solid-rapat          { background-color: rgba(99, 102, 241, 0.2); color: #a5b4fc !important; border-color: rgba(99, 102, 241, 0.4) !important; }
        .dark .scp-solid-lainnya        { background-color: rgba(107, 114, 128, 0.2); color: #d1d5db !important; border-color: rgba(107, 114, 128, 0.4) !important; }

        /* Agenda Multi-Hari: Satu Garis Menyambung Penuh (Continuous Bar) */
        .scp-event-bar {
            display: block !important;
            height: 22px !important;
            min-height: 22px !important;
            line-height: 20px !important;
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            outline: none !important;
            cursor: pointer !important;
            box-sizing: border-box !important;
            position: relative !important;
            z-index: 2;
            padding: 0 8px !important;
            text-align: left !important;
            border-style: solid !important;
            transition: filter 0.15s ease-in-out;
        }
        .scp-event-bar:hover {
            filter: brightness(0.95);
            z-index: 10;
        }
        .scp-bar-start {
            width: calc(100% - 5px) !important;
            margin-left: 6px !important;
            margin-right: -1px !important;
            border-top-left-radius: 6px !important;
            border-bottom-left-radius: 6px !important;
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-width: 1px 0 1px 1px !important;
        }
        .scp-bar-middle {
            width: calc(100% + 2px) !important;
            margin-left: -1px !important;
            margin-right: -1px !important;
            border-radius: 0 !important;
            border-width: 1px 0 1px 0 !important;
        }
        .scp-bar-end {
            width: calc(100% - 5px) !important;
            margin-left: -1px !important;
            margin-right: 6px !important;
            border-top-right-radius: 6px !important;
            border-bottom-right-radius: 6px !important;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            border-width: 1px 1px 1px 0 !important;
        }
        .scp-bar-single {
            width: calc(100% - 12px) !important;
            margin-left: 6px !important;
            margin-right: 6px !important;
            border-radius: 6px !important;
            border-width: 1px !important;
        }

        /* Block Seleksi Rentang Tanggal (Transparan) */
        .scp-calendar-cell-selected {
            background-color: rgba(59, 130, 246, 0.15) !important;
            outline: 2px solid rgba(59, 130, 246, 0.45) !important;
            outline-offset: -2px;
        }
        .dark .scp-calendar-cell-selected {
            background-color: rgba(59, 130, 246, 0.25) !important;
            outline: 2px solid rgba(96, 165, 250, 0.55) !important;
            outline-offset: -2px;
        }

        /* ==========================================================================
           RESPONSIVE LIGHT / DARK MODE DESIGN SYSTEM
           ========================================================================== */

        /* 1. Header & Tabs */
        .scp-cal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
        }
        .dark .scp-cal-header {
            border-bottom-color: #1f2937;
        }
        .scp-cal-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }
        .dark .scp-cal-title {
            color: #f9fafb;
        }
        .scp-cal-icon-box {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background-color: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dark .scp-cal-icon-box {
            background-color: rgba(37, 99, 235, 0.2);
            color: #60a5fa;
        }
        .scp-cal-tabs {
            display: inline-flex;
            align-items: center;
            padding: 4px;
            gap: 8px;
            background: #f3f4f6;
            border-radius: 12px;
        }
        .dark .scp-cal-tabs {
            background: #1f2937;
        }
        .scp-cal-tab-btn {
            padding: 6px 12px;
            font-size: 0.8125rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease-in-out;
            background: transparent;
            color: #6b7280;
        }
        .dark .scp-cal-tab-btn {
            color: #9ca3af;
        }
        .scp-cal-tab-btn.active {
            background: #ffffff;
            color: #2563eb;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .dark .scp-cal-tab-btn.active {
            background: #111827;
            color: #60a5fa;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        .scp-cal-tab-badge {
            padding: 1px 6px;
            font-size: 0.65rem;
            border-radius: 9999px;
            background: #e5e7eb;
            color: #374151;
            font-weight: 700;
        }
        .dark .scp-cal-tab-badge {
            background: #374151;
            color: #d1d5db;
        }

        /* 2. Toolbar Filter Card */
        .scp-cal-toolbar-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 10px 16px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .dark .scp-cal-toolbar-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: 0 1px 3px rgba(0,0,0,0.25);
        }
        .scp-cal-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }
        .dark .scp-cal-label {
            color: #9ca3af;
        }
        .scp-cal-select {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 6px 32px 6px 12px;
            font-size: 0.875rem;
            color: #111827;
            background-color: #ffffff;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.65rem center;
            background-repeat: no-repeat;
            background-size: 1.25em 1.25em;
            cursor: pointer;
        }
        .dark .scp-cal-select {
            border-color: #374151;
            background-color: #1f2937;
            color: #f9fafb;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.65rem center;
            background-repeat: no-repeat;
            background-size: 1.25em 1.25em;
        }
        .scp-cal-btn-secondary {
            padding: 8px 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #374151;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s ease-in-out;
        }
        .dark .scp-cal-btn-secondary {
            border-color: #374151;
            background: #1f2937;
            color: #e5e7eb;
        }
        .dark .scp-cal-btn-secondary:hover {
            background: #374151;
        }

        /* 3. Main Calendar Card */
        .scp-cal-main-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .dark .scp-cal-main-card {
            background: #111827;
            border-color: #1f2937;
            box-shadow: 0 1px 3px rgba(0,0,0,0.25);
        }
        .scp-cal-nav-group {
            display: inline-flex;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
        }
        .dark .scp-cal-nav-group {
            border-color: #374151;
            background: #1f2937;
        }
        .scp-cal-nav-btn {
            padding: 6px 12px;
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            color: #4b5563;
        }
        .dark .scp-cal-nav-btn {
            color: #9ca3af;
        }
        .scp-cal-month-heading {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }
        .dark .scp-cal-month-heading {
            color: #f9fafb;
        }
        .scp-cal-search-input {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 0.875rem;
            width: 170px;
            outline: none;
            background: #ffffff;
            color: #111827;
        }
        .dark .scp-cal-search-input {
            border-color: #374151;
            background: #1f2937;
            color: #f9fafb;
        }
        .scp-cal-view-switcher {
            display: inline-flex;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 2px;
            background: #f3f4f6;
        }
        .dark .scp-cal-view-switcher {
            border-color: #374151;
            background: #1f2937;
        }
        .scp-cal-view-btn {
            padding: 4px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            background: transparent;
            color: #4b5563;
        }
        .dark .scp-cal-view-btn {
            color: #9ca3af;
        }
        .scp-cal-view-btn.active {
            background: #2563eb;
            color: #ffffff;
        }

        /* 4. Calendar Grid Table */
        .scp-cal-grid-wrapper {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            user-select: none;
            -webkit-user-select: none;
            position: relative;
        }
        .dark .scp-cal-grid-wrapper {
            border-color: #1f2937;
        }
        .scp-cal-thead tr {
            background-color: #f9fafb;
        }
        .dark .scp-cal-thead tr {
            background-color: #1a2234;
        }
        .scp-cal-th {
            border: 1px solid #e5e7eb;
            height: auto;
            vertical-align: middle;
            padding: 10px;
            text-align: center;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #374151;
            width: 14.285%;
        }
        .dark .scp-cal-th {
            border-color: #1f2937;
            color: #cbd5e1;
        }
        .scp-cal-th-weekend {
            color: #ef4444;
        }
        .dark .scp-cal-th-weekend {
            color: #f87171;
        }
        .scp-cal-td {
            border: 1px solid #e5e7eb;
            height: 105px;
            min-height: 105px;
            vertical-align: top;
            padding: 6px 0;
            position: relative;
        }
        .dark .scp-cal-td {
            border-color: #1f2937;
        }
        .scp-day-current {
            background-color: #ffffff;
        }
        .dark .scp-day-current {
            background-color: #111827;
        }
        .scp-day-today {
            background-color: #eff6ff;
        }
        .dark .scp-day-today {
            background-color: rgba(30, 58, 138, 0.25);
        }
        .scp-day-other {
            background-color: #f9fafb;
        }
        .dark .scp-day-other {
            background-color: rgba(15, 23, 42, 0.45);
        }
        .scp-day-weekend {
            background-color: #fef8f8;
        }
        .dark .scp-day-weekend {
            background-color: rgba(153, 27, 27, 0.12);
        }
        .scp-day-num {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1f2937;
        }
        .dark .scp-day-num {
            color: #e2e8f0;
        }
        .scp-day-num-today {
            color: #2563eb;
            font-weight: 800;
        }
        .dark .scp-day-num-today {
            color: #60a5fa;
            font-weight: 800;
        }
        .scp-day-num-weekend {
            color: #ef4444;
            font-weight: 800;
        }
        .dark .scp-day-num-weekend {
            color: #f87171;
            font-weight: 800;
        }
        .scp-day-num-weekend-other {
            color: #fca5a5;
        }
        .dark .scp-day-num-weekend-other {
            color: rgba(248, 113, 113, 0.4);
        }
        .scp-day-num-other {
            color: #9ca3af;
        }
        .dark .scp-day-num-other {
            color: #64748b;
        }
        .scp-day-event-btn {
            width: calc(100% - 12px);
            margin: 0 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 500;
            color: #1f2937;
            padding: 2px 4px;
            border-radius: 4px;
            border: none;
            background: transparent;
            cursor: pointer;
        }
        .dark .scp-day-event-btn {
            color: #e2e8f0;
        }
        .dark .scp-day-event-btn:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        .scp-cal-popover {
            position: absolute;
            z-index: 50;
            left: 50%;
            transform: translateX(-50%);
            bottom: calc(100% + 6px);
            width: 250px;
            padding: 12px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            font-size: 0.75rem;
            color: #1f2937;
            pointer-events: none;
        }
        .dark .scp-cal-popover {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.5);
        }
        .dark .scp-cal-popover-title {
            color: #f8fafc !important;
        }
        .dark .scp-cal-popover-sub {
            color: #94a3b8 !important;
            border-top-color: #334155 !important;
        }
        .dark .scp-cal-popover-desc {
            color: #cbd5e1 !important;
        }

        /* 5. List View Styles */
        .scp-cal-list-container {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
        }
        .dark .scp-cal-list-container {
            border-color: #1f2937;
        }
        .scp-cal-list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 8px;
        }
        .dark .scp-cal-list-header {
            border-bottom-color: #1f2937;
        }
        .scp-cal-list-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #fafafa;
        }
        .dark .scp-cal-list-card {
            background: #1a2234;
            border-color: #1f2937;
        }
        .scp-cal-date-badge {
            width: 48px;
            text-align: center;
            padding: 6px;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            flex-shrink: 0;
        }
        .dark .scp-cal-date-badge {
            background: #111827;
            border-color: #374151;
        }
        .dark .scp-cal-date-badge-month {
            color: #94a3b8 !important;
        }
        .dark .scp-cal-date-badge-day {
            color: #f8fafc !important;
        }

        /* 6. Modal Styles */
        .scp-cal-modal-card {
            background: #ffffff;
            border-radius: 12px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        .dark .scp-cal-modal-card {
            background: #111827;
            border: 1px solid #1f2937;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.4);
        }
        .scp-cal-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }
        .dark .scp-cal-modal-header {
            border-bottom-color: #1f2937;
        }
        .scp-cal-modal-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dark .scp-cal-modal-title {
            color: #f9fafb;
        }
        .scp-cal-form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #374151;
        }
        .dark .scp-cal-form-label {
            color: #cbd5e1;
        }
        .scp-cal-form-input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.875rem;
            margin-top: 4px;
            outline: none;
            background-color: #ffffff;
            color: #111827;
            box-sizing: border-box;
        }
        .dark .scp-cal-form-input {
            border-color: #374151;
            background-color: #1f2937;
            color: #f9fafb;
        }
        select.scp-cal-form-input {
            appearance: none !important;
            -webkit-appearance: none !important;
            background-color: #ffffff !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 0.75rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.25rem 1.25rem !important;
            padding-right: 2.5rem !important;
            cursor: pointer;
        }
        .dark select.scp-cal-form-input {
            background-color: #1f2937 !important;
            border-color: #374151 !important;
            color: #f9fafb !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 0.75rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.25rem 1.25rem !important;
        }
        .scp-cal-impact-box {
            background: #f9fafb;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .dark .scp-cal-impact-box {
            background: #1a2234;
            border-color: #1f2937;
        }
        .scp-cal-checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #1f2937;
            cursor: pointer;
        }
        .dark .scp-cal-checkbox-label {
            color: #e2e8f0;
        }
        .scp-cal-modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            margin-top: 20px;
        }
        .dark .scp-cal-modal-footer {
            border-top-color: #1f2937;
        }
        .scp-cal-modal-backdrop {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            margin: 0 !important;
            padding: 16px !important;
            background-color: rgba(15, 23, 42, 0.65) !important;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            z-index: 99999 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-sizing: border-box !important;
        }
        .dark .scp-cal-modal-backdrop {
            background-color: rgba(3, 7, 18, 0.75) !important;
        }

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
        <div class="scp-cal-header no-print">
            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="scp-cal-icon-box">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="w-5 h-5" />
                    </div>
                    <h1 class="scp-cal-title">
                        Kalender Akademik
                    </h1>
                </div>

                <!-- Navigasi Tab (Hanya tampil jika user memiliki hak akses kelola tahun ajaran) -->
                @if ($this->canManage())
                    <div class="scp-cal-tabs">
                        <button
                            type="button"
                            wire:click="switchTab('calendar')"
                            class="scp-cal-tab-btn {{ $currentViewTab === 'calendar' ? 'active' : '' }}">
                            <x-filament::icon icon="heroicon-o-calendar" class="w-4 h-4" />
                            <span>Kalender Akademik</span>
                        </button>

                        <button
                            type="button"
                            wire:click="switchTab('years')"
                            class="scp-cal-tab-btn {{ $currentViewTab === 'years' ? 'active' : '' }}">
                            <x-filament::icon icon="heroicon-o-table-cells" class="w-4 h-4" />
                            <span>Data Tahun Ajaran</span>
                            <span class="scp-cal-tab-badge">
                                {{ $this->academicYears->count() }}
                            </span>
                        </button>
                    </div>
                @endif
            </div>

            <!-- Sisi Kanan Header: Action Button -->
            <div style="display: flex; align-items: center; gap: 8px;">
                @if ($currentViewTab === 'years' && $this->canManage())
                    <a
                        href="{{ \App\Filament\Resources\AcademicYears\AcademicYearResource::getUrl('create') }}"
                        style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; border-radius: 8px; background-color: #2563eb; color: #ffffff; font-size: 0.875rem; font-weight: 600; text-decoration: none;">
                        <x-filament::icon icon="heroicon-m-plus" class="w-4 h-4" />
                        <span>Buat Tahun Ajaran</span>
                    </a>
                @endif
            </div>
        </div>

        @if ($currentViewTab === 'years' && $this->canManage())
            <!-- TAB 2: DATA TAHUN AJARAN (TABEL RESMI FILAMENT) -->
            <div class="mt-2">
                {{ $this->table }}
            </div>
        @else
            <!-- TAB 1: KALENDER AKADEMIK -->
            <div class="space-y-4">
                
                <!-- 2. SATU BARIS TOOLBAR (FILTER DI KIRI & TOMBOL AKSI DI KANAN) -->
                <div class="scp-cal-toolbar-card no-print">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                        <!-- Sisi Kiri: Filter Dropdowns -->
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <!-- Dropdown Tahun Ajaran / Semester -->
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label class="scp-cal-label">
                                    Tahun Ajaran / Semester:
                                </label>
                                <select
                                    wire:model.live="selectedYearId"
                                    class="scp-cal-select"
                                    style="font-weight: 600;">
                                    @foreach ($this->academicYears as $year)
                                        <option value="{{ $year->id }}">
                                            {{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Dropdown Target Tingkat Siswa -->
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label class="scp-cal-label">
                                    Target Tingkat Siswa:
                                </label>
                                <select
                                    wire:model.live="selectedGrade"
                                    class="scp-cal-select"
                                    style="font-weight: 500;">
                                    <option value="">Semua Tingkat</option>
                                    <option value="X">Khusus Kelas X</option>
                                    <option value="XI">Khusus Kelas XI</option>
                                    <option value="XII">Khusus Kelas XII</option>
                                </select>
                            </div>
                        </div>

                        <!-- Sisi Kanan: Action Buttons -->
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            @if ($this->canManage())
                                <span style="font-size: 0.75rem; color: #6b7280; display: inline-flex; align-items: center; gap: 4px; margin-right: 4px;" class="hidden md:inline-flex dark:text-gray-400">
                                    <x-filament::icon icon="heroicon-m-cursor-arrow-rays" class="w-3.5 h-3.5 text-primary-500" />
                                    <span>Geser / swipe tanggal untuk agenda multi-hari</span>
                                </span>

                                <button
                                    type="button"
                                    wire:click="openSyncModal"
                                    class="scp-cal-btn-secondary">
                                    <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4 text-primary-500" />
                                    <span>Tarik Libur Nasional</span>
                                </button>

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

                <!-- 3. CONTAINER CARD UTAMA -->
                <div id="scp-calendar-print-area" class="scp-cal-main-card space-y-4">
                    
                    <!-- 1. TOOLBAR NAVIGASI BULAN SEJAJAR 1 BARIS (KIRI - TENGAH - KANAN) -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px;" class="no-print">
                        
                        <!-- Kiri: Grup tombol < > dan Hari Ini -->
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div class="scp-cal-nav-group">
                                <button
                                    type="button"
                                    wire:click="prevMonth"
                                    title="Bulan Sebelumnya"
                                    class="scp-cal-nav-btn"
                                    style="border-right: 1px solid #d1d5db;"
                                    class="dark:border-r-gray-700!">
                                    <x-filament::icon icon="heroicon-m-chevron-left" class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    wire:click="nextMonth"
                                    title="Bulan Berikutnya"
                                    class="scp-cal-nav-btn">
                                    <x-filament::icon icon="heroicon-m-chevron-right" class="w-4 h-4" />
                                </button>
                            </div>

                            <button
                                type="button"
                                wire:click="goToToday"
                                class="scp-cal-btn-secondary"
                                style="padding: 6px 14px; font-weight: 600;">
                                Hari Ini
                            </button>
                        </div>

                        <!-- Tengah: Judul Bulan dan Tahun -->
                        <div style="text-align: center;">
                            <h2 class="scp-cal-month-heading">
                                {{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->locale('id')->translatedFormat('F Y') }}
                            </h2>
                        </div>

                        <!-- Kanan: Input Pencarian dan Switcher -->
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <!-- Input Cari agenda... -->
                            <div style="position: relative; display: flex; align-items: center;">
                                <input
                                    type="text"
                                    wire:model.live.debounce.250ms="searchQuery"
                                    placeholder="Cari agenda..."
                                    class="scp-cal-search-input" />
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
                            <div class="scp-cal-view-switcher">
                                <button
                                    type="button"
                                    wire:click="$set('viewMode', 'month')"
                                    class="scp-cal-view-btn {{ $viewMode === 'month' ? 'active' : '' }}">
                                    Bulan
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('viewMode', 'week')"
                                    class="scp-cal-view-btn {{ $viewMode === 'week' ? 'active' : '' }}">
                                    Minggu
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('viewMode', 'list')"
                                    class="scp-cal-view-btn {{ $viewMode === 'list' ? 'active' : '' }}">
                                    Daftar Agenda
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 5. TAMPILAN UTAMA KALENDER (BULAN / MINGGU / DAFTAR AGENDA) -->
                    @if ($viewMode === 'list')
                        <!-- LIST VIEW: DAFTAR AGENDA BULAN INI -->
                        <div class="scp-cal-list-container space-y-3">
                            <div class="scp-cal-list-header">
                                <h3 class="scp-cal-title" style="font-size: 0.875rem;">
                                    Agenda Bulan {{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->locale('id')->translatedFormat('F Y') }}
                                    <span style="font-size: 0.75rem; color: #6b7280; font-weight: 400;" class="dark:text-gray-400">({{ $this->monthEvents->count() }} agenda ditemukan)</span>
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
                                        <div class="scp-cal-list-card">
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div class="scp-cal-date-badge">
                                                    <div style="font-size: 0.625rem; font-weight: 700; color: #9ca3af; text-transform: uppercase;" class="scp-cal-date-badge-month">{{ $item->start_date->translatedFormat('M') }}</div>
                                                    <div style="font-size: 1.125rem; font-weight: 800; color: #111827; line-height: 1.2;" class="scp-cal-date-badge-day">{{ $item->start_date->day }}</div>
                                                </div>

                                                <div class="space-y-1">
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold scp-solid-{{ $item->category instanceof \App\Enums\AcademicEventCategory ? $item->category->value : $item->category }}">
                                                            {{ $item->category instanceof \App\Enums\AcademicEventCategory ? $item->category->getShortLabel() : $item->category }}
                                                        </span>
                                                        @if ($item->target_grades)
                                                            <span style="padding: 2px 8px; font-size: 0.625rem; font-weight: 600; border-radius: 4px;" class="bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                                                Kelas {{ implode(', ', $item->target_grades) }}
                                                            </span>
                                                        @endif
                                                        <span style="font-size: 0.75rem; color: #6b7280;" class="dark:text-gray-400">
                                                            {{ $item->start_date->format('d M Y') }}
                                                            @if ($isMultiDay)
                                                                s/d {{ $item->end_date->format('d M Y') }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <h4 style="font-size: 0.875rem; font-weight: 700; margin: 0;" class="text-gray-900 dark:text-gray-100">
                                                        {{ $item->title }}
                                                    </h4>
                                                    @if ($item->description)
                                                        <p style="font-size: 0.75rem; color: #6b7280; margin: 0;" class="dark:text-gray-400">
                                                            {{ $item->description }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($this->canManage())
                                                <div style="display: flex; align-items: center; gap: 4px;">
                                                    {{ ($this->editEventAction)(['id' => $item->id]) }}
                                                    {{ ($this->deleteEventAction)(['id' => $item->id]) }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <!-- 2. TABEL GRID KALENDER (SWIPE / DRAG MULTI-DAY SELECTION) -->
                        <div
                            x-data="{
                                isDragging: false,
                                startDate: null,
                                hoverDate: null,
                                canManage: {{ $this->canManage() ? 'true' : 'false' }},

                                startDrag(date, event) {
                                    if (!this.canManage) return;
                                    if (event.target.closest('button') || event.target.closest('a') || event.target.closest('.pointer-events-auto')) {
                                        return;
                                    }
                                    if (event.type === 'mousedown' && event.button !== 0) return;

                                    this.isDragging = true;
                                    this.startDate = date;
                                    this.hoverDate = date;
                                },

                                onHover(date) {
                                    if (!this.isDragging) return;
                                    this.hoverDate = date;
                                },

                                endDrag(date) {
                                    if (!this.isDragging) return;
                                    this.isDragging = false;

                                    let start = this.startDate;
                                    let end = date || this.hoverDate || this.startDate;

                                    if (start && end) {
                                        let s = start <= end ? start : end;
                                        let e = start <= end ? end : start;
                                        $wire.openCreateEventModal(s, e);
                                    }

                                    this.startDate = null;
                                    this.hoverDate = null;
                                },

                                isInRange(date) {
                                    if (!this.isDragging || !this.startDate || !this.hoverDate) return false;
                                    let s = this.startDate <= this.hoverDate ? this.startDate : this.hoverDate;
                                    let e = this.startDate <= this.hoverDate ? this.hoverDate : this.startDate;
                                    return date >= s && date <= e;
                                },

                                handleTouchMove(event) {
                                    if (!this.isDragging) return;
                                    let touch = event.touches[0];
                                    let el = document.elementFromPoint(touch.clientX, touch.clientY);
                                    if (el) {
                                        let cell = el.closest('[data-calendar-date]');
                                        if (cell) {
                                            let date = cell.getAttribute('data-calendar-date');
                                            if (date) {
                                                this.hoverDate = date;
                                            }
                                        }
                                    }
                                },

                                handleTouchEnd(event) {
                                    if (!this.isDragging) return;
                                    this.endDrag(this.hoverDate);
                                }
                            }"
                            @mouseup.window="endDrag()"
                            @touchmove="handleTouchMove($event)"
                            @touchend="handleTouchEnd($event)"
                            class="scp-cal-grid-wrapper">
                            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                                <thead class="scp-cal-thead">
                                    <tr>
                                        <th class="scp-cal-th">Senin</th>
                                        <th class="scp-cal-th">Selasa</th>
                                        <th class="scp-cal-th">Rabu</th>
                                        <th class="scp-cal-th">Kamis</th>
                                        <th class="scp-cal-th">Jumat</th>
                                        <th class="scp-cal-th scp-cal-th-weekend">Sabtu</th>
                                        <th class="scp-cal-th scp-cal-th-weekend">Minggu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->calendarWeeks as $week)
                                        <tr>
                                            @foreach ($week['days'] as $day)
                                                @php
                                                    $cellBgClass = 'scp-day-current';
                                                    if ($day['is_today']) {
                                                        $cellBgClass = 'scp-day-today';
                                                    } elseif (! $day['is_current_month']) {
                                                        $cellBgClass = 'scp-day-other';
                                                    } elseif ($day['is_weekend']) {
                                                        $cellBgClass = 'scp-day-weekend';
                                                    }

                                                    $numClass = 'scp-day-num';
                                                    if ($day['is_today']) {
                                                        $numClass = 'scp-day-num-today';
                                                    } elseif ($day['is_weekend']) {
                                                        $numClass = $day['is_current_month'] ? 'scp-day-num-weekend' : 'scp-day-num-weekend-other';
                                                    } elseif (! $day['is_current_month']) {
                                                        $numClass = 'scp-day-num-other';
                                                    }
                                                @endphp
                                                <td
                                                    data-calendar-date="{{ $day['date'] }}"
                                                    @if ($this->canManage())
                                                        @mousedown="startDrag('{{ $day['date'] }}', $event)"
                                                        @mouseenter="onHover('{{ $day['date'] }}')"
                                                        @mouseup="endDrag('{{ $day['date'] }}')"
                                                        @touchstart="startDrag('{{ $day['date'] }}', $event)"
                                                    @endif
                                                    :class="isInRange('{{ $day['date'] }}') ? 'scp-calendar-cell-selected' : ''"
                                                    class="scp-cal-td {{ $cellBgClass }} align-top transition duration-150 hover:bg-blue-50/40 dark:hover:bg-gray-800/50 {{ $this->canManage() ? 'cursor-pointer' : 'cursor-default' }}">
                                                    
                                                    <!-- Top Row: Angka Tanggal Saja di Pojok Kanan Atas -->
                                                    <div style="display: flex; justify-content: flex-end; margin-bottom: 4px; padding: 0 8px; pointer-events: none;">
                                                        <span class="{{ $numClass }}">
                                                            {{ $day['day_number'] }}
                                                        </span>
                                                    </div>

                                                    <!-- Container Daftar Agenda Sel -->
                                                    <div style="display: flex; flex-direction: column; gap: 3px; min-height: 68px; width: 100%;">
                                                        @foreach ($day['events'] as $event)
                                                            @php
                                                                $isMultiDay = $event->start_date->ne($event->end_date);
                                                            @endphp
                                                            <div
                                                                x-data="{ showPopover: false }"
                                                                @mouseenter="showPopover = true"
                                                                @mouseleave="showPopover = false"
                                                                @mousedown.stop
                                                                @touchstart.stop
                                                                @mouseup.stop
                                                                style="position: relative; width: 100%;"
                                                                class="pointer-events-auto">
                                                                
                                                                @if ($isMultiDay)
                                                                    @php
                                                                        $dayDate = \Carbon\Carbon::parse($day['date']);
                                                                        $isStart = $event->start_date->toDateString() === $day['date'] || $dayDate->isMonday();
                                                                        $isEnd   = $event->end_date->toDateString() === $day['date'] || $dayDate->isSunday();

                                                                        if ($isStart && $isEnd) {
                                                                            $barClass = 'scp-bar-single';
                                                                            $showTitle = true;
                                                                        } elseif ($isStart && ! $isEnd) {
                                                                            $barClass = 'scp-bar-start';
                                                                            $showTitle = true;
                                                                        } elseif (! $isStart && ! $isEnd) {
                                                                            $barClass = 'scp-bar-middle';
                                                                            $showTitle = false;
                                                                        } else {
                                                                            $barClass = 'scp-bar-end';
                                                                            $showTitle = false;
                                                                        }
                                                                    @endphp
                                                                    <!-- Multi-day Event: Satu Garis Menyambung (Continuous Bar) -->
                                                                    @if ($this->canManage())
                                                                        <button
                                                                            type="button"
                                                                            wire:click.stop="openEditEventModal({{ $event->id }})"
                                                                            class="scp-event-bar {{ $barClass }} scp-solid-{{ $event->category instanceof \App\Enums\AcademicEventCategory ? $event->category->value : $event->category }}">
                                                                            @if ($showTitle)
                                                                                @if ($event->target_grades)
                                                                                    <span style="font-weight: 700; font-size: 0.625rem; opacity: 0.9;">[{{ implode(',', $event->target_grades) }}]</span>
                                                                                @endif
                                                                                <span>{{ $event->title }}</span>
                                                                            @else
                                                                                <span style="visibility: hidden;">&nbsp;</span>
                                                                            @endif
                                                                        </button>
                                                                    @else
                                                                        <div
                                                                            class="scp-event-bar {{ $barClass }} scp-solid-{{ $event->category instanceof \App\Enums\AcademicEventCategory ? $event->category->value : $event->category }}"
                                                                            style="cursor: default;">
                                                                            @if ($showTitle)
                                                                                @if ($event->target_grades)
                                                                                    <span style="font-weight: 700; font-size: 0.625rem; opacity: 0.9;">[{{ implode(',', $event->target_grades) }}]</span>
                                                                                @endif
                                                                                <span>{{ $event->title }}</span>
                                                                            @else
                                                                                <span style="visibility: hidden;">&nbsp;</span>
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                @else
                                                                    <!-- 1-day Event: Titik Warna Kategori (Dot) Diikuti Teks Singkat Satu Baris -->
                                                                    @if ($this->canManage())
                                                                        <button
                                                                            type="button"
                                                                            wire:click.stop="openEditEventModal({{ $event->id }})"
                                                                            class="scp-day-event-btn">
                                                                            <span class="w-2 h-2 rounded-full shrink-0 scp-dot-{{ $event->category instanceof \App\Enums\AcademicEventCategory ? $event->category->value : $event->category }}"></span>
                                                                            @if ($event->target_grades)
                                                                                <span style="font-size: 0.625rem; font-weight: 700; color: #6b7280; flex-shrink: 0;" class="dark:text-gray-400">[{{ implode(',', $event->target_grades) }}]</span>
                                                                            @endif
                                                                            <span class="truncate">{{ $event->title }}</span>
                                                                        </button>
                                                                    @else
                                                                        <div
                                                                            class="scp-day-event-btn"
                                                                            style="cursor: default;">
                                                                            <span class="w-2 h-2 rounded-full shrink-0 scp-dot-{{ $event->category instanceof \App\Enums\AcademicEventCategory ? $event->category->value : $event->category }}"></span>
                                                                            @if ($event->target_grades)
                                                                                <span style="font-size: 0.625rem; font-weight: 700; color: #6b7280; flex-shrink: 0;" class="dark:text-gray-400">[{{ implode(',', $event->target_grades) }}]</span>
                                                                            @endif
                                                                            <span class="truncate">{{ $event->title }}</span>
                                                                        </div>
                                                                    @endif
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
                                                                    class="scp-cal-popover no-print">
                                                                    <div class="scp-cal-popover-title" style="font-weight: 700; font-size: 0.875rem; color: #111827; margin-bottom: 4px; line-height: 1.25;">
                                                                        {{ $event->title }}
                                                                    </div>
                                                                    <div class="scp-cal-popover-sub" style="display: flex; align-items: center; gap: 6px; font-size: 0.6875rem; color: #6b7280; margin-bottom: 8px;">
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
                                                                            <span style="color: #6b7280;" class="dark:text-gray-400">Kategori:</span>
                                                                            <span style="font-weight: 600;" class="text-gray-800 dark:text-gray-200">{{ $event->category instanceof \App\Enums\AcademicEventCategory ? $event->category->getLabel() : $event->category }}</span>
                                                                        </div>
                                                                        @if ($event->target_grades)
                                                                            <div>
                                                                                <span style="color: #6b7280;" class="dark:text-gray-400">Target Siswa:</span>
                                                                                <span style="font-weight: 600;" class="text-gray-800 dark:text-gray-200">Kelas {{ implode(', ', $event->target_grades) }}</span>
                                                                            </div>
                                                                        @endif
                                                                        <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px; padding-top: 4px; border-top: 1px solid #f3f4f6;" class="dark:border-t-gray-700">
                                                                            @if ($event->is_holiday)
                                                                                <span style="padding: 2px 6px; border-radius: 4px; font-size: 0.625rem; font-weight: 700;" class="bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300">Libur</span>
                                                                            @endif
                                                                            @if ($event->disable_kbm)
                                                                                <span style="padding: 2px 6px; border-radius: 4px; font-size: 0.625rem; font-weight: 700;" class="bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">KBM Nonaktif</span>
                                                                            @endif
                                                                            @if ($event->requires_attendance)
                                                                                <span style="padding: 2px 6px; border-radius: 4px; font-size: 0.625rem; font-weight: 700;" class="bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">Wajib Presensi</span>
                                                                            @endif
                                                                        </div>
                                                                        @if ($event->description)
                                                                            <div class="scp-cal-popover-desc" style="margin-top: 6px; color: #4b5563; font-style: italic; font-size: 0.6875rem;">
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

        <x-filament-actions::modals />
    </div>

    <!-- 3, 4, 5. MODAL POPUP TAMBAH / EDIT AGENDA AKADEMIK -->
        @if ($showEventModal)
            <div
                class="scp-cal-modal-backdrop no-print"
                style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; width: 100vw !important; height: 100vh !important; margin: 0 !important; padding: 16px !important; z-index: 99999 !important; display: flex !important; align-items: center !important; justify-content: center !important; box-sizing: border-box !important;"
                wire:click.self="closeEventModal">
                <div class="scp-cal-modal-card">
                        
                    <!-- Header Modal -->
                    <div class="scp-cal-modal-header">
                        <h3 class="scp-cal-modal-title">
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

                    <!-- Isi Form Modal -->
                    <div style="display: flex; flex-direction: column; gap: 16px; flex: 1;">
                        <!-- Judul Agenda -->
                        <div>
                            <label class="scp-cal-form-label">
                                Judul Agenda <span style="color: #ef4444;">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="eventTitle"
                                placeholder="Contoh: Praktik Kerja Lapangan (PKL) Gelombang 1"
                                class="scp-cal-form-input" />
                            @error('eventTitle')
                                <p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Kategori Agenda -->
                        <div>
                            <label class="scp-cal-form-label">
                                Kategori Agenda <span style="color: #ef4444;">*</span>
                            </label>
                            <select
                                wire:model.live="eventCategory"
                                class="scp-cal-form-input">
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
                            <label class="scp-cal-form-label">
                                Target Tingkat Siswa (Kosongkan jika berlaku untuk semua kelas)
                            </label>
                            <div style="display: flex; align-items: center; gap: 16px; margin-top: 8px;">
                                @foreach (['X', 'XI', 'XII'] as $grade)
                                    <label class="scp-cal-checkbox-label">
                                        <input
                                            type="checkbox"
                                            value="{{ $grade }}"
                                            wire:model="eventTargetGrades"
                                            style="border-radius: 4px; border: 1px solid #d1d5db;"
                                            class="dark:border-gray-700!" />
                                        <span>Kelas {{ $grade }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Rentang Tanggal -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div>
                                <label class="scp-cal-form-label">
                                    Tanggal Mulai <span style="color: #ef4444;">*</span>
                                </label>
                                <input
                                    type="date"
                                    wire:model="eventStartDate"
                                    class="scp-cal-form-input" />
                                @error('eventStartDate')
                                    <p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="scp-cal-form-label">
                                    Tanggal Selesai <span style="color: #ef4444;">*</span>
                                </label>
                                <input
                                    type="date"
                                    wire:model="eventEndDate"
                                    class="scp-cal-form-input" />
                                @error('eventEndDate')
                                    <p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Pengaturan Status & Dampak Operasional -->
                        <div class="scp-cal-impact-box">
                            <label class="scp-cal-label">
                                Dampak Operasional Sekolah:
                            </label>
                            
                            <label class="scp-cal-checkbox-label">
                                <input
                                    type="checkbox"
                                    wire:model="eventIsHoliday" />
                                <span>Tanggal Merah / Libur Resmi</span>
                            </label>

                            <label class="scp-cal-checkbox-label">
                                <input
                                    type="checkbox"
                                    wire:model="eventDisableKbm" />
                                <span>Nonaktifkan KBM (Kegiatan Belajar Mengajar Ditiadakan)</span>
                            </label>

                            <label class="scp-cal-checkbox-label">
                                <input
                                    type="checkbox"
                                    wire:model="eventRequiresAttendance" />
                                <span>Wajib Presensi Kehadiran Siswa</span>
                            </label>
                        </div>

                        <!-- Deskripsi Agenda -->
                        <div>
                            <label class="scp-cal-form-label">
                                Deskripsi / Catatan Tambahan
                            </label>
                            <textarea
                                wire:model="eventDescription"
                                rows="3"
                                placeholder="Tuliskan keterangan detail terkait agenda..."
                                class="scp-cal-form-input"></textarea>
                        </div>
                    </div>

                    <!-- Footer Action Modal -->
                    <div class="scp-cal-modal-footer">
                        <div>
                            @if ($editingEventId)
                                <button
                                    type="button"
                                    wire:click="mountAction('deleteEvent', { id: {{ $editingEventId }} })"
                                    style="padding: 8px 14px; border: none; background: transparent; color: #ef4444; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                                    Hapus
                                </button>
                            @endif
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px;">
                            <button
                                type="button"
                                wire:click="closeEventModal"
                                class="scp-cal-btn-secondary">
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
        @endif

        <!-- 7. MODAL TARIK LIBUR NASIONAL -->
        @if ($showSyncModal)
            <div
                class="scp-cal-modal-backdrop no-print"
                style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; width: 100vw !important; height: 100vh !important; margin: 0 !important; padding: 16px !important; z-index: 99999 !important; display: flex !important; align-items: center !important; justify-content: center !important; box-sizing: border-box !important;"
                wire:click.self="closeSyncModal">
                <div class="scp-cal-modal-card" style="max-width: 450px;">
                    <div class="scp-cal-modal-header">
                        <h3 class="scp-cal-modal-title">
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
                        <div style="padding: 12px; border-radius: 8px; font-size: 0.8125rem; display: flex; align-items: flex-start; gap: 10px;" class="bg-blue-50 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-900/50">
                            <x-filament::icon icon="heroicon-o-information-circle" class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                            <div class="space-y-1">
                                <p style="font-weight: 700; margin: 0;">Sinkronisasi Resmi SKB 3 Menteri</p>
                                <p style="font-size: 0.75rem; margin: 0; line-height: 1.4;">
                                    Sistem akan mengambil data hari libur nasional resmi dari API Pemerintah Republik Indonesia dan menyinkronkannya khusus untuk rentang semester aktif terpilih.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="scp-cal-form-label">
                                Tahun Kalender Sinkronisasi
                            </label>
                            <input
                                type="number"
                                wire:model="syncYear"
                                class="scp-cal-form-input" />
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid #e5e7eb;" class="dark:border-t-gray-800">
                        <button
                            type="button"
                            wire:click="closeSyncModal"
                            class="scp-cal-btn-secondary">
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
        @endif
</x-filament-panels::page>
