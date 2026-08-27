<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            'Pemrograman Web dan Perangkat Bergerak',
            'Basis Data',
            'Pemodelan Perangkat Lunak',
            'Pemrograman Berorientasi Objek',
            'Produk Kreatif dan Kewirausahaan',
        ];

        foreach ($subjects as $subject) {
            Subject::firstOrCreate([
                'name' => $subject,
            ]);
        }
    }
}
