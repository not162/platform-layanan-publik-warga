<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthSelfRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, string>  $override
     * @return array<string, string>
     */
    protected function payload(array $override = []): array
    {
        return array_merge([
            'nik' => '3171041203900001',
            'no_kk' => '3171040409950001',
            'name' => 'Budi Santoso',
            'username' => 'budi.santoso',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $override);
    }

    protected function resetToken(array $identity): string
    {
        $res = $this->postJson('/api/v1/auth/forgot-password', $identity)->assertOk();

        return basename((string) parse_url($res->json('reset_url'), PHP_URL_PATH));
    }

    public function test_new_resident_registers_as_pending_with_family_card_and_username(): void
    {
        $this->postJson('/api/v1/register', $this->payload())
            ->assertCreated()
            ->assertJsonPath('is_pending_verification', true)
            ->assertJsonPath('user.username', 'budi.santoso')
            ->assertJsonPath('user.tier', 'user');

        $citizen = Citizen::query()->where('nik_hash', hash('sha256', '3171041203900001'))->firstOrFail();
        $this->assertSame('pending_verification', $citizen->status_warga);
        $this->assertSame('3171040409950001', $citizen->familyCard->no_kk);
        $this->assertNotNull($citizen->user_id);
    }

    public function test_same_nik_cannot_register_twice(): void
    {
        $this->postJson('/api/v1/register', $this->payload())->assertCreated();

        $this->postJson('/api/v1/register', $this->payload(['username' => 'budi.dua']))
            ->assertStatus(422)->assertJsonValidationErrors('nik');
    }

    public function test_same_name_and_family_card_cannot_register_twice_even_with_other_nik_and_username(): void
    {
        $this->postJson('/api/v1/register', $this->payload())->assertCreated();

        $this->postJson('/api/v1/register', $this->payload([
            'nik' => '3171041203900099', 'username' => 'budi.lain', 'name' => '  budi   SANTOSO ',
        ]))->assertStatus(422)->assertJsonValidationErrors('name');

        $this->assertSame(1, Citizen::query()->count());
    }

    public function test_same_name_in_different_family_card_is_allowed(): void
    {
        $this->postJson('/api/v1/register', $this->payload())->assertCreated();

        $this->postJson('/api/v1/register', $this->payload([
            'nik' => '3171041203900002', 'no_kk' => '3171040409950002', 'username' => 'budi.kk2',
        ]))->assertCreated();
    }

    public function test_username_must_be_unique_case_insensitively(): void
    {
        $this->postJson('/api/v1/register', $this->payload())->assertCreated();

        $this->postJson('/api/v1/register', $this->payload([
            'nik' => '3171041203900002', 'no_kk' => '3171040409950002', 'username' => 'BUDI.SANTOSO',
        ]))->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_two_residents_may_choose_the_same_password(): void
    {
        $this->postJson('/api/v1/register', $this->payload())->assertCreated();

        $this->postJson('/api/v1/register', $this->payload([
            'nik' => '3171041203900002', 'no_kk' => '3171040409950002', 'name' => 'Ani Wijaya', 'username' => 'ani.wijaya',
        ]))->assertCreated();
    }

    public function test_pre_registered_citizen_activates_account_and_name_is_locked(): void
    {
        $card = FamilyCard::factory()->create(['no_kk' => '3171040409950001', 'no_kk_hash' => hash('sha256', '3171040409950001')]);
        Citizen::factory()->create([
            'family_card_id' => $card->id, 'nik' => '3171041203900001', 'nik_hash' => hash('sha256', '3171041203900001'),
            'full_name' => 'Budi Santoso', 'status_warga' => 'tetap',
        ]);

        $this->postJson('/api/v1/register', $this->payload(['name' => 'Nama Lain']))
            ->assertStatus(422)->assertJsonValidationErrors('name');

        $this->postJson('/api/v1/register', $this->payload())
            ->assertCreated()->assertJsonPath('is_pending_verification', false);

        $this->assertSame(1, Citizen::query()->count());
    }

    public function test_pre_registered_citizen_requires_matching_family_card(): void
    {
        $card = FamilyCard::factory()->create(['no_kk' => '3171040409950001', 'no_kk_hash' => hash('sha256', '3171040409950001')]);
        Citizen::factory()->create([
            'family_card_id' => $card->id, 'nik' => '3171041203900001', 'nik_hash' => hash('sha256', '3171041203900001'), 'full_name' => 'Budi Santoso',
        ]);

        $this->postJson('/api/v1/register', $this->payload(['no_kk' => '3171040409950777']))
            ->assertStatus(422)->assertJsonValidationErrors('no_kk');
    }

    public function test_database_enforces_unique_name_per_family_card(): void
    {
        $card = FamilyCard::factory()->create();
        Citizen::factory()->create(['family_card_id' => $card->id, 'full_name' => 'Siti Aminah']);

        $this->expectException(QueryException::class);
        DB::table('citizens')->insert([
            'family_card_id' => $card->id, 'nik' => 'x', 'nik_hash' => hash('sha256', 'dup'), 'full_name' => 'siti aminah',
            'full_name_key' => 'siti aminah', 'gender' => 'Perempuan', 'status_warga' => 'tetap', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_check_endpoint_reports_each_field_in_real_time(): void
    {
        $this->postJson('/api/v1/register', $this->payload())->assertCreated();

        $this->postJson('/api/v1/auth/check-citizen', $this->payload())->assertOk()
            ->assertJsonPath('status', 'ALREADY_REGISTERED')
            ->assertJsonPath('checks.nik.ok', false)
            ->assertJsonPath('checks.name.ok', false)
            ->assertJsonPath('checks.username.ok', false);

        $this->postJson('/api/v1/auth/check-citizen', ['username' => 'baru.sekali'])
            ->assertJsonPath('checks.username.ok', true);
    }

    public function test_forgot_password_requires_matching_identity_then_resets_password(): void
    {
        $this->postJson('/api/v1/register', $this->payload())->assertCreated();
        $identity = ['username' => 'budi.santoso', 'nik' => '3171041203900001', 'no_kk' => '3171040409950001'];

        $this->postJson('/api/v1/auth/forgot-password', array_merge($identity, ['no_kk' => '3171040409950999']))->assertStatus(422);

        $token = $this->resetToken($identity);
        auth()->guard('web')->logout();
        $this->get('/reset-password/'.$token)->assertOk()->assertSee('Buat password baru');

        $new = ['token' => $token, 'password' => 'baruaman99', 'password_confirmation' => 'baruaman99'];
        $this->postJson('/api/v1/auth/reset-password', $new)->assertOk();
        $this->postJson('/api/v1/auth/reset-password', $new)->assertStatus(422);

        $this->post('/login', ['login' => 'budi.santoso', 'password' => 'baruaman99'])->assertRedirect('/dashboard');
    }

    public function test_expired_reset_token_is_rejected(): void
    {
        $this->postJson('/api/v1/register', $this->payload())->assertCreated();
        $token = $this->resetToken(['username' => 'budi.santoso', 'nik' => '3171041203900001', 'no_kk' => '3171040409950001']);

        DB::table('password_reset_requests')->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/api/v1/auth/reset-password', ['token' => $token, 'password' => 'baruaman99', 'password_confirmation' => 'baruaman99'])
            ->assertStatus(422)->assertJsonValidationErrors('token');
    }

    public function test_public_endpoints_never_reveal_a_residents_full_name(): void
    {
        $card = FamilyCard::factory()->create(['no_kk' => '3171040409950001', 'no_kk_hash' => hash('sha256', '3171040409950001')]);
        Citizen::factory()->create([
            'family_card_id' => $card->id, 'nik' => '3171041203900001', 'nik_hash' => hash('sha256', '3171041203900001'), 'full_name' => 'Siti Aminah',
        ]);

        $check = $this->postJson('/api/v1/auth/check-citizen', ['nik' => '3171041203900001'])->assertOk();
        $this->assertStringNotContainsString('Siti Aminah', $check->getContent());
        $check->assertJsonPath('registered_name', 'S*** A*****');

        $wrong = $this->postJson('/api/v1/register', $this->payload(['name' => 'Orang Lain']))->assertStatus(422);
        $this->assertStringNotContainsString('Siti', $wrong->getContent());
    }

    public function test_successful_login_is_written_to_audit_log(): void
    {
        User::factory()->warga()->create(['username' => 'auditme', 'password' => 'password123']);

        $this->post('/login', ['login' => 'auditme', 'password' => 'password123'])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_staff_accounts_cannot_use_self_service_password_reset(): void
    {
        User::factory()->ketuaRt()->create(['username' => 'ketua_rt']);

        $this->postJson('/api/v1/auth/forgot-password', ['username' => 'ketua_rt', 'nik' => '3171041203900001', 'no_kk' => '3171040409950001'])
            ->assertStatus(422);
    }
}
