<?php

namespace App\Http\Resources\V1;

use App\Models\FinancialReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FinancialReport
 */
class FinancialReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'year' => (int) $this->year,
            'quarter' => (int) $this->quarter,
            'quarter_label' => "Triwulan {$this->quarter} ({$this->year})",
            'revision' => (int) $this->revision,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'opening_balance_idr' => (int) $this->opening_balance_idr,
            'income_idr' => (int) $this->income_idr,
            'expense_idr' => (int) $this->expense_idr,
            'closing_balance_idr' => (int) $this->closing_balance_idr,
            'dues_assessed_idr' => (int) $this->dues_assessed_idr,
            'dues_collected_idr' => (int) $this->dues_collected_idr,
            'dues_outstanding_idr' => (int) $this->dues_outstanding_idr,
            'status' => $this->status,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'published_by_name' => $this->publisher?->name,
            'checksum_sha256' => $this->checksum_sha256,
            'version' => (int) $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
