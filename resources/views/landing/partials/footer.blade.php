@php
    $logoSrc = file_exists(public_path('images/logo_smk.png'))
        ? asset('images/logo_smk.png')
        : asset('logo.png');
@endphp

<footer class="border-t border-hairline py-10 sm:py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-8 border-b border-hairline">
            <div class="flex items-start sm:items-center gap-3">
                <img
                    src="{{ $logoSrc }}"
                    alt="Logo SMK Negeri 1 Bantul"
                    width="28"
                    height="28"
                    class="w-7 h-7 object-contain shrink-0 mt-0.5 sm:mt-0"
                />
                <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                    <span class="text-[15px] font-medium text-ink">School Class Progress</span>
                    <span class="text-ink-muted text-[13px]" aria-hidden="true">·</span>
                    <span class="text-[14px] text-ink-secondary">SMK Negeri 1 Bantul</span>
                    <span class="text-ink-muted text-[13px]" aria-hidden="true">·</span>
                    <span class="text-[13px] text-ink-muted tabular-nums">Tahun Ajaran 2026/2027</span>
                </div>
            </div>

            <nav aria-label="Navigasi footer" class="flex flex-wrap items-center gap-x-6 gap-y-2 text-[14px]">
                <a href="#alur" class="text-ink-secondary hover:text-ink ui-interactive ui-focus rounded">Alur</a>
                <a href="#peran" class="text-ink-secondary hover:text-ink ui-interactive ui-focus rounded">Peran</a>
                <a href="#aplikasi" class="text-ink-secondary hover:text-ink ui-interactive ui-focus rounded">Aplikasi</a>
                <a href="#faq" class="text-ink-secondary hover:text-ink ui-interactive ui-focus rounded">FAQ</a>
                <a href="{{ $loginUrl }}" class="font-medium text-accent-ink hover:text-accent-hover ui-interactive ui-focus rounded">Masuk</a>
            </nav>
        </div>

        <div class="pt-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-[13px] text-ink-muted">
            <p>
                Bantuan kendala akses dilayani melalui administrator sistem atau Waka Kurikulum SMK Negeri 1 Bantul.
            </p>
            <p class="tabular-nums shrink-0">
                &copy; 2026 School Class Progress · SMK Negeri 1 Bantul
            </p>
        </div>
    </div>
</footer>
