<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\EmergencyContact;
use App\Models\FamilyCard;
use App\Models\FinanceTransaction;
use App\Models\FinancialReport;
use App\Models\Letter;
use App\Models\RoundSchedule;
use App\Models\RtOfficer;
use App\Models\SecurityReport;
use App\Models\User;
use App\Services\FinanceService;
use App\Services\LetterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected FinanceService $financeService,
        protected LetterService $letterService
    ) {}

    public function index(Request $request): View
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return view('auth.login');
        }

        $officer = RtOfficer::query()
            ->where('name', $user->name)
            ->first();

        // 1. KETUA RT: Fokus pengesahan surat, pengesahan laporan kuartal, pengawasan umum
        if ($user->isKetuaRt()) {
            $pendingApprovalLetters = Letter::query()
                ->whereIn('status', ['submitted', 'verified'], 'and', false)
                ->with(['citizen', 'letterType'])
                ->latest()
                ->get();

            $pendingApprovalCount = $pendingApprovalLetters->count('*');
            $approvedLettersCount = Letter::query()->whereIn('status', ['approved', 'completed'], 'and', false)->count('*');
            $activeComplaintsCount = Complaint::query()->whereIn('status', ['submitted', 'in_progress', 'reviewed'], 'and', false)->count('*');
            $securityReportsCount = SecurityReport::query()->whereIn('status', ['submitted', 'reviewed', 'assigned', 'processing'], 'and', false)->count('*');
            $citizensCount = Citizen::count('*');
            $financeSummary = $this->financeService->getPublicSummary();
            $quarterlyReports = FinancialReport::query()->latest()->limit(5)->get();

            return view('dashboard.ketua_rt', compact(
                'user',
                'officer',
                'pendingApprovalLetters',
                'pendingApprovalCount',
                'approvedLettersCount',
                'activeComplaintsCount',
                'securityReportsCount',
                'citizensCount',
                'financeSummary',
                'quarterlyReports'
            ));
        }

        // 2. SEKRETARIS: Fokus verifikasi berkas surat, manajemen kependudukan, pengumuman
        if ($user->isSekretaris()) {
            $verificationQueue = Letter::query()
                ->where('status', '=', 'submitted', 'and')
                ->with(['citizen', 'letterType'])
                ->latest()
                ->get();

            $verifiedLetters = Letter::query()
                ->whereIn('status', ['verified', 'approved', 'completed'], 'and', false)
                ->with(['citizen', 'letterType'])
                ->latest()
                ->limit(10)
                ->get();

            $submittedCount = $verificationQueue->count('*');
            $verifiedCount = Letter::query()->where('status', '=', 'verified', 'and')->count('*');
            $citizensCount = Citizen::count('*');
            $familyCardsCount = FamilyCard::count('*');
            $totalLettersCount = Letter::count('*');

            return view('dashboard.sekretaris', compact(
                'user',
                'officer',
                'verificationQueue',
                'verifiedLetters',
                'submittedCount',
                'verifiedCount',
                'citizensCount',
                'familyCardsCount',
                'totalLettersCount'
            ));
        }

        // 3. BENDAHARA: Fokus pembukuan kas, iuran warga, belanja belanja, backup store
        if ($user->isBendahara()) {
            $financeSummary = $this->financeService->getPublicSummary();
            $recentTransactions = FinanceTransaction::query()->latest('transaction_date')->limit(12)->get();
            $quarterlyReports = FinancialReport::query()->latest()->limit(5)->get();

            return view('dashboard.bendahara', compact(
                'user',
                'officer',
                'financeSummary',
                'recentTransactions',
                'quarterlyReports'
            ));
        }

        // 4. PETUGAS KEAMANAN: Fokus insiden keamanan, jadwal ronda pos kamling, kontak darurat
        if ($user->isPetugasKeamanan()) {
            $activeReports = SecurityReport::query()
                ->whereIn('status', ['submitted', 'reviewed', 'assigned', 'processing'], 'and', false)
                ->latest()
                ->get();

            $resolvedReports = SecurityReport::query()
                ->where('status', '=', 'resolved', 'and')
                ->latest()
                ->limit(5)
                ->get();

            $roundSchedules = RoundSchedule::query()->where('is_active', '=', true, 'and')->get();
            $emergencyContacts = EmergencyContact::query()->where('is_active', '=', true, 'and')->orderBy('order_index')->get();

            return view('dashboard.keamanan', compact(
                'user',
                'officer',
                'activeReports',
                'resolvedReports',
                'roundSchedules',
                'emergencyContacts'
            ));
        }

        // 5. SUPERADMIN / ADMIN: Konsol administratif menyeluruh
        if ($user->isSuperadmin() || $user->isAdmin()) {
            $pendingLettersCount = Letter::query()->whereIn('status', ['submitted', 'verified'], 'and', false)->count('*');
            $approvedLettersCount = Letter::query()->whereIn('status', ['approved', 'completed'], 'and', false)->count('*');
            $activeComplaintsCount = Complaint::query()->whereIn('status', ['submitted', 'in_progress', 'reviewed'], 'and', false)->count('*');
            $securityReportsCount = SecurityReport::query()->whereIn('status', ['submitted', 'reviewed', 'assigned', 'processing'], 'and', false)->count('*');
            $citizensCount = Citizen::count('*');
            $financeSummary = $this->financeService->getPublicSummary();

            $recentLetters = Letter::query()->with(['citizen', 'letterType'])->latest()->limit(6)->get();
            $recentComplaints = Complaint::query()->latest()->limit(5)->get();
            $recentSecurityReports = SecurityReport::query()->latest()->limit(5)->get();

            return view('dashboard.admin', compact(
                'user',
                'officer',
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

        // 6. WARGA: Layanan mandiri permohonan surat, iuran kas, dan pelaporan aduan
        $citizenId = $user->citizen?->id;
        $myLetters = Letter::query()->where('citizen_id', '=', $citizenId, 'and')->latest()->limit(10)->get();
        $myLettersCount = Letter::query()->where('citizen_id', '=', $citizenId, 'and')->count('*');
        $myCompletedLettersCount = Letter::query()->where('citizen_id', '=', $citizenId, 'and')->whereIn('status', ['approved', 'completed'], 'and', false)->count('*');
        $myComplaintsCount = Complaint::query()->where('user_id', '=', $user->id, 'and')->count('*');
        $mySecurityReportsCount = SecurityReport::query()->where('reporter_user_id', '=', $user->id, 'and')->count('*');
        $financeSummary = $this->financeService->getPublicSummary();

        return view('dashboard.warga', compact(
            'user',
            'officer',
            'myLetters',
            'myLettersCount',
            'myCompletedLettersCount',
            'myComplaintsCount',
            'mySecurityReportsCount',
            'financeSummary'
        ));
    }

    public function profile(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $officer = RtOfficer::query()
            ->where('name', $user->name)
            ->first();

        return view('dashboard.profile', compact('user', 'officer'));
    }

    public function verifyLetter(Request $request, string $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $letter = Letter::query()->findOrFail($id);

        if (! $user->hasPermission('letter.verify') && ! $user->isSuperadmin()) {
            abort(403, 'Anda tidak memiliki wewenang untuk memverifikasi surat ini.');
        }

        $notes = $request->input('notes');
        $this->letterService->verify($letter, $user, (int) $letter->version, $notes);

        return back()->with('status', 'Berkas surat berhasil diverifikasi dan diteruskan ke Ketua RT untuk disahkan.');
    }

    public function approveLetter(Request $request, string $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $letter = Letter::query()->findOrFail($id);

        if (! $user->hasPermission('letter.approve') && ! $user->isSuperadmin()) {
            abort(403, 'Hanya Ketua RT atau administrator yang berwenang mengesahkan surat.');
        }

        $this->letterService->approve($letter, $user, (int) $letter->version);

        return back()->with('status', 'Surat berhasil disahkan secara resmi dan siap diunduh oleh warga.');
    }

    public function rejectLetter(Request $request, string $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $letter = Letter::query()->findOrFail($id);

        $reason = trim((string) $request->input('rejection_reason', 'Data persyaratan belum lengkap.'));

        $this->letterService->reject($letter, $user, (int) $letter->version, $reason);

        return back()->with('status', 'Permohonan surat telah ditolak dengan catatan evaluasi.');
    }

    public function approveFinancialReport(Request $request, string $id): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isKetuaRt() && ! $user->isSuperadmin()) {
            abort(403, 'Hanya Ketua RT atau Superadmin yang berwenang mengesahkan laporan keuangan triwulan.');
        }

        $report = FinancialReport::query()->findOrFail($id);

        if ($report->status === 'published') {
            return back()->with('status', 'Laporan keuangan ini sudah disahkan sebelumnya.');
        }

        $report->status = 'published';
        $report->published_at = now();
        $report->published_by = $user->id;
        $report->version = $report->version + 1;
        $report->save();

        return back()->with('status', "Laporan Keuangan Kuartal {$report->quarter} Tahun {$report->year} berhasil disahkan secara resmi.");
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $isKepengurusan = $user->isKetuaRt() || $user->isSekretaris() || $user->isBendahara() || $user->isPetugasKeamanan() || $user->isAdmin() || $user->isSuperadmin();

        if (! $isKepengurusan) {
            abort(403, 'Akses edit foto profil ini khusus untuk anggota kepengurusan RT.');
        }

        $request->validate([
            'photo' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,gif,svg,webp,bmp,ico,tiff,tif,heic,heif,avif',
                'max:10240',
            ],
        ], [
            'photo.required' => 'Berkas foto wajib dipilih.',
            'photo.file' => 'Berkas harus berupa file gambar.',
            'photo.mimes' => 'Format berkas foto tidak didukung. Harap pilih gambar yang valid.',
            'photo.max' => 'Ukuran foto maksimal adalah 10 MB.',
        ]);

        $file = $request->file('photo');
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $fileName = 'officer_'.$user->id.'_'.time().'.'.$extension;
        $path = $file->storeAs('profiles', $fileName, 'public');
        $avatarUrl = Storage::url($path);

        $user->avatar_url = $avatarUrl;
        $user->save();

        $officer = RtOfficer::query()->where('name', '=', $user->name, 'and')->first();
        if ($officer) {
            $officer->photo_url = $avatarUrl;
            $officer->save();
        }

        return back()->with('status', 'Foto profil resmi kepengurusan berhasil diperbarui.');
    }
}
