<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\DuePayment;
use App\Models\FamilyCard;
use App\Models\FinanceTransaction;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\ResidentDue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint5DocumentationAndIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_relational_integrity_across_all_sprint_modules(): void
    {
        // 1. Account & Family Card & Citizen
        $user = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();
        $citizen = Citizen::factory()->create([
            'user_id' => $user->id,
            'family_card_id' => $familyCard->id,
        ]);

        $this->assertEquals($citizen->id, $user->fresh()->citizen?->id);
        $this->assertEquals($familyCard->id, $citizen->fresh()->familyCard?->id);
        $this->assertTrue($familyCard->fresh()->citizens->contains($citizen));

        // 2. Letters & LetterType
        $letterType = LetterType::create([
            'kode_surat' => 'SK-DOM-01',
            'nama_surat' => 'Surat Domisili',
            'template_key' => 'domisili',
            'is_active' => true,
        ]);
        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'jenis_surat_id' => $letterType->id,
            'type' => 'Surat Domisili',
            'status' => 'draft',
            'ticket_number' => 'SRT-INTEGRITY-01',
            'keperluan' => 'Pendaftaran Sekolah',
        ]);
        $this->assertTrue($citizen->fresh()->letters->contains($letter));
        $this->assertEquals($letterType->id, $letter->fresh()->letterType?->id);

        // 3. Complaints
        $complaint = Complaint::create([
            'citizen_id' => $citizen->id,
            'user_id' => $user->id,
            'ticket_number' => 'ADU-INTEGRITY-01',
            'title' => 'Lampu Taman Padam',
            'description' => 'Perlu penggantian bohlam di area taman bermain.',
            'status' => 'submitted',
        ]);
        $this->assertTrue($citizen->fresh()->complaints->contains($complaint));
        $this->assertTrue($user->fresh()->complaints->contains($complaint));

        // 4. Resident Due, Due Payment, and Finance Transaction linkage
        $due = ResidentDue::create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 75000,
            'due_date' => '2026-10-30',
            'status' => 'UNPAID',
        ]);

        $tx = FinanceTransaction::create([
            'transaction_number' => 'TX-INTEGRITY-01',
            'source' => 'Iuran Kas',
            'amount_idr' => 75000,
            'created_by' => $user->id,
            'type' => 'income',
            'category' => 'Iuran',
            'amount' => 75000,
            'description' => 'Pembayaran iuran kas warga',
            'transaction_date' => now(),
            'status' => 'published',
        ]);

        $payment = DuePayment::create([
            'resident_due_id' => $due->id,
            'finance_transaction_id' => $tx->id,
            'amount_paid_idr' => 75000,
            'paid_at' => now(),
            'payment_method' => 'qris',
            'status' => 'PAID',
        ]);

        $this->assertTrue($citizen->fresh()->dues->contains($due));
        $this->assertEquals($tx->id, $payment->fresh()->financeTransaction?->id);
        $this->assertEquals($due->id, $payment->fresh()->due?->id);
    }
}
