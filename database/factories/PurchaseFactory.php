<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return [
            'finance_transaction_id' => null,
            'vendor_name' => fake()->company(),
            'purchase_date' => now()->toDateString(),
            'invoice_number' => 'INV-'.fake()->numerify('####-####'),
            'purpose' => 'Pembelian perlengkapan pos keamanan dan kebersihan',
            'notes' => 'Nota pembelian terlampir',
            'receipt_path' => null,
            'created_by' => User::factory()->bendahara(),
            'version' => 1,
        ];
    }
}
