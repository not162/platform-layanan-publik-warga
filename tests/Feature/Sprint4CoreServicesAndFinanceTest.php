<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\DuePayment;
use App\Models\FamilyCard;
use App\Models\FinanceTransaction;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Purchase;
use App\Models\ResidentDue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class Sprint4CoreServicesAndFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_letter_birokrasi_2_tier_workflow_and_completed_finality(): void
    {
        $wargaUser = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();
        $citizen = Citizen::create([
            'family_card_id' => $familyCard->id,
            'user_id' => $wargaUser->id,
            'nik' => '3201889900112233',
            'nik_hash' => hash('sha256', '3201889900112233'),
            'name' => 'Warga Surat S4',
            'full_name' => 'Warga Surat S4',
            'gender' => 'Laki-laki',
            'place_of_birth' => 'Jakarta',
            'date_of_birth' => '1992-04-10',
            'status_warga' => 'tetap',
            'is_active' => true,
            'version' => 1,
        ]);

        $letterType = LetterType::firstOrCreate(
            ['kode_surat' => 'SKD'],
            [
                'nama_surat' => 'Surat Keterangan Domisili',
                'deskripsi' => 'Surat keterangan domisili warga',
                'is_active' => true,
            ]
        );

        $sekretaris = User::factory()->admin(['letter.verify', 'letter.read'])->create();
        $ketuaRt = User::factory()->admin(['letter.approve', 'letter.complete', 'letter.read'])->create();

        // 1. Warga creates draft letter
        $createResponse = $this->actingAs($wargaUser)->postJson('/api/v1/letters', [
            'jenis_surat_id' => $letterType->id,
            'type' => 'Surat Keterangan Domisili',
            'keperluan' => 'Pengurusan Rekening Bank',
        ]);
        $createResponse->assertStatus(Response::HTTP_CREATED);
        $letterId = $createResponse->json('data.id');

        // 2. Warga submits draft letter (draft -> submitted)
        $submitResponse = $this->actingAs($wargaUser)->postJson("/api/v1/letters/{$letterId}/submit", [
            'version' => 1,
        ]);
        $submitResponse->assertStatus(Response::HTTP_OK);
        $this->assertEquals('submitted', $submitResponse->json('data.status'));

        // 3. Illegal transition: Ketua RT attempts to approve directly from submitted (must conflict)
        $illegalApprove = $this->actingAs($ketuaRt)->patchJson("/api/v1/letters/{$letterId}", [
            'status' => 'approved',
            'version' => 2,
        ]);
        $illegalApprove->assertStatus(Response::HTTP_CONFLICT);

        // 4. Sekretaris verifies letter (submitted -> verified)
        $verifyResponse = $this->actingAs($sekretaris)->postJson("/api/v1/admin/letters/{$letterId}/verify", [
            'version' => 2,
            'notes' => 'Dokumen KTP dan KK terverifikasi lengkap.',
        ]);
        $verifyResponse->assertStatus(Response::HTTP_OK);
        $this->assertEquals('verified', $verifyResponse->json('data.status'));

        // 5. Ketua RT approves letter (verified -> approved)
        $approveResponse = $this->actingAs($ketuaRt)->postJson("/api/v1/admin/letters/{$letterId}/approve", [
            'version' => 3,
            'letter_number' => 'SKD/004/RT04/10/2026',
        ]);
        $approveResponse->assertStatus(Response::HTTP_OK);
        $this->assertEquals('approved', $approveResponse->json('data.status'));
        $this->assertNotEmpty($approveResponse->json('data.verification_token'));

        // 6. Complete letter (approved -> completed)
        $completeResponse = $this->actingAs($ketuaRt)->postJson("/api/v1/admin/letters/{$letterId}/complete", [
            'version' => 4,
        ]);
        $completeResponse->assertStatus(Response::HTTP_OK);
        $this->assertEquals('completed', $completeResponse->json('data.status'));

        // 7. Completed finality protection: attempting to revert to draft or verified fails
        $revertResponse = $this->actingAs($ketuaRt)->patchJson("/api/v1/letters/{$letterId}", [
            'status' => 'draft',
            'version' => 5,
        ]);
        $revertResponse->assertStatus(Response::HTTP_CONFLICT);
    }

    public function test_letter_tenant_isolation_warga_cannot_read_or_alter_other_warga_letter(): void
    {
        $wargaA = User::factory()->warga()->create();
        $citizenA = Citizen::factory()->create(['user_id' => $wargaA->id]);

        $wargaB = User::factory()->warga()->create();
        $citizenB = Citizen::factory()->create(['user_id' => $wargaB->id]);

        $letterA = Letter::create([
            'citizen_id' => $citizenA->id,
            'type' => 'Surat Pengantar A',
            'ticket_number' => 'SRT-WARGA-A-001',
            'status' => 'draft',
            'version' => 1,
        ]);

        // Warga B tries to view Warga A's letter -> 403 Forbidden
        $viewResponse = $this->actingAs($wargaB)->getJson("/api/v1/letters/{$letterA->id}");
        $viewResponse->assertStatus(Response::HTTP_FORBIDDEN);

        // Warga B tries to submit Warga A's letter -> 403 Forbidden
        $submitResponse = $this->actingAs($wargaB)->postJson("/api/v1/letters/{$letterA->id}/submit");
        $submitResponse->assertStatus(Response::HTTP_FORBIDDEN);

        // Warga B tries to delete Warga A's letter -> 403 Forbidden
        $deleteResponse = $this->actingAs($wargaB)->deleteJson("/api/v1/letters/{$letterA->id}");
        $deleteResponse->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_complaint_submission_with_anonymity_protection(): void
    {
        $warga = User::factory()->warga()->create(['name' => 'Warga Pelapor Rahasia']);
        $admin = User::factory()->admin(['complaint.manage'])->create();

        // 1. Submit complaint with is_anonymous = true
        $complaintResponse = $this->actingAs($warga)->postJson('/api/v1/complaints', [
            'kategori' => 'kebersihan',
            'title' => 'Tumpukan Sampah Liar di Gang 3',
            'description' => 'Sampah menumpuk dan menimbulkan bau tidak sedap.',
            'location' => 'Gang 3 Depan Pos RW',
            'is_anonymous' => true,
        ]);
        $complaintResponse->assertStatus(Response::HTTP_CREATED);
        $complaintId = $complaintResponse->json('data.id');
        $this->assertTrue($complaintResponse->json('data.is_anonymous'));

        // 2. View complaint: user payload is omitted / null for anonymity
        $showResponse = $this->actingAs($admin)->getJson("/api/v1/complaints/{$complaintId}");
        $showResponse->assertStatus(Response::HTTP_OK);
        $this->assertNull($showResponse->json('data.user'));

        // 3. Admin updates status to in_progress and resolved
        $updateResponse = $this->actingAs($admin)->patchJson("/api/v1/admin/complaints/{$complaintId}/status", [
            'status' => 'in_progress',
            'version' => 1,
            'admin_response' => 'Petugas PPSU sedang diarahkan ke lokasi gang 3.',
        ]);
        $updateResponse->assertStatus(Response::HTTP_OK);
        $this->assertEquals('in_progress', $updateResponse->json('data.status'));
    }

    public function test_security_incident_report_and_handling_by_security_officer(): void
    {
        $warga = User::factory()->warga()->create();
        $hansip = User::factory()->admin(['security.manage', 'security.read'])->create();

        // 1. Warga files security incident report
        $reportResponse = $this->actingAs($warga)->postJson('/api/v1/security-reports', [
            'category' => 'kehilangan',
            'severity' => 'medium',
            'title' => 'Kehilangan Helm di Depan Pos Ronda',
            'description' => 'Helm hitam hilang saat ditinggal taraweh.',
            'location' => 'Pos Ronda RT 04',
            'incident_at' => '2026-10-16 20:00:00',
        ]);
        $reportResponse->assertStatus(Response::HTTP_CREATED);
        $reportId = $reportResponse->json('data.id');

        // 2. Security officer handles and resolves report
        $resolveResponse = $this->actingAs($hansip)->patchJson("/api/v1/admin/security-reports/{$reportId}", [
            'status' => 'resolved',
            'assigned_to' => $hansip->id,
            'resolution' => 'Petugas telah memeriksa CCTV pos ronda dan mengamankan barang bukti.',
            'version' => 1,
        ]);
        $resolveResponse->assertStatus(Response::HTTP_OK);
        $this->assertEquals('resolved', $resolveResponse->json('data.status'));
    }

    public function test_resident_due_payment_and_overpayment_prevention(): void
    {
        $admin = User::factory()->admin(['finance.payment.manage', 'finance.payment.create', 'finance.manage'])->create();
        $citizen = Citizen::factory()->create();

        $due = ResidentDue::create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 50000,
            'due_date' => '2026-10-25',
            'status' => 'UNPAID',
        ]);

        // 1. Pay partial: 30,000 IDR
        $partialPayment = $this->actingAs($admin)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 30000,
            'payment_method' => 'transfer',
            'notes' => 'Cicilan pertama iuran',
        ]);
        $partialPayment->assertStatus(Response::HTTP_CREATED);
        $due->refresh();
        $this->assertEquals('PARTIAL', $due->status);

        // 2. Attempt overpayment: paying 30,000 when remaining is only 20,000 (must fail with 422)
        $overpayResponse = $this->actingAs($admin)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 30000,
            'payment_method' => 'cash',
        ]);
        $overpayResponse->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        // 3. Pay exact remaining balance: 20,000 IDR
        $finalPayment = $this->actingAs($admin)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 20000,
            'payment_method' => 'cash',
        ]);
        $finalPayment->assertStatus(Response::HTTP_CREATED);
        $due->refresh();
        $this->assertEquals('PAID', $due->status);

        // 4. Verify linked finance transaction has created_by = admin->id
        $paymentRecord = DuePayment::where('resident_due_id', $due->id)->latest()->first();
        $this->assertNotNull($paymentRecord->finance_transaction_id);
        $financeTx = FinanceTransaction::find($paymentRecord->finance_transaction_id);
        $this->assertNotNull($financeTx);
        $this->assertEquals($admin->id, $financeTx->created_by);
        $this->assertEquals('income', $financeTx->type);

        // 5. Attempt paying again on fully paid due (must fail with 422)
        $duplicatePayResponse = $this->actingAs($admin)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 10000,
            'payment_method' => 'cash',
        ]);
        $duplicatePayResponse->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_finance_transaction_reversal_anti_fraud_flow(): void
    {
        $bendahara = User::factory()->admin(['finance.transaction.create', 'finance.transaction.publish', 'finance.transaction.reverse', 'finance.manage'])->create();

        // 1. Create income transaction (initially draft)
        $createResponse = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/transactions', [
            'source' => 'other',
            'amount_idr' => 500000,
            'type' => 'income',
            'category' => 'Donasi',
            'description' => 'Bantuan pembangunan pos kamling',
            'transaction_date' => '2026-10-16',
        ]);
        $createResponse->assertStatus(Response::HTTP_CREATED);
        $txId = $createResponse->json('data.id');

        // 2. Publish transaction (draft -> published)
        $publishResponse = $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$txId}/publish", [
            'version' => 1,
        ]);
        $publishResponse->assertStatus(Response::HTTP_OK);
        $this->assertEquals('published', $publishResponse->json('data.status'));

        // 3. Published transaction cannot be deleted directly (anti-fraud: must fail with 403)
        $deleteResponse = $this->actingAs($bendahara)->deleteJson("/api/v1/admin/finance/{$txId}");
        $deleteResponse->assertStatus(Response::HTTP_FORBIDDEN);

        // 4. Reverse the transaction (version is now 2 after publish)
        $reverseResponse = $this->actingAs($bendahara)->postJson("/api/v1/admin/finance/transactions/{$txId}/reverse", [
            'version' => 2,
            'reason' => 'Salah input nominal, seharusnya ditujukan ke kas RT 02.',
        ]);
        $reverseResponse->assertStatus(Response::HTTP_CREATED);

        // 5. Original is now reversed, and reversal transaction is expense
        $originalTx = FinanceTransaction::find($txId);
        $this->assertEquals('reversed', $originalTx->status);

        $reversalTxId = $reverseResponse->json('data.id');
        $reversalTx = FinanceTransaction::find($reversalTxId);
        $this->assertEquals('expense', $reversalTx->type);
        $this->assertEquals($originalTx->id, $reversalTx->original_transaction_id);

        // 6. Reversal transaction cannot be deleted (must fail with 403)
        $deleteReversalResponse = $this->actingAs($bendahara)->deleteJson("/api/v1/admin/finance/{$reversalTxId}");
        $deleteReversalResponse->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_itemized_purchase_recording_and_ledger_integration(): void
    {
        $bendahara = User::factory()->admin(['finance.purchase.create', 'finance.purchase.manage', 'finance.manage'])->create();

        $purchaseResponse = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/purchases', [
            'vendor_name' => 'Toko Material Sentosa',
            'purchase_date' => '2026-10-16',
            'invoice_number' => 'INV-MAT-20261016',
            'purpose' => 'Pengadaan Cat Pos Ronda',
            'items' => [
                [
                    'item_name' => 'Cat Tembok Putih 5kg',
                    'quantity' => 2,
                    'unit' => 'kaleng',
                    'unit_price_idr' => 125000,
                ],
                [
                    'item_name' => 'Kuas Cat 3 Inch',
                    'quantity' => 3,
                    'unit' => 'pcs',
                    'unit_price_idr' => 15000,
                ],
            ],
        ]);

        $purchaseResponse->assertStatus(Response::HTTP_CREATED);
        $purchaseId = $purchaseResponse->json('data.id');

        $purchase = Purchase::with(['items', 'financeTransaction'])->findOrFail($purchaseId);
        $this->assertEquals(2, $purchase->items->count());
        $this->assertEquals(295000, $purchase->total_amount_idr); // (2*125000) + (3*15000) = 250000 + 45000 = 295000
        $this->assertNotNull($purchase->financeTransaction);
        $this->assertEquals('expense', $purchase->financeTransaction->type);
        $this->assertEquals($bendahara->id, $purchase->created_by);
        $this->assertEquals($bendahara->id, $purchase->financeTransaction->created_by);
    }
}
