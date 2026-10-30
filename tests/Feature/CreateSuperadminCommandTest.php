<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSuperadminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_a_working_superadmin(): void
    {
        $this->artisan('app:create-superadmin', ['username' => 'Owner', '--password' => 'rahasia123', '--email' => 'owner@example.com'])
            ->assertSuccessful();

        $user = User::query()->where('username', 'owner')->firstOrFail();
        $this->assertTrue($user->isSuperadmin());

        $this->post('/login', ['login' => 'owner', 'password' => 'rahasia123'])->assertRedirect('/dashboard');
    }

    public function test_command_can_bootstrap_ketua_rt_as_superadmin_tier(): void
    {
        $this->artisan('app:create-superadmin', ['username' => 'pak.rt', '--password' => 'rahasia123', '--role' => 'ketua_rt', '--name' => 'Pak RT'])
            ->assertSuccessful();

        $user = User::query()->where('username', 'pak.rt')->firstOrFail();
        $this->assertTrue($user->isKetuaRt());
        $this->assertTrue($user->isSuperadminTier());
        $this->assertFalse($user->isSuperadmin());
        $this->assertSame('superadmin', $user->tier());
    }

    public function test_command_rejects_non_superadmin_tier_role(): void
    {
        $this->artisan('app:create-superadmin', ['username' => 'bendahara1', '--password' => 'rahasia123', '--role' => 'BENDAHARA'])
            ->assertFailed();
        $this->assertDatabaseMissing('users', ['username' => 'bendahara1']);
    }

    public function test_command_rejects_duplicate_or_weak_input(): void
    {
        User::factory()->create(['username' => 'owner']);

        $this->artisan('app:create-superadmin', ['username' => 'owner', '--password' => 'rahasia123'])->assertFailed();
        $this->artisan('app:create-superadmin', ['username' => 'baru.baru', '--password' => 'pendek'])->assertFailed();
        $this->artisan('app:create-superadmin', ['username' => 'x', '--password' => 'rahasia123'])->assertFailed();
    }
}
