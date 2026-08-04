<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $wakaRole = Role::where('name', 'Waka Kurikulum')->first();

        User::firstOrCreate(
            ['username' => 'waka'],
            [
                'full_name' => 'Waka Kurikulum Admin',
                'email' => 'waka@scp.sch.id',
                'password' => Hash::make('password'),
                'role_id' => $wakaRole->id,
            ]
        );

    }
}
