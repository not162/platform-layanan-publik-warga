<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterAttachment extends Model
{
    protected $fillable = [
        'letter_id',
        'judul_lampiran',
        'file_path',
        'mime_type',
        'file_size',
    ];

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }
}
