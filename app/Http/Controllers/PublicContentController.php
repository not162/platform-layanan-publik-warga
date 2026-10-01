<?php

namespace App\Http\Controllers;

use App\Http\Resources\V1\AnnouncementResource;
use App\Http\Resources\V1\CommunityEventResource;
use App\Http\Resources\V1\EmergencyContactResource;
use App\Http\Resources\V1\RoundScheduleResource;
use App\Http\Resources\V1\RtOfficerResource;
use App\Models\Announcement;
use App\Models\CommunityEvent;
use App\Models\EmergencyContact;
use App\Models\RoundSchedule;
use App\Models\RtOfficer;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicContentController extends Controller
{
    public function announcements(Request $request): AnonymousResourceCollection
    {
        $query = Announcement::query()
            ->where('is_published', true);

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $announcements = $query
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->paginate(10);

        return AnnouncementResource::collection($announcements);
    }

    public function events(): AnonymousResourceCollection
    {
        $events = CommunityEvent::query()
            ->where('is_published', true)
            ->where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->paginate(10);

        return CommunityEventResource::collection($events);
    }

    public function officers(): AnonymousResourceCollection
    {
        $officers = RtOfficer::query()
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        return RtOfficerResource::collection($officers);
    }

    public function emergencyContacts(): AnonymousResourceCollection
    {
        $contacts = EmergencyContact::query()
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();

        return EmergencyContactResource::collection($contacts);
    }

    public function roundSchedules(): AnonymousResourceCollection
    {
        $schedules = RoundSchedule::query()
            ->where('is_active', true)
            ->get();

        return RoundScheduleResource::collection($schedules);
    }

    public function portalOverview(FinanceService $financeService): JsonResponse
    {
        $latestAnnouncements = Announcement::query()
            ->where('is_published', true)
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->limit(3)
            ->get();

        $upcomingEvents = CommunityEvent::query()
            ->where('is_published', true)
            ->where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->limit(3)
            ->get();

        $officers = RtOfficer::query()
            ->where('is_active', true)
            ->orderBy('order_index')
            ->limit(6)
            ->get();

        $emergencyContacts = EmergencyContact::query()
            ->where('is_active', true)
            ->orderBy('order_index')
            ->limit(6)
            ->get();

        $financeSummary = $financeService->getPublicSummary();

        return response()->json([
            'data' => [
                'announcements' => AnnouncementResource::collection($latestAnnouncements),
                'upcoming_events' => CommunityEventResource::collection($upcomingEvents),
                'officers' => RtOfficerResource::collection($officers),
                'emergency_contacts' => EmergencyContactResource::collection($emergencyContacts),
                'finance' => [
                    'total_income' => $financeSummary['total_income'],
                    'total_expense' => $financeSummary['total_expense'],
                    'net_balance' => $financeSummary['net_balance'],
                ],
            ],
        ]);
    }
}
