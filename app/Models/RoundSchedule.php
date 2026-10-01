<?php

namespace App\Models;

use Database\Factories\RoundScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoundSchedule extends Model
{
    /** @use HasFactory<RoundScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'day_of_week',
        'shift_name',
        'officer_names',
        'pos_location',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'officer_names' => 'array',
        'is_active' => 'boolean',
    ];
}
