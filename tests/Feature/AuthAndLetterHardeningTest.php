<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\User;
use Database\Seeders\LetterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthAndLetterHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function wargaWithCitizen(): User
    {
        $warga = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $warga->id]);
        $warga->update(['warga_id' => $citizen->id]);

        return $warga->refresh();
    }

    protected function letterFor(User $warga, string $status = 'draft'): Letter
    {
        return Letter::create([
            'citizen_id' => $warga->citizen->id,
            'type' => 'Surat Keterangan Domisili',
            'ticket_number' => 'SRT-T-'.uniqid(),
            'status' => $status,
            'version' => 1,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Surat: kepemilikan draf & gerbang status dokumen                    */
    /* ------------------------------------------------------------------ */

    public function test_non_owner_staff_cannot_touch_someone_elses_draft_letter(): void
    {
        Storage::fake('local');
        $warga = $this->wargaWithCitizen();

        foreach (['bendahara', 'petugasKeamanan', 'sekretaris', 'ketuaRt', 'admin'] as $state) {
            $staff = User::factory()->{$state}()->create();
            $letter = $this->letterFor($warga);
            $attachment = $letter->attachments()->create(['judul_lampiran' => 'ktp.pdf', 'file_path' => 'x/ktp.pdf', 'mime_type' => 'application/pdf', 'file_size' => 10]);

            $this->actingAs($staff)->deleteJson("/api/v1/letters/{$letter->id}")->assertForbidden();
            $this->actingAs($staff)->postJson("/api/v1/letters/{$letter->id}/submit", ['version' => 1])->assertForbidden();
            $this->actingAs($staff)->post("/api/v1/letters/{$letter->id}/attachments", ['attachment' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertForbidden();
            $this->actingAs($staff)->deleteJson("/api/v1/letters/{$letter->id}/attachments/{$attachment->id}")->assertForbidden();

            $this->assertDatabaseHas('letters', ['id' => $letter->id, 'status' => 'draft']);
            $this->assertDatabaseHas('letter_attachments', ['id' => $attachment->id]);
        }

        // Pemilik & Superadmin sistem tetap boleh.
        $own = $this->letterFor($warga);
        $this->actingAs($warga)->postJson("/api/v1/letters/{$own->id}/submit", ['version' => 1])->assertOk();
        $this->actingAs(User::factory()->superadmin()->create())->deleteJson('/api/v1/letters/'.$this->letterFor($warga)->id)->assertOk();
    }

    public function test_applicant_cannot_obtain_unapproved_document_by_navigation_or_preview(): void
    {
        Storage::fake('local');
        $warga = $this->wargaWithCitizen();
        $sekretaris = User::factory()->sekretaris()->create();

        foreach (['draft', 'submitted', 'verified', 'rejected'] as $status) {
            $letter = $this->letterFor($warga, $status);

            $this->actingAs($warga)->get("/api/v1/letters/{$letter->id}/download")->assertStatus(422)->assertJsonPath('status', $status);
            $this->actingAs($warga)->get("/api/v1/letters/{$letter->id}/preview")->assertStatus(422)->assertJsonPath('status', $status);
            $this->actingAs($warga)->getJson("/api/v1/letters/{$letter->id}/export-payload")->assertStatus(409);
        }

        // Pengurus surat tetap dapat meninjau berkas yang sedang diproses.
        $this->actingAs($sekretaris)->get('/api/v1/letters/'.$this->letterFor($warga, 'submitted')->id.'/preview')->assertOk();
    }

    public function test_patch_letter_without_status_is_a_validation_error_not_a_crash(): void
    {
        $warga = $this->wargaWithCitizen();
        $letter = $this->letterFor($warga);

        $this->actingAs($warga)->patchJson("/api/v1/letters/{$letter->id}", ['version' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('status');
    }

    /* ------------------------------------------------------------------ */
    /* Surat: penolakan per tahap & notifikasi pengesahan                  */
    /* ------------------------------------------------------------------ */

    public function test_rejection_follows_the_workflow_stage(): void
    {
        $warga = $this->wargaWithCitizen();
        $sekretaris = User::factory()->sekretaris()->create();
        $ketua = User::factory()->ketuaRt()->create();
        $reason = ['version' => 1, 'rejection_reason' => 'Berkas belum lengkap'];

        // Diajukan: Ketua belum boleh menolak (melompati verifikasi Sekretaris).
        $submitted = $this->letterFor($warga, 'submitted');
        $this->actingAs($ketua)->postJson("/api/v1/admin/letters/{$submitted->id}/reject", $reason)->assertForbidden();
        $this->actingAs($ketua)->postJson("/api/v1/manage/letters/{$submitted->id}/reject", ['version' => 1, 'reason' => 'Berkas belum lengkap'])->assertForbidden();
        $this->actingAs($sekretaris)->postJson("/api/v1/admin/letters/{$submitted->id}/reject", $reason)->assertOk()->assertJsonPath('data.status', 'rejected');

        // Terverifikasi: Sekretaris tidak boleh menolak keputusan yang menjadi wewenang Ketua.
        $verified = $this->letterFor($warga, 'verified');
        $this->actingAs($sekretaris)->postJson("/api/v1/admin/letters/{$verified->id}/reject", $reason)->assertForbidden();
        $this->actingAs($ketua)->postJson("/api/v1/admin/letters/{$verified->id}/reject", $reason)->assertOk()->assertJsonPath('data.status', 'rejected');

        // Superadmin sistem tetap bypass di kedua tahap.
        $super = User::factory()->superadmin()->create();
        foreach (['submitted', 'verified'] as $status) {
            $this->actingAs($super)->postJson('/api/v1/admin/letters/'.$this->letterFor($warga, $status)->id.'/reject', $reason)->assertOk();
        }
    }

    public function test_verify_notes_are_validated_and_approval_notifies_sekretaris(): void
    {
        Storage::fake('local');
        $this->seed(LetterSeeder::class);
        $warga = $this->wargaWithCitizen();
        $sekretaris = User::factory()->sekretaris()->create();
        $ketua = User::factory()->ketuaRt()->create();

        $letter = $this->letterFor($warga, 'submitted');
        $letter->update(['jenis_surat_id' => LetterType::query()->value('id')]);

        $this->actingAs($sekretaris)->postJson("/api/v1/admin/letters/{$letter->id}/verify", ['version' => 1, 'notes' => str_repeat('a', 501)])
            ->assertStatus(422)->assertJsonValidationErrors('notes');
        $this->actingAs($sekretaris)->postJson("/api/v1/manage/letters/{$letter->id}/verify", ['version' => 1, 'notes' => str_repeat('a', 501)])
            ->assertStatus(422);

        $this->actingAs($sekretaris)->postJson("/api/v1/admin/letters/{$letter->id}/verify", ['version' => 1, 'notes' => 'Berkas lengkap'])->assertOk();
        $this->actingAs($ketua)->postJson("/api/v1/admin/letters/{$letter->id}/approve", ['version' => 2])->assertOk();

        $titles = $sekretaris->notifications()->get()->pluck('data.title');
        $this->assertContains('Surat siap diselesaikan', $titles);
    }

    /* ------------------------------------------------------------------ */
    /* Akun nonaktif & pencabutan sesi                                     */
    /* ------------------------------------------------------------------ */

    public function test_inactive_user_session_and_tokens_are_rejected_and_revoked(): void
    {
        $user = $this->wargaWithCitizen();
        $plain = $user->createToken('device')->plainTextToken;

        $this->withToken($plain)->getJson('/api/v1/me')->assertOk();
        $this->app['auth']->forgetGuards();

        $user->update(['is_active' => false]);

        $this->withToken($plain)->getJson('/api/v1/me')->assertForbidden();
        $this->assertSame(0, $user->tokens()->count());
        $this->app['auth']->forgetGuards();

        $this->actingAs($user->refresh())->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest('web');

        $this->actingAs($user)->postJson('/api/v1/letters', ['keperluan' => 'x'])->assertForbidden();
    }

    public function test_password_change_revokes_tokens_and_other_sessions_but_keeps_current_one(): void
    {
        $user = User::factory()->warga()->create();
        $user->createToken('ponsel');
        $user->createToken('tablet');
        $oldFingerprint = Auth::guard('web')->hashPasswordForCookie($user->getAuthPassword());
        $otherDevice = ['web_session_password' => ['id' => $user->id, 'hash' => $oldFingerprint]];

        // Sesi web saat ini (SPA, stateful) mengganti password.
        $this->actingAs($user)->withSession($otherDevice)->withHeader('Referer', config('app.url'))
            ->postJson('/api/v1/me/password', ['current_password' => 'password', 'password' => 'passwordBaru99', 'password_confirmation' => 'passwordBaru99'])
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());
        $this->actingAs($user)->get('/dashboard')->assertOk();

        // Perangkat lain yang masih memegang sidik password lama dikeluarkan.
        $this->actingAs($user->refresh())->withSession($otherDevice)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest('web');
    }

    public function test_password_reset_revokes_all_tokens(): void
    {
        $user = User::factory()->warga()->create();
        $user->createToken('lama');
        DB::table('password_reset_requests')->insert([
            'user_id' => $user->id, 'token_hash' => hash('sha256', 'tok-123'), 'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/reset-password', ['token' => 'tok-123', 'password' => 'passwordBaru99', 'password_confirmation' => 'passwordBaru99'])->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    /* ------------------------------------------------------------------ */
    /* Verifikasi warga lewat endpoint admin (satu jalur di CitizenService) */
    /* ------------------------------------------------------------------ */

    protected function pendingCitizen(): Citizen
    {
        $user = User::factory()->warga()->create();

        return Citizen::factory()->create([
            'user_id' => $user->id,
            'family_card_id' => FamilyCard::factory()->create()->id,
            'status_warga' => 'pending_verification',
            'version' => 1,
        ]);
    }

    public function test_admin_citizen_verify_and_reject_share_the_service_rules(): void
    {
        $ketua = User::factory()->ketuaRt()->create();
        $sekretaris = User::factory()->sekretaris()->create();

        // Ketua RT (citizen.manage) kini benar-benar dapat memverifikasi.
        $pending = $this->pendingCitizen();
        $pending->user->update(['is_active' => false]);
        $this->actingAs($ketua)->postJson("/api/v1/admin/citizens/{$pending->id}/verify", ['notes' => 'KTP cocok'])->assertOk();
        $pending->refresh();
        $this->assertSame('tetap', $pending->status_warga);
        $this->assertSame(2, (int) $pending->version);
        $this->assertTrue($pending->user->is_active);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $pending->user_id]);

        // Tidak bisa diverifikasi/ditolak dua kali.
        $this->actingAs($ketua)->postJson("/api/v1/admin/citizens/{$pending->id}/verify")->assertStatus(409);
        $this->actingAs($ketua)->postJson("/api/v1/admin/citizens/{$pending->id}/reject", ['reason' => 'Bukan warga'])->assertStatus(409);

        // Tolak: status 'ditolak', akun dinonaktifkan, token dicabut.
        $rejected = $this->pendingCitizen();
        $rejected->user->createToken('ponsel');
        $this->actingAs($sekretaris)->postJson("/api/v1/admin/citizens/{$rejected->id}/reject", ['reason' => 'Tidak berdomisili di RT 04'])->assertOk();
        $rejected->refresh();
        $this->assertSame('ditolak', $rejected->status_warga);
        $this->assertFalse($rejected->is_active);
        $this->assertSame(2, (int) $rejected->version);
        $this->assertFalse($rejected->user->is_active);
        $this->assertSame(0, $rejected->user->tokens()->count());
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $rejected->user_id]);

        // Tanpa citizen.manage: ditolak oleh izin, bukan oleh nama peran.
        $this->actingAs(User::factory()->bendahara()->create())->postJson('/api/v1/admin/citizens/'.$this->pendingCitizen()->id.'/verify')->assertForbidden();
    }

    public function test_citizen_read_permission_can_list_admin_citizens(): void
    {
        Citizen::factory()->create();

        $this->actingAs(User::factory()->petugasKeamanan()->create())->getJson('/api/v1/admin/citizens')->assertOk();
    }

    /* ------------------------------------------------------------------ */
    /* Lain-lain: jenis surat, push, rute                                  */
    /* ------------------------------------------------------------------ */

    public function test_letter_type_update_cannot_null_required_columns(): void
    {
        $this->seed(LetterSeeder::class);
        $type = LetterType::query()->firstOrFail();
        $sekretaris = User::factory()->sekretaris()->create();

        $this->actingAs($sekretaris)->patchJson("/api/v1/admin/letter-types/{$type->id}", ['nama_surat' => null])
            ->assertStatus(422)->assertJsonValidationErrors('nama_surat');
        $this->actingAs($sekretaris)->patchJson("/api/v1/admin/letter-types/{$type->id}", ['estimated_process_hours' => 12])->assertOk();
    }

    public function test_push_subscription_requires_login_and_binds_the_user(): void
    {
        $payload = ['endpoint' => 'https://push.example.test/abc', 'public_key' => 'pk', 'auth_token' => 'at'];

        $this->postJson('/api/v1/push/subscribe', $payload)->assertUnauthorized();

        $user = User::factory()->warga()->create();
        $this->actingAs($user)->postJson('/api/v1/push/subscribe', $payload)->assertCreated();
        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $user->id]);
    }
}
