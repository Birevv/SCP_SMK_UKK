@php
    $faqs = [
        [
            'question' => 'Apa itu School Class Progress?',
            'answer' => 'School Class Progress (SCP) adalah sistem informasi sekolah yang dirancang khusus untuk SMK Negeri 1 Bantul guna mengelola jadwal pembelajaran blok dua mingguan, pencatatan jurnal mengajar guru, presensi kehadiran siswa setiap jam pelajaran, serta pengajuan dan verifikasi perizinan siswa secara terintegrasi.',
        ],
        [
            'question' => 'Bagaimana guru mengisi jurnal mengajar?',
            'answer' => 'Guru masuk ke panel operasional pada jadwal mengajar aktif hari tersebut, menginput rangkuman materi pokok pembelajaran, mengunggah dokumentasi foto suasana kegiatan kelas secara langsung, serta memeriksa daftar presensi siswa yang otomatis telah terisi.',
        ],
        [
            'question' => 'Bagaimana sistem menentukan Putaran 1 dan Putaran 2?',
            'answer' => 'Waka Kurikulum mengatur siklus putaran aktif (Putaran 1 atau Putaran 2) melalui saklar global pada dashboard. Begitu putaran diubah, seluruh matriks jadwal pelajaran, dashboard guru, dan form jurnal otomatis menyesuaikan putaran tersebut tanpa perlu filter manual.',
        ],
        [
            'question' => 'Apakah presensi siswa tersinkronisasi dengan pengajuan izin?',
            'answer' => 'Ya, sistem menerapkan sinkronisasi dua arah otomatis. Siswa yang memiliki surat izin atau sakit berstatus Approved otomatis tercatat sebagai Sakit atau Izin pada jurnal harian guru tanpa perlu input manual berulang kali.',
        ],
        [
            'question' => 'Siapa yang dapat melakukan verifikasi surat izin siswa?',
            'answer' => 'Verifikasi dan persetujuan surat permohonan izin atau sakit dilakukan oleh Wali Kelas untuk siswa di rombel binaannya, serta Guru BK sesuai kewenangan bimbingan konseling dan kesiswaan.',
        ],
        [
            'question' => 'Apakah laporan presensi dapat dicetak?',
            'answer' => 'Ya. Sistem menyediakan modul rekapitulasi kehadiran lengkap dengan kalkulasi jumlah Hadir, Sakit, Izin, Alpa, dan persentase kehadiran bulanan yang siap dicetak langsung dalam format kertas A4 resmi sekolah.',
        ],
        [
            'question' => 'Bagaimana cara siswa mengajukan izin sakit?',
            'answer' => 'Siswa atau orang tua masuk menggunakan akun NISN, membuka formulir pengajuan izin, memilih jenis Sakit, menentukan rentang tanggal, memberikan keterangan alasan, dan mengunggah foto surat keterangan dokter atau surat dari orang tua.',
        ],
        [
            'question' => 'Siapa yang harus dihubungi apabila mengalami kendala akses?',
            'answer' => 'Apabila mengalami kendala masuk ke akun, lupa kata sandi, atau terdapat ketidaksesuaian data kelas dan jadwal, pengguna dapat menghubungi administrator sistem sekolah atau Waka Kurikulum SMK Negeri 1 Bantul.',
        ],
    ];
@endphp

<section id="faq" class="py-20 sm:py-24 lg:py-28 relative">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start">
            
            {{-- Kolom Kiri: Heading, Deskripsi & Tombol Masuk Panel (5 Kolom) --}}
            <div class="lg:col-span-5 flex flex-col items-start lg:sticky lg:top-28">
                <span class="text-xs font-semibold tracking-wider text-[#1D4ED8] dark:text-blue-400 mb-3">
                    BANTUAN & PANDUAN
                </span>

                <h2 class="text-2xl sm:text-3xl lg:text-[38px] font-semibold text-ink leading-[1.16] tracking-tight">
                    Pertanyaan yang Sering Diajukan.
                </h2>

                <p class="text-[16px] text-ink-secondary leading-relaxed mt-4">
                    Temukan informasi mengenai penggunaan School Class Progress dan fitur yang tersedia. Tim kami siap membantu Anda dengan informasi yang jelas dan terpercaya kapan pun dibutuhkan.
                </p>

                <div class="mt-8">
                    <a
                        href="{{ $loginUrl }}"
                        class="inline-flex items-center justify-center gap-2.5 h-11 px-5 rounded-lg bg-[#1D4ED8] text-white text-[14px] font-medium hover:bg-blue-800 shadow-subtle ui-interactive ui-focus"
                    >
                        <span>Masuk Panel</span>
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>

                <div class="mt-10 pt-5 border-t border-hairline w-full text-xs text-ink-muted">
                    <p>Kendala akun dilayani oleh Admin Sekolah & Waka Kurikulum SMK Negeri 1 Bantul.</p>
                </div>
            </div>

            {{-- Kolom Kanan: Accordion Elegan dengan Pembatas Horizontal Tipis Saja (7 Kolom, Tanpa Kotak Tebal) --}}
            <div class="lg:col-span-7 divide-y divide-hairline border-t border-b border-hairline">
                @foreach ($faqs as $index => $faq)
                    <details class="group py-5 first:pt-4 last:pb-4" @if ($index === 0) open @endif>
                        <summary class="flex items-center justify-between gap-4 cursor-pointer select-none text-[16px] font-medium text-ink hover:text-[#1D4ED8] transition-colors ui-focus">
                            <span class="leading-snug">{{ $faq['question'] }}</span>
                            <span class="w-7 h-7 rounded-md bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 flex items-center justify-center shrink-0 text-slate-600 dark:text-slate-300 group-hover:bg-blue-50 group-hover:text-[#1D4ED8] group-hover:border-blue-300 dark:group-hover:bg-blue-950 dark:group-hover:text-blue-300 transition-colors shadow-2xs">
                                <svg class="faq-icon w-4 h-4 transition-transform duration-150" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </span>
                        </summary>
                        <div class="pt-3 text-[15px] text-ink-secondary leading-relaxed pr-6">
                            {{ $faq['answer'] }}
                        </div>
                    </details>
                @endforeach
            </div>

        </div>
    </div>
</section>
