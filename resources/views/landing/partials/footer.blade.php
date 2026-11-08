@php
    $logoSrc = file_exists(public_path('images/logo_smk.png'))
        ? asset('images/logo_smk.png')
        : asset('logo.png');
@endphp

<footer class="bg-[#1E293B] text-white border-t border-slate-800 pt-16 pb-12 transition-colors">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {{-- Grid Footer Minimalis --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 lg:gap-8 pb-12 border-b border-slate-700/80">
            
            {{-- Kolom 1: Logo & Nama SCP + Deskripsi Pendek (Col 6) --}}
            <div class="lg:col-span-6 flex flex-col items-start">
                <a href="{{ route('landing') }}" class="inline-flex items-center gap-3 ui-focus rounded-lg group">
                    <img
                        src="{{ $logoSrc }}"
                        alt="Logo SMK Negeri 1 Bantul"
                        width="36"
                        height="36"
                        class="w-9 h-9 object-contain shrink-0"
                    />
                    <div class="flex flex-col">
                        <span class="text-base font-semibold text-white tracking-tight leading-tight">School Class Progress</span>
                        <span class="text-xs text-slate-400 leading-tight">SMK Negeri 1 Bantul</span>
                    </div>
                </a>

                <p class="text-sm text-slate-400 leading-relaxed mt-4 max-w-md">
                    Sistem informasi presensi, jurnal mengajar guru, dan perizinan siswa berbasis siklus jadwal blok dua mingguan untuk SMK Negeri 1 Bantul.
                </p>

                <div class="mt-6 text-xs text-slate-400">
                    <span>Tahun Ajaran 2026/2027</span>
                </div>
            </div>

            {{-- Kolom 2: Navigasi (Col 3) --}}
            <div class="lg:col-span-3">
                <h3 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-3.5">Navigasi</h3>
                <ul class="space-y-2.5 text-sm">
                    <li>
                        <a href="#hero" class="text-slate-400 hover:text-white transition-colors">Beranda</a>
                    </li>
                    <li>
                        <a href="#fitur" class="text-slate-400 hover:text-white transition-colors">Fitur</a>
                    </li>
                    <li>
                        <a href="#cara-kerja" class="text-slate-400 hover:text-white transition-colors">Cara kerja</a>
                    </li>
                    <li>
                        <a href="#preview-fitur" class="text-slate-400 hover:text-white transition-colors">Antarmuka</a>
                    </li>
                    <li>
                        <a href="#tentang" class="text-slate-400 hover:text-white transition-colors">Tentang kami</a>
                    </li>
                    <li>
                        <a href="#faq" class="text-slate-400 hover:text-white transition-colors">FAQ</a>
                    </li>
                </ul>
            </div>

            {{-- Kolom 3: Informasi Sekolah (Col 3) --}}
            <div class="lg:col-span-3">
                <h3 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-3.5">Informasi Sekolah</h3>
                <div class="text-sm text-slate-400 space-y-1.5 leading-relaxed">
                    <p class="font-medium text-slate-200">SMK Negeri 1 Bantul</p>
                    <p class="text-xs">Jl. Parangtritis Km 11, Sabdodadi, Bantul, D.I. Yogyakarta 55715</p>
                    <p class="text-xs text-slate-500 pt-2">
                        Kompetensi Keahlian Rekayasa Perangkat Lunak (RPL)
                    </p>
                    <div class="pt-2">
                        <a
                            href="{{ $loginUrl }}"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-400 hover:text-blue-300 transition-colors"
                        >
                            <span>Masuk ke Panel Operasional</span>
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        {{-- Copyright & Catatan Bawah --}}
        <div class="pt-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs text-slate-400">
            <p>
                Bantuan kendala akses dilayani melalui administrator sistem atau Waka Kurikulum SMK Negeri 1 Bantul.
            </p>
            <p class="tabular-nums shrink-0">
                &copy; 2026 School Class Progress. All Rights Reserved.
            </p>
        </div>

    </div>
</footer>
