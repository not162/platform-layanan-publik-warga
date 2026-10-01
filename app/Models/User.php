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

        // Default role permissions matrix based on singular resource names
        $rolePermissions = match ($this->role) {
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
                'finance.read',
                'round_schedule.manage',
                'emergency.manage',
                'audit.read',
            ],
            UserRole::SEKRETARIS => [
                'citizen.read',
                'citizen.manage',
                'citizens.manage',
                'letter.read',
                'letter.create',
                'letter.verify',
                'letter.reject',
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
            ],
            UserRole::BENDAHARA => [
                'finance.read',
                'finance.manage',
                'announcement.manage',
                'announcements.manage',
                'emergency.manage',
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

        return in_array($permission, $rolePermissions, true);
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
