@php
    $user = auth()->user();
    $roleName = $user?->role?->name ?? 'Pengguna';
    $initials = strtoupper(substr($user?->full_name ?? 'U', 0, 2));

    $badgeStyle = match($roleName) {
        'Super Admin' => 'background-color: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe;',
        'Waka Kurikulum' => 'background-color: #e0e7ff; color: #4338ca; border: 1px solid #a5b4fc;',
        'Guru' => 'background-color: #ecfdf5; color: #047857; border: 1px solid #6ee7b7;',
        'Wali Kelas' => 'background-color: #eff6ff; color: #1d4ed8; border: 1px solid #93c5fd;',
        'Guru BK' => 'background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;',
        'Siswa' => 'background-color: #f0fdfa; color: #0f766e; border: 1px solid #99f6e4;',
        default => 'background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db;',
    };
@endphp

<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="padding: 1.5rem; margin-bottom: 1.5rem;">
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
                <span style="position: absolute; bottom: 2px; right: 2px; width: 14px; height: 14px; background-color: #10b981; border-radius: 9999px; border: 2px solid #ffffff;" title="Status: Aktif"></span>
            </div>

            <!-- Name & Meta -->
            <div>
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    <h2 style="font-size: 1.35rem; font-weight: 700; color: #111827; margin: 0;" class="dark:text-white">
                        {{ $user?->full_name }}
                    </h2>
                    <span style="font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.65rem; border-radius: 9999px; {{ $badgeStyle }}">
                        {{ $roleName }}
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.85rem; color: #6b7280; margin-top: 0.25rem;" class="dark:text-gray-400">
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
            <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                <svg style="width: 1rem; height: 1rem; color: #10b981; display: inline-block;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                Akun Terverifikasi
            </span>
        </div>
    </div>

    <!-- Divider -->
    <div style="height: 1px; background-color: #e5e7eb; margin: 1.25rem 0 1rem 0;" class="dark:bg-gray-800"></div>

    <!-- Read-Only Data Cards -->
    <div>
        <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 0.75rem;" class="dark:text-gray-400">
            Kartu Data Diri (Read-Only)
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
            <!-- 1. Identitas -->
            <div style="display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 1rem; border-radius: 0.625rem; background-color: #f9fafb; border: 1px solid #e5e7eb;" class="dark:bg-gray-800/50 dark:border-gray-800">
                <div style="display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background-color: #eff6ff; color: #2563eb; flex-shrink: 0;" class="dark:bg-blue-950/60 dark:text-blue-400">
                    <svg style="width: 1.25rem; height: 1.25rem; display: block;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                    </svg>
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div style="font-size: 0.72rem; font-weight: 500; color: #6b7280;" class="dark:text-gray-400">Identitas (NISN / NIP)</div>
                    <div style="font-size: 0.925rem; font-weight: 700; color: #111827; word-break: break-all; margin-top: 0.15rem;" class="dark:text-white">
                        {{ $user?->identity_number ?: '-' }}
                    </div>
                </div>
            </div>

            <!-- 2. Role Badge -->
            <div style="display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 1rem; border-radius: 0.625rem; background-color: #f9fafb; border: 1px solid #e5e7eb;" class="dark:bg-gray-800/50 dark:border-gray-800">
                <div style="display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background-color: #f3e8ff; color: #7e22ce; flex-shrink: 0;" class="dark:bg-purple-950/60 dark:text-purple-400">
                    <svg style="width: 1.25rem; height: 1.25rem; display: block;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div style="font-size: 0.72rem; font-weight: 500; color: #6b7280;" class="dark:text-gray-400">Role Badge</div>
                    <div style="font-size: 0.925rem; font-weight: 700; color: #111827; margin-top: 0.15rem;" class="dark:text-white">
                        {{ $roleName }}
                    </div>
                </div>
            </div>

            <!-- 3. Kelas / Penugasan -->
            <div style="display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 1rem; border-radius: 0.625rem; background-color: #f9fafb; border: 1px solid #e5e7eb;" class="dark:bg-gray-800/50 dark:border-gray-800">
                <div style="display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background-color: #e0e7ff; color: #4338ca; flex-shrink: 0;" class="dark:bg-indigo-950/60 dark:text-indigo-400">
                    <svg style="width: 1.25rem; height: 1.25rem; display: block;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                    </svg>
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div style="font-size: 0.72rem; font-weight: 500; color: #6b7280;" class="dark:text-gray-400">Kelas / Penugasan</div>
                    <div style="font-size: 0.925rem; font-weight: 700; color: #111827; word-break: break-word; margin-top: 0.15rem;" class="dark:text-white">
                        {{ $user?->assignment ?: '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
