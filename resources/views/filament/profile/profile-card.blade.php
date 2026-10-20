@php
    $user = auth()->user();
    $roleName = $user?->role?->name ?? 'Pengguna';
    $initials = strtoupper(substr($user?->full_name ?? 'U', 0, 2));

    $roleBadgeClass = match($roleName) {
        'Super Admin' => 'scp-badge-super-admin',
        'Waka Kurikulum' => 'scp-badge-waka',
        'Guru' => 'scp-badge-guru',
        'Wali Kelas' => 'scp-badge-wali',
        'Guru BK' => 'scp-badge-bk',
        'Siswa' => 'scp-badge-siswa',
        default => 'scp-badge-default',
    };
@endphp

<style>
    .scp-profile-card {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .scp-user-name {
        font-size: 1.35rem;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }
    .scp-user-meta {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.85rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
    .scp-divider {
        height: 1px;
        background-color: #e5e7eb;
        margin: 1.25rem 0 1rem 0;
    }
    .scp-section-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
        margin-bottom: 0.75rem;
    }
    .scp-data-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
    }
    .scp-data-card {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.85rem 1rem;
        border-radius: 0.625rem;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        transition: all 0.15s ease-in-out;
    }
    .scp-data-card-label {
        font-size: 0.72rem;
        font-weight: 500;
        color: #6b7280;
    }
    .scp-data-card-value {
        font-size: 0.925rem;
        font-weight: 700;
        color: #111827;
        margin-top: 0.15rem;
    }
    .scp-icon-box {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        flex-shrink: 0;
    }
    .scp-icon-identity {
        background-color: #eff6ff;
        color: #2563eb;
    }
    .scp-icon-role {
        background-color: #f3e8ff;
        color: #7e22ce;
    }
    .scp-icon-assignment {
        background-color: #e0e7ff;
        color: #4338ca;
    }
    .scp-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.2rem 0.65rem;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
    }
    .scp-badge-super-admin {
        background-color: #f3e8ff;
        color: #7e22ce;
        border: 1px solid #d8b4fe;
    }
    .scp-badge-waka {
        background-color: #e0e7ff;
        color: #4338ca;
        border: 1px solid #a5b4fc;
    }
    .scp-badge-guru {
        background-color: #ecfdf5;
        color: #047857;
        border: 1px solid #6ee7b7;
    }
    .scp-badge-wali {
        background-color: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #93c5fd;
    }
    .scp-badge-bk {
        background-color: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .scp-badge-siswa {
        background-color: #f0fdfa;
        color: #0f766e;
        border: 1px solid #99f6e4;
    }
    .scp-badge-default {
        background-color: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
    }
    .scp-verified-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        background-color: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .scp-avatar-status {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 14px;
        height: 14px;
        background-color: #10b981;
        border-radius: 9999px;
        border: 2px solid #ffffff;
    }

    /* DARK MODE ADAPTATIONS */
    :where(.dark, .dark *) .scp-user-name,
    html.dark .scp-user-name {
        color: #f9fafb !important;
    }
    :where(.dark, .dark *) .scp-user-meta,
    html.dark .scp-user-meta {
        color: #9ca3af !important;
    }
    :where(.dark, .dark *) .scp-divider,
    html.dark .scp-divider {
        background-color: rgba(255, 255, 255, 0.1) !important;
    }
    :where(.dark, .dark *) .scp-section-label,
    html.dark .scp-section-label {
        color: #9ca3af !important;
    }
    :where(.dark, .dark *) .scp-data-card,
    html.dark .scp-data-card {
        background-color: rgba(255, 255, 255, 0.05) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    :where(.dark, .dark *) .scp-data-card-label,
    html.dark .scp-data-card-label {
        color: #9ca3af !important;
    }
    :where(.dark, .dark *) .scp-data-card-value,
    html.dark .scp-data-card-value {
        color: #f9fafb !important;
    }
    :where(.dark, .dark *) .scp-avatar-status,
    html.dark .scp-avatar-status {
        border-color: #111827 !important;
    }
    :where(.dark, .dark *) .scp-icon-identity,
    html.dark .scp-icon-identity {
        background-color: rgba(37, 99, 235, 0.2) !important;
        color: #93c5fd !important;
    }
    :where(.dark, .dark *) .scp-icon-role,
    html.dark .scp-icon-role {
        background-color: rgba(126, 34, 206, 0.2) !important;
        color: #d8b4fe !important;
    }
    :where(.dark, .dark *) .scp-icon-assignment,
    html.dark .scp-icon-assignment {
        background-color: rgba(67, 56, 202, 0.2) !important;
        color: #a5b4fc !important;
    }
    :where(.dark, .dark *) .scp-badge-super-admin,
    html.dark .scp-badge-super-admin {
        background-color: rgba(126, 34, 206, 0.2) !important;
        color: #d8b4fe !important;
        border-color: rgba(216, 180, 254, 0.3) !important;
    }
    :where(.dark, .dark *) .scp-badge-waka,
    html.dark .scp-badge-waka {
        background-color: rgba(67, 56, 202, 0.2) !important;
        color: #a5b4fc !important;
        border-color: rgba(165, 180, 252, 0.3) !important;
    }
    :where(.dark, .dark *) .scp-badge-guru,
    html.dark .scp-badge-guru {
        background-color: rgba(4, 120, 87, 0.2) !important;
        color: #6ee7b7 !important;
        border-color: rgba(110, 231, 183, 0.3) !important;
    }
    :where(.dark, .dark *) .scp-badge-wali,
    html.dark .scp-badge-wali {
        background-color: rgba(29, 78, 216, 0.2) !important;
        color: #93c5fd !important;
        border-color: rgba(147, 197, 253, 0.3) !important;
    }
    :where(.dark, .dark *) .scp-badge-bk,
    html.dark .scp-badge-bk {
        background-color: rgba(180, 83, 9, 0.2) !important;
        color: #fde68a !important;
        border-color: rgba(253, 230, 138, 0.3) !important;
    }
    :where(.dark, .dark *) .scp-badge-siswa,
    html.dark .scp-badge-siswa {
        background-color: rgba(15, 118, 110, 0.2) !important;
        color: #99f6e4 !important;
        border-color: rgba(153, 246, 228, 0.3) !important;
    }
    :where(.dark, .dark *) .scp-badge-default,
    html.dark .scp-badge-default {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #d1d5db !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
    }
    :where(.dark, .dark *) .scp-verified-badge,
    html.dark .scp-verified-badge {
        background-color: rgba(16, 185, 129, 0.15) !important;
        color: #34d399 !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
    }
</style>

<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 scp-profile-card">
    <!-- Top Row: Avatar & Basic Info -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
            <!-- Avatar -->
            <div style="position: relative; flex-shrink: 0;">
                @if ($user?->profile_photo_url)
                    <img src="{{ $user->profile_photo_url }}" alt="{{ $user->full_name }}" style="width: 4.5rem; height: 4.5rem; border-radius: 9999px; object-fit: cover; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 3px solid #3b82f6;">
                @else
                    <div style="width: 4.5rem; height: 4.5rem; border-radius: 9999px; background: linear-gradient(135deg, #2563eb, #1d4ed8); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; color: #ffffff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 3px solid #93c5fd;">
                        {{ $initials }}
                    </div>
                @endif
                <span class="scp-avatar-status" title="Status: Aktif"></span>
            </div>

            <!-- Name & Meta -->
            <div>
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    <h2 class="scp-user-name">
                        {{ $user?->full_name }}
                    </h2>
                    <span class="scp-badge {{ $roleBadgeClass }}">
                        {{ $roleName }}
                    </span>
                </div>
                <div class="scp-user-meta">
                    <span style="font-weight: 500;">{{ '@' . ($user?->username ?? 'user') }}</span>
                    <span>•</span>
                    <span>{{ $user?->email ?? 'Tidak ada email' }}</span>
                    @if ($user?->phone)
                        <span>•</span>
                        <span>{{ $user->phone }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div>
            <span class="scp-verified-badge">
                <svg style="width: 1rem; height: 1rem; display: inline-block;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                Akun Terverifikasi
            </span>
        </div>
    </div>

    <!-- Divider -->
    <div class="scp-divider"></div>

    <!-- Read-Only Data Cards -->
    <div>
        <div class="scp-section-label">
            Kartu Data Diri (Read-Only)
        </div>

        <div class="scp-data-grid">
            <!-- 1. Identitas -->
            <div class="scp-data-card">
                <div class="scp-icon-box scp-icon-identity">
                    <svg style="width: 1.25rem; height: 1.25rem; display: block;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                    </svg>
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div class="scp-data-card-label">Identitas (NISN / NIP)</div>
                    <div class="scp-data-card-value" style="word-break: break-all;">
                        {{ $user?->identity_number ?: '-' }}
                    </div>
                </div>
            </div>

            <!-- 2. Role Badge -->
            <div class="scp-data-card">
                <div class="scp-icon-box scp-icon-role">
                    <svg style="width: 1.25rem; height: 1.25rem; display: block;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div class="scp-data-card-label">Role Badge</div>
                    <div class="scp-data-card-value">
                        {{ $roleName }}
                    </div>
                </div>
            </div>

            <!-- 3. Kelas / Penugasan -->
            <div class="scp-data-card">
                <div class="scp-icon-box scp-icon-assignment">
                    <svg style="width: 1.25rem; height: 1.25rem; display: block;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                    </svg>
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div class="scp-data-card-label">Kelas / Penugasan</div>
                    <div class="scp-data-card-value" style="word-break: break-word;">
                        {{ $user?->assignment ?: '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
