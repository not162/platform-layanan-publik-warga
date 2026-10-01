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

    /**
     * Determine if the user has a specific permission scope.
     * Superadmin automatically possesses all permissions.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        if (! $this->isAdmin()) {
            return false;
        }

        // Check if relation is loaded to avoid N+1
        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('permission', $permission);
        }

        return $this->permissions()->where('permission', $permission)->exists();
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
