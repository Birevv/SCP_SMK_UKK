<?php

namespace Database\Seeders;

use App\Models\Rombel;
use Illuminate\Database\Seeder;

class RombelSeeder extends Seeder
{
    public function run(): void
    {
        $rombels = [
            ['name' => '1', 'number' => 1, 'description' => 'Rombel 1'],
            ['name' => '2', 'number' => 2, 'description' => 'Rombel 2'],
            ['name' => '3', 'number' => 3, 'description' => 'Rombel 3'],
            ['name' => '4', 'number' => 4, 'description' => 'Rombel 4'],
        ];

        foreach ($rombels as $rombel) {
            Rombel::firstOrCreate(
                ['name' => $rombel['name']],
                $rombel
            );
        }
    }
}
