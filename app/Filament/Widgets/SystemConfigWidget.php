<?php

namespace App\Filament\Widgets;

use App\Models\SystemConfiguration;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SystemConfigWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.system-config-widget';

    public static function canView(): bool
    {
        return Auth::user()?->role?->name === 'Waka Kurikulum';
    }

    public function getViewData(): array
    {
        $config = SystemConfiguration::current();

        return [
            'activeCycle' => $config->active_cycle,
            'isScheduleLocked' => $config->is_schedule_locked,
        ];
    }

    public function toggleCycleAction(): Action
    {
        return Action::make('toggleCycle')
            ->label(fn () => SystemConfiguration::current()->active_cycle === 'Putaran 1' ? 'Ganti ke Putaran 2' : 'Ganti ke Putaran 1')
            ->icon('heroicon-o-arrow-path')
            ->color(fn () => SystemConfiguration::current()->active_cycle === 'Putaran 1' ? 'warning' : 'info')
            ->size('lg')
            ->requiresConfirmation()
            ->modalHeading('Konfirmasi Ganti Putaran')
            ->modalDescription('Apakah Anda yakin ingin mengganti putaran aktif? Seluruh tampilan jadwal akan berubah secara global.')
            ->modalSubmitActionLabel('Ya, Ganti Putaran')
            ->modalIcon('heroicon-o-arrow-path')
            ->action(function () {
                $config = SystemConfiguration::first() ?? new SystemConfiguration();
                $config->update([
                    'active_cycle' => $config->active_cycle === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1',
                    'updated_by' => Auth::id(),
                ]);

                $freshCycle = $config->fresh()->active_cycle;

                $this->dispatch('$refresh');

                Notification::make()
                    ->title('Putaran berhasil diubah!')
                    ->body('Putaran aktif sekarang: ' . $freshCycle)
                    ->success()
                    ->send();
            });
    }

    public function toggleLockAction(): Action
    {
        return Action::make('toggleLock')
            ->label(fn () => SystemConfiguration::current()->is_schedule_locked ? 'Buka Kunci Jadwal' : 'Kunci Jadwal')
            ->icon(fn () => SystemConfiguration::current()->is_schedule_locked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
            ->color(fn () => SystemConfiguration::current()->is_schedule_locked ? 'success' : 'danger')
            ->size('lg')
            ->requiresConfirmation()
            ->modalHeading(fn () => SystemConfiguration::current()->is_schedule_locked ? 'Buka Kunci Jadwal?' : 'Kunci Jadwal?')
            ->modalDescription(fn () => SystemConfiguration::current()->is_schedule_locked
                ? 'Guru akan dapat menambah dan mengedit jadwal kembali.'
                : 'Guru tidak akan dapat menambah atau mengedit jadwal setelah dikunci.')
            ->modalSubmitActionLabel(fn () => SystemConfiguration::current()->is_schedule_locked ? 'Ya, Buka Kunci' : 'Ya, Kunci Jadwal')
            ->modalIcon(fn () => SystemConfiguration::current()->is_schedule_locked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
            ->action(function () {
                $config = SystemConfiguration::first() ?? new SystemConfiguration();
                $config->update([
                    'is_schedule_locked' => ! $config->is_schedule_locked,
                    'updated_by' => Auth::id(),
                ]);

                $status = $config->fresh()->is_schedule_locked ? 'dikunci' : 'dibuka';

                $this->dispatch('$refresh');

                Notification::make()
                    ->title('Status jadwal berhasil diubah!')
                    ->body("Jadwal sekarang $status.")
                    ->success()
                    ->send();
            });
    }
}
