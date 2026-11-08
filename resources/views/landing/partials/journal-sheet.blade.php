@php
    $students = [
        ['no' => '01', 'name' => 'Siswa 01', 'status' => 'H', 'note' => null],
        ['no' => '02', 'name' => 'Siswa 02', 'status' => 'H', 'note' => null],
        ['no' => '03', 'name' => 'Siswa 03', 'status' => 'I', 'note' => 'Tersinkron dari surat izin terverifikasi'],
        ['no' => '04', 'name' => 'Siswa 04', 'status' => 'H', 'note' => null],
        ['no' => '05', 'name' => 'Siswa 05', 'status' => 'H', 'note' => null],
        ['no' => '06', 'name' => 'Siswa 06', 'status' => 'H', 'note' => null],
    ];
@endphp

<div class="rounded-2xl bg-card border border-hairline-strong shadow-sheet p-5 sm:p-6" aria-label="Ilustrasi tampilan sistem">
    {{-- Window Chrome --}}
    <div class="flex items-center justify-between gap-3 pb-3.5 mb-3.5 border-b border-hairline">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-hairline-strong"></span>
            <span class="w-2.5 h-2.5 rounded-full bg-hairline-strong"></span>
            <span class="w-2.5 h-2.5 rounded-full bg-hairline-strong"></span>
            <span class="text-xs font-medium text-ink-muted ml-2">Ilustrasi tampilan sistem</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 h-6 px-2.5 rounded-md border border-accent-border bg-accent-tint text-xs font-medium text-accent-ink">
                <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                <span>Putaran 1</span>
            </span>
        </div>
    </div>

    {{-- Journal Header Info --}}
    <div class="pb-3.5 border-b border-hairline">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs font-medium text-ink-muted tabular-nums">Lembar jurnal harian · Senin, 9 November 2026</p>
            <span class="text-[11px] font-medium px-2 py-0.5 rounded bg-positive-tint text-positive">Terverifikasi</span>
        </div>
        <h2 class="text-[17px] sm:text-[18px] font-semibold text-ink leading-snug mt-1">Pemrograman Berorientasi Objek</h2>
        <p class="text-xs text-ink-secondary tabular-nums mt-1">Kelas XI RPL 2 · Lab RPL 1 · Jam ke-3–4 (08.30–10.00)</p>
    </div>

    {{-- Teaching Material & Real Photo Mockup --}}
    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 mt-3.5 p-3 rounded-xl bg-paper border border-hairline">
        <div class="sm:col-span-8">
            <p class="text-[11px] font-medium text-ink-muted">Materi pokok</p>
            <p class="text-[13px] text-ink font-medium leading-relaxed mt-0.5">Enkapsulasi dan pewarisan class pada modul peminjaman buku perpustakaan</p>
        </div>

        <div class="sm:col-span-4 flex items-center sm:justify-end gap-2.5 pt-2.5 sm:pt-0 sm:pl-3 border-t sm:border-t-0 sm:border-l border-hairline">
            <div class="w-8 h-8 rounded-lg bg-accent-tint flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-accent-ink shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-ink leading-tight">Dokumentasi kondisi kelas</p>
                <p class="text-[11px] text-ink-muted leading-tight mt-0.5">Lampiran foto kegiatan</p>
            </div>
        </div>
    </div>

    {{-- Attendance Table Header & Student Rows --}}
    <div class="mt-3.5">
        <div class="h-8 flex items-center justify-between text-xs font-medium text-ink-muted border-b border-hairline-strong px-1">
            <span>Daftar presensi (6 dari 32 siswa)</span>
            <span>Status (H · S · I · A)</span>
        </div>

        <ul class="divide-y divide-hairline">
            @foreach ($students as $student)
                <li class="py-2 px-1 flex items-center justify-between gap-2 sm:gap-3 hover:bg-raised/40 rounded transition-colors">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-ink-muted tabular-nums w-5 shrink-0">{{ $student['no'] }}</span>
                            <span class="text-[13px] font-medium text-ink truncate">{{ $student['name'] }}</span>
                        </div>
                        @if ($student['note'])
                            <p class="text-xs text-note font-medium pl-7 mt-0.5">{{ $student['note'] }}</p>
                        @endif
                    </div>

                    <x-attendance-code :active="$student['status']" class="shrink-0" />
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Summary Bar --}}
    <div class="mt-3 pt-3 border-t border-hairline-strong flex flex-wrap items-center justify-between gap-2 text-xs text-ink-secondary tabular-nums">
        <div class="flex items-center gap-1.5 font-medium">
            <span class="text-positive">Hadir 31</span>
            <span class="text-ink-muted">·</span>
            <span class="text-note">Sakit 0</span>
            <span class="text-ink-muted">·</span>
            <span class="text-note">Izin 1</span>
            <span class="text-ink-muted">·</span>
            <span class="text-danger">Alpa 0</span>
        </div>
        <span class="text-ink-muted font-medium">Guru Mata Pelajaran</span>
    </div>
</div>
