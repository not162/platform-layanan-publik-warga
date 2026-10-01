<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityReport extends Model
{
    use HasFactory;

    protected $attributes = [
        'severity' => 'medium',
        'status' => 'submitted',
        'is_anonymous' => false,
        'version' => 1,
    ];

    protected $fillable = [
        'ticket_number',
        'citizen_id',
        'reporter_user_id',
        'category',
        'severity',
        'title',
        'description',
        'location',
        'incident_at',
        'is_anonymous',
        'status',
        'assigned_to',
        'resolution',
        'resolved_at',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'incident_at' => 'datetime',
            'resolved_at' => 'datetime',
            'is_anonymous' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
