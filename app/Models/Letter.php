<?php

namespace App\Models;

use Database\Factories\LetterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Letter extends Model
{
    /** @use HasFactory<LetterFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'draft',
        'version' => 1,
    ];

    protected $fillable = [
        'citizen_id',
        'jenis_surat_id',
        'type',
        'ticket_number',
        'keperluan',
        'data_tambahan',
        'status',
        'letter_number',
        'catatan_admin',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'approved_by',
        'approved_at',
        'verification_token',
        'file_path',
        'attachment_path',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'data_tambahan' => 'array',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class, 'jenis_surat_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LetterAttachment::class);
    }
}
