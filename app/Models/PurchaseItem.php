<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'item_name',
        'description',
        'quantity',
        'unit',
        'unit_price_idr',
        'subtotal_idr',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_idr' => 'integer',
            'subtotal_idr' => 'integer',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }
}
