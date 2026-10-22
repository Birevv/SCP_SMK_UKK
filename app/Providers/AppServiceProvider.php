<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Observers diregistrasi via #[ObservedBy] attribute pada model masing-masing:
     * - Journal -> JournalObserver
     * - LeaveRequest -> LeaveRequestObserver
     */
    public function boot(): void
    {
        // Super Admin memiliki akses penuh ke seluruh gate & abilities
        Gate::before(function ($user, $ability) {
            return ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin') ? true : null;
        });

        // Konfigurasi konfirmasi global untuk aksi-aksi CRUD Filament
        \Filament\Actions\DeleteAction::configureUsing(function (\Filament\Actions\DeleteAction $action): void {
            $action
                ->requiresConfirmation()
                ->modalHeading(fn (\Filament\Actions\DeleteAction $action): string => 'Konfirmasi Hapus ' . ($action->getRecordTitle() ?: 'Data'))
                ->modalDescription('Apakah Anda yakin ingin menghapus data ini? Pastikan data sudah sesuai sebelum dihapus karena tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus Data')
                ->modalCancelActionLabel('Batal');
        });

        \Filament\Actions\DeleteBulkAction::configureUsing(function (\Filament\Actions\DeleteBulkAction $action): void {
            $action
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Hapus Data Terpilih')
                ->modalDescription('Apakah Anda yakin ingin menghapus semua data terpilih? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus Data Terpilih')
                ->modalCancelActionLabel('Batal');
        });

        \Filament\Actions\ForceDeleteAction::configureUsing(function (\Filament\Actions\ForceDeleteAction $action): void {
            $action
                ->requiresConfirmation()
                ->modalHeading(fn (\Filament\Actions\ForceDeleteAction $action): string => 'Konfirmasi Hapus Permanen ' . ($action->getRecordTitle() ?: 'Data'))
                ->modalDescription('Apakah Anda yakin ingin menghapus permanen data ini? Data akan dihapus selamanya dari sistem.')
                ->modalSubmitActionLabel('Ya, Hapus Permanen')
                ->modalCancelActionLabel('Batal');
        });

        \Filament\Actions\RestoreAction::configureUsing(function (\Filament\Actions\RestoreAction $action): void {
            $action
                ->requiresConfirmation()
                ->modalHeading(fn (\Filament\Actions\RestoreAction $action): string => 'Konfirmasi Pemulihan ' . ($action->getRecordTitle() ?: 'Data'))
                ->modalDescription('Apakah Anda yakin ingin memulihkan kembali data ini ke sistem?')
                ->modalSubmitActionLabel('Ya, Pulihkan Data')
                ->modalCancelActionLabel('Batal');
        });

        \Filament\Actions\CreateAction::configureUsing(function (\Filament\Actions\CreateAction $action): void {
            $action
                ->modalSubmitActionLabel('Simpan Data')
                ->modalCancelActionLabel('Batal');
        });

        \Filament\Actions\EditAction::configureUsing(function (\Filament\Actions\EditAction $action): void {
            $action
                ->modalSubmitActionLabel('Simpan Perubahan')
                ->modalCancelActionLabel('Batal');
        });
    }
}
