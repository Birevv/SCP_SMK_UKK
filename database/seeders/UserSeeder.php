<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'full_name' => 'Admin',
            'username' => 'admin',
            'password'=> bcrypt('password'),
            'email' => 'admin@gmail.com'
        ]);

    }
}
