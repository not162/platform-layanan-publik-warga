<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\SecurityReportResource;
use App\Models\SecurityReport;
use App\Services\DocumentGeneratorService;
use App\Services\SecurityReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SecurityReportController extends Controller
{
    public function __construct(
        protected SecurityReportService $securityService,
        protected DocumentGeneratorService $documentGeneratorService
    ) {}

    /**
     * Warga: List own security reports.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;
        $reports = SecurityReport::query()
            ->with(['reporter', 'assignee'])
            ->where('reporter_user_id', $userId)
            ->latest()
            ->paginate(15);

        return SecurityReportResource::collection($reports);
    }

    /**
     * Warga: Create a new security report.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:50'],
            'severity' => ['required', 'string', 'in:low,medium,high,emergency'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'incident_at' => ['required', 'date'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);

        $report = $this->securityService->create($validated, $request->user());
        $report->loadMissing(['reporter', 'assignee']);

        return (new SecurityReportResource($report))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show detail of a security report.
     */
    public function show(Request $request, string $id): SecurityReportResource
    {
        $report = SecurityReport::query()->with(['reporter', 'assignee'])->findOrFail($id);

        $user = $request->user();
        $isOwner = $report->reporter_user_id === $user->id;
        $isStaff = $user->hasPermission('security.read') || $user->hasPermission('security.manage') || $user->isSuperadmin();

        if (! $isOwner && ! $isStaff) {
            abort(Response::HTTP_FORBIDDEN, 'Akses terhadap laporan keamanan ini ditolak.');
        }

        return new SecurityReportResource($report);
    }

    /**
     * Admin / Petugas Keamanan: List all reports.
     */
    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        abort_if(
            ! $request->user()->hasPermission('security.read') &&
            ! $request->user()->hasPermission('security.manage') &&
            ! $request->user()->isSuperadmin(),
            Response::HTTP_FORBIDDEN,
            'Akses ditolak: Membutuhkan izin pemantauan keamanan (security.read).'
        );

        $reports = SecurityReport::query()
            ->with(['reporter', 'assignee'])
            ->latest()
            ->paginate(20);

        return SecurityReportResource::collection($reports);
    }

    /**
     * Admin / Petugas Keamanan: Update status, assign officer, or add resolution.
     */
    public function adminUpdate(Request $request, string $id): SecurityReportResource
    {
        abort_if(
            ! $request->user()->hasPermission('security.manage') && ! $request->user()->isSuperadmin(),
            Response::HTTP_FORBIDDEN,
            'Akses ditolak: Membutuhkan izin pengelolaan keamanan (security.manage).'
        );

        $validated = $request->validate([
            'version' => ['required', 'integer'],
            'status' => ['nullable', 'string', 'in:submitted,reviewed,assigned,processing,resolved,closed,rejected'],
            'resolution' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $report = SecurityReport::query()->findOrFail($id);
        $user = $request->user();
        $version = (int) $validated['version'];

        if (isset($validated['assigned_to'])) {
            $report = $this->securityService->assign($report, $user, $version, (int) $validated['assigned_to']);
            $version = $report->version;
        }

        if (isset($validated['status'])) {
            $report = $this->securityService->updateStatus(
                $report,
                $user,
                $version,
                $validated['status'],
                $validated['resolution'] ?? null
            );
        }

        $report->loadMissing(['reporter', 'assignee']);

        return new SecurityReportResource($report);
    }

    /**
     * Download or view printable security report document.
     */
    public function downloadDocument(Request $request, string $id)
    {
        $report = SecurityReport::query()->findOrFail($id);

        $user = $request->user();
        $isOwner = $report->reporter_user_id === $user->id;
        $isStaff = $user->hasPermission('security.read') || $user->hasPermission('security.manage') || $user->isSuperadmin();

        if (! $isOwner && ! $isStaff) {
            abort(Response::HTTP_FORBIDDEN, 'Akses ditolak.');
        }

        $filePath = $this->documentGeneratorService->generateSecurityReportDocument($report);
        $content = Storage::disk('local')->get($filePath);

        return response($content, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="Laporan_Keamanan_'.$report->ticket_number.'.html"',
        ]);
    }
}
