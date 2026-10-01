<?php

namespace Database\Factories;

use App\Models\EmergencyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmergencyContact>
 */
class EmergencyContactFactory extends Factory
{
    protected $model = EmergencyContact::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'category' => fake()->randomElement(['polisi', 'damkar', 'ambulans', 'puskesmas', 'keamanan_rt']),
            'phone_number' => fake()->phoneNumber(),
            'description' => fake()->sentence(),
            'order_index' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
