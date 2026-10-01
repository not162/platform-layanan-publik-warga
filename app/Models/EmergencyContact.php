<?php

namespace App\Models;

use Database\Factories\EmergencyContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyContact extends Model
{
    /** @use HasFactory<EmergencyContactFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'phone_number',
        'description',
        'order_index',
        'is_active',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'is_active' => 'boolean',
    ];
}
