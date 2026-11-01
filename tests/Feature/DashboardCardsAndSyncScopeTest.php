<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\DuePayment;
use App\Models\FinanceTransaction;
use App\Models\FinancialReport;
use App\Models\Letter;
use App\Models\ResidentDue;
use App\Models\SecurityReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Kartu dashboard per peran, daftar "Aktivitas terbaru" sebagai antrean kerja, dan cakupan /sync per izin.
 */
class DashboardCardsAndSyncScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return Collection<string, int>
     */
    protected function cards(User $user): Collection
    {
        return collect($this->actingAs($user)->getJson('/api/v1/dashboard/summary')->assertOk()->json('data.cards'))
            ->pluck('value', 'label');
    }

    protected function letter(Citizen $citizen, string $status, string $ticket): Letter
    {
        return Letter::query()->create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Keterangan Domisili',
            'ticket_number' => $ticket,
            'status' => $status,
            'version' => 1,
        ]);
    }

    public function test_ketua_rt_sees_approval_queues_registrants_and_active_users(): void
    {
        $ketua = User::factory()->ketuaRt()->create();
        $citizen = Citizen::factory()->create();
        $this->letter($citizen, 'verified', 'SRT-K-1');
        FinancialReport::factory()->create(['status' => 'draft']);
        Citizen::factory()->create(['status_warga' => 'pending_verification']);

        $cards = $this->cards($ketua);

        $this->assertSame(1, $cards['Menunggu pengesahan']);
        $this->assertSame(1, $cards['Laporan triwulan menunggu pengesahan']);
        $this->assertSame(1, $cards['Pendaftar menunggu verifikasi']);
        $this->assertGreaterThanOrEqual(1, $cards['Pengguna aktif']);
        $this->assertArrayNotHasKey('Perlu verifikasi', $cards->all());
    }

    public function test_sekretaris_sees_completion_card_and_own_work_queue_as_recent(): void
    {
        $sekretaris = User::factory()->sekretaris()->create();
        $citizen = Citizen::factory()->create(['full_name' => 'Dewi Pemohon']);
        $this->letter($citizen, 'approved', 'SRT-S-1');
        $this->letter($citizen, 'submitted', 'SRT-S-2');
        Citizen::factory()->create(['full_name' => 'Eko Pendaftar', 'status_warga' => 'pending_verification']);

        $res = $this->actingAs($sekretaris)->getJson('/api/v1/dashboard/summary')->assertOk();
        $cards = collect($res->json('data.cards'))->pluck('value', 'label');

        $this->assertSame(1, $cards['Siap diselesaikan']);
        $this->assertSame(1, $cards['Perlu verifikasi']);

        $recent = collect($res->json('data.recent'));
        $this->assertNotEmpty($recent);
        $this->assertTrue($recent->contains(fn ($r) => str_contains($r['meta'], 'SRT-S-1') && $r['status'] === 'approved'));
        $this->assertTrue($recent->contains(fn ($r) => $r['title'] === 'Eko Pendaftar' && $r['status'] === 'pending_verification'));
        $this->assertArrayHasKey('href', $recent->first());
    }

    public function test_bendahara_sees_dues_cards_and_recent_ledger_entries(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        ResidentDue::factory()->create(['period_year' => now()->year, 'period_month' => now()->month, 'status' => 'UNPAID', 'due_date' => now()->addDays(5)->toDateString()]);
        $paid = ResidentDue::factory()->create(['period_year' => now()->year, 'period_month' => now()->month, 'status' => 'PAID']);
        DuePayment::factory()->create(['resident_due_id' => $paid->id, 'amount_paid_idr' => 40000, 'status' => 'PAID', 'paid_at' => now()]);
        ResidentDue::factory()->create(['period_year' => 2025, 'period_month' => 1, 'status' => 'UNPAID', 'due_date' => '2025-01-10']);
        FinanceTransaction::factory()->create(['status' => 'draft', 'category' => 'Konsumsi rapat']);

        $res = $this->actingAs($bendahara)->getJson('/api/v1/dashboard/summary')->assertOk();
        $cards = collect($res->json('data.cards'))->pluck('value', 'label');

        $this->assertSame(1, $cards['Tagihan belum lunas']);
        $this->assertSame(1, $cards['Tagihan terlambat']);
        $this->assertSame(40000, $cards['Iuran terkumpul bulan ini']);
        $this->assertSame(1, $cards['Transaksi draft']);
        $this->assertTrue(collect($res->json('data.recent'))->contains(fn ($r) => str_contains($r['title'], 'Konsumsi rapat')));
        // Bendahara tidak memegang izin surat/warga, jadi blok ringkasan itu tidak dikirim.
        $this->assertNull($res->json('data.letters'));
        $this->assertNull($res->json('data.citizens'));
    }

    public function test_petugas_keamanan_recent_lists_active_security_reports(): void
    {
        $petugas = User::factory()->petugasKeamanan()->create();
        SecurityReport::query()->create([
            'ticket_number' => 'KAM-1', 'category' => 'pencurian', 'severity' => 'high', 'title' => 'Motor hilang',
            'description' => 'Di depan pos', 'location' => 'Gg. Bugis', 'incident_at' => now(), 'status' => 'submitted',
        ]);

        $recent = collect($this->actingAs($petugas)->getJson('/api/v1/dashboard/summary')->json('data.recent'));

        $this->assertSame('Motor hilang', $recent->first()['title'] ?? null);
    }

    public function test_warga_dashboard_shows_outstanding_dues(): void
    {
        $warga = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $warga->id]);
        $due = ResidentDue::factory()->create(['citizen_id' => $citizen->id, 'amount_due_idr' => 50000, 'status' => 'PARTIAL']);
        DuePayment::factory()->create(['resident_due_id' => $due->id, 'amount_paid_idr' => 20000, 'status' => 'PAID']);

        $cards = $this->cards($warga);

        $this->assertSame(30000, $cards['Tunggakan iuran']);
        $this->assertSame(0, $cards['Surat saya']);
    }

    public function test_sync_scopes_letters_complaints_and_dues_by_permission(): void
    {
        $other = Citizen::factory()->create();
        $this->letter($other, 'submitted', 'SRT-X-1');
        Complaint::query()->create(['ticket_number' => 'ADU-X-1', 'title' => 'Got mampet', 'description' => 'x', 'status' => 'submitted', 'version' => 1]);
        ResidentDue::factory()->create(['citizen_id' => $other->id]);
        $since = '?since='.urlencode(now()->subHour()->toIso8601String());

        $bendahara = User::factory()->bendahara()->create();
        $sync = $this->actingAs($bendahara)->getJson('/api/v1/sync'.$since)->assertOk();
        $this->assertCount(0, $sync->json('data.letters'));
        $this->assertCount(0, $sync->json('data.complaints'));
        $this->assertCount(1, $sync->json('data.dues'));

        $petugas = User::factory()->petugasKeamanan()->create();
        $sync = $this->actingAs($petugas)->getJson('/api/v1/sync'.$since)->assertOk();
        $this->assertCount(0, $sync->json('data.letters'));
        $this->assertCount(0, $sync->json('data.complaints'));
        $this->assertCount(0, $sync->json('data.dues'));

        $sekretaris = User::factory()->sekretaris()->create();
        $sync = $this->actingAs($sekretaris)->getJson('/api/v1/sync'.$since)->assertOk();
        $this->assertCount(1, $sync->json('data.letters'));
        $this->assertCount(1, $sync->json('data.complaints'));
    }
}
