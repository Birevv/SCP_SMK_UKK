<?php

namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum ReasonTypeEnum: string implements HasLabel
{
    case SAKIT = 'sakit';
    case IZIN = 'izin';
    case PULANG_CEPAT = 'pulang_cepat';
    case KEGIATAN_LUAR = 'kegiatan_luar';

    public function getLabel(): string {
        return match($this) {
            self::SAKIT => 'Sakit',
            self::IZIN => 'Izin',
            self::PULANG_CEPAT => 'Izin Pulang Cepat',
            self::KEGIATAN_LUAR => 'Izin Kegiatan Luar',
        };
    }
}
