<?php

namespace App\Models;

use Database\Factories\FinanceTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceTransaction extends Model
{
    /** @use HasFactory<FinanceTransactionFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'draft',
        'version' => 1,
    ];

    protected $fillable = [
        'type',
        'category',
        'amount',
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
}
