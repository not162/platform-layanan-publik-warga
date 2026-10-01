<?php

namespace Database\Factories;

use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceTransaction>
 */
class FinanceTransactionFactory extends Factory
{
    protected $model = FinanceTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['income', 'expense']),
            'category' => fake()->randomElement(['Iuran Bulanan', 'Kerja Bakti', 'Kebersihan', 'Perbaikan Lampu Jalan']),
            'amount' => fake()->randomFloat(2, 50000, 2500000),
            'description' => fake()->sentence(),
            'transaction_date' => fake()->date(),
            'status' => 'draft',
            'created_by' => User::factory(),
            'published_at' => null,
            'reversal_reason' => null,
            'original_transaction_id' => null,
            'version' => 1,
        ];
    }

    public function income(float $amount = 100000): static
    {
        return $this->state(fn () => [
            'type' => 'income',
            'amount' => $amount,
        ]);
    }

    public function expense(float $amount = 50000): static
    {
        return $this->state(fn () => [
            'type' => 'expense',
            'amount' => $amount,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function reversed(string $reason = 'Salah input nominal'): static
    {
        return $this->state(fn () => [
            'status' => 'reversed',
            'reversal_reason' => $reason,
        ]);
    }
}
