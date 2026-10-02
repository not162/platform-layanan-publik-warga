<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\DuePayment;
use App\Models\FinanceTransaction;
use App\Models\ResidentDue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentDuePersonalLedgerSprint3Test extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_view_own_dues_with_tenant_isolation(): void
    {
        $userA = User::factory()->warga()->create();
        $citizenA = Citizen::factory()->create(['user_id' => $userA->id]);

        $userB = User::factory()->warga()->create();
        $citizenB = Citizen::factory()->create(['user_id' => $userB->id]);

        ResidentDue::factory()->create([
            'citizen_id' => $citizenA->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 50000,
            'status' => 'UNPAID',
        ]);

        ResidentDue::factory()->create([
            'citizen_id' => $citizenB->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 75000,
            'status' => 'UNPAID',
        ]);

        $responseA = $this->actingAs($userA)->getJson('/api/v1/me/dues');
        $responseA->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.citizen_id', $citizenA->id)
            ->assertJsonPath('data.0.amount_due_idr', 50000);

        // Citizen A cannot view Citizen B's due detail
        $dueB = ResidentDue::query()->where('citizen_id', '=', $citizenB->id, 'and')->firstOrFail();
        $responseForbidden = $this->actingAs($userA)->getJson("/api/v1/me/dues/{$dueB->id}");
        $responseForbidden->assertStatus(404);
    }

    public function test_citizen_can_view_own_due_detail_with_payment_history(): void
    {
        $user = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $user->id]);

        $due = ResidentDue::factory()->create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 9,
            'amount_due_idr' => 50000,
            'status' => 'PAID',
        ]);

        DuePayment::factory()->create([
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 50000,
            'status' => 'PAID',
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/me/dues/{$due->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $due->id)
            ->assertJsonPath('data.total_paid_idr', 50000)
            ->assertJsonPath('data.remaining_due_idr', 0)
            ->assertJsonPath('data.status', 'PAID')
            ->assertJsonCount(1, 'data.payments');
    }

    public function test_citizen_can_view_own_payment_receipts(): void
    {
        $user = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $user->id]);

        $due = ResidentDue::factory()->create([
            'citizen_id' => $citizen->id,
            'amount_due_idr' => 50000,
        ]);

        $payment = DuePayment::factory()->create([
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 50000,
            'payment_method' => 'qris',
            'status' => 'PAID',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/me/payments');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $payment->id)
            ->assertJsonPath('data.0.payment_method', 'qris')
            ->assertJsonPath('data.0.amount_paid_idr', 50000);
    }

    public function test_citizen_personal_financial_summary_calculates_correct_totals(): void
    {
        $user = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $user->id]);

        // Due 1: fully paid
        $due1 = ResidentDue::factory()->create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 8,
            'amount_due_idr' => 50000,
            'status' => 'PAID',
        ]);
        DuePayment::factory()->create([
            'resident_due_id' => $due1->id,
            'amount_paid_idr' => 50000,
            'status' => 'PAID',
        ]);

        // Due 2: overdue unpaid
        ResidentDue::factory()->create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 9,
            'amount_due_idr' => 50000,
            'due_date' => Carbon::now()->subDays(5),
            'status' => 'OVERDUE',
        ]);

        // Due 3: upcoming unpaid
        ResidentDue::factory()->create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 50000,
            'due_date' => Carbon::now()->addDays(10),
            'status' => 'UNPAID',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/me/finance/summary');
        $response->assertStatus(200)
            ->assertJsonPath('data.total_billed_idr', 150000)
            ->assertJsonPath('data.total_paid_idr', 50000)
            ->assertJsonPath('data.total_outstanding_idr', 100000)
            ->assertJsonPath('data.overdue_count', 1);
    }

    public function test_bendahara_can_generate_monthly_dues_for_all_citizens(): void
    {
        $bendahara = User::factory()->bendahara()->create();

        Citizen::factory()->count(3)->create(['is_active' => true]);

        $response = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/dues/generate', [
            'period_year' => 2026,
            'period_month' => 11,
            'amount_due_idr' => 50000,
            'due_date' => '2026-11-10',
            'notes' => 'Iuran Keamanan & Kebersihan RT November 2026',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('generated_count', 3);

        $this->assertDatabaseCount('resident_dues', 3);
        $this->assertDatabaseHas('resident_dues', [
            'period_year' => 2026,
            'period_month' => 11,
            'amount_due_idr' => 50000,
            'status' => 'UNPAID',
        ]);

        // Running generate again should be idempotent (0 additional dues created)
        $idempotentResponse = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/dues/generate', [
            'period_year' => 2026,
            'period_month' => 11,
            'amount_due_idr' => 50000,
        ]);
        $idempotentResponse->assertStatus(201)
            ->assertJsonPath('generated_count', 0);
    }

    public function test_bendahara_can_record_payment_and_automatically_posts_finance_transaction(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $citizen = Citizen::factory()->create();

        $due = ResidentDue::factory()->create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 50000,
            'status' => 'UNPAID',
        ]);

        // Step 1: Partial payment of 20.000 IDR
        $responsePartial = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 20000,
            'payment_method' => 'cash',
            'notes' => 'Pembayaran parsial pertama',
        ]);

        $responsePartial->assertStatus(201)
            ->assertJsonPath('data.amount_paid_idr', 20000)
            ->assertJsonPath('data.due.status', 'PARTIAL');

        $due->refresh();
        $this->assertEquals('PARTIAL', $due->status);
        $this->assertEquals(20000, $due->getTotalPaidIdr());
        $this->assertEquals(30000, $due->getRemainingDueIdr());

        // Verify FinanceTransaction was automatically created and published
        $this->assertDatabaseHas('finance_transactions', [
            'type' => 'income',
            'amount_idr' => 20000,
            'status' => 'published',
        ]);

        // Step 2: Final payment of remaining 30.000 IDR
        $responseFinal = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 30000,
            'payment_method' => 'transfer',
            'notes' => 'Pelunasan sisa iuran',
        ]);

        $responseFinal->assertStatus(201)
            ->assertJsonPath('data.amount_paid_idr', 30000)
            ->assertJsonPath('data.due.status', 'PAID');

        $due->refresh();
        $this->assertEquals('PAID', $due->status);
        $this->assertEquals(50000, $due->getTotalPaidIdr());
        $this->assertEquals(0, $due->getRemainingDueIdr());

        $this->assertEquals(2, FinanceTransaction::query()->where('category', '=', 'Iuran Kas Warga', 'and')->count('*'));
    }

    public function test_warga_cannot_access_admin_finance_dues_endpoints(): void
    {
        $warga = User::factory()->warga()->create();
        Citizen::factory()->create(['user_id' => $warga->id]);

        $generateResponse = $this->actingAs($warga)->postJson('/api/v1/admin/finance/dues/generate', [
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 50000,
        ]);
        $generateResponse->assertStatus(403);

        $recordResponse = $this->actingAs($warga)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => 1,
            'amount_paid_idr' => 50000,
            'payment_method' => 'cash',
        ]);
        $recordResponse->assertStatus(403);
    }
}
