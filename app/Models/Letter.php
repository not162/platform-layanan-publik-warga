<?php

namespace App\Models;

use Database\Factories\LetterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'type',
        'status',
        'attachment_path',
        'ticket_number',
        'letter_number',
        'version',
    ];

    public function citizen()
    {
        return $this->belongsTo(Citizen::class);
    }
}
