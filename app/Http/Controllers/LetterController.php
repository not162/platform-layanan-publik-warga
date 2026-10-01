<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterRequest;
use App\Http\Requests\UpdateLetterRequest;
use App\Http\Resources\V1\LetterResource;
use App\Models\Letter;
use App\Services\LetterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class LetterController extends Controller
{
    public function __construct(protected LetterService $letterService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $citizenId = $request->user()?->citizen?->id ?? 0;
        $letters = Letter::query()->where('citizen_id', $citizenId)->latest()->paginate(15);

        return LetterResource::collection($letters);
    }

    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        abort_if(
            ! $request->user()?->hasPermission('letter.verify') &&
            ! $request->user()?->hasPermission('letter.approve') &&
            ! $request->user()?->isSuperadmin(),
            Response::HTTP_FORBIDDEN,
            'Akses ditolak.'
        );

        $letters = Letter::query()->with('citizen.user')->latest()->paginate(15);

        return LetterResource::collection($letters);
    }

    public function store(StoreLetterRequest $request): JsonResponse
    {
        $citizen = $request->user()?->citizen;
        if (! $citizen) {
            abort(Response::HTTP_FORBIDDEN, 'Profil warga diperlukan untuk mengajukan surat.');
        }

        $data = $request->validated();
        $data['citizen_id'] = $citizen->id;

        $letter = $this->letterService->create($data, $request->user());

        return (new LetterResource($letter))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id): LetterResource
    {
        $letter = Letter::query()->findOrFail($id);

        $user = $request->user();
        $isOwner = $letter->citizen_id === ($user?->citizen?->id ?? null);
        $hasStaffAccess = $user?->hasPermission('letter.verify') || $user?->hasPermission('letter.approve') || $user?->isSuperadmin();

        if (! $isOwner && ! $hasStaffAccess) {
            abort(Response::HTTP_FORBIDDEN, 'Akses surat ini ditolak.');
        }

        $letter->load('citizen.user');

        return new LetterResource($letter);
    }

    public function update(UpdateLetterRequest $request, string $id): LetterResource
    {
        $letter = Letter::query()->findOrFail($id);
        $data = $request->validated();
        $user = $request->user();

        $newStatus = $data['status'];
        $currentStatus = $letter->status;

        if ($user->isWarga()) {
            if ($letter->citizen_id !== ($user->citizen->id ?? null)) {
                abort(Response::HTTP_FORBIDDEN, 'Anda tidak berwenang memperbarui surat ini.');
            }
            if ($currentStatus !== 'draft' || $newStatus !== 'submitted') {
                abort(Response::HTTP_CONFLICT, 'Warga hanya dapat mengajukan surat berstatus draft.');
            }
        } elseif ($user->hasPermission('letter.approve') && in_array($newStatus, ['approved', 'rejected'], true)) {
            if ($currentStatus !== 'verified') {
                abort(Response::HTTP_CONFLICT, 'Hanya surat terverifikasi yang dapat disetujui.');
            }
        } elseif ($user->hasPermission('letter.verify') && in_array($newStatus, ['verified', 'rejected'], true)) {
            if ($currentStatus !== 'submitted') {
                abort(Response::HTTP_CONFLICT, 'Hanya surat diajukan yang dapat diverifikasi.');
            }
        } elseif ($user->isSuperadmin()) {
            // Superadmin can transition as needed
        } else {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses status ini.');
        }

        $letter = $this->letterService->updateStatus($letter, $data['status'], $data);

        return new LetterResource($letter);
    }

    public function adminVerify(Request $request, string $id): LetterResource
    {
        $request->validate([
            'version' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $letter = Letter::query()->findOrFail($id);
        $verified = $this->letterService->verify($letter, $request->user(), (int) $request->input('version'), $request->input('notes'));

        return new LetterResource($verified);
    }

    public function adminApprove(Request $request, string $id): LetterResource
    {
        $request->validate([
            'version' => ['required', 'integer'],
            'letter_number' => ['nullable', 'string', 'max:100'],
        ]);

        $letter = Letter::query()->findOrFail($id);
        $approved = $this->letterService->approve($letter, $request->user(), (int) $request->input('version'), $request->input('letter_number'));

        return new LetterResource($approved);
    }

    public function adminReject(Request $request, string $id): LetterResource
    {
        $request->validate([
            'version' => ['required', 'integer'],
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $letter = Letter::query()->findOrFail($id);
        $rejected = $this->letterService->reject($letter, $request->user(), (int) $request->input('version'), $request->input('rejection_reason'));

        return new LetterResource($rejected);
    }

    public function verifyPublic(string $token): JsonResponse
    {
        $letter = $this->letterService->findByToken($token);

        if (! $letter) {
            return response()->json([
                'valid' => false,
                'message' => 'Dokumen surat tidak ditemukan atau belum disahkan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'valid' => true,
            'data' => [
                'letter_number' => $letter->letter_number,
                'type' => $letter->type,
                'status' => $letter->status,
                'recipient_name' => $letter->citizen?->full_name,
                'approved_at' => $letter->approved_at?->toIso8601String(),
                'issued_by' => 'Pengurus RT 01',
            ],
            'message' => 'Dokumen sah dan terverifikasi secara resmi.',
        ]);
    }

    public function trackPublic(string $ticket): JsonResponse
    {
        $letter = $this->letterService->findByTicket($ticket);

        if (! $letter) {
            return response()->json([
                'found' => false,
                'message' => 'Nomor tiket surat tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'found' => true,
            'data' => [
                'ticket_number' => $letter->ticket_number,
                'type' => $letter->type,
                'status' => $letter->status,
                'created_at' => $letter->created_at?->toIso8601String(),
                'verified_at' => $letter->verified_at?->toIso8601String(),
                'approved_at' => $letter->approved_at?->toIso8601String(),
            ],
            'message' => 'Informasi pelacakan surat berhasil dimuat.',
        ]);
    }
}
