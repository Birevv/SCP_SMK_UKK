<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            RoleSeeder::class,
            ClassroomSeeder::class,
            RoomSeeder::class,
            UserSeeder::class,
            SystemConfigurationSeeder::class,
        ]);
    }
}
