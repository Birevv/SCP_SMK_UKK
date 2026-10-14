<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academics = [
            '2024/2025 - Ganjil',
            '2024/2025 - Genap',
            '2025/2026 - Ganjil',
            '2025/2026 - Genap',
            '2026/2027 - Ganjil',
            '2026/2027 - Genap',
        ];

        foreach ($academics as $index => $academic) {
            AcademicYear::firstOrCreate([
                'name' => $academic,
                'is_active' => $index === 2,
            ]);
        }
    }
}
