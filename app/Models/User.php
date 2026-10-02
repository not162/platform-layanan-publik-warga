<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'avatar_url',
        'warga_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function citizen(): HasOne
    {
        return $this->hasOne(Citizen::class, 'user_id');
    }

    public function warga(): HasOne
    {
        return $this->citizen();
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(AdminPermission::class, 'user_id');
    }

    public function isSuperadmin(): bool
    {
        return $this->role === UserRole::SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isWarga(): bool
    {
        return $this->role === UserRole::WARGA;
    }

    public function isKetuaRt(): bool
    {
        return $this->role === UserRole::KETUA_RT;
    }

    public function isBendahara(): bool
    {
        return $this->role === UserRole::BENDAHARA;
    }

    public function isSekretaris(): bool
    {
        return $this->role === UserRole::SEKRETARIS;
    }

    public function isPetugasKeamanan(): bool
    {
        return $this->role === UserRole::PETUGAS_KEAMANAN;
    }

    /**
     * Determine if the user has a specific permission scope.
     * Superadmin automatically possesses all permissions (bypass).
     * Other roles use explicit permissions combined with predefined role duties.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        // Check explicit permissions assigned in admin_permissions table
        if ($this->relationLoaded('permissions')) {
            if ($this->permissions->contains('permission', $permission)) {
                return true;
            }
        } elseif ($this->permissions()->where('permission', $permission)->exists()) {
            return true;
        }

        // Default role permissions matrix based on normalized singular resource names
        $rolePermissions = match ($this->role) {
            UserRole::BENDAHARA => [
                // Canonical Finance Permissions
                'finance.transaction.read',
                'finance.transaction.create',
                'finance.transaction.publish',
                'finance.transaction.reverse',
                'finance.dues.read',
                'finance.dues.manage',
                'finance.payment.read',
                'finance.payment.manage',
                'finance.purchase.read',
                'finance.purchase.manage',
                'finance.report.read',
                'finance.report.generate',
                'finance.audit.read',
                'download.audit.read',
                // Legacy compatibility aliases
                'finance.read',
                'finance.manage',
                'finance.report',
                'announcement.manage',
                'announcements.manage',
                'emergency.manage',
            ],
            UserRole::KETUA_RT => [
                'citizen.read',
                'letter.read',
                'letter.approve',
                'letter.reject',
                'complaint.read',
                'complaint.manage',
                'complaints.manage',
                'security.read',
                'announcement.manage',
                'announcements.manage',
                'event.manage',
                'round_schedule.manage',
                'emergency.manage',
                'audit.read',
                // Finance read & report publishing only (no direct ledger mutations)
                'finance.transaction.read',
                'finance.dues.read',
                'finance.payment.read',
                'finance.purchase.read',
                'finance.report.read',
                'finance.report.publish',
                'finance.read',
                'finance.report',
                'download.audit.read',
            ],
            UserRole::SEKRETARIS => [
                'citizen.read',
                'citizen.manage',
                'citizens.manage',
                'letter.read',
                'letter.create',
                'letter.verify',
                'letter.reject',
                'letter.complete',
                'letter.template.manage',
                'letters.manage',
                'complaint.read',
                'complaint.manage',
                'complaints.manage',
                'security.read',
                'announcement.manage',
                'announcements.manage',
                'event.manage',
                'round_schedule.manage',
                'emergency.manage',
                // Finance read only (no mutation)
                'finance.transaction.read',
                'finance.dues.read',
                'finance.purchase.read',
                'finance.report.read',
                'finance.read',
                'finance.report',
                'download.audit.read',
            ],
            UserRole::PETUGAS_KEAMANAN => [
                'security.read',
                'security.manage',
                'round_schedule.manage',
                'emergency.manage',
                'citizen.read',
            ],
            UserRole::WARGA => [
                'letter.create',
            ],
            default => [],
        };

        if (in_array($permission, $rolePermissions, true)) {
            return true;
        }

        // Backward compatibility mappings
        if ($permission === 'finance.read' && in_array('finance.transaction.read', $rolePermissions, true)) {
            return true;
        }
        if ($permission === 'finance.report' && (in_array('finance.report.read', $rolePermissions, true) || in_array('finance.report.publish', $rolePermissions, true))) {
            return true;
        }

        return false;
    }

    public function givePermission(string $permission): void
    {
        $this->permissions()->firstOrCreate(['permission' => $permission]);
    }

    public function revokePermission(string $permission): void
    {
        $this->permissions()->where('permission', $permission)->delete();
    }

    public function syncPermissions(array $permissions): void
    {
        $this->permissions()->delete();
        foreach ($permissions as $perm) {
            $this->permissions()->create(['permission' => $perm]);
        }
    }
}
