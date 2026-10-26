<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Citizen;
use App\Models\FinanceTransaction;
use App\Models\User;
use Database\Seeders\LetterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageApiCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function wargaWithCitizen(): User
    {
        $warga = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $warga->id]);
        $warga->update(['warga_id' => $citizen->id]);

        return $warga->refresh();
    }

    public function test_announcement_full_crud_persists_to_database_and_notifies_everyone(): void
    {
        $sekretaris = User::factory()->sekretaris()->create();
        $warga = User::factory()->warga()->create();

        $created = $this->actingAs($sekretaris)->postJson('/api/v1/manage/announcements', [
            'title' => 'Kerja Bakti', 'category' => 'kegiatan', 'content' => 'Minggu pagi', 'is_published' => true, 'is_pinned' => true,
        ])->assertCreated()->assertJsonPath('data.title', 'Kerja Bakti');

        $id = $created->json('data.id');
        $this->assertDatabaseHas('announcements', ['id' => $id, 'title' => 'Kerja Bakti', 'is_published' => 1]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $warga->id]);

        $this->actingAs($warga)->getJson('/api/v1/manage/announcements')->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($sekretaris)->patchJson("/api/v1/manage/announcements/{$id}", [
            'title' => 'Kerja Bakti Besar', 'category' => 'kegiatan', 'content' => 'Minggu pagi', 'version' => 1,
        ])->assertOk()->assertJsonPath('data.title', 'Kerja Bakti Besar');
        $this->assertDatabaseHas('announcements', ['id' => $id, 'title' => 'Kerja Bakti Besar', 'version' => 2]);

        $this->actingAs($sekretaris)->deleteJson("/api/v1/manage/announcements/{$id}")->assertOk();
        $this->assertDatabaseMissing('announcements', ['id' => $id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'announcements.deleted']);
    }

    public function test_stale_version_returns_409_conflict(): void
    {
        $sekretaris = User::factory()->sekretaris()->create();
        $announcement = Announcement::create(['title' => 'A', 'category' => 'info', 'content' => 'x', 'is_published' => false, 'version' => 3]);

        $this->actingAs($sekretaris)->patchJson("/api/v1/manage/announcements/{$announcement->id}", [
            'title' => 'B', 'category' => 'info', 'content' => 'x', 'version' => 1,
        ])->assertStatus(409);
    }

    public function test_warga_cannot_write_content_and_unpublished_content_is_hidden(): void
    {
        $warga = User::factory()->warga()->create();
        Announcement::create(['title' => 'Draft', 'category' => 'info', 'content' => 'x', 'is_published' => false, 'version' => 1]);

        $this->actingAs($warga)->postJson('/api/v1/manage/announcements', ['title' => 'x', 'category' => 'info', 'content' => 'y'])->assertForbidden();
        $this->actingAs($warga)->getJson('/api/v1/manage/announcements')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($warga)->getJson('/api/v1/manage/unknown')->assertNotFound();
    }

    public function test_letter_workflow_runs_warga_to_sekretaris_to_ketua_with_notifications(): void
    {
        $this->seed(LetterSeeder::class);
        $warga = $this->wargaWithCitizen();
        $other = $this->wargaWithCitizen();
        $sekretaris = User::factory()->sekretaris()->create();
        $ketua = User::factory()->ketuaRt()->create();

        $res = $this->actingAs($warga)->postJson('/api/v1/manage/letters', ['jenis_surat_id' => 1, 'keperluan' => 'Izin usaha', 'submit' => true])
            ->assertCreated()->assertJsonPath('data.status', 'submitted');
        $id = $res->json('data.id');
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $sekretaris->id]);

        // warga lain tidak boleh melihat / mengubah surat ini
        $this->actingAs($other)->getJson('/api/v1/manage/letters')->assertJsonCount(0, 'data');
        $this->actingAs($other)->getJson("/api/v1/manage/letters/{$id}")->assertNotFound();

        // ketua belum bisa mengesahkan sebelum diverifikasi, warga tidak bisa memverifikasi
        $this->actingAs($warga)->postJson("/api/v1/manage/letters/{$id}/verify", ['version' => 2])->assertForbidden();
        $this->actingAs($ketua)->postJson("/api/v1/manage/letters/{$id}/approve", ['version' => 2])->assertStatus(409);

        $this->actingAs($sekretaris)->postJson("/api/v1/manage/letters/{$id}/verify", ['version' => 2])->assertOk()->assertJsonPath('data.status', 'verified');
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $ketua->id]);

        $this->actingAs($ketua)->postJson("/api/v1/manage/letters/{$id}/approve", ['version' => 3])->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertNotNull($this->actingAs($warga)->getJson("/api/v1/manage/letters/{$id}")->json('data.letter_number'));

        $notifications = $this->actingAs($warga)->getJson('/api/v1/notifications')->assertOk();
        $this->assertGreaterThanOrEqual(2, $notifications->json('meta.unread_count'));
    }

    public function test_letter_rejection_requires_reason_and_notifies_applicant(): void
    {
        $this->seed(LetterSeeder::class);
        $warga = $this->wargaWithCitizen();
        $sekretaris = User::factory()->sekretaris()->create();

        $id = $this->actingAs($warga)->postJson('/api/v1/manage/letters', ['jenis_surat_id' => 1, 'keperluan' => 'X', 'submit' => true])->json('data.id');

        $this->actingAs($sekretaris)->postJson("/api/v1/manage/letters/{$id}/reject", ['version' => 2])->assertStatus(422);
        $this->actingAs($sekretaris)->postJson("/api/v1/manage/letters/{$id}/reject", ['version' => 2, 'reason' => 'KTP buram'])
            ->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $warga->id, 'type' => 'App\Notifications\AppNotification']);
    }

    public function test_warga_can_edit_and_delete_only_own_draft_letter(): void
    {
        $this->seed(LetterSeeder::class);
        $warga = $this->wargaWithCitizen();

        $id = $this->actingAs($warga)->postJson('/api/v1/manage/letters', ['jenis_surat_id' => 1, 'keperluan' => 'Awal'])->assertCreated()->json('data.id');
        $this->actingAs($warga)->patchJson("/api/v1/manage/letters/{$id}", ['jenis_surat_id' => 1, 'keperluan' => 'Revisi'])->assertOk()->assertJsonPath('data.keperluan', 'Revisi');
        $this->actingAs($warga)->postJson("/api/v1/manage/letters/{$id}/submit", ['version' => 2])->assertOk();
        $this->actingAs($warga)->deleteJson("/api/v1/manage/letters/{$id}")->assertForbidden();
    }

    public function test_finance_ledger_crud_publish_and_reverse_follow_role_permissions(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $ketua = User::factory()->ketuaRt()->create();
        $warga = User::factory()->warga()->create();

        $payload = ['type' => 'income', 'category' => 'Iuran', 'amount' => 150000, 'transaction_date' => '2026-10-01', 'description' => 'Oktober'];

        $this->actingAs($ketua)->postJson('/api/v1/manage/finance', $payload)->assertForbidden();
        $this->actingAs($warga)->getJson('/api/v1/manage/finance')->assertForbidden();

        $id = $this->actingAs($bendahara)->postJson('/api/v1/manage/finance', $payload)->assertCreated()->assertJsonPath('data.status', 'draft')->json('data.id');
        $this->assertDatabaseHas('finance_transactions', ['id' => $id, 'amount_idr' => 150000, 'status' => 'draft']);

        $this->actingAs($bendahara)->patchJson("/api/v1/manage/finance/{$id}", ['amount' => 175000] + $payload)->assertOk();
        $this->assertDatabaseHas('finance_transactions', ['id' => $id, 'amount_idr' => 175000]);

        $this->actingAs($bendahara)->postJson("/api/v1/manage/finance/{$id}/publish", ['version' => 2])->assertOk()->assertJsonPath('data.status', 'published');
        $this->getJson('/api/v1/portal')->assertJsonPath('data.finance.income', 175000);

        // transaksi terbit tidak dapat diedit atau dihapus — hanya dikoreksi
        $this->actingAs($bendahara)->patchJson("/api/v1/manage/finance/{$id}", $payload)->assertForbidden();
        $this->actingAs($bendahara)->deleteJson("/api/v1/manage/finance/{$id}")->assertForbidden();
        $this->actingAs($bendahara)->postJson("/api/v1/manage/finance/{$id}/reverse", ['version' => 3])->assertStatus(422);
        $this->actingAs($bendahara)->postJson("/api/v1/manage/finance/{$id}/reverse", ['version' => 3, 'reason' => 'Salah input'])->assertOk();

        $this->assertSame(1, FinanceTransaction::query()->where('status', 'draft')->count() + 1);
    }

    public function test_user_management_enforces_role_tiers(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $admin = User::factory()->admin()->create();
        $warga = User::factory()->warga()->create();

        $body = ['name' => 'Pak Bendahara', 'username' => 'pak.bendahara', 'role' => 'BENDAHARA', 'password' => 'password123', 'is_active' => true];

        $this->actingAs($warga)->getJson('/api/v1/manage/users')->assertForbidden();

        $id = $this->actingAs($admin)->postJson('/api/v1/manage/users', $body)->assertCreated()->assertJsonPath('data.tier', 'admin')->json('data.id');
        $this->assertDatabaseHas('users', ['id' => $id, 'username' => 'pak.bendahara', 'role' => 'BENDAHARA']);

        // admin tidak boleh membuat superadmin / admin lain
        $this->actingAs($admin)->postJson('/api/v1/manage/users', ['username' => 'sa.baru', 'role' => 'SUPERADMIN'] + $body)->assertStatus(422);
        $this->actingAs($admin)->postJson('/api/v1/manage/users', ['username' => 'adm.baru', 'role' => 'ADMIN'] + $body)->assertStatus(422);
        $this->actingAs($superadmin)->postJson('/api/v1/manage/users', ['username' => 'adm.baru', 'role' => 'ADMIN'] + $body)->assertCreated();

        // admin tidak boleh mengubah/menghapus superadmin dan diri sendiri
        $this->actingAs($admin)->deleteJson("/api/v1/manage/users/{$superadmin->id}")->assertForbidden();
        $this->actingAs($admin)->patchJson("/api/v1/manage/users/{$superadmin->id}", ['name' => 'x', 'username' => 'superx', 'role' => 'WARGA'])->assertForbidden();
        $this->actingAs($admin)->deleteJson("/api/v1/manage/users/{$admin->id}")->assertForbidden();

        // username unik
        $this->actingAs($admin)->postJson('/api/v1/manage/users', $body)->assertStatus(422)->assertJsonValidationErrors('username');

        // password baru diterapkan
        $this->actingAs($admin)->patchJson("/api/v1/manage/users/{$id}", ['name' => 'Pak Bendahara', 'username' => 'pak.bendahara', 'role' => 'BENDAHARA', 'password' => 'passwordbaru1'])->assertOk();
        $this->post('/login', ['login' => 'pak.bendahara', 'password' => 'passwordbaru1'])->assertRedirect('/dashboard');

        $this->actingAs($admin)->deleteJson("/api/v1/manage/users/{$id}")->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $id]);
    }

    public function test_citizen_crud_and_verification_flow_by_sekretaris(): void
    {
        $sekretaris = User::factory()->sekretaris()->create();
        $payload = ['nik' => '3171041203900001', 'no_kk' => '3171040409950001', 'full_name' => 'Budi Santoso', 'gender' => 'Laki-laki'];

        $id = $this->actingAs($sekretaris)->postJson('/api/v1/manage/citizens', $payload + ['status_warga' => 'pending_verification'])
            ->assertCreated()->assertJsonPath('data.nik_masked', '3171********0001')->json('data.id');

        $this->actingAs($sekretaris)->postJson('/api/v1/manage/citizens', $payload)->assertStatus(422)->assertJsonValidationErrors('nik');
        $this->actingAs($sekretaris)->postJson("/api/v1/manage/citizens/{$id}/verify")->assertOk()->assertJsonPath('data.status_warga', 'tetap');
        $this->actingAs($sekretaris)->getJson('/api/v1/manage/citizens?q=Budi')->assertJsonCount(1, 'data');
        $this->actingAs($sekretaris)->deleteJson("/api/v1/manage/citizens/{$id}")->assertOk();
    }

    public function test_notifications_can_be_listed_marked_read_and_deleted(): void
    {
        $sekretaris = User::factory()->sekretaris()->create();
        $warga = User::factory()->warga()->create();

        $this->actingAs($sekretaris)->postJson('/api/v1/manage/announcements', ['title' => 'A', 'category' => 'info', 'content' => 'x', 'is_published' => true]);
        $this->actingAs($sekretaris)->postJson('/api/v1/manage/announcements', ['title' => 'B', 'category' => 'info', 'content' => 'x', 'is_published' => true]);

        $list = $this->actingAs($warga)->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('meta.unread_count', 2);
        $first = $list->json('data.0.id');

        $this->actingAs($warga)->postJson("/api/v1/notifications/{$first}/read")->assertOk();
        $this->actingAs($warga)->getJson('/api/v1/notifications')->assertJsonPath('meta.unread_count', 1);

        $this->actingAs($warga)->postJson('/api/v1/notifications/read-all')->assertOk();
        $this->actingAs($warga)->getJson('/api/v1/notifications')->assertJsonPath('meta.unread_count', 0);

        $this->actingAs($warga)->deleteJson("/api/v1/notifications/{$first}")->assertOk();
        $this->actingAs($warga)->getJson('/api/v1/notifications')->assertJsonCount(1, 'data');
    }

    public function test_dashboard_summary_is_role_aware(): void
    {
        $this->actingAs(User::factory()->bendahara()->create())->getJson('/api/v1/dashboard/summary')
            ->assertOk()->assertJsonFragment(['label' => 'Saldo kas']);

        $this->actingAs(User::factory()->warga()->create())->getJson('/api/v1/dashboard/summary')
            ->assertOk()->assertJsonFragment(['label' => 'Surat saya'])->assertJsonMissing(['label' => 'Saldo kas']);

        $this->actingAs(User::factory()->admin()->create())->getJson('/api/v1/dashboard/summary')
            ->assertOk()->assertJsonFragment(['label' => 'Pengguna aktif']);
    }

    public function test_public_portal_endpoint_is_real_time_without_authentication(): void
    {
        $this->getJson('/api/v1/portal')->assertOk()->assertJsonStructure(['data' => ['announcements', 'events', 'finance' => ['income', 'expense', 'balance', 'recent'], 'officers', 'contacts', 'schedules', 'letter_types']]);

        Announcement::create(['title' => 'Baru', 'category' => 'info', 'content' => 'x', 'is_published' => true, 'published_at' => now(), 'version' => 1]);
        $this->getJson('/api/v1/portal')->assertJsonPath('data.announcements.0.title', 'Baru');
    }
}
