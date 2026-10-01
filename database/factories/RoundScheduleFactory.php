<?php

namespace Database\Factories;

use App\Models\RoundSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoundSchedule>
 */
class RoundScheduleFactory extends Factory
{
    protected $model = RoundSchedule::class;

    public function definition(): array
    {
        return [
            'day_of_week' => fake()->randomElement(['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']),
            'shift_name' => 'Malam (22:00 - 04:00)',
            'officer_names' => [fake()->name(), fake()->name(), fake()->name()],
            'pos_location' => 'Pos Ronda Utama RT 01',
            'notes' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
