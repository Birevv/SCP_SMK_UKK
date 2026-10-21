<?php

namespace Database\Seeders;

use App\Models\ClassLevel;
use Illuminate\Database\Seeder;

class ClassLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'name' => 'X',
                'level' => 10,
                'description' => 'Kelas 10',
            ],
            [
                'name' => 'XI',
                'level' => 11,
                'description' => 'Kelas 11',
            ],
            [
                'name' => 'XII',
                'level' => 12,
                'description' => 'Kelas 12',
            ],
            [
                'name' => 'Lulus',
                'level' => 0,
                'description' => 'Alumni / Sudah Lulus',
            ],
        ];

        foreach ($levels as $level) {
            ClassLevel::firstOrCreate(
                ['name' => $level['name']],
                $level
            );
        }
    }
}
