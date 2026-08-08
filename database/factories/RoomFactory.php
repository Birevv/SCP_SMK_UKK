<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'name' => 'Lab ' . fake()->numberBetween(1, 20) . ' ' . fake()->randomElement(['RPL', 'TKJ', 'MM']),
        ];
    }
}
