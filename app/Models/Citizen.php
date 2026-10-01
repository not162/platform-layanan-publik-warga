<?php

namespace App\Models;

use Database\Factories\CitizenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'version',
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'date_of_birth' => 'date',
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
}
