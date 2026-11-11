@php
    $logoSrc = file_exists(public_path('images/logo_smk.png'))
        ? asset('images/logo_smk.png')
        : asset('logo.png');
    $apkUrl = $apkUrl ?? null;
    $apkVersion = $apkVersion ?? null;
    $apkSha256 = $apkSha256 ?? null;
@endphp

<footer class="bg-[#1E293B] dark:bg-[#09090b] text-white border-t border-slate-800 dark:border-zinc-800 pt-16 pb-12 transition-colors">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {{-- Grid Footer Minimalis --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 lg:gap-8 pb-12 border-b border-slate-700/80 dark:border-zinc-800">
            
            {{-- Kolom 1: Logo & Nama SCP + Deskripsi Pendek (Col 4) --}}
            <div class="lg:col-span-4 flex flex-col items-start">
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

                <p class="text-sm text-slate-400 leading-relaxed mt-4 max-w-sm">
                    Sistem informasi presensi, jurnal mengajar guru, dan perizinan siswa berbasis siklus jadwal blok dua mingguan untuk SMK Negeri 1 Bantul.
                </p>

                <div class="mt-6 text-xs text-slate-400">
                    <span>Tahun Ajaran 2026/2027</span>
                </div>
            </div>

            {{-- Kolom 2: Navigasi (Col 2) --}}
            <div class="lg:col-span-2">
                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3.5">Navigasi</h3>
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
                    <li>
                        <a href="{{ route('docs') }}" class="text-[#ff2d20] hover:text-red-400 font-medium transition-colors">Dokumentasi</a>
                    </li>
                    <li>
                        <a href="{{ route('download') }}" class="text-white font-medium hover:text-blue-300 transition-colors">Download APK</a>
                    </li>
                </ul>
            </div>

            {{-- Kolom 3: Aplikasi Mobile (Col 3) --}}
            <div class="lg:col-span-3">
                <span id="aplikasi" class="sr-only" aria-hidden="true"></span>
                <span id="download" class="sr-only" aria-hidden="true"></span>

                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3.5">Aplikasi Mobile</h3>
                @if ($apkUrl)
                    <div class="space-y-2">
                        <a
                            href="{{ $apkUrl }}"
                            rel="noopener"
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-800 text-white border border-slate-700 hover:bg-slate-700 text-xs font-medium transition-colors"
                        >
                            <svg class="w-4 h-4 text-blue-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span>Unduh APK</span>
                        </a>
                        @if ($apkVersion)
                            <p class="text-xs text-slate-400 tabular-nums">Versi {{ $apkVersion }}</p>
                        @endif
                        @if ($apkSha256)
                            <p class="text-[11px] text-slate-500 font-mono break-all">SHA-256: {{ $apkSha256 }}</p>
                        @endif
                    </div>
                @else
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800/80 border border-slate-700 text-xs text-slate-300" aria-disabled="true">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                            <span>Aplikasi Android segera hadir</span>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Aplikasi sedang dalam tahap persiapan sebelum dibagikan kepada pengguna sekolah.
                        </p>
                    </div>
                @endif
            </div>

            {{-- Kolom 4: Informasi Sekolah (Col 3) --}}
            <div class="lg:col-span-3">
                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3.5">Informasi Sekolah</h3>
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
