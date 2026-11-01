<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\DuePayment;
use App\Models\FinanceTransaction;
use App\Models\FinancialReport;
use App\Models\Purchase;
use App\Models\ResidentDue;
use App\Models\User;
use App\Services\ResidentDueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Kebenaran buku kas: koreksi (reversal), transaksi otomatis, iuran, laporan triwulan, dan otorisasi laporan.
 */
class FinanceCorrectnessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_publish_then_reverse_nets_ledger_to_zero_everywhere(): void
    {
        $bendahara = User::factory()->bendahara()->create();

        $id = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/transactions', [
            'type' => 'income',
            'category' => 'Donasi',
            'amount_idr' => 100000,
            'transaction_date' => now()->toDateString(),
        ])->assertCreated()->json('data.id');

        $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$id}/publish", ['version' => 1])->assertOk();

        // Isi cache ringkasan publik sebelum koreksi untuk memastikan cache ikut dibersihkan.
        $this->getJson('/api/v1/public/finance/summary')->assertJsonPath('data.net_balance_idr', 100000);

        $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$id}/reverse", [
            'version' => 2,
            'reason' => 'Salah catat nominal',
        ])->assertCreated();

        $this->getJson('/api/v1/public/finance/summary')
            ->assertJsonPath('data.net_balance_idr', 0)
            ->assertJsonPath('data.total_income_idr', 100000)
            ->assertJsonPath('data.total_expense_idr', 100000);
        $this->getJson('/api/v1/portal')->assertJsonPath('data.finance.balance', 0);

        $cards = collect($this->actingAs($bendahara)->getJson('/api/v1/dashboard/summary')->json('data.cards'));
        $this->assertSame(0, $cards->firstWhere('label', 'Saldo kas')['value']);

        $report = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/reports/quarterly/generate', [
            'year' => now()->year,
            'quarter' => (int) ceil(now()->month / 3),
        ])->assertCreated();
        $report->assertJsonPath('data.income_idr', 100000)->assertJsonPath('data.closing_balance_idr', 0);
    }

    public function test_only_draft_can_be_published_and_reversal_rows_cannot_be_reversed(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $tx = FinanceTransaction::factory()->published()->income(50000)->create(['source' => 'other', 'version' => 1]);

        $reversalId = $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$tx->id}/reverse", [
            'version' => 1,
            'reason' => 'Dobel input',
        ])->assertCreated()->json('data.id');

        // Transaksi asal kini 'reversed' (versi 2): tidak boleh diterbitkan ulang.
        $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$tx->id}/publish", ['version' => 2])
            ->assertStatus(409);
        $this->assertSame('reversed', $tx->fresh()->status);

        // Jurnal pembalik tidak dapat dikoreksi lagi.
        $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$reversalId}/reverse", [
            'version' => 1,
            'reason' => 'Coba balik lagi',
        ])->assertStatus(422);
        $this->assertSame(1, FinanceTransaction::query()->whereNotNull('original_transaction_id')->count());
    }

    public function test_system_rows_from_dues_and_purchases_cannot_be_reversed_from_the_ledger(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $due = ResidentDue::factory()->create(['amount_due_idr' => 50000, 'status' => 'UNPAID']);
        $payment = app(ResidentDueService::class)->recordPayment($due, 50000, 'cash', $bendahara);
        $duesTx = $payment->financeTransaction;

        $this->assertTrue($duesTx->isSystem());
        $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$duesTx->id}/reverse", [
            'version' => $duesTx->version,
            'reason' => 'Salah warga',
        ])->assertStatus(422)->assertJsonFragment(['message' => 'Transaksi ini dibuat otomatis dari pembayaran iuran. Koreksi melalui halaman Iuran Warga (/app/dues).']);

        $purchaseId = $this->actingAs($bendahara)->post('/api/v1/admin/finance/purchases', [
            'vendor_name' => 'Toko Lampu',
            'purchase_date' => now()->toDateString(),
            'purpose' => 'Lampu jalan',
            'items' => [['item_name' => 'Bohlam', 'quantity' => 2, 'unit_price_idr' => 20000]],
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');
        $purchaseTx = Purchase::query()->findOrFail($purchaseId)->transaction;

        $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$purchaseTx->id}/reverse", [
            'version' => $purchaseTx->version,
            'reason' => 'Salah vendor',
        ])->assertStatus(422);

        $this->assertSame('published', $duesTx->fresh()->status);
        $this->assertSame('published', $purchaseTx->fresh()->status);
        $this->assertSame('PAID', $payment->fresh()->status);
    }

    public function test_dues_payment_hides_payer_name_publicly_and_refreshes_cached_summary(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $citizen = Citizen::factory()->create(['full_name' => 'Siti Rahayu']);
        $due = ResidentDue::factory()->create(['citizen_id' => $citizen->id, 'amount_due_idr' => 30000, 'status' => 'UNPAID']);

        $this->getJson('/api/v1/public/finance/summary')->assertJsonPath('data.net_balance_idr', 0);

        $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 30000,
            'payment_method' => 'cash',
        ])->assertCreated();

        $tx = FinanceTransaction::query()->where('source', FinanceTransaction::SOURCE_DUES)->firstOrFail();
        $this->assertStringNotContainsString('Siti', (string) $tx->description);

        // Cache ringkasan publik langsung segar setelah pembayaran iuran.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/public/finance/summary')->assertJsonPath('data.net_balance_idr', 30000);

        // Baris lama yang masih memuat nama pembayar disamarkan di jalur publik.
        FinanceTransaction::factory()->published()->income(25000)->create([
            'source' => FinanceTransaction::SOURCE_DUES,
            'description' => 'Pembayaran iuran kas RT 9/2026 oleh Budi Santoso (Kuitansi: RCP-1)',
        ]);
        $portal = json_encode($this->getJson('/api/v1/portal')->json('data.finance.recent'), JSON_UNESCAPED_SLASHES);
        $this->assertStringNotContainsString('Budi', $portal);
        $this->assertStringContainsString('Pembayaran iuran kas RT 9/2026', $portal);
        $this->assertStringNotContainsString('Budi', json_encode($this->getJson('/api/v1/public/finance/transactions')->json('data')));
    }

    public function test_generate_dues_bills_only_permanent_residents(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        Citizen::factory()->count(2)->create(['status_warga' => 'tetap']);
        Citizen::factory()->create(['status_warga' => 'pindah']);
        Citizen::factory()->create(['status_warga' => 'pending_verification']);

        $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/dues/generate', [
            'period_year' => 2026,
            'period_month' => 11,
            'amount_due_idr' => 20000,
        ])->assertCreated()->assertJsonPath('generated_count', 2);

        $this->assertSame(2, app(ResidentDueService::class)->generateMonthlyDues(2026, 12, 20000));
        $this->assertSame(0, app(ResidentDueService::class)->generateMonthlyDues(2026, 12, 20000));
    }

    public function test_overdue_dues_are_derived_in_queries_and_marked_by_scheduled_command(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $pastUnpaid = ResidentDue::factory()->create(['status' => 'UNPAID', 'due_date' => now()->subDays(3)->toDateString()]);
        $futureUnpaid = ResidentDue::factory()->create(['status' => 'UNPAID', 'due_date' => now()->addDays(3)->toDateString(), 'period_month' => 2]);
        $pastPaid = ResidentDue::factory()->create(['status' => 'PAID', 'due_date' => now()->subDays(3)->toDateString(), 'period_month' => 3]);

        // Sebelum penjadwal berjalan, status efektif sudah OVERDUE.
        $this->assertSame('OVERDUE', $pastUnpaid->effectiveStatus());
        $this->actingAs($bendahara)->getJson('/api/v1/admin/finance/dues?status=overdue')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $pastUnpaid->id)
            ->assertJsonPath('data.0.effective_status', 'OVERDUE');

        $this->artisan('app:mark-overdue-dues')->assertSuccessful();

        $this->assertSame('OVERDUE', $pastUnpaid->fresh()->status);
        $this->assertSame('UNPAID', $futureUnpaid->fresh()->status);
        $this->assertSame('PAID', $pastPaid->fresh()->status);

        $this->artisan('schedule:list')->expectsOutputToContain('app:mark-overdue-dues');
    }

    public function test_citizen_dues_report_is_built_from_resident_dues(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $paid = Citizen::factory()->create(['full_name' => 'Ani Lunas']);
        $unpaid = Citizen::factory()->create(['full_name' => 'Budi Belum']);
        $partial = Citizen::factory()->create(['full_name' => 'Cici Sebagian']);

        $paidDue = ResidentDue::factory()->create(['citizen_id' => $paid->id, 'period_year' => 2026, 'period_month' => 10, 'amount_due_idr' => 50000, 'status' => 'PAID']);
        DuePayment::factory()->create(['resident_due_id' => $paidDue->id, 'amount_paid_idr' => 50000, 'status' => 'PAID']);
        ResidentDue::factory()->create(['citizen_id' => $unpaid->id, 'period_year' => 2026, 'period_month' => 10, 'amount_due_idr' => 50000, 'status' => 'UNPAID', 'due_date' => '2099-01-01']);
        $partialDue = ResidentDue::factory()->create(['citizen_id' => $partial->id, 'period_year' => 2026, 'period_month' => 10, 'amount_due_idr' => 50000, 'status' => 'PARTIAL']);
        DuePayment::factory()->create(['resident_due_id' => $partialDue->id, 'amount_paid_idr' => 20000, 'status' => 'PAID']);

        // Keterangan kas yang memuat nama/angka tidak lagi dianggap bukti lunas.
        FinanceTransaction::factory()->published()->income(50000)->create([
            'category' => 'Iuran Warga',
            'description' => "Iuran Budi Belum dan Cici Sebagian {$unpaid->id} {$partial->id}",
            'transaction_date' => '2026-10-05',
        ]);

        $html = $this->actingAs($bendahara)->get('/dashboard/finance/reports/citizen-dues?year=2026&month=10')
            ->assertOk()->getContent();

        $this->assertStringContainsString('Warga Telah Lunas: <strong>1 Warga</strong>', $html);
        $this->assertSame(1, substr_count($html, '>LUNAS<'));
        $this->assertStringContainsString('BELUM BAYAR', $html);
        $this->assertStringContainsString('SEBAGIAN', $html);
        $this->assertStringContainsString('Rp 70.000', $html);

        $this->actingAs($bendahara)->get('/dashboard/finance/reports/citizen-dues?year=2026&month=11')->assertNotFound();
    }

    public function test_quarterly_publish_supersedes_older_revision_checks_version_and_notifies(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $ketua = User::factory()->ketuaRt()->create();
        $warga = User::factory()->warga()->create();

        $rev1 = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/reports/quarterly/generate', ['year' => 2026, 'quarter' => 3])
            ->assertCreated()->assertJsonPath('data.can_publish', false)->json('data');
        $this->assertSame('Laporan triwulan menunggu pengesahan', $ketua->notifications()->first()?->data['title']);

        $this->actingAs($ketua)->getJson('/api/v1/admin/finance/reports/quarterly')->assertJsonPath('data.0.can_publish', true);

        // Versi usang -> 409.
        $this->actingAs($ketua)->postJson("/api/v1/admin/finance/reports/quarterly/{$rev1['id']}/publish", ['version' => 7])->assertStatus(409);

        $this->actingAs($ketua)->postJson("/api/v1/admin/finance/reports/quarterly/{$rev1['id']}/publish", ['version' => 1])
            ->assertOk()->assertJsonPath('data.status', 'published');
        $this->assertTrue($warga->notifications()->get()->contains(fn ($n) => $n->data['title'] === 'Laporan keuangan triwulan terbit'));

        // Menerbitkan ulang laporan yang sudah terbit -> 409.
        $this->actingAs($ketua)->postJson("/api/v1/admin/finance/reports/quarterly/{$rev1['id']}/publish")->assertStatus(409);

        $rev2 = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/reports/quarterly/generate', ['year' => 2026, 'quarter' => 3])
            ->assertCreated()->assertJsonPath('data.revision', 2)->json('data');
        $this->actingAs($ketua)->postJson("/api/v1/admin/finance/reports/quarterly/{$rev2['id']}/publish", ['version' => 1])->assertOk();

        $this->assertSame('superseded', FinancialReport::query()->find($rev1['id'])->status);
        $this->getJson('/api/v1/public/finance/reports/quarterly')
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.revision', 2);

        // Revisi yang sudah digantikan tidak dapat diterbitkan.
        $this->actingAs($ketua)->postJson("/api/v1/admin/finance/reports/quarterly/{$rev1['id']}/publish")->assertStatus(409);
    }

    public function test_finance_report_and_backup_routes_use_permissions_on_web_and_api(): void
    {
        FinanceTransaction::factory()->published()->income(10000)->create(['transaction_date' => now()->toDateString()]);
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->petugasKeamanan()->create();
        $bendahara = User::factory()->bendahara()->create();

        // ADMIN: finance.read -> boleh unduh/daftar, tetapi tidak boleh membuat backup (sama di web & API).
        $this->actingAs($admin)->get('/dashboard/finance/reports/monthly?year='.now()->year.'&month='.now()->month)->assertOk();
        $this->actingAs($admin)->getJson('/dashboard/finance/backups')->assertOk();
        $this->actingAs($admin)->postJson('/dashboard/finance/backups')->assertForbidden();
        $this->actingAs($admin)->postJson('/api/v1/admin/finance/backups')->assertForbidden();

        $this->actingAs($petugas)->get('/dashboard/finance/reports/monthly')->assertForbidden();
        $this->actingAs($petugas)->getJson('/dashboard/finance/backups')->assertForbidden();

        // Izin eksplisit dihormati walaupun perannya bukan pengurus keuangan.
        $petugas->givePermission('finance.report.generate');
        $this->actingAs($petugas->fresh())->postJson('/dashboard/finance/backups')->assertCreated();

        $this->actingAs($bendahara)->postJson('/dashboard/finance/backups')->assertCreated();
    }

    public function test_purchase_receipt_is_private_and_served_only_to_finance_staff(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $warga = User::factory()->warga()->create();

        $res = $this->actingAs($bendahara)->post('/api/v1/admin/finance/purchases', [
            'vendor_name' => 'Toko ATK',
            'purchase_date' => now()->toDateString(),
            'purpose' => 'Alat tulis sekretariat',
            'receipt' => UploadedFile::fake()->image('nota.jpg'),
            'items' => [['item_name' => 'Map', 'quantity' => 3, 'unit_price_idr' => 5000]],
        ], ['Accept' => 'application/json'])->assertCreated();

        $purchase = Purchase::query()->findOrFail($res->json('data.id'));
        Storage::disk('local')->assertExists($purchase->receipt_path);
        Storage::disk('public')->assertMissing($purchase->receipt_path);
        $url = $res->json('data.receipt_url');
        $this->assertSame(url("/api/v1/files/purchase-receipts/{$purchase->id}"), $url);

        $this->actingAs($bendahara)->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($warga)->get($url)->assertForbidden();

        // Nota lama di disk publik tetap tersaji lewat jalur berotorisasi.
        Storage::disk('public')->put('receipts/lama.jpg', 'x');
        $legacy = Purchase::factory()->create(['receipt_path' => 'receipts/lama.jpg']);
        $this->actingAs($bendahara)->get("/api/v1/files/purchase-receipts/{$legacy->id}")->assertOk();
    }
}
