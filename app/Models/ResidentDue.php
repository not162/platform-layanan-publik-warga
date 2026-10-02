<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResidentDue extends Model
{
    use HasFactory;

    protected $fillable = [
        'citizen_id',
        'period_year',
        'period_month',
        'amount_due_idr',
        'due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'amount_due_idr' => 'integer',
            'due_date' => 'date',
        ];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DuePayment::class, 'resident_due_id');
    }

    public function getTotalPaidIdr(): int
    {
        if ($this->relationLoaded('payments')) {
            return (int) $this->payments->where('status', 'PAID')->sum('amount_paid_idr');
        }

        return (int) $this->payments()->where('status', 'PAID')->sum('amount_paid_idr');
    }

    public function getRemainingDueIdr(): int
    {
        $paid = $this->getTotalPaidIdr();

        return max(0, $this->amount_due_idr - $paid);
    }
}
