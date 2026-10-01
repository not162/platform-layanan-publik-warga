<?php

namespace Database\Factories;

use App\Models\CommunityEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityEvent>
 */
class CommunityEventFactory extends Factory
{
    protected $model = CommunityEvent::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'location' => 'Balai Warga RT 01',
            'event_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '11:30',
            'is_published' => true,
            'version' => 1,
        ];
    }
}
