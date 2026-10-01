<?php

namespace Database\Factories;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'category' => fake()->randomElement(['kegiatan', 'iuran', 'keamanan', 'darurat', 'umum']),
            'content' => fake()->paragraph(),
            'is_pinned' => false,
            'is_published' => false,
            'published_at' => null,
            'version' => 1,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn () => [
            'is_pinned' => true,
        ]);
    }
}
