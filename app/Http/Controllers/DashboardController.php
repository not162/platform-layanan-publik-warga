<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\Letter;
use App\Models\SecurityReport;
use App\Models\User;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected FinanceService $financeService) {}

    public function index(Request $request): View
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            // Fallback for Sanctum API token or guest
            return view('auth.login');
        }

        $isStaff = $user->isSuperadmin() || $user->isAdmin() || $user->isKetuaRt() || $user->isSekretaris() || $user->isBendahara() || $user->isPetugasKeamanan();

        if ($isStaff) {
            $pendingLettersCount = Letter::query()->where(function ($q) {
                $q->where('status', 'submitted')->orWhere('status', 'verified');
            })->count();

            $approvedLettersCount = Letter::query()->where(function ($q) {
                $q->where('status', 'approved')->orWhere('status', 'completed');
            })->count();

            $activeComplaintsCount = Complaint::query()->where(function ($q) {
                $q->where('status', 'submitted')->orWhere('status', 'in_progress')->orWhere('status', 'reviewed');
            })->count();

            $securityReportsCount = SecurityReport::query()->where(function ($q) {
                $q->where('status', 'submitted')->orWhere('status', 'reviewed')->orWhere('status', 'assigned')->orWhere('status', 'processing');
            })->count();

            $citizensCount = Citizen::all()->count();
            $financeSummary = $this->financeService->getPublicSummary();

            $recentLetters = Letter::query()
                ->with(['citizen', 'letterType'])
                ->latest()
                ->limit(6)
                ->get();

            $recentComplaints = Complaint::query()
                ->latest()
                ->limit(5)
                ->get();

            $recentSecurityReports = SecurityReport::query()
                ->latest()
                ->limit(5)
                ->get();

            return view('dashboard.admin', compact(
                'user',
                'pendingLettersCount',
                'approvedLettersCount',
                'activeComplaintsCount',
                'securityReportsCount',
                'citizensCount',
                'financeSummary',
                'recentLetters',
                'recentComplaints',
                'recentSecurityReports'
            ));
        }

        // Dashboard for WARGA
        $citizenId = $user->citizen?->id;
        $myLetters = Letter::query()
            ->where('citizen_id', $citizenId)
            ->latest()
            ->limit(10)
            ->get();

        $myLettersCount = Letter::query()->where('citizen_id', $citizenId)->count();
        $myCompletedLettersCount = Letter::query()->where('citizen_id', $citizenId)->whereIn('status', ['approved', 'completed'])->count();
        $myComplaintsCount = Complaint::query()->where('user_id', $user->id)->count();
        $mySecurityReportsCount = SecurityReport::query()->where('reporter_user_id', $user->id)->count();
        $financeSummary = $this->financeService->getPublicSummary();

        return view('dashboard.warga', compact(
            'user',
            'myLetters',
            'myLettersCount',
            'myCompletedLettersCount',
            'myComplaintsCount',
            'mySecurityReportsCount',
            'financeSummary'
        ));
    }
}
