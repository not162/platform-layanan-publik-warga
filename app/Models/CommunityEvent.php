<?php

namespace App\Models;

use Database\Factories\CommunityEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityEvent extends Model
{
    /** @use HasFactory<CommunityEventFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location',
        'event_date',
        'start_time',
        'end_time',
        'is_published',
        'version',
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_published' => 'boolean',
        'version' => 'integer',
    ];
}
