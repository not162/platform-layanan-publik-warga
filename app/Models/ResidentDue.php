<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Status efektif: tagihan UNPAID yang melewati jatuh tempo dianggap OVERDUE
     * walaupun penjadwal harian (app:mark-overdue-dues) belum berjalan.
     */
    public function effectiveStatus(): string
    {
        if ($this->status === 'UNPAID' && $this->due_date !== null && $this->due_date->lt(today())) {
            return 'OVERDUE';
        }

        return (string) $this->status;
    }

    /**
     * Tagihan terlambat: OVERDUE, atau UNPAID yang jatuh temponya sudah lewat.
     *
     * @param  Builder<ResidentDue>  $query
     * @return Builder<ResidentDue>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('status', 'OVERDUE')
            ->orWhere(fn (Builder $q) => $q->where('status', 'UNPAID')->whereDate('due_date', '<', today()->toDateString())));
    }

    /**
     * Saring berdasarkan status efektif (lihat effectiveStatus()).
     *
     * @param  Builder<ResidentDue>  $query
     * @return Builder<ResidentDue>
     */
    public function scopeWhereEffectiveStatus(Builder $query, string $status): Builder
    {
        return match (strtoupper($status)) {
            'OVERDUE' => $query->overdue(),
            'UNPAID' => $query->where('status', 'UNPAID')->where(fn (Builder $q) => $q->whereNull('due_date')
                ->orWhereDate('due_date', '>=', today()->toDateString())),
            default => $query->where('status', strtoupper($status)),
        };
    }
}
