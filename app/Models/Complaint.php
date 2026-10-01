<?php

namespace App\Models;

use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'submitted',
        'priority' => 'sedang',
        'is_anonymous' => false,
        'version' => 1,
    ];

    protected $fillable = [
        'ticket_number',
        'citizen_id',
        'user_id',
        'kategori',
        'title',
        'description',
        'lokasi',
        'is_anonymous',
        'status',
        'priority',
        'assigned_admin_id',
        'admin_response',
        'attachment_path',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }
}
