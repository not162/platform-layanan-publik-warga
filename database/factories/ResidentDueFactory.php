<?php

namespace Database\Factories;

use App\Models\Citizen;
use App\Models\ResidentDue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResidentDue>
 */
class ResidentDueFactory extends Factory
{
    protected $model = ResidentDue::class;

    public function definition(): array
    {
        return [
            'citizen_id' => Citizen::factory(),
            'period_year' => (int) date('Y'),
            'period_month' => fake()->numberBetween(1, 12),
            'amount_due_idr' => 50000,
            'due_date' => now()->addDays(15)->toDateString(),
            'status' => 'UNPAID',
            'notes' => 'Iuran Warga RT 01',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => 'PAID',
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn () => [
            'status' => 'PARTIAL',
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => 'OVERDUE',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);
    }
}
