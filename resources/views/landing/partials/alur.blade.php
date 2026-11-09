@php
    $steps = [
        [
            'marker' => '07.00',
            'title' => 'Pengaturan putaran dan jadwal',
            'body' => 'Waka Kurikulum mengatur siklus Putaran 1 atau Putaran 2 serta mengelola jadwal pembelajaran.',
        ],
        [
            'marker' => '08.30',
            'title' => 'Pengisian jurnal dan presensi',
            'body' => 'Guru mencatat materi pembelajaran dan kehadiran siswa sesuai jadwal mengajar.',
        ],
        [
            'marker' => 'Proses perizinan',
            'title' => 'Verifikasi surat izin',
            'body' => 'Siswa atau orang tua mengajukan izin. Wali Kelas atau Guru BK melakukan verifikasi sesuai kewenangannya.',
        ],
        [
            'marker' => 'Rekap kehadiran',
            'title' => 'Pemantauan dan evaluasi',
            'body' => 'Pihak sekolah dapat memantau rekapitulasi kehadiran berdasarkan hak akses masing-masing.',
        ],
    ];

    $blockDays = [
        ['day' => 'Senin', 'subject' => 'Pemrograman Berorientasi Objek', 'room' => 'Lab RPL 1', 'hours' => '08.30–10.00', 'active' => true],
        ['day' => 'Selasa', 'subject' => 'Basis Data', 'room' => 'Lab RPL 2', 'hours' => '08.30–10.00', 'active' => false],
        ['day' => 'Rabu', 'subject' => 'Pemrograman Web dan Perangkat Bergerak', 'room' => 'Lab RPL 1', 'hours' => '08.30–11.30', 'active' => false],
        ['day' => 'Kamis', 'subject' => 'Produk Kreatif dan Kewirausahaan', 'room' => 'Lab RPL 3', 'hours' => '08.30–11.30', 'active' => false],
        ['day' => 'Jumat', 'subject' => 'Konsentrasi Keahlian RPL', 'room' => 'Lab RPL 1', 'hours' => '07.00–10.00', 'active' => false],
    ];
@endphp

<section id="alur" class="border-t border-hairline py-14 sm:py-16 lg:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 sm:gap-10 lg:gap-12 items-start">
            <div class="lg:col-span-7">
                <p class="text-xs text-ink-muted">Alur sistem</p>
                <h2 class="section-title text-ink mt-2">
                    Tahapan pengelolaan jadwal, jurnal kelas, perizinan, dan rekapitulasi kehadiran
                </h2>

                <ol class="mt-8 divide-y divide-hairline border-t border-b border-hairline">
                    @foreach ($steps as $step)
                        <li class="py-5 flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-6">
                            <span class="text-[13px] font-medium text-accent-ink tabular-nums sm:w-36 shrink-0 sm:pt-0.5">{{ $step['marker'] }}</span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-[16px] font-bold text-ink leading-6">{{ $step['title'] }}</h3>
                                <p class="text-[14px] text-ink-secondary leading-6 mt-1">{{ $step['body'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-4 text-xs text-ink-muted">
                    Sistem memeriksa otomatis ketersediaan hari, putaran, ruang, kelas, dan guru saat jadwal disusun agar tidak terjadi bentrok jam mengajar.
                </p>
            </div>

            <div class="lg:col-span-5">
                <div class="rounded-2xl bg-card border border-hairline-strong p-4 sm:p-6" aria-label="Ilustrasi jadwal blok dua mingguan">
                    <div class="flex items-center justify-between gap-2 pb-3 mb-3 border-b border-hairline">
                        <span class="text-xs text-ink-muted">Ilustrasi tampilan sistem</span>
                        <span class="inline-flex items-center gap-1.5 text-xs text-ink-secondary">
                            <svg class="w-4 h-4 text-ink-muted shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            <span>Jadwal dikunci</span>
                        </span>
                    </div>

                    <div>
                        <p class="text-xs text-ink-muted tabular-nums">Jadwal blok dua mingguan · Senin, 9 November 2026</p>
                        <h3 class="text-[16px] font-bold text-ink mt-0.5">Siklus blok Kelas XI RPL 2</h3>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2 p-2 rounded-lg bg-paper border border-hairline" role="group" aria-label="Status putaran blok">
                        <div class="rounded bg-accent text-on-accent px-3 py-2">
                            <p class="text-xs font-medium">Putaran 1 · Aktif</p>
                            <p class="text-xs opacity-90 mt-0.5">Blok praktik kejuruan</p>
                        </div>
                        <div class="rounded bg-transparent text-ink-muted px-3 py-2">
                            <p class="text-xs">Putaran 2</p>
                            <p class="text-xs mt-0.5">Blok mata pelajaran umum</p>
                        </div>
                    </div>

                    <ul class="mt-4 divide-y divide-hairline">
                        @foreach ($blockDays as $item)
                            <li class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-3">
                                <div class="min-w-0 flex-1 flex items-center gap-2">
                                    <span class="text-xs w-14 shrink-0 {{ $item['active'] ? 'text-accent-ink font-medium' : 'text-ink-muted' }}">{{ $item['day'] }}</span>
                                    <span class="text-[14px] text-ink truncate">{{ $item['subject'] }}</span>
                                </div>
                                <div class="pl-16 sm:pl-0 sm:text-right shrink-0 text-xs tabular-nums">
                                    <span class="text-ink-secondary">{{ $item['room'] }}</span>
                                    <span class="text-ink-muted ml-1.5">{{ $item['hours'] }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-3 pt-3 border-t border-hairline flex flex-wrap items-center justify-between gap-2 text-xs text-ink-muted">
                        <span>Senin, Jam ke-3–4 (08.30–10.00)</span>
                        <span>Guru Mata Pelajaran</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
