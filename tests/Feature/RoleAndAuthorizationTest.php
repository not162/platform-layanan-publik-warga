<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_has_all_permissions_automatically(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        $this->assertTrue($superadmin->isSuperadmin());
        $this->assertFalse($superadmin->isAdmin());
        $this->assertFalse($superadmin->isWarga());

        // Any arbitrary permission scope is true for superadmin
        $this->assertTrue($superadmin->hasPermission('citizen.manage'));
        $this->assertTrue($superadmin->hasPermission('letter.verify'));
        $this->assertTrue($superadmin->hasPermission('letter.approve'));
        $this->assertTrue($superadmin->hasPermission('finance.manage'));
        $this->assertTrue($superadmin->hasPermission('system.manage'));
    }

    public function test_admin_only_has_assigned_permissions(): void
    {
        $admin = User::factory()->admin(['letter.verify', 'announcement.manage'])->create();

        $this->assertFalse($admin->isSuperadmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isWarga());

        $this->assertTrue($admin->hasPermission('letter.verify'));
        $this->assertTrue($admin->hasPermission('announcement.manage'));

        // Lacks other scopes
        $this->assertFalse($admin->hasPermission('letter.approve'));
        $this->assertFalse($admin->hasPermission('finance.manage'));
        $this->assertFalse($admin->hasPermission('citizen.manage'));
    }

    public function test_warga_cannot_have_admin_permissions(): void
    {
        $warga = User::factory()->warga()->create();

        $this->assertFalse($warga->isSuperadmin());
        $this->assertFalse($warga->isAdmin());
        $this->assertTrue($warga->isWarga());

        $this->assertFalse($warga->hasPermission('citizen.manage'));
        $this->assertFalse($warga->hasPermission('letter.verify'));
    }

    public function test_inactive_user_is_blocked(): void
    {
        $inactiveAdmin = User::factory()->admin(['citizen.manage'])->inactive()->create();

        $this->assertFalse($inactiveAdmin->is_active);

        // When inactive admin calls admin endpoint, middleware aborts 403
        $response = $this->actingAs($inactiveAdmin)->getJson('/api/v1/admin/letters');
        $response->assertStatus(403);
    }
}
