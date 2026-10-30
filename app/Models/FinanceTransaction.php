<?php

namespace App\Models;

use Database\Factories\FinanceTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinanceTransaction extends Model
{
    /** @use HasFactory<FinanceTransactionFactory> */
    use HasFactory;

    /** Sumber transaksi otomatis dari modul iuran warga (ResidentDueService). */
    public const SOURCE_DUES = 'Iuran Kas Warga';

    /** Sumber transaksi otomatis dari modul belanja RT (PurchaseService). */
    public const SOURCE_PURCHASE = 'Pengeluaran Belanja RT';

    /**
     * Status yang ikut dihitung dalam saldo. Transaksi asal yang dikoreksi
     * ('reversed') tetap dihitung bersama jurnal pembaliknya ('published'),
     * sehingga keduanya saling meniadakan dan saldo bersih kembali benar.
     */
    public const COUNTABLE_STATUSES = ['published', 'reversed'];

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

    public function duePayment(): HasOne
    {
        return $this->hasOne(DuePayment::class, 'finance_transaction_id');
    }

    /**
     * Satu-satunya aturan transaksi yang dihitung dalam saldo, laporan, dan ringkasan.
     *
     * @param  Builder<FinanceTransaction>  $query
     * @return Builder<FinanceTransaction>
     */
    public function scopeCountable(Builder $query): Builder
    {
        return $query->whereIn('status', self::COUNTABLE_STATUSES);
    }

    /**
     * Nominal dalam Rupiah bulat (fallback ke kolom lama 'amount' untuk data legacy).
     */
    public function amountIdr(): int
    {
        return (int) ($this->amount_idr ?: round((float) $this->amount));
    }

    /**
     * Jurnal pembalik hasil koreksi (punya transaksi asal).
     */
    public function isReversal(): bool
    {
        return $this->original_transaction_id !== null;
    }

    /**
     * Transaksi otomatis dari modul iuran/belanja. Koreksi harus dilakukan
     * di modul asalnya agar data iuran/belanja tetap sinkron dengan buku kas.
     */
    public function isSystem(): bool
    {
        if (in_array($this->source, [self::SOURCE_DUES, self::SOURCE_PURCHASE], true)) {
            return true;
        }

        if (! $this->exists) {
            return false;
        }

        $hasPurchase = $this->relationLoaded('purchase') ? $this->purchase !== null : $this->purchase()->exists();
        if ($hasPurchase) {
            return true;
        }

        return $this->relationLoaded('duePayment') ? $this->duePayment !== null : $this->duePayment()->exists();
    }

    /**
     * Keterangan aman untuk publik: nama pembayar iuran tidak ditampilkan.
     */
    public function publicDescription(): ?string
    {
        if ($this->source !== self::SOURCE_DUES || $this->description === null) {
            return $this->description;
        }

        return trim((string) preg_replace('/\s+oleh\s+.*$/su', '', $this->description));
    }
}
