<?php

namespace App\Models;

use Database\Factories\FinanceTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceTransaction extends Model
{
    /** @use HasFactory<FinanceTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'category',
        'amount',
        'description',
        'transaction_date',
        'status',
        'original_transaction_id',
        'version',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];
}
