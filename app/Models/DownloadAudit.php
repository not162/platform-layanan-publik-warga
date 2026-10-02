<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadAudit extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'actor_user_id',
        'resource_type',
        'resource_id',
        'file_type',
        'period_year',
        'period_quarter',
        'action',
        'result',
        'ip_hash',
        'user_agent_hash',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_quarter' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
