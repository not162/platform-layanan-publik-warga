<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\CommunityEvent;
use App\Models\EmergencyContact;
use App\Models\FinanceTransaction;
use App\Models\LetterType;
use App\Models\RoundSchedule;
use App\Models\RtOfficer;
use Illuminate\Http\JsonResponse;

class PortalController extends Controller
{
    /**
     * Data publik beranda — langsung dari MySQL tanpa cache agar CRUD pengurus tampil seketika.
     */
    public function index(): JsonResponse
    {
        $published = FinanceTransaction::query()->where('status', 'published');
        $income = (int) (clone $published)->where('type', 'income')->sum('amount_idr');
        $expense = (int) (clone $published)->where('type', 'expense')->sum('amount_idr');

        return response()->json([
            'data' => [
                'announcements' => Announcement::query()->where('is_published', true)
                    ->orderByDesc('is_pinned')->latest('published_at')->limit(6)->get()
                    ->map(fn ($a) => [
                        'id' => $a->id, 'title' => $a->title, 'category' => $a->category, 'content' => $a->content,
                        'is_pinned' => (bool) $a->is_pinned, 'published_at' => $a->published_at?->toIso8601String(),
                    ]),
                'events' => CommunityEvent::query()->where('is_published', true)
                    ->whereDate('event_date', '>=', now()->toDateString())->orderBy('event_date')->limit(6)->get()
                    ->map(fn ($e) => [
                        'id' => $e->id, 'title' => $e->title, 'description' => $e->description, 'location' => $e->location,
                        'event_date' => $e->event_date?->toDateString(),
                        'start_time' => $e->start_time ? substr((string) $e->start_time, 0, 5) : null,
                    ]),
                'finance' => [
                    'income' => $income,
                    'expense' => $expense,
                    'balance' => $income - $expense,
                    'recent' => FinanceTransaction::query()->where('status', 'published')
                        ->orderByDesc('transaction_date')->orderByDesc('id')->limit(6)->get()
                        ->map(fn ($t) => [
                            'type' => $t->type, 'category' => $t->category, 'description' => $t->description,
                            'amount' => (int) $t->amount_idr, 'date' => $t->transaction_date?->toDateString(),
                        ]),
                ],
                'officers' => RtOfficer::query()->where('is_active', true)->orderBy('order_index')->get(['name', 'position', 'phone']),
                'contacts' => EmergencyContact::query()->where('is_active', true)->orderBy('order_index')->get(['name', 'category', 'phone_number', 'description']),
                'schedules' => RoundSchedule::query()->where('is_active', true)->get()
                    ->map(fn ($s) => ['day' => $s->day_of_week, 'shift' => $s->shift_name, 'officers' => $s->officer_names, 'pos' => $s->pos_location]),
                'letter_types' => LetterType::query()->where('is_active', true)->get(['id', 'kode_surat', 'nama_surat']),
            ],
            'meta' => ['server_time' => now()->toIso8601String()],
        ]);
    }
}
