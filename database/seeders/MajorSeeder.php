<?php

namespace Database\Seeders;

use App\Models\Major;
use Illuminate\Database\Seeder;

class MajorSeeder extends Seeder
{
    public function run(): void
    {
        $majors = [
            [
                'code' => 'RPL',
                'name' => 'Rekayasa Perangkat Lunak',
                'description' => 'Program Keahlian Pengembangan Perangkat Lunak dan Gim',
            ],
            [
                'code' => 'AKL',
                'name' => 'Akuntansi dan Keuangan Lembaga',
                'description' => 'Program Keahlian Akuntansi dan Keuangan Lembaga',
            ],
            [
                'code' => 'TKJ',
                'name' => 'Teknik Komputer dan Jaringan',
                'description' => 'Program Keahlian Teknik Jaringan Komputer dan Telekomunikasi',
            ],
            [
                'code' => 'DKV',
                'name' => 'Desain Komunikasi Visual',
                'description' => 'Program Keahlian Desain Komunikasi Visual',
            ],
            [
                'code' => 'MPLB',
                'name' => 'Manajemen Perkantoran dan Layanan Bisnis',
                'description' => 'Program Keahlian Manajemen Perkantoran',
            ],
        ];

        foreach ($majors as $major) {
            Major::firstOrCreate(
                ['code' => $major['code']],
                $major
            );
        }
    }
}
