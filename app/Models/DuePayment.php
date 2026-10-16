<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'resident_due_id',
        'finance_transaction_id',
        'amount_paid_idr',
        'paid_at',
        'payment_method',
        'receipt_number',
        'proof_path',
        'received_by',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid_idr' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function due(): BelongsTo
    {
        return $this->belongsTo(ResidentDue::class, 'resident_due_id');
    }

    public function financeTransaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
