<?php

namespace App\Providers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
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
        DeleteAction::configureUsing(function (DeleteAction $action): void {
            $action
                ->requiresConfirmation()
                ->modalHeading(fn (DeleteAction $action): string => 'Konfirmasi Hapus '.($action->getRecordTitle() ?: 'Data'))
                ->modalDescription('Apakah Anda yakin ingin menghapus data ini? Pastikan data sudah sesuai sebelum dihapus karena tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus Data')
                ->modalCancelActionLabel('Batal');
        });

        DeleteBulkAction::configureUsing(function (DeleteBulkAction $action): void {
            $action
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Hapus Data Terpilih')
                ->modalDescription('Apakah Anda yakin ingin menghapus semua data terpilih? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus Data Terpilih')
                ->modalCancelActionLabel('Batal');
        });

        ForceDeleteAction::configureUsing(function (ForceDeleteAction $action): void {
            $action
                ->requiresConfirmation()
                ->modalHeading(fn (ForceDeleteAction $action): string => 'Konfirmasi Hapus Permanen '.($action->getRecordTitle() ?: 'Data'))
                ->modalDescription('Apakah Anda yakin ingin menghapus permanen data ini? Data akan dihapus selamanya dari sistem.')
                ->modalSubmitActionLabel('Ya, Hapus Permanen')
                ->modalCancelActionLabel('Batal');
        });

        RestoreAction::configureUsing(function (RestoreAction $action): void {
            $action
                ->requiresConfirmation()
                ->modalHeading(fn (RestoreAction $action): string => 'Konfirmasi Pemulihan '.($action->getRecordTitle() ?: 'Data'))
                ->modalDescription('Apakah Anda yakin ingin memulihkan kembali data ini ke sistem?')
                ->modalSubmitActionLabel('Ya, Pulihkan Data')
                ->modalCancelActionLabel('Batal');
        });

        CreateAction::configureUsing(function (CreateAction $action): void {
            $action
                ->successNotificationTitle('Data berhasil diinput')
                ->modalSubmitActionLabel('Simpan Data')
                ->modalCancelActionLabel('Batal');
        });

        EditAction::configureUsing(function (EditAction $action): void {
            $action
                ->modalSubmitActionLabel('Simpan Perubahan')
                ->modalCancelActionLabel('Batal');
        });
    }
}
