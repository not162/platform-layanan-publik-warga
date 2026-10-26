<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\User;
use Database\Seeders\LetterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DuesAttachmentsAndRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function wargaWithCitizen(?string $email = null): User
    {
        $warga = User::factory()->warga()->create(['email' => $email]);
        $card = FamilyCard::factory()->create();
        $citizen = Citizen::factory()->create(['user_id' => $warga->id, 'family_card_id' => $card->id, 'nik' => '3171041203900001', 'nik_hash' => hash('sha256', '3171041203900001')]);
        $card->update(['no_kk' => '3171040409950001', 'no_kk_hash' => hash('sha256', '3171040409950001')]);
        $warga->update(['warga_id' => $citizen->id]);

        return $warga->refresh();
    }

    public function test_reset_link_is_emailed_when_account_has_email_and_not_exposed_in_response(): void
    {
        Mail::fake();
        $warga = $this->wargaWithCitizen('budi@example.com');

        $res = $this->postJson('/api/v1/auth/forgot-password', ['username' => $warga->username, 'nik' => '3171041203900001', 'no_kk' => '3171040409950001'])
            ->assertOk()->assertJsonPath('emailed', true);

        $this->assertArrayNotHasKey('reset_url', $res->json());
        $this->assertStringContainsString('b***@example.com', $res->json('message'));
        Mail::assertSent(ResetPasswordMail::class, fn ($m) => $m->hasTo('budi@example.com') && str_contains($m->url, '/reset-password/'));
    }

    public function test_reset_link_falls_back_to_screen_when_account_has_no_email(): void
    {
        Mail::fake();
        $warga = $this->wargaWithCitizen();

        $this->postJson('/api/v1/auth/forgot-password', ['username' => $warga->username, 'nik' => '3171041203900001', 'no_kk' => '3171040409950001'])
            ->assertOk()->assertJsonPath('emailed', false)->assertJsonStructure(['reset_url']);
        Mail::assertNothingSent();
    }

    public function test_account_is_locked_temporarily_after_five_failed_logins(): void
    {
        User::factory()->warga()->create(['username' => 'dikunci', 'password' => 'password123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['login' => 'dikunci', 'password' => 'salah'])->assertSessionHasErrors('login');
        }

        $this->post('/login', ['login' => 'dikunci', 'password' => 'password123'])
            ->assertSessionHasErrors(['login' => 'Terlalu banyak percobaan gagal. Coba lagi dalam 300 detik.']);
    }

    public function test_bendahara_generates_dues_records_payments_and_warga_sees_only_own(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $warga = $this->wargaWithCitizen();
        $other = User::factory()->warga()->create();
        Citizen::factory()->create(['user_id' => $other->id]);

        $this->actingAs($warga)->postJson('/api/v1/manage/dues', ['period_year' => 2026, 'period_month' => 10, 'amount_due_idr' => 50000])->assertForbidden();

        $this->actingAs($bendahara)->postJson('/api/v1/manage/dues', ['period_year' => 2026, 'period_month' => 10, 'amount_due_idr' => 50000])->assertCreated();
        $this->actingAs($bendahara)->postJson('/api/v1/manage/dues', ['period_year' => 2026, 'period_month' => 10, 'amount_due_idr' => 50000])->assertStatus(409);
        $this->assertDatabaseCount('resident_dues', 2);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $warga->id]);

        $mine = $this->actingAs($warga)->getJson('/api/v1/manage/dues')->assertOk()->assertJsonCount(1, 'data');
        $id = $mine->json('data.0.id');
        $this->assertSame(50000, $mine->json('data.0.remaining'));

        $this->actingAs($warga)->postJson("/api/v1/manage/dues/{$id}/pay", ['amount' => 50000])->assertForbidden();
        $this->actingAs($bendahara)->postJson("/api/v1/manage/dues/{$id}/pay", ['amount' => 60000])->assertStatus(422);
        $this->actingAs($bendahara)->postJson("/api/v1/manage/dues/{$id}/pay", ['amount' => 20000, 'payment_method' => 'cash'])
            ->assertOk()->assertJsonPath('data.status', 'partial')->assertJsonPath('data.remaining', 30000);
        $this->actingAs($bendahara)->postJson("/api/v1/manage/dues/{$id}/pay", ['amount' => 30000])->assertOk()->assertJsonPath('data.status', 'paid');
        $this->actingAs($bendahara)->postJson("/api/v1/manage/dues/{$id}/pay", ['amount' => 1000])->assertStatus(409);

        $this->actingAs($bendahara)->getJson('/api/v1/manage/dues')->assertJsonCount(2, 'data');
        $this->actingAs($warga)->getJson("/api/v1/manage/dues/{$id}")->assertOk();
        $this->actingAs($other)->getJson("/api/v1/manage/dues/{$id}")->assertNotFound();
    }

    public function test_letter_attachments_are_only_readable_by_owner_and_staff_and_pdf_unlocks_after_approval(): void
    {
        Storage::fake('local');
        $this->seed(LetterSeeder::class);
        $warga = $this->wargaWithCitizen();
        $other = User::factory()->warga()->create();
        $sekretaris = User::factory()->sekretaris()->create();
        $ketua = User::factory()->ketuaRt()->create();

        $id = $this->actingAs($warga)->postJson('/api/v1/manage/letters', ['jenis_surat_id' => 1, 'keperluan' => 'Izin'])->assertCreated()->json('data.id');
        $this->actingAs($warga)->postJson("/api/v1/letters/{$id}/attachments", ['attachment' => UploadedFile::fake()->image('ktp.jpg')])->assertCreated();

        $row = $this->actingAs($warga)->getJson("/api/v1/manage/letters/{$id}")->assertOk();
        $this->assertCount(1, $row->json('data.attachments'));
        $this->assertFalse($row->json('data.can_download'));
        $url = $row->json('data.attachments.0.url');

        $this->actingAs($warga)->get($url)->assertOk();
        $this->actingAs($sekretaris)->get($url)->assertOk();
        $this->actingAs($other)->get($url)->assertForbidden();

        $this->actingAs($warga)->postJson("/api/v1/manage/letters/{$id}/submit", ['version' => 1])->assertOk();
        $this->actingAs($sekretaris)->postJson("/api/v1/manage/letters/{$id}/verify", ['version' => 2])->assertOk();
        $this->actingAs($ketua)->postJson("/api/v1/manage/letters/{$id}/approve", ['version' => 3])->assertOk();

        $done = $this->actingAs($warga)->getJson("/api/v1/manage/letters/{$id}")->json('data');
        $this->assertTrue($done['can_download']);
        $this->assertStringContainsString('format=pdf', $done['download_url']);
        $this->actingAs($warga)->get($done['download_url'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_complaint_photo_upload_and_restricted_access(): void
    {
        Storage::fake('local');
        $warga = User::factory()->warga()->create();
        $other = User::factory()->warga()->create();
        $ketua = User::factory()->ketuaRt()->create();

        $res = $this->actingAs($warga)->post('/api/v1/manage/complaints', [
            'title' => 'Lampu mati', 'kategori' => 'Fasilitas Umum', 'description' => 'Gelap', 'attachment' => UploadedFile::fake()->image('lampu.jpg'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $url = $res->json('data.photo_url');
        $this->assertNotNull($url);
        $this->actingAs($warga)->get($url)->assertOk();
        $this->actingAs($ketua)->get($url)->assertOk();
        $this->actingAs($other)->get($url)->assertForbidden();

        $this->actingAs($warga)->post('/api/v1/manage/complaints', [
            'title' => 'x', 'kategori' => 'y', 'description' => 'z', 'attachment' => UploadedFile::fake()->create('virus.exe', 10),
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_ktp_file_is_visible_to_citizen_managers_only(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('citizens/ktp/a.jpg', 'x');
        $citizen = Citizen::factory()->create(['ktp_file_path' => 'citizens/ktp/a.jpg']);
        $sekretaris = User::factory()->sekretaris()->create();
        $ketua = User::factory()->ketuaRt()->create();

        $this->actingAs($sekretaris)->get("/api/v1/files/ktp/{$citizen->id}")->assertOk();
        $this->actingAs($ketua)->get("/api/v1/files/ktp/{$citizen->id}")->assertForbidden();
        $this->actingAs($sekretaris)->getJson('/api/v1/manage/citizens')->assertJsonPath('data.0.ktp_url', url("/api/v1/files/ktp/{$citizen->id}"));
    }
}
