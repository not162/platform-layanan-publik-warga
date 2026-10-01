<?php

namespace App\Http\Controllers\Api\V1\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\UpdateProfileRequest;
use App\Http\Resources\V1\CitizenResource;
use App\Http\Resources\V1\UserResource;
use App\Models\Citizen;
use App\Services\CitizenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(protected CitizenService $citizenService) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // Load the citizen profile and family card
        $citizen = Citizen::query()->where('user_id', $user->id)->with('familyCard')->first();

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'citizen' => $citizen ? new CitizenResource($citizen) : null,
            ],
            'meta' => [
                'version' => $citizen?->version ?? 1,
                'updated_at' => $citizen?->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            ],
            'message' => 'Profil berhasil dimuat.',
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $citizen = Citizen::query()->where('user_id', $user->id)->firstOrFail();

        $data = $request->validated();
        $citizen = $this->citizenService->update($citizen, $data);

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'citizen' => new CitizenResource($citizen),
            ],
            'meta' => [
                'version' => $citizen->version,
                'updated_at' => $citizen->updated_at?->toIso8601String(),
            ],
            'message' => 'Profil warga berhasil diperbarui.',
        ]);
    }
}
