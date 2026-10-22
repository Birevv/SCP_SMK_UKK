<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;

trait HasEditConfirmation
{
    public function mountAction(string $name, array $arguments = [], array $context = []): mixed
    {
        if ($name === 'save') {
            $this->form->validate();
        }

        return parent::mountAction($name, $arguments, $context);
    }

    public function saveAction(): Action
    {
        return $this->getSaveFormAction();
    }

    protected function getSaveFormAction(): Action
    {
        $label = method_exists($this, 'getResource') ? ($this::getResource()::getNavigationLabel() ?? 'Data') : 'Data';

        return parent::getSaveFormAction()
            ->submit(null)
            ->requiresConfirmation()
            ->modalHeading("Konfirmasi Perubahan {$label}")
            ->modalDescription("Pastikan seluruh perubahan data sudah benar dan sesuai. Apakah Anda yakin ingin memperbarui {$label} ini?")
            ->modalSubmitActionLabel('Ya, Simpan Perubahan')
            ->modalCancelActionLabel('Batal / Periksa Kembali')
            ->modalIcon('heroicon-o-pencil-square')
            ->action(fn () => $this->save());
    }
}
