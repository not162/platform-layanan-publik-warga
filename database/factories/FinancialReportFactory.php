<?php

namespace Database\Factories;

use App\Models\FinancialReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialReport>
 */
class FinancialReportFactory extends Factory
{
    protected $model = FinancialReport::class;

    public function definition(): array
    {
        $year = 2026;
        $quarter = 1;
        $opening = 5000000;
        $income = 3500000;
        $expense = 2100000;
        $closing = $opening + $income - $expense;

        return [
            'year' => $year,
            'quarter' => $quarter,
            'revision' => 1,
            'period_start' => '2026-01-01',
            'period_end' => '2026-03-31',
            'opening_balance_idr' => $opening,
            'income_idr' => $income,
            'expense_idr' => $expense,
            'closing_balance_idr' => $closing,
            'dues_assessed_idr' => 3500000,
            'dues_collected_idr' => 3200000,
            'dues_outstanding_idr' => 300000,
            'status' => 'draft',
            'generated_at' => now(),
            'published_at' => null,
            'published_by' => null,
            'checksum_sha256' => null,
            'version' => 1,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
            'published_by' => User::factory()->ketuaRt(),
            'checksum_sha256' => hash('sha256', "2026-1-1-{$attributes['closing_balance_idr']}"),
        ]);
    }
}
