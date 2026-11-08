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

<div class="rounded-2xl bg-card border border-hairline-strong shadow-sheet p-4 sm:p-6" aria-label="Ilustrasi tampilan lembar jurnal mengajar dan presensi">
    <div class="flex items-center justify-between gap-2 pb-3 mb-3 border-b border-hairline">
        <span class="text-xs text-ink-muted">Ilustrasi tampilan sistem</span>
        <span class="inline-flex items-center h-6 px-2.5 rounded border border-accent-border bg-accent-tint text-xs font-medium text-accent-ink">
            Putaran 1
        </span>
    </div>

    <div class="pb-4 border-b border-hairline">
        <p class="text-xs text-ink-muted tabular-nums">Lembar jurnal harian · Senin, 9 November 2026</p>
        <h2 class="text-[17px] sm:text-[18px] font-medium text-ink leading-6 mt-1">Pemrograman Berorientasi Objek</h2>
        <p class="text-xs text-ink-secondary tabular-nums mt-1">Kelas XI RPL 2 · Lab RPL 1 · Jam ke-3–4 (08.30–10.00)</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 mt-4 p-3 rounded-lg bg-paper border border-hairline">
        <div class="sm:col-span-8">
            <p class="text-xs text-ink-muted">Materi pokok</p>
            <p class="text-[13px] text-ink leading-5 mt-0.5">Enkapsulasi dan pewarisan class pada modul peminjaman buku perpustakaan</p>
        </div>

        <div class="sm:col-span-4 flex items-center sm:justify-end gap-2.5 pt-2.5 sm:pt-0 sm:pl-3 border-t sm:border-t-0 sm:border-l border-hairline">
            <svg class="w-4.5 h-4.5 text-ink-muted shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
            </svg>
            <div>
                <p class="text-xs font-medium text-ink leading-4">Dokumentasi kondisi kelas</p>
                <p class="text-xs text-ink-secondary leading-4 mt-0.5">Lampiran foto kegiatan</p>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <div class="h-8 flex items-center justify-between text-xs text-ink-muted border-b border-hairline-strong">
            <span>Daftar presensi (6 dari 32 siswa)</span>
            <span>Status (H · S · I · A)</span>
        </div>

        <ul class="divide-y divide-hairline">
            @foreach ($students as $student)
                <li class="py-2.5 flex items-center justify-between gap-2 sm:gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xs text-ink-muted tabular-nums w-5 shrink-0">{{ $student['no'] }}</span>
                            <span class="text-[14px] text-ink truncate">{{ $student['name'] }}</span>
                        </div>
                        @if ($student['note'])
                            <p class="text-xs text-note pl-7 mt-0.5">{{ $student['note'] }}</p>
                        @endif
                    </div>

                    <x-attendance-code :active="$student['status']" class="shrink-0" />
                </li>
            @endforeach
        </ul>
    </div>

    <div class="mt-3 pt-3 border-t border-hairline-strong flex flex-wrap items-center justify-between gap-2 text-xs text-ink-secondary tabular-nums">
        <span>Rekap jam ke-3–4: Hadir 31 · Sakit 0 · Izin 1 · Alpa 0</span>
        <span class="text-ink-muted">Guru Mata Pelajaran</span>
    </div>
</div>
