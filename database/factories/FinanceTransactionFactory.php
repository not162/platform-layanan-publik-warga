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
        $amount = fake()->numberBetween(50000, 2500000);

        return [
            'transaction_number' => 'TXN-'.fake()->unique()->numerify('202610-######'),
            'type' => fake()->randomElement(['income', 'expense']),
            'source' => fake()->randomElement(['dues', 'purchase', 'other']),
            'category' => fake()->randomElement(['Iuran Bulanan', 'Kerja Bakti', 'Kebersihan', 'Perbaikan Lampu Jalan']),
            'amount' => (float) $amount,
            'amount_idr' => $amount,
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

    public function income(float|int $amount = 100000): static
    {
        return $this->state(fn () => [
            'type' => 'income',
            'amount' => (float) $amount,
            'amount_idr' => (int) round((float) $amount),
        ]);
    }

    public function expense(float|int $amount = 50000): static
    {
        return $this->state(fn () => [
            'type' => 'expense',
            'amount' => (float) $amount,
            'amount_idr' => (int) round((float) $amount),
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
