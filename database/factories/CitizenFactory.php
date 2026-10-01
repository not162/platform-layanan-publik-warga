<?php

namespace Database\Factories;

use App\Models\Citizen;
use App\Models\FamilyCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Citizen>
 */
class CitizenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nik = fake()->numerify('################');

        return [
            'family_card_id' => FamilyCard::factory(),
            'nik' => $nik,
            'nik_hash' => hash('sha256', $nik),
            'full_name' => fake()->name(),
            'place_of_birth' => fake()->city(),
            'date_of_birth' => fake()->date(),
            'gender' => fake()->randomElement(['Laki-laki', 'Perempuan']),
            'religion' => 'Islam',
            'blood_type' => 'O',
            'version' => 1,
        ];
    }
}
