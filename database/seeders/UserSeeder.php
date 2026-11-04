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
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        $wakaRole = Role::where('name', 'Waka Kurikulum')->first();

        // 1. Super Admin
        $superAdmin = User::firstOrCreate(
            ['username' => 'Muzan'],
            [
                'full_name' => 'Kibutsuji Muzan',
                'identity_number' => '197001011990011001',
                'email' => 'admin@scp.sch.id',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole?->id,
            ]
        );

        if ($superAdminRole) {
            $superAdmin->syncRoles(['Super Admin']);
        }

        // 2. Waka Kurikulum
        $waka = User::firstOrCreate(
            ['username' => 'waka'],
            [
                'full_name' => 'Waka Kurikulum Admin',
                'identity_number' => '197501012000031001',
                'email' => 'waka@scp.sch.id',
                'password' => Hash::make('password'),
                'role_id' => $wakaRole?->id,
            ]
        );

        if ($wakaRole) {
            $waka->syncRoles(['Waka Kurikulum']);
        }
    }
}
