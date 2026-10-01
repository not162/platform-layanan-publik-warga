<?php

namespace Database\Factories;

use App\Models\RtOfficer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RtOfficer>
 */
class RtOfficerFactory extends Factory
{
    protected $model = RtOfficer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'position' => fake()->randomElement(['Ketua RT', 'Sekretaris', 'Bendahara', 'Seksi Keamanan', 'Seksi Kebersihan']),
            'phone' => fake()->phoneNumber(),
            'order_index' => fake()->numberBetween(1, 10),
            'photo_url' => null,
            'is_active' => true,
        ];
    }
}
