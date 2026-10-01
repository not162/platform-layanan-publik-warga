<?php

namespace App\Models;

use Database\Factories\RtOfficerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RtOfficer extends Model
{
    /** @use HasFactory<RtOfficerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'position',
        'phone',
        'order_index',
        'photo_url',
        'is_active',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'is_active' => 'boolean',
    ];
}
