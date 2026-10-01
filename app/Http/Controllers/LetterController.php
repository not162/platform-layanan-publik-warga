<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterRequest;
use App\Http\Requests\UpdateLetterRequest;
use App\Http\Resources\V1\LetterResource;
use App\Models\Letter;
use App\Models\LetterType;
use App\Services\DocumentGeneratorService;
use App\Services\LetterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class LetterController extends Controller
{
    public function __construct(
        protected LetterService $letterService,
        protected DocumentGeneratorService $documentGeneratorService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $citizenId = $request->user()?->citizen?->id ?? 0;
        $letters = Letter::query()
            ->with(['letterType', 'attachments'])
            ->where('citizen_id', $citizenId)
            ->latest()
            ->paginate(15);

        return LetterResource::collection($letters);
    }

    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        abort_if(
            ! $request->user()?->hasPermission('letter.read') &&
            ! $request->user()?->hasPermission('letter.verify') &&
            ! $request->user()?->hasPermission('letter.approve') &&
            ! $request->user()?->isSuperadmin(),
            Response::HTTP_FORBIDDEN,
            'Akses ditolak.'
        );

        $letters = Letter::query()->with(['citizen.user', 'letterType', 'attachments'])->latest()->paginate(15);

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

        // Resolve letter type if code or id is provided
        if ($request->filled('letter_type_code')) {
            $letterType = LetterType::query()->where('kode_surat', $request->input('letter_type_code'))->first();
            if ($letterType) {
                $data['jenis_surat_id'] = $letterType->id;
                $data['type'] = $data['type'] ?? $letterType->nama_surat;
            }
        } elseif ($request->filled('jenis_surat_id')) {
            $letterType = LetterType::query()->find($request->input('jenis_surat_id'));
            if ($letterType) {
                $data['type'] = $data['type'] ?? $letterType->nama_surat;
            }
        }

        $data['keperluan'] = $data['keperluan'] ?? ($data['purpose'] ?? null);

        $letter = $this->letterService->create($data, $request->user());

        // Process actual multipart uploads safely to private storage
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('private/letter-attachments/'.$letter->id, 'local');
                $letter->attachments()->create([
                    'judul_lampiran' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $letter->loadMissing(['letterType', 'attachments']);

        return (new LetterResource($letter))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id): LetterResource
    {
        $letter = Letter::query()->with(['citizen.user', 'letterType', 'attachments'])->findOrFail($id);

        $user = $request->user();
        $isOwner = $letter->citizen_id === ($user?->citizen?->id ?? null);
        $hasStaffAccess = $user?->hasPermission('letter.read') || $user?->hasPermission('letter.verify') || $user?->hasPermission('letter.approve') || $user?->isSuperadmin();

        if (! $isOwner && ! $hasStaffAccess) {
            abort(Response::HTTP_FORBIDDEN, 'Akses surat ini ditolak.');
        }

        return new LetterResource($letter);
    }

    /**
     * Warga action: submit draft letter.
     */
    public function submit(Request $request, string $id): LetterResource
    {
        $letter = Letter::query()->findOrFail($id);
        $user = $request->user();

        if ($user->isWarga() && $letter->citizen_id !== ($user->citizen?->id ?? null)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak berwenang mengajukan surat ini.');
        }

        $version = (int) $request->input('version', $letter->version);
        $submitted = $this->letterService->submit($letter, $user, $version);
        $submitted->loadMissing(['citizen.user', 'letterType', 'attachments']);

        return new LetterResource($submitted);
    }

    /**
     * Warga / Admin: Update letter (draft edits or status transitions).
     */
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

        $letter = $this->letterService->updateStatus($letter, $data['status'], $data, $user);
        $letter->loadMissing(['citizen.user', 'letterType', 'attachments']);

        return new LetterResource($letter);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $letter = Letter::query()->findOrFail($id);
        $user = $request->user();

        if ($user->isWarga() && $letter->citizen_id !== ($user->citizen?->id ?? null)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak berwenang menghapus surat ini.');
        }

        if ($letter->status !== 'draft') {
            abort(Response::HTTP_CONFLICT, 'Hanya surat berstatus draf yang dapat dihapus.');
        }

        // Delete physical attachment files
        foreach ($letter->attachments as $attachment) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        $letter->delete();

        return response()->json([
            'message' => 'Draf pengajuan surat berhasil dihapus.',
        ]);
    }

    public function adminVerify(Request $request, string $id): LetterResource
    {
        $request->validate([
            'version' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $letter = Letter::query()->findOrFail($id);
        $verified = $this->letterService->verify($letter, $request->user(), (int) $request->input('version'), $request->input('notes'));
        $verified->loadMissing(['citizen.user', 'letterType', 'attachments']);

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
        $approved->loadMissing(['citizen.user', 'letterType', 'attachments']);

        return new LetterResource($approved);
    }

    public function adminComplete(Request $request, string $id): LetterResource
    {
        $request->validate([
            'version' => ['required', 'integer'],
        ]);

        $letter = Letter::query()->findOrFail($id);
        $completed = $this->letterService->complete($letter, $request->user(), (int) $request->input('version'));
        $completed->loadMissing(['citizen.user', 'letterType', 'attachments']);

        return new LetterResource($completed);
    }

    public function adminReject(Request $request, string $id): LetterResource
    {
        $request->validate([
            'version' => ['required', 'integer'],
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $letter = Letter::query()->findOrFail($id);
        $rejected = $this->letterService->reject($letter, $request->user(), (int) $request->input('version'), $request->input('rejection_reason'));
        $rejected->loadMissing(['citizen.user', 'letterType', 'attachments']);

        return new LetterResource($rejected);
    }

    /**
     * Preview letter document in HTML format.
     */
    public function preview(Request $request, string $id)
    {
        $letter = Letter::query()->findOrFail($id);
        $user = $request->user();

        $isOwner = $letter->citizen_id === ($user?->citizen?->id ?? null);
        $hasStaffAccess = $user?->hasPermission('letter.read') || $user?->hasPermission('letter.verify') || $user?->hasPermission('letter.approve') || $user?->isSuperadmin();

        if (! $isOwner && ! $hasStaffAccess) {
            abort(Response::HTTP_FORBIDDEN, 'Akses tinjau surat ditolak.');
        }

        if (! $letter->file_path || ! Storage::disk('local')->exists($letter->file_path)) {
            $this->documentGeneratorService->generateLetterDocument($letter);
            $letter->refresh();
        }

        $content = Storage::disk('local')->get($letter->file_path);

        return response($content, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * Authorized download of official letter document.
     */
    public function download(Request $request, string $id)
    {
        $letter = Letter::query()->findOrFail($id);
        $user = $request->user();

        $isOwner = $letter->citizen_id === ($user?->citizen?->id ?? null);
        $hasStaffAccess = $user?->hasPermission('letter.read') || $user?->hasPermission('letter.verify') || $user?->hasPermission('letter.approve') || $user?->isSuperadmin();

        if (! $isOwner && ! $hasStaffAccess) {
            abort(Response::HTTP_FORBIDDEN, 'Akses unduh surat ditolak.');
        }

        if (! $letter->file_path || ! Storage::disk('local')->exists($letter->file_path)) {
            $this->documentGeneratorService->generateLetterDocument($letter);
            $letter->refresh();
        }

        $content = Storage::disk('local')->get($letter->file_path);
        $safeNumber = str_replace(['/', '\\'], '_', $letter->letter_number ?: $letter->ticket_number);

        return response($content, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Surat_'.$safeNumber.'.html"',
        ]);
    }

    /**
     * Upload an attachment to a draft letter.
     */
    public function addAttachment(Request $request, string $id): JsonResponse
    {
        $letter = Letter::query()->findOrFail($id);
        $user = $request->user();

        if ($user->isWarga() && $letter->citizen_id !== ($user->citizen?->id ?? null)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak berwenang menambahkan lampiran pada surat ini.');
        }

        if ($letter->status !== 'draft') {
            abort(Response::HTTP_CONFLICT, 'Lampiran hanya dapat ditambahkan pada surat berstatus draf.');
        }

        $request->validate([
            'attachment' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $file = $request->file('attachment');
        $path = $file->store('private/letter-attachments/'.$letter->id, 'local');

        $attachment = $letter->attachments()->create([
            'judul_lampiran' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'data' => $attachment,
            'message' => 'Lampiran berkas berhasil diunggah.',
        ], Response::HTTP_CREATED);
    }

    /**
     * Delete an attachment from a draft letter.
     */
    public function deleteAttachment(Request $request, string $id, string $attachmentId): JsonResponse
    {
        $letter = Letter::query()->findOrFail($id);
        $user = $request->user();

        if ($user->isWarga() && $letter->citizen_id !== ($user->citizen?->id ?? null)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak berwenang menghapus lampiran surat ini.');
        }

        if ($letter->status !== 'draft') {
            abort(Response::HTTP_CONFLICT, 'Lampiran hanya dapat dihapus pada surat berstatus draf.');
        }

        $attachment = $letter->attachments()->findOrFail($attachmentId);
        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json([
            'message' => 'Lampiran berkas berhasil dihapus.',
        ]);
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
