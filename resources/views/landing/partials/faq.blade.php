@php
    $faqs = [
        [
            'question' => 'Apa itu School Class Progress?',
            'answer' => 'School Class Progress (SCP) adalah sistem informasi sekolah untuk mengelola jadwal pembelajaran blok, pencatatan jurnal mengajar guru, presensi kehadiran siswa setiap jam pelajaran, serta pengajuan dan verifikasi surat izin siswa secara terintegrasi.',
        ],
        [
            'question' => 'Siapa saja yang dapat menggunakan sistem SCP?',
            'answer' => 'Sistem ini digunakan oleh lima peran pengguna sesuai kewenangannya di sekolah, yaitu Waka Kurikulum, Guru, Wali Kelas, Guru BK, serta Siswa dan Orang Tua.',
        ],
        [
            'question' => 'Bagaimana mekanisme Putaran 1 dan Putaran 2?',
            'answer' => 'Sekolah menerapkan sistem jadwal blok dua mingguan yang terbagi menjadi Putaran 1 dan Putaran 2. Waka Kurikulum mengatur putaran yang sedang aktif pada sistem sehingga daftar jadwal mengajar guru dan jadwal pelajaran siswa otomatis menyesuaikan putaran tersebut.',
        ],
        [
            'question' => 'Bagaimana siswa atau orang tua mengajukan surat izin?',
            'answer' => 'Siswa atau orang tua masuk menggunakan akun yang terdaftar, membuka menu pengajuan surat izin, memilih kategori izin atau sakit beserta rentang tanggal dan keterangan alasan, lalu mengunggah foto dokumen pendukung.',
        ],
        [
            'question' => 'Siapa yang dapat melakukan verifikasi surat izin?',
            'answer' => 'Pengajuan surat izin diverifikasi oleh Wali Kelas untuk siswa pada kelas binaannya atau oleh Guru BK sesuai kewenangan masing-masing. Surat izin yang telah disetujui otomatis tersinkron ke daftar presensi siswa pada jurnal mengajar guru.',
        ],
        [
            'question' => 'Bagaimana pengguna mengakses aplikasi SCP?',
            'answer' => 'Pengguna dapat mengakses SCP melalui panel web menggunakan peramban di komputer, tablet, maupun ponsel melalui tombol Masuk. Aplikasi Android untuk perangkat bergerak saat ini sedang dalam tahap persiapan.',
        ],
        [
            'question' => 'Siapa yang harus dihubungi apabila mengalami kendala akses?',
            'answer' => 'Apabila mengalami kendala masuk ke akun, lupa kata sandi, atau terdapat ketidaksesuaian data kelas dan jadwal, pengguna dapat menghubungi administrator sistem sekolah atau Waka Kurikulum SMK Negeri 1 Bantul.',
        ],
    ];
@endphp

<section id="faq" class="border-t border-hairline py-14 sm:py-16 lg:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 sm:gap-10 lg:gap-12 items-start">
            <div class="lg:col-span-5">
                <p class="text-xs text-ink-muted">Pertanyaan umum</p>
                <h2 class="section-title text-ink mt-2">
                    Informasi penggunaan sistem School Class Progress
                </h2>
                <p class="text-[15px] text-ink-secondary leading-6 mt-3">
                    Ringkasan jawaban mengenai alur penggunaan, mekanisme jadwal blok, perizinan siswa, dan akses pengguna.
                </p>
            </div>

            <div class="lg:col-span-7">
                <div class="rounded-2xl bg-card border border-hairline-strong divide-y divide-hairline">
                    @foreach ($faqs as $index => $faq)
                        <details class="group" @if ($index === 0) open @endif>
                            <summary class="p-4 sm:p-5 flex items-center justify-between gap-4 cursor-pointer select-none text-[15px] font-medium text-ink hover:bg-raised/60 ui-interactive ui-focus rounded-xl">
                                <span>{{ $faq['question'] }}</span>
                                <svg class="faq-chevron w-4.5 h-4.5 text-ink-muted shrink-0 ui-interactive" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </summary>
                            <div class="px-4 sm:px-5 pb-4 sm:pb-5 text-[14px] text-ink-secondary leading-6">
                                {{ $faq['answer'] }}
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

