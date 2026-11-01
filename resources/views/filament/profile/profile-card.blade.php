@php
    $user = auth()->user();
    $roleName = $user?->role?->name ?? 'Pengguna';
    
    // Inisial avatar dua huruf dari nama depan & belakang (contoh: Kibutsuji Muzan -> KM)
    $nameParts = array_values(array_filter(explode(' ', trim($user?->full_name ?? ''))));
    if (count($nameParts) >= 2) {
        $initials = strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr(end($nameParts), 0, 1));
    } else {
        $initials = strtoupper(mb_substr($user?->full_name ?? 'U', 0, 2));
    }

    $roleTagClass = match($roleName) {
        'Super Admin' => 'scp-tag-super-admin',
        'Waka Kurikulum' => 'scp-tag-waka',
        'Guru' => 'scp-tag-guru',
        'Wali Kelas' => 'scp-tag-wali',
        'Guru BK' => 'scp-tag-bk',
        'Siswa' => 'scp-tag-siswa',
        default => 'scp-tag-default',
    };
@endphp

<style>
    /* Profile Card Scope */
    .scp-profile-card {
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        box-sizing: border-box;
    }
    .scp-profile-header {
        display: flex !important;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1.25rem;
    }
    .scp-profile-identity {
        display: flex !important;
        align-items: center;
        gap: 1.25rem;
        min-width: 0;
    }
    .scp-avatar-container {
        position: relative;
        flex-shrink: 0;
        width: 4rem !important;
        height: 4rem !important;
    }
    .scp-avatar-img {
        width: 4rem !important;
        height: 4rem !important;
        border-radius: 9999px !important;
        object-fit: cover;
        border: 1px solid rgba(0, 0, 0, 0.08);
        display: block;
    }
    .scp-avatar-fallback {
        width: 4rem !important;
        height: 4rem !important;
        border-radius: 9999px !important;
        background-color: #f1f5f9;
        color: #1e293b;
        display: flex !important;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        font-weight: 600;
        letter-spacing: 0.025em;
        border: 1px solid #e2e8f0;
        user-select: none;
    }
    .scp-identity-text {
        min-width: 0;
        flex: 1;
    }
    .scp-user-name-row {
        display: flex !important;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.65rem;
    }
    .scp-user-name {
        font-size: 1.25rem;
        font-weight: 600;
        letter-spacing: -0.015em;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
    }
    .scp-role-tag {
        display: inline-flex !important;
        align-items: center;
        padding: 0.2rem 0.6rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1.25;
        white-space: nowrap;
    }
    .scp-tag-super-admin {
        background-color: #faf5ff;
        color: #7e22ce;
        border: 1px solid #e9d5ff;
    }
    .scp-tag-waka {
        background-color: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
    }
    .scp-tag-guru {
        background-color: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .scp-tag-wali {
        background-color: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    .scp-tag-bk {
        background-color: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .scp-tag-siswa {
        background-color: #f0fdfa;
        color: #0f766e;
        border: 1px solid #99f6e4;
    }
    .scp-tag-default {
        background-color: #f8fafc;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .scp-user-meta {
        display: flex !important;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.65rem;
        font-size: 0.8125rem;
        color: #64748b;
        margin-top: 0.35rem;
    }
    .scp-meta-username {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
        font-size: 0.75rem;
        color: #64748b;
    }
    .scp-meta-dot {
        color: #cbd5e1;
    }
    .scp-card-divider {
        height: 1px;
        background-color: #e2e8f0;
        margin: 1.25rem 0 1rem 0;
    }
    .scp-meta-heading {
        font-size: 0.75rem;
        font-weight: 500;
        color: #64748b;
        margin-bottom: 0.75rem;
    }
    .scp-data-grid {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 0.75rem !important;
    }
    @media (max-width: 768px) {
        .scp-data-grid {
            grid-template-columns: 1fr !important;
        }
    }
    .scp-data-card {
        display: flex !important;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border-radius: 0.5rem;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        box-sizing: border-box;
    }
    .scp-data-icon {
        width: 1.125rem;
        height: 1.125rem;
        color: #94a3b8;
        margin-top: 0.125rem;
        flex-shrink: 0;
    }
    .scp-data-content {
        min-width: 0;
        flex: 1;
    }
    .scp-data-label {
        font-size: 0.6875rem;
        font-weight: 500;
        color: #64748b;
    }
    .scp-data-val {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #0f172a;
        margin-top: 0.2rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .scp-data-val-mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
        font-size: 0.8125rem;
        font-weight: 500;
        font-variant-numeric: tabular-nums;
        color: #0f172a;
        letter-spacing: 0.025em;
        margin-top: 0.2rem;
    }

    /* DARK MODE OVERRIDES */
    html.dark .scp-profile-card,
    .dark .scp-profile-card {
        background-color: #111827;
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.2);
    }
    html.dark .scp-avatar-fallback,
    .dark .scp-avatar-fallback {
        background-color: #1f2937;
        color: #f3f4f6;
        border-color: rgba(255, 255, 255, 0.12);
    }
    html.dark .scp-avatar-img,
    .dark .scp-avatar-img {
        border-color: rgba(255, 255, 255, 0.12);
    }
    html.dark .scp-user-name,
    .dark .scp-user-name {
        color: #f9fafb;
    }
    html.dark .scp-user-meta,
    .dark .scp-user-meta {
        color: #9ca3af;
    }
    html.dark .scp-meta-username,
    .dark .scp-meta-username {
        color: #9ca3af;
    }
    html.dark .scp-meta-dot,
    .dark .scp-meta-dot {
        color: #4b5563;
    }
    html.dark .scp-card-divider,
    .dark .scp-card-divider {
        background-color: rgba(255, 255, 255, 0.08);
    }
    html.dark .scp-meta-heading,
    .dark .scp-meta-heading {
        color: #9ca3af;
    }
    html.dark .scp-data-card,
    .dark .scp-data-card {
        background-color: rgba(255, 255, 255, 0.03);
        border-color: rgba(255, 255, 255, 0.07);
    }
    html.dark .scp-data-icon,
    .dark .scp-data-icon {
        color: #6b7280;
    }
    html.dark .scp-data-label,
    .dark .scp-data-label {
        color: #9ca3af;
    }
    html.dark .scp-data-val,
    .dark .scp-data-val {
        color: #f9fafb;
    }
    html.dark .scp-data-val-mono,
    .dark .scp-data-val-mono {
        color: #f9fafb;
    }
    html.dark .scp-tag-super-admin,
    .dark .scp-tag-super-admin {
        background-color: rgba(126, 34, 206, 0.15);
        color: #d8b4fe;
        border-color: rgba(126, 34, 206, 0.3);
    }
    html.dark .scp-tag-waka,
    .dark .scp-tag-waka {
        background-color: rgba(67, 56, 202, 0.15);
        color: #a5b4fc;
        border-color: rgba(67, 56, 202, 0.3);
    }
    html.dark .scp-tag-guru,
    .dark .scp-tag-guru {
        background-color: rgba(22, 163, 74, 0.15);
        color: #86efac;
        border-color: rgba(22, 163, 74, 0.3);
    }
    html.dark .scp-tag-wali,
    .dark .scp-tag-wali {
        background-color: rgba(29, 78, 216, 0.15);
        color: #93c5fd;
        border-color: rgba(29, 78, 216, 0.3);
    }
    html.dark .scp-tag-bk,
    .dark .scp-tag-bk {
        background-color: rgba(180, 83, 9, 0.15);
        color: #fde68a;
        border-color: rgba(180, 83, 9, 0.3);
    }
    html.dark .scp-tag-siswa,
    .dark .scp-tag-siswa {
        background-color: rgba(15, 118, 110, 0.15);
        color: #5eead4;
        border-color: rgba(15, 118, 110, 0.3);
    }
    html.dark .scp-tag-default,
    .dark .scp-tag-default {
        background-color: rgba(255, 255, 255, 0.05);
        color: #d1d5db;
        border-color: rgba(255, 255, 255, 0.1);
    }
</style>

<div class="scp-profile-card">
    <!-- Top Row: Avatar & Identity Header -->
    <div class="scp-profile-header">
        <div class="scp-profile-identity">
            <!-- Avatar -->
            <div class="scp-avatar-container">
                @if ($user?->profile_photo_url)
                    <img
                        src="{{ $user->profile_photo_url }}"
                        alt="{{ $user->full_name }}"
                        class="scp-avatar-img"
                    >
                @else
                    <div class="scp-avatar-fallback">
                        {{ $initials }}
                    </div>
                @endif
            </div>

            <!-- Name, Meta, and Role Tag -->
            <div class="scp-identity-text">
                <div class="scp-user-name-row">
                    <h2 class="scp-user-name">
                        {{ $user?->full_name }}
                    </h2>
                    <span class="scp-role-tag {{ $roleTagClass }}">
                        {{ $roleName }}
                    </span>
                </div>

                <div class="scp-user-meta">
                    <span class="scp-meta-username">{{ '@' . ($user?->username ?? 'user') }}</span>
                    <span class="scp-meta-dot">•</span>
                    <span>{{ $user?->email ?? 'Belum ada email' }}</span>
                    @if ($user?->phone)
                        <span class="scp-meta-dot">•</span>
                        <span>{{ $user->phone }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quiet Hairline Divider -->
    <div class="scp-card-divider"></div>

    <!-- Structured Institutional Metadata Grid -->
    <div>
        <div class="scp-meta-heading">
            Informasi identitas akun
        </div>

        <div class="scp-data-grid">
            <!-- 1. Identitas (NISN / NIP) -->
            <div class="scp-data-card">
                <svg class="scp-data-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                </svg>
                <div class="scp-data-content">
                    <div class="scp-data-label">
                        Nomor Identitas (NISN / NIP)
                    </div>
                    <div class="scp-data-val-mono">
                        {{ $user?->identity_number ?: '-' }}
                    </div>
                </div>
            </div>

            <!-- 2. Peran Sistem -->
            <div class="scp-data-card">
                <svg class="scp-data-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
                <div class="scp-data-content">
                    <div class="scp-data-label">
                        Peran dalam Sistem
                    </div>
                    <div class="scp-data-val">
                        {{ $roleName }}
                    </div>
                </div>
            </div>

            <!-- 3. Rombel / Penugasan -->
            <div class="scp-data-card">
                <svg class="scp-data-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                </svg>
                <div class="scp-data-content">
                    <div class="scp-data-label">
                        Penugasan & Rombel
                    </div>
                    <div class="scp-data-val" title="{{ $user?->assignment ?: '-' }}">
                        {{ $user?->assignment ?: '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
