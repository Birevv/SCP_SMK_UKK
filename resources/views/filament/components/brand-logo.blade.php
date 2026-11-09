@php
    $logoSrc = file_exists(public_path('images/logo_smk.png'))
        ? asset('images/logo_smk.png')
        : asset(file_exists(public_path('storage/assets/SMK_Negeri_1_Bantul_logo (1).png')) ? 'storage/assets/SMK_Negeri_1_Bantul_logo (1).png' : 'storage/Assets/SMK_Negeri_1_Bantul_logo (1).png');
@endphp

<a href="{{ route('landing') }}" class="fi-brand-logo-wrapper hover:opacity-90 transition-opacity" title="Kembali ke Beranda">
    <img
        src="{{ $logoSrc }}"
        alt="Logo SMK Negeri 1 Bantul"
        class="fi-brand-logo-img"
    />
    <span class="fi-brand-logo-text">
        School Class Progress
    </span>
</a>

