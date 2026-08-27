<?php

namespace Database\Seeders;

use App\Models\Classroom;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    public function run(): void
    {
        $classrooms = ['XII RPL 2', 'XII RPL 1', 'XI RPL 2', 'XI RPL 1', 'X RPL 2', 'X RPL 1'];

        foreach ($classrooms as $classroom) {
            Classroom::firstOrCreate([
                'name' => $classroom,
            ]);
        }
    }
}
