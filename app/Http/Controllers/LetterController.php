<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
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
        $letters = Letter::where('citizen_id', $request->user()->citizen->id ?? 0)->latest()->paginate(15);

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

        // Authorization: Must be owner or admin/secretary/rt
        $user = request()->user();
        if ($letter->citizen_id !== ($user->citizen->id ?? null) && ! in_array($user->role, [UserRole::Admin, UserRole::Secretary, UserRole::RtHead])) {
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

        $userRole = $user->role instanceof UserRole ? $user->role : UserRole::tryFrom($user->role);

        if ($userRole === UserRole::Citizen) {
            if ($letter->citizen_id !== ($user->citizen->id ?? null)) {
                abort(403, 'Unauthorized to update this letter');
            }
            if ($currentStatus !== 'draft' || $newStatus !== 'submitted') {
                abort(409, 'Citizens can only submit draft letters');
            }
        } elseif ($userRole === UserRole::Secretary) {
            if ($currentStatus !== 'submitted' || ! in_array($newStatus, ['verified', 'rejected'])) {
                abort(409, 'Secretary can only verify or reject submitted letters');
            }
        } elseif ($userRole === UserRole::RtHead) {
            if ($currentStatus !== 'verified' || ! in_array($newStatus, ['approved', 'rejected'])) {
                abort(409, 'RT Head can only approve or reject verified letters');
            }
        } elseif ($userRole === UserRole::Admin) {
            // Admin can do anything or specifically mark approved as completed
        } else {
            abort(403);
        }

        $letter = $this->letterService->updateStatus($letter, $data['status'], $data);

        return new LetterResource($letter);
    }
}
