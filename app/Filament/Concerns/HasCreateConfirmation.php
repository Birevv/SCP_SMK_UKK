<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;

trait HasCreateConfirmation
{
    public function mountAction(string $name, array $arguments = [], array $context = []): mixed
    {
        if (in_array($name, ['create', 'createAnother'])) {
            $this->form->validate();
        }

        return parent::mountAction($name, $arguments, $context);
    }

    public function createAction(): Action
    {
        return $this->getCreateFormAction();
    }

    public function createAnotherAction(): Action
    {
        return $this->getCreateAnotherFormAction();
    }

    protected function getCreateFormAction(): Action
    {
        $label = method_exists($this, 'getResource') ? ($this::getResource()::getNavigationLabel() ?? 'Data') : 'Data';

        return parent::getCreateFormAction()
            ->submit(null)
            ->requiresConfirmation()
            ->modalHeading("Konfirmasi Simpan {$label}")
            ->modalDescription("Pastikan seluruh data yang Anda masukkan sudah benar dan valid sebelum disimpan. Apakah Anda yakin ingin menyimpan {$label} ini?")
            ->modalSubmitActionLabel('Ya, Simpan')
            ->modalCancelActionLabel('Batal / Periksa Kembali')
            ->modalIcon('heroicon-o-check-circle')
            ->action(fn () => $this->create());
    }

    protected function getCreateAnotherFormAction(): Action
    {
        $label = method_exists($this, 'getResource') ? ($this::getResource()::getNavigationLabel() ?? 'Data') : 'Data';

        return parent::getCreateAnotherFormAction()
            ->requiresConfirmation()
            ->modalHeading("Konfirmasi Simpan {$label}")
            ->modalDescription("Pastikan seluruh data yang Anda masukkan sudah benar dan valid. Apakah Anda yakin ingin menyimpan dan menambah {$label} baru?")
            ->modalSubmitActionLabel('Ya, Simpan & Tambah Lain')
            ->modalCancelActionLabel('Batal / Periksa Kembali')
            ->modalIcon('heroicon-o-check-circle')
            ->action(fn () => $this->createAnother());
    }
}
