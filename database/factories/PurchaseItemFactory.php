<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    protected $model = PurchaseItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->numberBetween(10000, 150000);

        return [
            'purchase_id' => Purchase::factory(),
            'item_name' => fake()->words(2, true),
            'description' => 'Keperluan operasional RT',
            'quantity' => $quantity,
            'unit' => 'pcs',
            'unit_price_idr' => $unitPrice,
            'subtotal_idr' => $quantity * $unitPrice,
        ];
    }
}
