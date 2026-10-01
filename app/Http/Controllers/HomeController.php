<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\CommunityEvent;
use App\Models\EmergencyContact;
use App\Models\RoundSchedule;
use App\Models\RtOfficer;
use App\Services\FinanceService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(protected FinanceService $financeService) {}

    public function __invoke(): View
    {
        $financeSummary = $this->financeService->getPublicSummary();

        $announcements = Announcement::query()
            ->where('is_published', true)
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->limit(4)
            ->get();

        $upcomingEvents = CommunityEvent::query()
            ->where('is_published', true)
            ->where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->limit(3)
            ->get();

        $officers = RtOfficer::query()
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        $emergencyContacts = EmergencyContact::query()
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        $roundSchedules = RoundSchedule::query()
            ->where('is_active', true)
            ->get();

        return view('welcome', compact(
            'financeSummary',
            'announcements',
            'upcomingEvents',
            'officers',
            'emergencyContacts',
            'roundSchedules'
        ));
    }
}
