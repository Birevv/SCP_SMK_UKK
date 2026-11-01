@php
    $logoSrc = asset(file_exists(public_path('storage/assets/SMK_Negeri_1_Bantul_logo (1).png')) ? 'storage/assets/SMK_Negeri_1_Bantul_logo (1).png' : 'storage/Assets/SMK_Negeri_1_Bantul_logo (1).png');
@endphp

<div class="fi-brand-logo-wrapper">
    <img
        src="{{ $logoSrc }}"
        alt="Logo SMK Negeri 1 Bantul"
        class="fi-brand-logo-img"
    />
    <span class="fi-brand-logo-text">
        School Class Progress
    </span>
</div>

