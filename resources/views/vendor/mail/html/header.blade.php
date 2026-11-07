@props(['url'])
@php
    $appUrl = config('app.url');
    $logoUrl = (!empty($appUrl) && !str_contains($appUrl, 'localhost') && !str_contains($appUrl, '127.0.0.1'))
        ? asset('images/logo_smk.png')
        : 'https://progreskelas.my.id/images/logo_smk.png';
@endphp
<tr>
<td class="header" style="text-align: center; padding: 25px 0;">
<a href="{{ $url }}" style="display: inline-block; text-align: center; text-decoration: none;">
    <img src="{{ $logoUrl }}" class="logo" alt="Logo SMK" style="height: 75px; width: 75px; max-height: 75px; object-fit: contain; display: block; margin: 0 auto;">
    <span style="display: block; margin-top: 10px; font-size: 16px; font-weight: 700; color: #1e293b;">
        School Class Progress
    </span>
</a>
</td>
</tr>
