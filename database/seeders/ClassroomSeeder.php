<?php

namespace Database\Seeders;

use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\Rombel;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ClassLevelSeeder::class,
            MajorSeeder::class,
            RombelSeeder::class,
        ]);

        $classrooms = [
            'XII RPL 2',
            'XII RPL 1',
            'XI RPL 2',
            'XI RPL 1',
            'X RPL 2',
            'X RPL 1',
            'X AKL 1',
            'XI AKL 1',
            'XII AKL 1',
            'X TKJ 1',
            'XI TKJ 1',
            'XII TKJ 1',
        ];

        foreach ($classrooms as $classroomName) {
            $classroom = Classroom::firstOrNew(['name' => $classroomName]);
            $classroom->syncRelationsFromName();
            $classroom->save();
        }
    }
}
