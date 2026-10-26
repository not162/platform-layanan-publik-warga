<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\FinanceTransaction;
use App\Models\Letter;
use App\Models\SecurityReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    /**
     * Ringkasan dashboard sesuai peran (dipoll berkala oleh UI untuk data real-time).
     */
    public function dashboard(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $cards = [];

        if ($user->isWarga()) {
            $citizenId = $user->citizen?->id ?? 0;
            $letters = Letter::query()->where('citizen_id', $citizenId);
            $cards = [
                $this->card('Surat saya', (clone $letters)->count(), 'mail', '/app/my-letters', 'blue'),
                $this->card('Surat disahkan', (clone $letters)->whereIn('status', ['approved', 'completed'])->count(), 'check', '/app/my-letters', 'green'),
                $this->card('Pengaduan saya', Complaint::query()->where('user_id', $user->id)->count(), 'alert', '/app/my-complaints', 'amber'),
                $this->card('Laporan keamanan', SecurityReport::query()->where('reporter_user_id', $user->id)->count(), 'shield', '/app/my-security', 'rose'),
            ];
        } else {
            if ($user->hasPermission('letter.verify')) {
                $cards[] = $this->card('Perlu verifikasi', Letter::query()->where('status', 'submitted')->count(), 'mail', '/app/letters', 'amber');
            }
            if ($user->hasPermission('letter.approve')) {
                $cards[] = $this->card('Menunggu pengesahan', Letter::query()->where('status', 'verified')->count(), 'check', '/app/letters', 'blue');
            }
            if ($user->hasPermission('citizen.read')) {
                $cards[] = $this->card('Data warga', Citizen::query()->count(), 'users', '/app/citizens', 'blue');
                $cards[] = $this->card('Warga baru pending', Citizen::query()->where('status_warga', 'pending_verification')->count(), 'user-plus', '/app/citizens', 'amber');
            }
            if ($user->hasPermission('complaint.read')) {
                $cards[] = $this->card('Pengaduan aktif', Complaint::query()->whereNotIn('status', ['resolved', 'closed', 'rejected'])->count(), 'alert', '/app/complaints', 'rose');
            }
            if ($user->hasPermission('security.read')) {
                $cards[] = $this->card('Laporan keamanan aktif', SecurityReport::query()->whereNotIn('status', ['resolved', 'closed'])->count(), 'shield', '/app/security', 'rose');
            }
            if ($user->hasPermission('finance.transaction.read')) {
                $income = (int) FinanceTransaction::query()->where('status', 'published')->where('type', 'income')->sum('amount_idr');
                $expense = (int) FinanceTransaction::query()->where('status', 'published')->where('type', 'expense')->sum('amount_idr');
                $cards[] = $this->card('Saldo kas', $income - $expense, 'wallet', '/app/finance', 'green', true);
                $cards[] = $this->card('Transaksi draft', FinanceTransaction::query()->where('status', 'draft')->count(), 'file', '/app/finance', 'amber');
            }
            if ($user->hasPermission('user.manage')) {
                $cards[] = $this->card('Pengguna aktif', User::query()->where('is_active', true)->count(), 'users', '/app/users', 'blue');
            }
            if ($user->hasPermission('announcement.manage')) {
                $cards[] = $this->card('Pengumuman tayang', Announcement::query()->where('is_published', true)->count(), 'megaphone', '/app/announcements', 'green');
            }
        }

        $recent = $user->isWarga()
            ? Letter::query()->where('citizen_id', $user->citizen?->id ?? 0)->latest('updated_at')->limit(6)->get()
                ->map(fn ($l) => ['title' => $l->type, 'meta' => $l->ticket_number, 'status' => $l->status, 'at' => $l->updated_at->toIso8601String()])
            : (($user->hasPermission('audit.read') ? AuditLog::query()->with('user')->latest('id')->limit(8)->get() : collect())
                ->map(fn ($a) => ['title' => $a->action, 'meta' => ($a->user?->name ?? 'Sistem').' · '.$a->entity_type.' #'.$a->entity_id, 'status' => null, 'at' => $a->created_at->toIso8601String()]));

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
