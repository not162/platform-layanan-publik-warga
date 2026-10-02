<?php

namespace Database\Factories;

use App\Models\DuePayment;
use App\Models\ResidentDue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DuePayment>
 */
class DuePaymentFactory extends Factory
{
    protected $model = DuePayment::class;

    public function definition(): array
    {
        return [
            'resident_due_id' => ResidentDue::factory(),
            'amount_paid_idr' => 50000,
            'paid_at' => now(),
            'payment_method' => 'cash',
            'receipt_number' => 'RCP-'.date('Ymd').'-'.strtoupper(Str::random(6)),
            'received_by' => User::factory()->bendahara(),
            'notes' => 'Pembayaran iuran tunai',
            'status' => 'PAID',
        ];
    }
}
