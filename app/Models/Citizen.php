<?php

namespace App\Models;

use Database\Factories\CitizenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Citizen extends Model
{
    /** @use HasFactory<CitizenFactory> */
    use HasFactory;

    protected $fillable = [
        'family_card_id',
        'user_id',
        'nik',
        'nik_hash',
        'full_name',
        'place_of_birth',
        'date_of_birth',
        'gender',
        'religion',
        'blood_type',
        'occupation',
        'phone',
        'email',
        'status_warga',
        'is_active',
        'version',
    ];

    protected $attributes = [
        'status_warga' => 'tetap',
        'is_active' => true,
        'version' => 1,
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function familyCard(): BelongsTo
    {
        return $this->belongsTo(FamilyCard::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
