<?php

namespace Database\Seeders;

use App\Models\Room;

use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = ['Lab 16 RPL', 'Lab 18 RPL', 'Lab 19 RPL', 'Ruang 13 Normada'];

        foreach ($rooms as $room) {
            Room::firstOrCreate([
                'name' => $room,
            ]);
        }
    }
}
