<?php

namespace Tests\Feature;

use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceLedgerSprint2Test extends TestCase
{
    use RefreshDatabase;

    public function test_bendahara_can_create_draft_transaction_with_integer_amount_idr(): void
    {
        $bendahara = User::factory()->bendahara()->create();

        $response = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/transactions', [
            'type' => 'income',
            'source' => 'dues',
            'category' => 'Iuran Warga Oktober',
            'amount_idr' => 2500000,
            'description' => 'Iuran warga terverifikasi 50 KK',
            'transaction_date' => '2026-10-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'income')
            ->assertJsonPath('data.source', 'dues')
            ->assertJsonPath('data.amount_idr', 2500000)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.version', 1);

        $this->assertNotEmpty($response->json('data.transaction_number'));
        $this->assertDatabaseHas('finance_transactions', [
            'category' => 'Iuran Warga Oktober',
            'amount_idr' => 2500000,
            'source' => 'dues',
            'status' => 'draft',
        ]);
    }

    public function test_bendahara_can_publish_transaction_with_optimistic_locking(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $transaction = FinanceTransaction::factory()->create([
            'status' => 'draft',
            'version' => 1,
        ]);

        $response = $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$transaction->id}/publish", [
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

    public function test_publishing_with_stale_version_triggers_http_409_conflict(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $transaction = FinanceTransaction::factory()->create([
            'status' => 'draft',
            'version' => 2,
        ]);

        // Trying to publish with stale version 1 instead of current 2
        $response = $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$transaction->id}/publish", [
            'version' => 1,
        ]);

        $response->assertStatus(409);
    }

    public function test_bendahara_can_reverse_published_transaction(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $transaction = FinanceTransaction::factory()->published()->income(750000)->create([
            'version' => 1,
            'category' => 'Sumbangan Donatur',
        ]);

        $response = $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$transaction->id}/reverse", [
            'version' => 1,
            'reason' => 'Koreksi salah rekening tujuan donasi',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'expense')
            ->assertJsonPath('data.amount_idr', 750000)
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.original_transaction_id', $transaction->id);

        // Original transaction is updated with status reversed and incremented version
        $this->assertDatabaseHas('finance_transactions', [
            'id' => $transaction->id,
            'status' => 'reversed',
            'reversal_reason' => 'Koreksi salah rekening tujuan donasi',
            'version' => 2,
        ]);
    }

    public function test_reversing_draft_or_stale_version_is_rejected(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $draftTransaction = FinanceTransaction::factory()->create([
            'status' => 'draft',
            'version' => 1,
        ]);

        // Attempting to reverse a draft transaction
        $responseDraft = $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$draftTransaction->id}/reverse", [
            'version' => 1,
            'reason' => 'Mencoba membalik draf',
        ]);
        $responseDraft->assertStatus(422);

        // Published transaction with wrong version
        $publishedTransaction = FinanceTransaction::factory()->published()->create([
            'version' => 3,
        ]);

        $responseStale = $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$publishedTransaction->id}/reverse", [
            'version' => 1,
            'reason' => 'Alasan valid tetapi versi usang',
        ]);
        $responseStale->assertStatus(409);
    }

    public function test_published_or_reversed_transaction_cannot_be_deleted(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $published = FinanceTransaction::factory()->published()->create();
        $reversed = FinanceTransaction::factory()->reversed()->create();

        $resPublished = $this->actingAs($bendahara)->deleteJson("/api/v1/admin/finance/{$published->id}");
        $resPublished->assertStatus(403);

        $resReversed = $this->actingAs($bendahara)->deleteJson("/api/v1/admin/finance/{$reversed->id}");
        $resReversed->assertStatus(403);

        $this->assertDatabaseHas('finance_transactions', ['id' => $published->id]);
        $this->assertDatabaseHas('finance_transactions', ['id' => $reversed->id]);
    }

    public function test_public_finance_summary_v11_returns_accurate_database_aggregates_without_full_transactions(): void
    {
        // 2 published income
        FinanceTransaction::factory()->published()->income(1500000)->create([
            'transaction_date' => '2026-08-10', // Q3
        ]);
        FinanceTransaction::factory()->published()->income(500000)->create([
            'transaction_date' => '2026-09-15', // Q3
        ]);

        // 1 published expense
        FinanceTransaction::factory()->published()->expense(400000)->create([
            'transaction_date' => '2026-07-20', // Q3
        ]);

        // 1 published income in Q2 (must be excluded when filtering Q3)
        FinanceTransaction::factory()->published()->income(2000000)->create([
            'transaction_date' => '2026-05-10', // Q2
        ]);

        // 1 draft in Q3 (must not appear)
        FinanceTransaction::factory()->create([
            'status' => 'draft',
            'amount_idr' => 10000000,
            'transaction_date' => '2026-08-01',
        ]);

        $response = $this->getJson('/api/v1/public/finance/summary?period=2026-Q3');

        $response->assertStatus(200)
            ->assertJsonPath('data.period', '2026-Q3')
            ->assertJsonPath('data.total_income_idr', 2000000)
            ->assertJsonPath('data.total_expense_idr', 400000)
            ->assertJsonPath('data.net_balance_idr', 1600000)
            ->assertJsonPath('data.total_transactions', 3)
            ->assertJsonMissingPath('data.transactions'); // Ensured payload is lightweight!
    }

    public function test_public_finance_transactions_supports_cursor_pagination(): void
    {
        FinanceTransaction::factory()->count(20)->published()->income(100000)->create([
            'transaction_date' => '2026-08-15',
        ]);

        $response = $this->getJson('/api/v1/public/finance/transactions?period=2026-Q3');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links' => ['next', 'prev'],
                'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
            ])
            ->assertJsonCount(15, 'data');
    }
}
