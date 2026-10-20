<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Jalankan ShieldSeeder yang menangani seluruh role dan permission
        $this->call(ShieldSeeder::class);
    }
}
