<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\DuePayment;
use App\Models\FinanceTransaction;
use App\Models\ResidentDue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class Sprint3FinanceDueLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_payment_links_due_payment_to_finance_transaction(): void
    {
        $bendahara = User::factory()->admin(['finance.payment.manage', 'finance.payment.read'])->create();
        $citizen = Citizen::factory()->create(['full_name' => 'Pak Joko']);

        $due = ResidentDue::create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 100000,
            'due_date' => '2026-10-25',
            'status' => 'UNPAID',
        ]);

        $response = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 100000,
            'payment_method' => 'transfer',
            'notes' => 'Pembayaran iuran kas via transfer bank',
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $paymentId = $response->json('data.id');
        $txId = $response->json('data.finance_transaction_id');

        $this->assertNotNull($txId, 'finance_transaction_id must be populated');

        // Verify DuePayment in database
        $this->assertDatabaseHas('due_payments', [
            'id' => $paymentId,
            'finance_transaction_id' => $txId,
            'amount_paid_idr' => 100000,
            'status' => 'PAID',
        ]);

        // Verify FinanceTransaction in general ledger
        $this->assertDatabaseHas('finance_transactions', [
            'id' => $txId,
            'type' => 'income',
            'amount_idr' => 100000,
            'category' => 'Iuran Kas Warga',
        ]);

        // Verify Eloquent relationship
        $payment = DuePayment::query()->find($paymentId);
        $this->assertNotNull($payment?->financeTransaction);
        $this->assertEquals($txId, $payment->financeTransaction->id);
    }

    public function test_recording_payment_with_create_permission_scope(): void
    {
        $adminWithCreate = User::factory()->admin(['finance.payment.create', 'finance.payment.read'])->create();
        $citizen = Citizen::factory()->create();

        $due = ResidentDue::create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 11,
            'amount_due_idr' => 50000,
            'due_date' => '2026-11-25',
            'status' => 'UNPAID',
        ]);

        $response = $this->actingAs($adminWithCreate)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 50000,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    public function test_recording_payment_fails_for_unauthorized_user(): void
    {
        $warga = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create();

        $due = ResidentDue::create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 100000,
            'due_date' => '2026-10-25',
            'status' => 'UNPAID',
        ]);

        $response = $this->actingAs($warga)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 100000,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_recording_payment_with_invalid_amount_is_rejected(): void
    {
        $bendahara = User::factory()->admin(['finance.payment.manage'])->create();
        $citizen = Citizen::factory()->create();

        $due = ResidentDue::create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 100000,
            'due_date' => '2026-10-25',
            'status' => 'UNPAID',
        ]);

        $response = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 0,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('amount_paid_idr');
    }
}
