<?php

namespace Database\Seeders;

use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;

class SystemConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $wakaUser = User::where('username', 'waka')->first();

        if (SystemConfiguration::count() === 0) {
            SystemConfiguration::create([
                'active_cycle' => 'Putaran 1',
                'is_schedule_locked' => false,
                'updated_by' => $wakaUser->id,
            ]);
        }
    }
}
