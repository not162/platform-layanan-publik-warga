<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\UpdateComplaintRequest;
use App\Http\Resources\V1\ComplaintResource;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ComplaintController extends Controller
{
    public function __construct(protected ComplaintService $complaintService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $complaints = Complaint::query()
            ->where('user_id', $request->user()?->id)
            ->latest()
            ->paginate(15);

        return ComplaintResource::collection($complaints);
    }

    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        abort_if(
            ! $request->user()?->hasPermission('complaint.manage') && ! $request->user()?->isSuperadmin(),
            Response::HTTP_FORBIDDEN,
            'Akses ditolak.'
        );

        $query = Complaint::query()->with('user');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $complaints = $query->latest()->paginate(15);

        return ComplaintResource::collection($complaints);
    }

    public function show(Request $request, string $id): ComplaintResource
    {
        $complaint = Complaint::query()->findOrFail($id);
        $user = $request->user();

        $isOwner = $complaint->user_id !== null && $complaint->user_id === $user?->id;
        $isStaff = $user?->hasPermission('complaint.manage') || $user?->isSuperadmin();

        if (! $isOwner && ! $isStaff) {
            abort(Response::HTTP_FORBIDDEN, 'Akses ke pengaduan ini ditolak.');
        }

        if (! $complaint->is_anonymous) {
            $complaint->load('user');
        }

        return new ComplaintResource($complaint);
    }

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $user = $request->user();
        $hasImage = $request->hasFile('image') || $request->hasFile('attachment');

        // Security check: Only verified citizens (or superadmin) can upload camera/image proof
        if ($hasImage) {
            if (! $user || (! $user->isWarga() && ! $user->isSuperadmin())) {
                abort(Response::HTTP_FORBIDDEN, 'Akses upload gambar/kamera hanya diizinkan untuk akun warga yang terverifikasi.');
            }
        }

        $data = $request->validated();

        // Format detailed street and location info
        $street = $request->input('street_name');
        $detail = $request->input('location_detail');
        if ($street || $detail) {
            $prefix = $street ? "Jalan/Gang: {$street}" : '';
            $suffix = $detail ? " (Bagian/Posisi: {$detail})" : '';
            $data['lokasi'] = trim($prefix.$suffix);
        }

        if (empty($data['kategori']) && ! empty($data['category'])) {
            $data['kategori'] = $data['category'];
        }

        // Store camera image to private storage
        if ($hasImage) {
            $file = $request->file('image') ?? $request->file('attachment');
            $path = $file->store('private/complaints', 'local');
            $data['attachment_path'] = $path;
        }

        $complaint = $this->complaintService->create($data, $user);

        return (new ComplaintResource($complaint))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateComplaintRequest $request, string $id): ComplaintResource
    {
        $complaint = Complaint::query()->findOrFail($id);
        $data = $request->validated();

        $complaint = $this->complaintService->updateStatus(
            complaint: $complaint,
            status: $data['status'],
            data: $data,
            admin: $request->user()
        );

        return new ComplaintResource($complaint);
    }
}
