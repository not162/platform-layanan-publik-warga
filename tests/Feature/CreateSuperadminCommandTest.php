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

    public function test_command_rejects_duplicate_or_weak_input(): void
    {
        User::factory()->create(['username' => 'owner']);

        $this->artisan('app:create-superadmin', ['username' => 'owner', '--password' => 'rahasia123'])->assertFailed();
        $this->artisan('app:create-superadmin', ['username' => 'baru.baru', '--password' => 'pendek'])->assertFailed();
        $this->artisan('app:create-superadmin', ['username' => 'x', '--password' => 'rahasia123'])->assertFailed();
    }
}
