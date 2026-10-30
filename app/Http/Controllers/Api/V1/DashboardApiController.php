<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\Letter;
use App\Models\ResidentDue;
use App\Models\SecurityReport;
use App\Models\User;
use App\Services\FinanceService;
use App\Services\ResidentDueService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DashboardApiController extends Controller
{
    public function __construct(
        protected FinanceService $financeService,
        protected ResidentDueService $residentDueService
    ) {}

    /**
     * Role-aware dashboard summary with short-lived cache to avoid N+1 queries.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $isWargaOnly = $user->isWarga() && ! $user->isSuperadmin() && ! $user->isAdmin();
        $cacheKey = "api_dashboard_summary_{$user->id}_".($isWargaOnly ? 'warga' : 'admin');

        $data = Cache::remember($cacheKey, 30, function () use ($user, $isWargaOnly) {
            $financeSummary = $this->financeService->getPublicSummary();

            if ($isWargaOnly) {
                $citizen = $user->citizen;
                $citizenId = $citizen?->id;

                $lettersQuery = Letter::query()->where('citizen_id', $citizenId);
                $letters = [
                    'total' => (clone $lettersQuery)->count(),
                    'draft' => (clone $lettersQuery)->where('status', 'draft')->count(),
                    'submitted' => (clone $lettersQuery)->where('status', 'submitted')->count(),
                    'verified' => (clone $lettersQuery)->where('status', 'verified')->count(),
                    'approved' => (clone $lettersQuery)->where('status', 'approved')->count(),
                    'completed' => (clone $lettersQuery)->where('status', 'completed')->count(),
                    'rejected' => (clone $lettersQuery)->where('status', 'rejected')->count(),
                ];

                $complaintsQuery = Complaint::query()->where('user_id', $user->id);
                $complaints = [
                    'total' => (clone $complaintsQuery)->count(),
                    'submitted' => (clone $complaintsQuery)->where('status', 'submitted')->count(),
                    'in_progress' => (clone $complaintsQuery)->whereIn('status', ['reviewed', 'processing', 'in_progress'])->count(),
                    'resolved' => (clone $complaintsQuery)->whereIn('status', ['resolved', 'closed'])->count(),
                ];

                $dues = $citizen ? $this->residentDueService->getPersonalSummary($citizen) : [
                    'total_billed_idr' => 0,
                    'total_paid_idr' => 0,
                    'total_outstanding_idr' => 0,
                    'unpaid_count' => 0,
                    'paid_count' => 0,
                ];

                return [
                    'role' => 'WARGA',
                    'letters' => $letters,
                    'complaints' => $complaints,
                    'dues' => $dues,
                    'finance' => [
                        'net_balance_idr' => $financeSummary['net_balance_idr'] ?? 0,
                        'total_income_idr' => $financeSummary['total_income_idr'] ?? 0,
                        'total_expense_idr' => $financeSummary['total_expense_idr'] ?? 0,
                    ],
                ];
            }

            // Admin / RT Officers summary: tiap blok hanya untuk pemegang izin terkait.
            $summary = ['role' => $user->role?->name ?? 'ADMIN'];

            if ($this->canAny($user, ['letter.read', 'letter.verify', 'letter.approve'])) {
                $summary['letters'] = [
                    'pending_verification' => Letter::query()->where('status', 'submitted')->count(),
                    'pending_approval' => Letter::query()->where('status', 'verified')->count(),
                    'total' => Letter::query()->count(),
                ];
            }

            if ($this->canAny($user, ['complaint.read', 'complaint.manage'])) {
                $summary['complaints'] = [
                    'open' => Complaint::query()->whereIn('status', ['submitted', 'reviewed', 'processing', 'in_progress'])->count(),
                    'resolved' => Complaint::query()->whereIn('status', ['resolved', 'closed'])->count(),
                    'total' => Complaint::query()->count(),
                ];
            }

            if ($this->canAny($user, ['security.read', 'security.manage'])) {
                $summary['security_reports'] = [
                    'active' => SecurityReport::query()->whereIn('status', ['submitted', 'reported', 'assigned', 'processing'])->count(),
                    'total' => SecurityReport::query()->count(),
                ];
            }

            if ($user->hasPermission('citizen.read')) {
                $summary['citizens'] = [
                    'total' => Citizen::query()->count(),
                    'pending_verification' => Citizen::query()->where('status_warga', 'pending_verification')->count(),
                ];
            }

            // Saldo kas memang publik (beranda), jadi tetap disertakan untuk semua pengurus.
            $summary['finance'] = [
                'net_balance_idr' => $financeSummary['net_balance_idr'] ?? 0,
                'total_income_idr' => $financeSummary['total_income_idr'] ?? 0,
                'total_expense_idr' => $financeSummary['total_expense_idr'] ?? 0,
                'total_transactions' => $financeSummary['total_transactions'] ?? 0,
            ];

            return $summary;
        });

        // Kartu & aktivitas terbaru untuk UI PRISMA digabung dengan ringkasan Sprint 5.
        $ui = app(SummaryController::class)->dashboard($request)->getData(true)['data'] ?? [];

        return response()->json([
            'data' => $data + $ui,
            'meta' => [
                'server_time' => now()->toIso8601String(),
                'request_id' => (string) Str::uuid(),
                'role' => $data['role'],
                'version' => 1,
                'updated_at' => now()->toIso8601String(),
            ],
            'message' => 'OK',
        ]);
    }

    /**
     * Incremental polling sync endpoint for near-real-time updates with ETag support.
     */
    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $sinceRaw = $request->query('since');
        if ($sinceRaw) {
            $sinceClean = preg_replace('/(\d{2}:\d{2}:\d{2})\s(\d{2}:\d{2})$/', '$1+$2', (string) $sinceRaw);
            try {
                $since = Carbon::parse($sinceClean);
            } catch (\Throwable) {
                $since = now()->subDay();
            }
        } else {
            $since = now()->subDay();
        }

        // Cakupan per izin, bukan per peran: tanpa izin modul, pengguna hanya menerima datanya sendiri.
        $citizenId = $user->citizen?->id ?? 0;

        // 1. Letters updated since
        $lettersQuery = Letter::query()->where('updated_at', '>=', $since);
        if (! $this->canAny($user, ['letter.read', 'letter.verify', 'letter.approve'])) {
            $lettersQuery->where('citizen_id', $citizenId);
        }
        $letters = $lettersQuery->latest('updated_at')->limit(50)->get()->map(fn ($l) => [
            'id' => $l->id,
            'ticket_number' => $l->ticket_number,
            'type' => $l->type,
            'status' => $l->status,
            'letter_number' => $l->letter_number,
            'version' => $l->version,
            'updated_at' => $l->updated_at?->toIso8601String(),
        ]);

        // 2. Complaints updated since
        $complaintsQuery = Complaint::query()->where('updated_at', '>=', $since);
        if (! $this->canAny($user, ['complaint.read', 'complaint.manage'])) {
            $complaintsQuery->where('user_id', $user->id);
        }
        $complaints = $complaintsQuery->latest('updated_at')->limit(50)->get()->map(fn ($c) => [
            'id' => $c->id,
            'ticket_number' => $c->ticket_number,
            'title' => $c->title,
            'status' => $c->status,
            'admin_response' => $c->admin_response,
            'version' => $c->version,
            'updated_at' => $c->updated_at?->toIso8601String(),
        ]);

        // 3. Announcements updated since
        $announcements = Announcement::query()
            ->where('is_published', true)
            ->where('updated_at', '>=', $since)
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'category' => $a->category,
                'updated_at' => $a->updated_at?->toIso8601String(),
            ]);

        // 4. Resident dues updated since
        $duesQuery = ResidentDue::query()->where('updated_at', '>=', $since);
        if (! $this->canAny($user, ['finance.dues.read', 'finance.dues.manage'])) {
            $duesQuery->where('citizen_id', $citizenId);
        }
        $dues = $duesQuery->latest('updated_at')->limit(50)->get()->map(fn ($d) => [
            'id' => $d->id,
            'period_year' => $d->period_year,
            'period_month' => $d->period_month,
            'amount_due_idr' => $d->amount_due_idr,
            'status' => $d->effectiveStatus(),
            'updated_at' => $d->updated_at?->toIso8601String(),
        ]);

        $totalChanges = $letters->count() + $complaints->count() + $announcements->count() + $dues->count();

        return response()->json([
            'data' => [
                'letters' => $letters,
                'complaints' => $complaints,
                'announcements' => $announcements,
                'dues' => $dues,
            ],
            'meta' => [
                'server_time' => now()->toIso8601String(),
                'since' => $since->toIso8601String(),
                'has_changes' => $totalChanges > 0,
                'changes_count' => $totalChanges,
                'next_cursor' => now()->toIso8601String(),
            ],
            'message' => 'OK',
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function canAny(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
