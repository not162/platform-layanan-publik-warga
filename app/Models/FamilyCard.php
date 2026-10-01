<?php

namespace App\Models;

use Database\Factories\FamilyCardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyCard extends Model
{
    /** @use HasFactory<FamilyCardFactory> */
    use HasFactory;

    protected $fillable = [
        'no_kk',
        'no_kk_hash',
        'address',
        'rt',
        'rw',
        'province',
        'city',
        'district',
        'village',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'no_kk' => 'encrypted',
        ];
    }

    public function citizens(): HasMany
    {
        return $this->hasMany(Citizen::class);
    }
}
