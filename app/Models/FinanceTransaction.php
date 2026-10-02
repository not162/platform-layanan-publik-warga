<?php

namespace App\Models;

use Database\Factories\FinanceTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinanceTransaction extends Model
{
    /** @use HasFactory<FinanceTransactionFactory> */
    use HasFactory;

    protected $attributes = [
        'source' => 'other',
        'status' => 'draft',
        'version' => 1,
    ];

    protected $fillable = [
        'transaction_number',
        'type',
        'source',
        'category',
        'amount',
        'amount_idr',
        'description',
        'transaction_date',
        'receipt_path',
        'status',
        'created_by',
        'published_at',
        'original_transaction_id',
        'reversal_reason',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'published_at' => 'datetime',
            'amount' => 'decimal:2',
            'amount_idr' => 'integer',
            'version' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'original_transaction_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'original_transaction_id');
    }

    public function purchase(): HasOne
    {
        return $this->hasOne(Purchase::class, 'finance_transaction_id');
    }
}
