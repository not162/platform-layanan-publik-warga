<?php

namespace Tests\Feature;

use App\Models\FinanceTransaction;
use App\Models\FinancialReport;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseAndQuarterlyReportSprint4Test extends TestCase
{
    use RefreshDatabase;

    public function test_bendahara_can_create_itemized_purchase_with_receipt_upload_and_auto_posts_expense(): void
    {
        Storage::fake('public');

        $bendahara = User::factory()->bendahara()->create();
        $receiptFile = UploadedFile::fake()->image('nota_semen_dan_cat.jpg');

        $payload = [
            'vendor_name' => 'Toko Bangunan Sejahtera Bersama',
            'purchase_date' => '2026-10-02',
            'invoice_number' => 'INV-TB-2026-889',
            'purpose' => 'Perbaikan gapura dan pos ronda RT',
            'notes' => 'Pembelian cat, kuas, dan semen untuk kerja bakti',
            'receipt' => $receiptFile,
            'items' => [
                [
                    'item_name' => 'Semen Tiga Roda 40kg',
                    'description' => 'Untuk plester dinding pos ronda',
                    'quantity' => 4,
                    'unit' => 'sak',
                    'unit_price_idr' => 75000,
                ],
                [
                    'item_name' => 'Cat Tembok Putih 5kg',
                    'description' => 'Pengecatan gapura masuk RT',
                    'quantity' => 2,
                    'unit' => 'kaleng',
                    'unit_price_idr' => 125000,
                ],
            ],
        ];

        $response = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/purchases', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.vendor_name', 'Toko Bangunan Sejahtera Bersama')
            ->assertJsonPath('data.total_amount_idr', 550000)
            ->assertJsonCount(2, 'data.items');

        $purchaseId = $response->json('data.id');
        $this->assertDatabaseHas('purchases', [
            'id' => $purchaseId,
            'vendor_name' => 'Toko Bangunan Sejahtera Bersama',
            'invoice_number' => 'INV-TB-2026-889',
        ]);

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchaseId,
            'item_name' => 'Semen Tiga Roda 40kg',
            'quantity' => 4,
            'unit_price_idr' => 75000,
            'subtotal_idr' => 300000,
        ]);

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchaseId,
            'item_name' => 'Cat Tembok Putih 5kg',
            'quantity' => 2,
            'unit_price_idr' => 125000,
            'subtotal_idr' => 250000,
        ]);

        // Verifikasi otomatis tercatat sebagai transaksi pengeluaran di buku kas umum
        $this->assertDatabaseHas('finance_transactions', [
            'type' => 'expense',
            'amount_idr' => 550000,
            'status' => 'published',
        ]);

        $purchase = Purchase::findOrFail($purchaseId);
        $this->assertNotNull($purchase->receipt_path);
        $this->assertTrue(Storage::disk('public')->exists($purchase->receipt_path));
    }

    public function test_bendahara_can_view_purchases_list_and_item_details(): void
    {
        $bendahara = User::factory()->bendahara()->create();

        $purchase = Purchase::factory()->create([
            'vendor_name' => 'CV Sumber Makmur',
            'purchase_date' => '2026-10-01',
        ]);

        PurchaseItem::factory()->create([
            'purchase_id' => $purchase->id,
            'item_name' => 'Sapu Lidi',
            'quantity' => 10,
            'unit_price_idr' => 15000,
            'subtotal_idr' => 150000,
        ]);

        $responseList = $this->actingAs($bendahara)->getJson('/api/v1/admin/finance/purchases');
        $responseList->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.vendor_name', 'CV Sumber Makmur');

        $responseDetail = $this->actingAs($bendahara)->getJson("/api/v1/admin/finance/purchases/{$purchase->id}");
        $responseDetail->assertStatus(200)
            ->assertJsonPath('data.id', $purchase->id)
            ->assertJsonPath('data.total_amount_idr', 150000)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_warga_cannot_access_admin_purchases(): void
    {
        $warga = User::factory()->warga()->create();

        $responsePost = $this->actingAs($warga)->postJson('/api/v1/admin/finance/purchases', [
            'vendor_name' => 'Toko Liar',
            'purchase_date' => '2026-10-01',
            'purpose' => 'Tanpa izin',
            'items' => [['item_name' => 'Barang', 'quantity' => 1, 'unit_price_idr' => 10000]],
        ]);
        $responsePost->assertStatus(403);

        $responseGet = $this->actingAs($warga)->getJson('/api/v1/admin/finance/purchases');
        $responseGet->assertStatus(403);
    }

    public function test_bendahara_can_generate_quarterly_report_with_accurate_balances(): void
    {
        $bendahara = User::factory()->bendahara()->create();

        // Transaksi sebelum Q1 (tahun 2025) untuk menguji saldo awal (opening balance)
        FinanceTransaction::factory()->create([
            'type' => 'income',
            'amount_idr' => 2000000,
            'transaction_date' => '2025-12-15',
            'status' => 'published',
        ]);
        FinanceTransaction::factory()->create([
            'type' => 'expense',
            'amount_idr' => 500000,
            'transaction_date' => '2025-12-20',
            'status' => 'published',
        ]);
        // Saldo awal 2026-Q1 = 2.000.000 - 500.000 = 1.500.000

        // Transaksi dalam 2026 Q1 (Jan-Mar 2026)
        FinanceTransaction::factory()->create([
            'type' => 'income',
            'amount_idr' => 3000000,
            'transaction_date' => '2026-01-10',
            'status' => 'published',
        ]);
        FinanceTransaction::factory()->create([
            'type' => 'expense',
            'amount_idr' => 1000000,
            'transaction_date' => '2026-02-14',
            'status' => 'published',
        ]);
        // Pemasukan Q1 = 3.000.000, Pengeluaran Q1 = 1.000.000
        // Saldo akhir Q1 = 1.500.000 + 3.000.000 - 1.000.000 = 3.500.000

        $response = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/reports/quarterly/generate', [
            'year' => 2026,
            'quarter' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.year', 2026)
            ->assertJsonPath('data.quarter', 1)
            ->assertJsonPath('data.revision', 1)
            ->assertJsonPath('data.opening_balance_idr', 1500000)
            ->assertJsonPath('data.income_idr', 3000000)
            ->assertJsonPath('data.expense_idr', 1000000)
            ->assertJsonPath('data.closing_balance_idr', 3500000)
            ->assertJsonPath('data.status', 'draft');

        $this->assertNotEmpty($response->json('data.checksum_sha256'));
    }

    public function test_ketua_rt_cannot_generate_quarterly_report(): void
    {
        $ketuaRt = User::factory()->ketuaRt()->create();

        $response = $this->actingAs($ketuaRt)->postJson('/api/v1/admin/finance/reports/quarterly/generate', [
            'year' => 2026,
            'quarter' => 4,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('financial_reports', ['year' => 2026, 'quarter' => 4]);
    }

    public function test_bendahara_cannot_publish_quarterly_report(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $report = FinancialReport::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/reports/quarterly/{$report->id}/publish");

        $response->assertForbidden();
        $this->assertDatabaseHas('financial_reports', [
            'id' => $report->id,
            'status' => 'draft',
            'published_by' => null,
        ]);
    }

    public function test_sekretaris_cannot_generate_or_publish_quarterly_report(): void
    {
        $sekretaris = User::factory()->sekretaris()->create();
        $report = FinancialReport::factory()->create([
            'status' => 'draft',
        ]);

        $generateResponse = $this->actingAs($sekretaris)->postJson('/api/v1/admin/finance/reports/quarterly/generate', [
            'year' => 2026,
            'quarter' => 4,
        ]);
        $publishResponse = $this->actingAs($sekretaris)->postJson("/api/v1/admin/finance/reports/quarterly/{$report->id}/publish");

        $generateResponse->assertForbidden();
        $publishResponse->assertForbidden();
        $this->assertDatabaseHas('financial_reports', [
            'id' => $report->id,
            'status' => 'draft',
            'published_by' => null,
        ]);
        $this->assertDatabaseMissing('financial_reports', ['year' => 2026, 'quarter' => 4]);
    }

    public function test_finance_manager_can_read_but_not_mutate_reports_without_specific_permissions(): void
    {
        $admin = User::factory()->admin(['finance.manage'])->create();
        FinancialReport::factory()->create(['status' => 'draft']);

        $readResponse = $this->actingAs($admin)->getJson('/api/v1/admin/finance/reports/quarterly');
        $generateResponse = $this->actingAs($admin)->postJson('/api/v1/admin/finance/reports/quarterly/generate', [
            'year' => 2026,
            'quarter' => 4,
        ]);

        $readResponse->assertOk()->assertJsonCount(1, 'data');
        $generateResponse->assertForbidden();
        $this->assertDatabaseMissing('financial_reports', ['year' => 2026, 'quarter' => 4]);
    }

    public function test_ketua_rt_can_publish_quarterly_report_with_anti_tamper_checksum(): void
    {
        $ketuaRt = User::factory()->ketuaRt()->create();

        $report = FinancialReport::factory()->create([
            'year' => 2026,
            'quarter' => 2,
            'revision' => 1,
            'opening_balance_idr' => 3500000,
            'income_idr' => 2000000,
            'expense_idr' => 1500000,
            'closing_balance_idr' => 4000000,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($ketuaRt)->postJson("/api/v1/admin/finance/reports/quarterly/{$report->id}/publish");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.published_by_name', $ketuaRt->name);

        $report->refresh();
        $this->assertEquals('published', $report->status);
        $this->assertNotNull($report->published_at);
        $this->assertEquals($ketuaRt->id, $report->published_by);
        $this->assertNotEmpty($report->checksum_sha256);
    }

    public function test_public_and_warga_can_only_view_published_quarterly_reports(): void
    {
        // Laporan terbit
        FinancialReport::factory()->create([
            'year' => 2026,
            'quarter' => 1,
            'status' => 'published',
        ]);

        // Laporan draf (belum terbit)
        FinancialReport::factory()->create([
            'year' => 2026,
            'quarter' => 2,
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/public/finance/reports/quarterly');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.quarter', 1)
            ->assertJsonPath('data.0.status', 'published');
    }
}
