<x-filament-widgets::widget>
    @php
        $config = \App\Models\SystemConfiguration::current();
        $isPutaran1 = $config->active_cycle === 'Putaran 1';
        $isLocked = (bool) $config->is_schedule_locked;
    @endphp

    <x-filament::section heading="Panel Kontrol Waka Kurikulum" description="Kelola putaran aktif dan izin pengisian jadwal mengajar guru secara langsung.">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; margin-top: 0.5rem;">
            
            {{-- Kartu 1: Putaran Jadwal --}}
            <div style="border: 1px solid rgba(156, 163, 175, 0.25); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; background-color: rgba(156, 163, 175, 0.04);">
                <div>
                    {{-- Baris Atas: Label & Badge --}}
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.7;">
                            Siklus Putaran Blok
                        </span>
                        <x-filament::badge :color="$isPutaran1 ? 'info' : 'warning'" size="sm">
                            {{ $config->active_cycle }}
                        </x-filament::badge>
                    </div>

                    {{-- Status Text --}}
                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.25rem;">
                            {{ $config->active_cycle }} Sedang Aktif
                        </div>
                        <p style="font-size: 0.825rem; opacity: 0.7; margin: 0; line-height: 1.4;">
                            {{ $isPutaran1 ? 'Seluruh guru & siswa saat ini mengakses jadwal mingguan Putaran 1.' : 'Seluruh guru & siswa saat ini mengakses jadwal mingguan Putaran 2.' }}
                        </p>
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div style="padding-top: 1rem; border-top: 1px solid rgba(156, 163, 175, 0.2);">
                    {{ $this->toggleCycleAction }}
                </div>
            </div>

            {{-- Kartu 2: Penguncian Jadwal --}}
            <div style="border: 1px solid rgba(156, 163, 175, 0.25); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; background-color: rgba(156, 163, 175, 0.04);">
                <div>
                    {{-- Baris Atas: Label & Badge --}}
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.7;">
                            Akses Pengisian Jadwal
                        </span>
                        <x-filament::badge :color="$isLocked ? 'danger' : 'success'" size="sm">
                            {{ $isLocked ? 'Terkunci' : 'Terbuka' }}
                        </x-filament::badge>
                    </div>

                    {{-- Status Text --}}
                    <div style="margin-bottom: 1rem;">
                        <div style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.25rem;">
                            Jadwal {{ $isLocked ? 'Sedang Dikunci' : 'Dapat Diisi Guru' }}
                        </div>
                        <p style="font-size: 0.825rem; opacity: 0.7; margin: 0; line-height: 1.4;">
                            {{ $isLocked ? 'Guru tidak dapat menambah, mengubah, atau menghapus jadwal mengajar.' : 'Guru memiliki izin mandiri untuk menginput jadwal mengajar masing-masing.' }}
                        </p>
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div style="padding-top: 1rem; border-top: 1px solid rgba(156, 163, 175, 0.2);">
                    {{ $this->toggleLockAction }}
                </div>
            </div>

        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
