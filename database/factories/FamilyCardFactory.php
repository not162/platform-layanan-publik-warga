<?php

namespace Database\Factories;

use App\Models\FamilyCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyCard>
 */
class FamilyCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $noKk = fake()->numerify('################');

        return [
            'no_kk' => $noKk,
            'no_kk_hash' => hash('sha256', $noKk),
            'address' => fake()->streetAddress(),
            'rt' => '001',
            'rw' => '002',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Tebet',
            'village' => 'Tebet Barat',
            'version' => 1,
        ];
    }
}
