<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\DuePayment;
use App\Models\FinanceTransaction;
use App\Models\FinancialReport;
use App\Models\Letter;
use App\Models\ResidentDue;
use App\Models\SecurityReport;
use App\Models\User;
use App\Services\ResidentDueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SummaryController extends Controller
{
    /**
     * Ringkasan dashboard sesuai peran (dipoll berkala oleh UI untuk data real-time).
     */
    public function dashboard(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $cards = $user->isWarga() ? $this->wargaCards($user) : $this->staffCards($user);
        $recent = $user->isWarga() ? $this->wargaRecent($user) : $this->staffRecent($user);

        return response()->json([
            'data' => [
                'cards' => $cards,
                'recent' => $recent->values(),
                'unread_notifications' => $user->unreadNotifications()->count(),
                'pending_verification' => $user->isWarga() && $user->citizen?->status_warga === 'pending_verification',
            ],
            'meta' => ['server_time' => now()->toIso8601String()],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function wargaCards(User $user): array
    {
        $citizen = $user->citizen;
        $letters = Letter::query()->where('citizen_id', $citizen?->id ?? 0);
        $outstanding = $citizen ? (int) app(ResidentDueService::class)->getPersonalSummary($citizen)['total_outstanding_idr'] : 0;

        return [
            $this->card('Surat saya', (clone $letters)->count(), 'mail', '/app/my-letters', 'blue'),
            $this->card('Surat disahkan', (clone $letters)->whereIn('status', ['approved', 'completed'])->count(), 'check', '/app/my-letters', 'green'),
            $this->card('Tunggakan iuran', $outstanding, 'wallet', '/app/my-dues', 'amber', true),
            $this->card('Pengaduan saya', Complaint::query()->where('user_id', $user->id)->count(), 'alert', '/app/my-complaints', 'amber'),
            $this->card('Laporan keamanan', SecurityReport::query()->where('reporter_user_id', $user->id)->count(), 'shield', '/app/my-security', 'rose'),
        ];
    }

    /**
     * Kartu pengurus: setiap kartu muncul hanya bagi pemegang izin terkait.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function staffCards(User $user): array
    {
        $cards = [];

        // Antrean surat per tahap: Sekretaris verifikasi -> Ketua RT sahkan -> Sekretaris selesaikan.
        if ($user->hasPermission('letter.verify')) {
            $cards[] = $this->card('Perlu verifikasi', Letter::query()->where('status', 'submitted')->count(), 'mail', '/app/letters?status=submitted', 'amber');
        }
        if ($user->hasPermission('letter.approve')) {
            $cards[] = $this->card('Menunggu pengesahan', Letter::query()->where('status', 'verified')->count(), 'check', '/app/letters?status=verified', 'blue');
        }
        if ($user->hasPermission('letter.complete')) {
            $cards[] = $this->card('Siap diselesaikan', Letter::query()->where('status', 'approved')->count(), 'check', '/app/letters?status=approved', 'green');
        }
        if ($user->hasPermission('finance.report.publish')) {
            $cards[] = $this->card('Laporan triwulan menunggu pengesahan', FinancialReport::query()->where('status', 'draft')->count(), 'layers', '/app/reports', 'amber');
        }
        if ($user->hasPermission('citizen.read')) {
            $cards[] = $this->card('Data warga', Citizen::query()->count(), 'users', '/app/citizens', 'blue');
        }
        if ($user->hasPermission('citizen.manage')) {
            $cards[] = $this->card('Pendaftar menunggu verifikasi', Citizen::query()->where('status_warga', 'pending_verification')->count(), 'user-plus', '/app/citizens?status_warga=pending_verification', 'amber');
        }
        if ($user->hasPermission('complaint.read')) {
            $cards[] = $this->card('Pengaduan aktif', Complaint::query()->whereNotIn('status', ['resolved', 'closed', 'rejected'])->count(), 'alert', '/app/complaints', 'rose');
        }
        if ($user->hasPermission('security.read')) {
            $cards[] = $this->card('Laporan keamanan aktif', SecurityReport::query()->whereNotIn('status', ['resolved', 'closed'])->count(), 'shield', '/app/security', 'rose');
        }
        if ($user->hasPermission('finance.transaction.read')) {
            $ledger = FinanceTransaction::query()->countable();
            $income = (int) (clone $ledger)->where('type', 'income')->sum('amount_idr');
            $expense = (int) (clone $ledger)->where('type', 'expense')->sum('amount_idr');
            $cards[] = $this->card('Saldo kas', $income - $expense, 'wallet', '/app/finance', 'green', true);
            $cards[] = $this->card('Transaksi draft', FinanceTransaction::query()->where('status', 'draft')->count(), 'file', '/app/finance?status=draft', 'amber');
        }
        if ($user->hasPermission('finance.dues.read')) {
            $cards[] = $this->card('Tagihan belum lunas', ResidentDue::query()
                ->where('period_year', now()->year)
                ->where('period_month', now()->month)
                ->where('status', '!=', 'PAID')
                ->count(), 'wallet', '/app/dues', 'amber');
            $cards[] = $this->card('Tagihan terlambat', ResidentDue::query()->overdue()->count(), 'clock', '/app/dues', 'rose');
            $cards[] = $this->card('Iuran terkumpul bulan ini', (int) DuePayment::query()
                ->where('status', 'PAID')
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount_paid_idr'), 'wallet', '/app/dues', 'green', true);
        }
        if ($user->hasPermission('user.manage')) {
            $cards[] = $this->card('Pengguna aktif', User::query()->where('is_active', true)->count(), 'users', '/app/users', 'blue');
        }
        if ($user->hasPermission('announcement.manage')) {
            $cards[] = $this->card('Pengumuman tayang', Announcement::query()->where('is_published', true)->count(), 'megaphone', '/app/announcements', 'green');
        }

        return $cards;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function wargaRecent(User $user): Collection
    {
        return Letter::query()->where('citizen_id', $user->citizen?->id ?? 0)->latest('updated_at')->limit(6)->get()
            ->map(fn (Letter $l) => $this->item($l->type, $l->ticket_number, $l->status, $l->updated_at, '/app/my-letters'));
    }

    /**
     * Pemegang audit.read melihat jejak audit; pengurus lain melihat antrean kerjanya sendiri.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function staffRecent(User $user): Collection
    {
        if ($user->hasPermission('audit.read')) {
            return AuditLog::query()->with('user')->latest('id')->limit(8)->get()
                ->map(fn (AuditLog $a) => $this->item($a->action, ($a->user?->name ?? 'Sistem').' · '.$a->entity_type.' #'.$a->entity_id, null, $a->created_at, '/app/audit'));
        }

        $items = collect();

        if ($user->hasPermission('letter.verify') || $user->hasPermission('letter.complete')) {
            $statuses = array_values(array_filter([
                $user->hasPermission('letter.verify') ? 'submitted' : null,
                $user->hasPermission('letter.complete') ? 'approved' : null,
            ]));
            $items = $items->concat(Letter::query()->with('citizen')->whereIn('status', $statuses)->latest('updated_at')->limit(8)->get()
                ->map(fn (Letter $l) => $this->item($l->type, $l->ticket_number.' · '.($l->citizen?->full_name ?? 'Pemohon'), $l->status, $l->updated_at, '/app/letters?status='.$l->status)));
        }

        if ($user->hasPermission('citizen.manage')) {
            $items = $items->concat(Citizen::query()->where('status_warga', 'pending_verification')->latest('updated_at')->limit(8)->get()
                ->map(fn (Citizen $c) => $this->item($c->full_name, 'Pendaftar baru menunggu verifikasi', 'pending_verification', $c->updated_at, '/app/citizens?status_warga=pending_verification')));
        }

        if ($user->hasPermission('complaint.manage')) {
            $items = $items->concat(Complaint::query()->whereIn('status', ['submitted', 'reviewed', 'processing', 'in_progress'])->latest('updated_at')->limit(8)->get()
                ->map(fn (Complaint $c) => $this->item($c->title, $c->ticket_number, $c->status, $c->updated_at, '/app/complaints')));
        }

        if ($user->hasPermission('security.manage')) {
            $items = $items->concat(SecurityReport::query()->whereNotIn('status', ['resolved', 'closed'])->latest('updated_at')->limit(8)->get()
                ->map(fn (SecurityReport $s) => $this->item($s->title, $s->ticket_number.($s->location ? ' · '.$s->location : ''), $s->status, $s->updated_at, '/app/security')));
        }

        if ($user->hasPermission('finance.transaction.create') || $user->hasPermission('finance.transaction.publish')) {
            $items = $items->concat(FinanceTransaction::query()->latest('updated_at')->latest('id')->limit(8)->get()
                ->map(fn (FinanceTransaction $t) => $this->item(
                    ($t->type === 'income' ? 'Pemasukan' : 'Pengeluaran').' · '.$t->category,
                    $t->transaction_number.' · Rp '.number_format($t->amountIdr(), 0, ',', '.'),
                    $t->status,
                    $t->updated_at,
                    '/app/finance'
                )));
        }

        return $items->sortByDesc('at')->take(8)->values();
    }

    /**
     * @return array<string, mixed>
     */
    protected function item(?string $title, ?string $meta, ?string $status, mixed $at, string $href): array
    {
        return [
            'title' => (string) $title,
            'meta' => (string) $meta,
            'status' => $status,
            'at' => $at?->toIso8601String(),
            'href' => $href,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function card(string $label, int $value, string $icon, string $href, string $tone, bool $money = false): array
    {
        return compact('label', 'value', 'icon', 'href', 'tone', 'money');
    }

    public function roles(Request $request): JsonResponse
    {
        $role = $request->user()->role;

        return response()->json(['data' => array_map(
            fn (UserRole $r) => ['value' => $r->value, 'label' => $r->label(), 'tier' => $r->tier()],
            $role->assignableRoles()
        )]);
    }
}
