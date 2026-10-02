<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'quarter',
        'revision',
        'period_start',
        'period_end',
        'opening_balance_idr',
        'income_idr',
        'expense_idr',
        'closing_balance_idr',
        'dues_assessed_idr',
        'dues_collected_idr',
        'dues_outstanding_idr',
        'status',
        'generated_at',
        'published_at',
        'published_by',
        'checksum_sha256',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'quarter' => 'integer',
            'revision' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'opening_balance_idr' => 'integer',
            'income_idr' => 'integer',
            'expense_idr' => 'integer',
            'closing_balance_idr' => 'integer',
            'dues_assessed_idr' => 'integer',
            'dues_collected_idr' => 'integer',
            'dues_outstanding_idr' => 'integer',
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function getQuarterLabel(): string
    {
        return sprintf('%d-Q%d (Rev %d)', $this->year, $this->quarter, $this->revision);
    }
}
