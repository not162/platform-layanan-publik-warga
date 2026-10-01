<?php

namespace Tests\Feature;

use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_finance_manage_permission_can_create_draft_transaction(): void
    {
        $admin = User::factory()->admin(['finance.manage'])->create();

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/finance', [
            'type' => 'income',
            'category' => 'Iuran Warga Oktober',
            'amount' => 1500000,
            'description' => 'Iuran warga RT 01 sebanyak 30 KK',
            'transaction_date' => '2026-10-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'income')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.version', 1);

        $this->assertDatabaseHas('finance_transactions', [
            'category' => 'Iuran Warga Oktober',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_without_finance_manage_permission_cannot_create_transaction(): void
    {
        $admin = User::factory()->admin(['letter.verify'])->create();

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/finance', [
            'type' => 'income',
            'category' => 'Iuran Warga',
            'amount' => 500000,
            'description' => 'Test unauthorized',
            'transaction_date' => '2026-10-01',
        ]);

        $response->assertStatus(403);
    }

    public function test_warga_cannot_access_admin_finance_endpoints(): void
    {
        $warga = User::factory()->warga()->create();

        $response = $this->actingAs($warga)->postJson('/api/v1/admin/finance', [
            'type' => 'income',
            'category' => 'Iuran Warga',
            'amount' => 500000,
            'transaction_date' => '2026-10-01',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_publish_draft_transaction(): void
    {
        $admin = User::factory()->admin(['finance.manage'])->create();
        $transaction = FinanceTransaction::factory()->create([
            'status' => 'draft',
            'version' => 1,
        ]);

        $response = $this->actingAs($admin)->patchJson("/api/v1/admin/finance/{$transaction->id}", [
            'action' => 'publish',
            'version' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('finance_transactions', [
            'id' => $transaction->id,
            'status' => 'published',
            'version' => 2,
        ]);
    }

    public function test_double_publish_or_stale_version_triggers_conflict(): void
    {
        $admin = User::factory()->admin(['finance.manage'])->create();
        $transaction = FinanceTransaction::factory()->published()->create([
            'version' => 2,
        ]);

        // Attempting to publish already published
        $response = $this->actingAs($admin)->patchJson("/api/v1/admin/finance/{$transaction->id}", [
            'action' => 'publish',
            'version' => 2,
        ]);

        $response->assertStatus(409);
    }

    public function test_published_transaction_cannot_be_deleted_directly(): void
    {
        $admin = User::factory()->admin(['finance.manage'])->create();
        $transaction = FinanceTransaction::factory()->published()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/v1/admin/finance/{$transaction->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('finance_transactions', ['id' => $transaction->id]);
    }

    public function test_admin_can_reverse_published_transaction(): void
    {
        $admin = User::factory()->admin(['finance.manage'])->create();
        $transaction = FinanceTransaction::factory()->published()->create([
            'type' => 'income',
            'amount' => 500000,
            'category' => 'Iuran Sampah',
            'version' => 2,
        ]);

        $response = $this->actingAs($admin)->patchJson("/api/v1/admin/finance/{$transaction->id}", [
            'action' => 'reverse',
            'version' => 2,
            'reason' => 'Koreksi salah pencatatan warga',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'expense')
            ->assertJsonPath('data.amount', '500000.00')
            ->assertJsonPath('data.original_transaction_id', $transaction->id);

        // Original transaction is marked as reversed
        $this->assertDatabaseHas('finance_transactions', [
            'id' => $transaction->id,
            'status' => 'reversed',
            'reversal_reason' => 'Koreksi salah pencatatan warga',
            'version' => 3,
        ]);
    }

    public function test_draft_transaction_can_be_deleted(): void
    {
        $admin = User::factory()->admin(['finance.manage'])->create();
        $transaction = FinanceTransaction::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/admin/finance/{$transaction->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('finance_transactions', ['id' => $transaction->id]);
    }

    public function test_public_can_view_published_finance_summary_with_accurate_balance(): void
    {
        // 1 published income
        FinanceTransaction::factory()->published()->income(1000000)->create([
            'transaction_date' => '2026-10-01',
        ]);

        // 1 published expense
        FinanceTransaction::factory()->published()->expense(300000)->create([
            'transaction_date' => '2026-10-02',
        ]);

        // 1 draft (must NOT appear in public summary)
        FinanceTransaction::factory()->create([
            'status' => 'draft',
            'type' => 'income',
            'amount' => 5000000,
            'transaction_date' => '2026-10-03',
        ]);

        $response = $this->getJson('/api/v1/finance/summary?year=2026&month=10');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_income', 1000000)
            ->assertJsonPath('data.total_expense', 300000)
            ->assertJsonPath('data.net_balance', 700000)
            ->assertJsonCount(2, 'data.transactions');
    }
}
