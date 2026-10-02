<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Citizen;
use App\Models\DownloadAudit;
use App\Models\DuePayment;
use App\Models\FamilyCard;
use App\Models\FinanceTransaction;
use App\Models\FinancialReport;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\ResidentDue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchitectureAndRbacNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_enum_contains_normalized_singular_taxonomy(): void
    {
        $this->assertEquals('finance.transaction.create', Permission::FINANCE_TRANSACTION_CREATE->value);
        $this->assertEquals('finance.transaction.publish', Permission::FINANCE_TRANSACTION_PUBLISH->value);
        $this->assertEquals('finance.transaction.reverse', Permission::FINANCE_TRANSACTION_REVERSE->value);
        $this->assertEquals('finance.dues.manage', Permission::FINANCE_DUES_MANAGE->value);
        $this->assertEquals('finance.purchase.manage', Permission::FINANCE_PURCHASE_MANAGE->value);
        $this->assertEquals('finance.report.publish', Permission::FINANCE_REPORT_PUBLISH->value);
        $this->assertEquals('download.audit.read', Permission::DOWNLOAD_AUDIT_READ->value);
    }

    public function test_bendahara_has_finance_mutation_and_audit_permissions(): void
    {
        $bendahara = User::factory()->bendahara()->create();

        $this->assertTrue($bendahara->hasPermission('finance.transaction.read'));
        $this->assertTrue($bendahara->hasPermission('finance.transaction.create'));
        $this->assertTrue($bendahara->hasPermission('finance.transaction.publish'));
        $this->assertTrue($bendahara->hasPermission('finance.transaction.reverse'));
        $this->assertTrue($bendahara->hasPermission('finance.dues.manage'));
        $this->assertTrue($bendahara->hasPermission('finance.purchase.manage'));
        $this->assertTrue($bendahara->hasPermission('finance.report.generate'));
        $this->assertTrue($bendahara->hasPermission('finance.audit.read'));
        $this->assertTrue($bendahara->hasPermission('download.audit.read'));

        // Legacy compatibility
        $this->assertTrue($bendahara->hasPermission('finance.read'));
        $this->assertTrue($bendahara->hasPermission('finance.manage'));
    }

    public function test_ketua_rt_and_sekretaris_cannot_mutate_finance_transactions_directly(): void
    {
        $ketuaRt = User::factory()->ketuaRt()->create();
        $sekretaris = User::factory()->sekretaris()->create();

        // Ketua RT can read finance and publish quarterly reports, but cannot directly create/reverse ledger
        $this->assertTrue($ketuaRt->hasPermission('finance.transaction.read'));
        $this->assertTrue($ketuaRt->hasPermission('finance.report.read'));
        $this->assertTrue($ketuaRt->hasPermission('finance.report.publish'));
        $this->assertFalse($ketuaRt->hasPermission('finance.transaction.create'));
        $this->assertFalse($ketuaRt->hasPermission('finance.transaction.reverse'));
        $this->assertFalse($ketuaRt->hasPermission('finance.purchase.manage'));

        // Sekretaris can read finance and reports, but cannot mutate ledger
        $this->assertTrue($sekretaris->hasPermission('finance.transaction.read'));
        $this->assertTrue($sekretaris->hasPermission('finance.report.read'));
        $this->assertFalse($sekretaris->hasPermission('finance.transaction.create'));
        $this->assertFalse($sekretaris->hasPermission('finance.transaction.publish'));
        $this->assertFalse($sekretaris->hasPermission('finance.transaction.reverse'));
    }

    public function test_canonical_fields_on_finance_transactions_table(): void
    {
        $bendahara = User::factory()->bendahara()->create();

        $txn = FinanceTransaction::query()->create([
            'transaction_number' => 'TXN-202610-000001',
            'type' => 'income',
            'source' => 'dues',
            'category' => 'Iuran Warga Oktober',
            'amount' => 150000.00,
            'amount_idr' => 150000,
            'description' => 'Iuran warga RT',
            'transaction_date' => '2026-10-01',
            'status' => 'draft',
            'created_by' => $bendahara->id,
            'version' => 1,
        ]);

        $this->assertDatabaseHas('finance_transactions', [
            'id' => $txn->id,
            'transaction_number' => 'TXN-202610-000001',
            'source' => 'dues',
            'amount_idr' => 150000,
        ]);

        $this->assertSame(150000, $txn->amount_idr);
    }

    public function test_resident_dues_and_due_payments_relational_schema(): void
    {
        $kk = FamilyCard::factory()->create();
        $citizen = Citizen::factory()->create(['family_card_id' => $kk->id]);
        $bendahara = User::factory()->bendahara()->create();

        $due = ResidentDue::query()->create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 50000,
            'due_date' => '2026-10-10',
            'status' => 'PARTIAL',
            'notes' => 'Iuran kebersihan dan keamanan',
        ]);

        $payment = DuePayment::query()->create([
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 25000,
            'paid_at' => now(),
            'payment_method' => 'cash',
            'receipt_number' => 'KW-202610-000001',
            'received_by' => $bendahara->id,
            'status' => 'PAID',
        ]);

        $this->assertEquals(25000, $due->getTotalPaidIdr());
        $this->assertEquals(25000, $due->getRemainingDueIdr());
        $this->assertSame($citizen->id, $due->citizen->id);
        $this->assertSame($due->id, $payment->due->id);
    }

    public function test_purchases_and_itemized_procurement_calculation(): void
    {
        $bendahara = User::factory()->bendahara()->create();

        $purchase = Purchase::query()->create([
            'vendor_name' => 'Toko Bangunan Sejahtera',
            'purchase_date' => '2026-10-02',
            'invoice_number' => 'INV-TB-9981',
            'purpose' => 'Perbaikan pos ronda kamling',
            'created_by' => $bendahara->id,
            'version' => 1,
        ]);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'item_name' => 'Cat Tembok Putih 5kg',
            'quantity' => 2,
            'unit' => 'kaleng',
            'unit_price_idr' => 125000,
            'subtotal_idr' => 250000,
        ]);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'item_name' => 'Kuas Cat 4 Inch',
            'quantity' => 3,
            'unit' => 'buah',
            'unit_price_idr' => 25000,
            'subtotal_idr' => 75000,
        ]);

        $this->assertEquals(325000, $purchase->calculateTotalIdr());
        $this->assertCount(2, $purchase->items);
    }

    public function test_quarterly_financial_report_contract(): void
    {
        $ketuaRt = User::factory()->ketuaRt()->create();

        $report = FinancialReport::query()->create([
            'year' => 2026,
            'quarter' => 3,
            'revision' => 1,
            'period_start' => '2026-07-01',
            'period_end' => '2026-09-30',
            'opening_balance_idr' => 5000000,
            'income_idr' => 12500000,
            'expense_idr' => 8200000,
            'closing_balance_idr' => 9300000,
            'dues_assessed_idr' => 6000000,
            'dues_collected_idr' => 5800000,
            'dues_outstanding_idr' => 200000,
            'status' => 'published',
            'generated_at' => now(),
            'published_at' => now(),
            'published_by' => $ketuaRt->id,
            'checksum_sha256' => hash('sha256', '2026-Q3-REV1-9300000'),
            'version' => 1,
        ]);

        $this->assertSame(9300000, $report->closing_balance_idr);
        $this->assertTrue($report->isPublished());
        $this->assertEquals('2026-Q3 (Rev 1)', $report->getQuarterLabel());
    }

    public function test_download_audit_uuid_and_hashing(): void
    {
        $user = User::factory()->create();

        $audit = DownloadAudit::query()->create([
            'actor_user_id' => $user->id,
            'resource_type' => 'FinancialReport',
            'resource_id' => '1',
            'file_type' => 'pdf',
            'period_year' => 2026,
            'period_quarter' => 3,
            'action' => 'download',
            'result' => 'success',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent_hash' => hash('sha256', 'Mozilla/5.0'),
            'created_at' => now(),
        ]);

        $this->assertNotEmpty($audit->id);
        $this->assertIsString($audit->id);
        $this->assertEquals('FinancialReport', $audit->resource_type);
        $this->assertEquals('success', $audit->result);
    }
}
