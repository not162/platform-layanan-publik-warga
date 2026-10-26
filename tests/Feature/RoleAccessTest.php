<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_works_with_username_and_with_email(): void
    {
        $user = User::factory()->sekretaris()->create(['username' => 'sekretaris', 'email' => 'sek@warga.local', 'password' => 'password123']);

        $this->post('/login', ['login' => 'sekretaris', 'password' => 'password123'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        auth()->guard('web')->logout();

        $this->post('/login', ['login' => 'sek@warga.local', 'password' => 'password123'])->assertRedirect('/dashboard');
        auth()->guard('web')->logout();

        $this->post('/login', ['login' => 'sekretaris', 'password' => 'salah'])->assertSessionHasErrors('login');
    }

    public function test_inactive_account_cannot_login(): void
    {
        User::factory()->inactive()->create(['username' => 'nonaktif', 'password' => 'password123']);

        $this->post('/login', ['login' => 'nonaktif', 'password' => 'password123'])->assertSessionHasErrors('login');
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>, 2: array<int, string>}>
     */
    public static function roleMatrix(): array
    {
        return [
            'superadmin' => ['superadmin', ['users', 'audit', 'system', 'citizens', 'finance', 'letters', 'announcements'], ['my-letters']],
            'admin' => ['admin', ['users', 'citizens', 'announcements', 'events', 'officers', 'audit', 'system'], ['my-letters']],
            'ketuaRt' => ['ketuaRt', ['letters', 'complaints', 'security', 'finance', 'citizens', 'audit'], ['users', 'my-letters']],
            'sekretaris' => ['sekretaris', ['letters', 'citizens', 'announcements', 'events', 'officers'], ['users', 'my-letters']],
            'bendahara' => ['bendahara', ['finance', 'announcements'], ['users', 'letters', 'citizens']],
            'petugasKeamanan' => ['petugasKeamanan', ['security', 'schedules', 'contacts'], ['users', 'finance', 'letters']],
            'warga' => ['warga', ['my-letters', 'my-complaints', 'my-security'], ['users', 'letters', 'finance', 'citizens', 'audit', 'system']],
        ];
    }

    /**
     * @param  array<int, string>  $allowed
     * @param  array<int, string>  $denied
     */
    #[DataProvider('roleMatrix')]
    public function test_each_role_only_reaches_its_own_pages(string $state, array $allowed, array $denied): void
    {
        $user = User::factory()->{$state}()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('class="bottom-nav"', false)->assertSee('class="sidebar"', false);
        $this->actingAs($user)->get('/dashboard/profile')->assertOk();

        foreach ($allowed as $page) {
            $this->actingAs($user)->get('/app/'.$page)->assertOk();
        }
        foreach ($denied as $page) {
            $this->actingAs($user)->get('/app/'.$page)->assertForbidden();
        }
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/app/users')->assertRedirect('/login');
        $this->getJson('/api/v1/manage/users')->assertUnauthorized();
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }

    public function test_dashboard_shows_three_access_tiers(): void
    {
        $this->actingAs(User::factory()->superadmin()->create())->get('/dashboard')->assertSee('Superadmin');
        $this->actingAs(User::factory()->bendahara()->create())->get('/dashboard')->assertSee('akses <b>Admin</b>', false);
        $this->actingAs(User::factory()->warga()->create())->get('/dashboard')->assertSee('User (Warga)');
    }

    public function test_system_page_lists_all_five_modelling_elements_and_live_rbac_matrix(): void
    {
        $this->actingAs(User::factory()->superadmin()->create())->get('/app/system')->assertOk()
            ->assertSee('Entity Relationship Diagram')
            ->assertSee('Logical Record Structure')
            ->assertSee('Use Case Diagram')
            ->assertSee('Activity Diagram')
            ->assertSee('UML')
            ->assertSee('Matriks hak akses')
            ->assertSee('Ketua RT');
    }

    public function test_public_pages_have_desktop_and_mobile_navigation(): void
    {
        $this->get('/')->assertOk()->assertSee('navlinks', false)->assertSee('bottom-nav', false)->assertSee('drawer-tpl', false);
        $this->get('/login')->assertOk()->assertSee('Lupa password?');
        $this->get('/forgot-password')->assertOk()->assertSee('No. Kartu Keluarga');
    }
}
