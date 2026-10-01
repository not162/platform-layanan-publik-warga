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
        'template_key',
        'template_version',
        'format_penomoran',
        'syarat_dokumen',
        'form_schema',
        'approval_flow',
        'estimated_process_hours',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'syarat_dokumen' => 'array',
            'form_schema' => 'array',
            'approval_flow' => 'array',
            'template_version' => 'integer',
            'estimated_process_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class, 'jenis_surat_id');
    }
}
