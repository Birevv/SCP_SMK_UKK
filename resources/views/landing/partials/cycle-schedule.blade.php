<section id="jadwal-blok" class="py-20 sm:py-24 lg:py-28 relative">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {{-- Section Header --}}
        <div class="max-w-3xl mb-12">
            <span class="text-xs font-semibold tracking-wider text-[#1D4ED8] dark:text-blue-400 mb-3 block">
                SIKLUS PEMBELAJARAN
            </span>
            <h2 class="text-2xl sm:text-3xl lg:text-[38px] font-semibold text-ink leading-tight tracking-tight">
                Dirancang Mengikuti Sistem Pembelajaran Blok Dua Mingguan.
            </h2>
            <p class="text-[16px] text-ink-secondary leading-relaxed mt-4">
                Sistem informasi yang secara khusus mengakomodasi model pembelajaran Teaching Factory (TeFa) di mana siswa bergantian secara berkala antara praktikum bengkel intensif dan pendalaman teori umum.
            </p>
        </div>

        {{-- Keterangan Waka Kurikulum (Informasional, Bukan Kontrol Toggle Palsu) --}}
        <div class="p-4 sm:p-5 rounded-xl bg-paper border border-hairline mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="text-ink-secondary">
                <span>Putaran aktif dikelola oleh Waka Kurikulum dan digunakan sebagai acuan tampilan jadwal operasional.</span>
            </div>
            <span class="text-ink-muted">Siklus Aktif Saat Ini: <strong class="text-ink font-semibold">Putaran 1</strong></span>
        </div>

        {{-- Dua Kolom Komparatif: Putaran 1 vs Putaran 2 --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">
            
            {{-- PUTARAN 1 --}}
            <div class="rounded-2xl bg-card border-2 border-[#1D4ED8]/30 shadow-subtle p-7 sm:p-8 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-hairline">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-[#1D4ED8] dark:text-blue-400">Siklus Pertama</span>
                            <h3 class="text-xl font-bold text-ink mt-0.5">PUTARAN 1</h3>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-blue-50 text-[#1D4ED8] border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">
                            Praktik Kejuruan
                        </span>
                    </div>

                    <p class="text-[15px] text-ink-secondary leading-relaxed mt-4">
                        Siklus pembelajaran praktik kejuruan dan laboratorium. Rombel kejuruan difokuskan pada praktikum intensif bengkel, coding perangkat lunak, dan proyek Teaching Factory di lab.
                    </p>

                    <div class="mt-6 border-t border-hairline pt-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-ink-muted block mb-3">Contoh Alokasi Jadwal:</span>
                        <ul class="space-y-2.5 text-xs">
                            <li class="p-3 rounded-lg bg-paper border border-hairline flex items-center justify-between gap-2">
                                <div>
                                    <span class="font-semibold text-ink block">Senin · Pemrograman Berorientasi Objek</span>
                                    <span class="text-ink-muted block mt-0.5">Lab RPL 1 · 08.30 – 10.00 WIB</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-[#1D4ED8] border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">Praktik Lab</span>
                            </li>
                            <li class="p-3 rounded-lg bg-paper border border-hairline flex items-center justify-between gap-2">
                                <div>
                                    <span class="font-semibold text-ink block">Selasa · Basis Data Relasional</span>
                                    <span class="text-ink-muted block mt-0.5">Lab RPL 2 · 08.30 – 10.00 WIB</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-[#1D4ED8] border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">Praktik Lab</span>
                            </li>
                            <li class="p-3 rounded-lg bg-paper border border-hairline flex items-center justify-between gap-2">
                                <div>
                                    <span class="font-semibold text-ink block">Rabu · Pemrograman Web & Bergerak</span>
                                    <span class="text-ink-muted block mt-0.5">Lab RPL 1 · 08.30 – 11.30 WIB</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-[#1D4ED8] border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">Praktik Lab</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-hairline text-xs text-ink-muted flex items-center justify-between">
                    <span>Fokus: Kompetensi Praktik Bengkel/Lab</span>
                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-[#1D4ED8] border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">Putaran 1</span>
                </div>
            </div>

            {{-- PUTARAN 2 --}}
            <div class="rounded-2xl bg-card border border-hairline-strong shadow-subtle p-7 sm:p-8 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-hairline">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Siklus Kedua</span>
                            <h3 class="text-xl font-bold text-ink mt-0.5">PUTARAN 2</h3>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 border border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                            Teori Umum
                        </span>
                    </div>

                    <p class="text-[15px] text-ink-secondary leading-relaxed mt-4">
                        Siklus pembelajaran teori umum dan muatan nasional. Rombel beralih ke ruang teori untuk mata pelajaran matematika kejuruan, bahasa, dan muatan kurikulum akademik nasional.
                    </p>

                    <div class="mt-6 border-t border-hairline pt-4">
                        <span class="text-xs font-semibold uppercase tracking-wider text-ink-muted block mb-3">Contoh Alokasi Jadwal:</span>
                        <ul class="space-y-2.5 text-xs">
                            <li class="p-3 rounded-lg bg-paper border border-hairline flex items-center justify-between gap-2">
                                <div>
                                    <span class="font-semibold text-ink block">Senin · Matematika Terapan Kejuruan</span>
                                    <span class="text-ink-muted block mt-0.5">Ruang Teori 4 · 07.00 – 08.30 WIB</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">Teori Kelas</span>
                            </li>
                            <li class="p-3 rounded-lg bg-paper border border-hairline flex items-center justify-between gap-2">
                                <div>
                                    <span class="font-semibold text-ink block">Selasa · Bahasa Inggris Komunikasi Bisnis</span>
                                    <span class="text-ink-muted block mt-0.5">Ruang Teori 2 · 08.30 – 10.00 WIB</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">Teori Kelas</span>
                            </li>
                            <li class="p-3 rounded-lg bg-paper border border-hairline flex items-center justify-between gap-2">
                                <div>
                                    <span class="font-semibold text-ink block">Rabu · Pendidikan Pancasila & Sejarah</span>
                                    <span class="text-ink-muted block mt-0.5">Ruang Teori 5 · 08.30 – 10.00 WIB</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">Teori Kelas</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-hairline text-xs text-ink-muted flex items-center justify-between">
                    <span>Fokus: Penguatan Teori & Muatan Akademik</span>
                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">Putaran 2</span>
                </div>
            </div>

        </div>
    </div>
</section>
