<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterRequest;
use App\Http\Requests\UpdateLetterRequest;
use App\Http\Resources\V1\LetterResource;
use App\Models\Letter;
use App\Services\LetterService;
use Illuminate\Http\Request;

class LetterController extends Controller
{
    public function __construct(protected LetterService $letterService) {}

    public function index(Request $request)
    {
        $citizenId = $request->user()?->citizen?->id ?? 0;
        $letters = Letter::query()->where('citizen_id', $citizenId)->latest()->paginate(15);

        return LetterResource::collection($letters);
    }

    public function adminIndex()
    {
        // Add auth/role check if not in middleware
        $letters = Letter::with('citizen.user')->latest()->paginate(15);

        return LetterResource::collection($letters);
    }

    public function store(StoreLetterRequest $request)
    {
        // Make sure citizen profile exists
        $citizen = $request->user()->citizen;
        if (! $citizen) {
            abort(403, 'Citizen profile required');
        }

        $data = $request->validated();
        $data['citizen_id'] = $citizen->id;

        $letter = $this->letterService->create($data);

        return new LetterResource($letter);
    }

    public function show($id)
    {
        $letter = Letter::findOrFail($id);

        // Authorization: Must be owner or admin with letter permission or superadmin
        $user = request()->user();
        if ($letter->citizen_id !== ($user->citizen->id ?? null) && ! $user->hasPermission('letter.verify') && ! $user->hasPermission('letter.approve') && ! $user->isSuperadmin()) {
            abort(403);
        }

        $letter->load('citizen.user');

        return new LetterResource($letter);
    }

    public function update(UpdateLetterRequest $request, $id)
    {
        $letter = Letter::findOrFail($id);
        $data = $request->validated();
        $user = $request->user();

        // Authorization and state transition validation
        $newStatus = $data['status'];
        $currentStatus = $letter->status;

        if ($user->isWarga()) {
            if ($letter->citizen_id !== ($user->citizen->id ?? null)) {
                abort(403, 'Unauthorized to update this letter');
            }
            if ($currentStatus !== 'draft' || $newStatus !== 'submitted') {
                abort(409, 'Citizens can only submit draft letters');
            }
        } elseif ($user->hasPermission('letter.approve') && in_array($newStatus, ['approved', 'rejected'])) {
            if ($currentStatus !== 'verified') {
                abort(409, 'Only verified letters can be approved or rejected by approval officer');
            }
        } elseif ($user->hasPermission('letter.verify') && in_array($newStatus, ['verified', 'rejected'])) {
            if ($currentStatus !== 'submitted') {
                abort(409, 'Only submitted letters can be verified or rejected');
            }
        } elseif ($user->isSuperadmin()) {
            // Superadmin can transition as needed
        } else {
            abort(403, 'Unauthorized action for your role or permission scope');
        }

        $letter = $this->letterService->updateStatus($letter, $data['status'], $data);

        return new LetterResource($letter);
    }
}
