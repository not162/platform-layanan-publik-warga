<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'finance_transaction_id',
        'vendor_name',
        'purchase_date',
        'invoice_number',
        'purpose',
        'notes',
        'receipt_path',
        'created_by',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'version' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function calculateTotalIdr(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum('subtotal_idr');
        }

        return (int) $this->items()->sum('subtotal_idr');
    }
}
