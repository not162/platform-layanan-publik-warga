<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterType extends Model
{
    protected $table = 'jenis_surat';

    protected $fillable = [
        'kode_surat',
        'nama_surat',
        'format_penomoran',
        'syarat_dokumen',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'syarat_dokumen' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class, 'jenis_surat_id');
    }
}
