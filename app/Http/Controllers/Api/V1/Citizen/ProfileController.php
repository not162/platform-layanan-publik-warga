<?php

namespace App\Http\Controllers\Api\V1\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CitizenResource;
use App\Http\Resources\V1\UserResource;
use App\Models\Citizen;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        // Load the citizen profile if available
        $citizen = Citizen::where('user_id', $user->id)->with('familyCard')->first();

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'citizen' => $citizen ? new CitizenResource($citizen) : null,
            ],
            'meta' => [
                'version' => 1,
                'updated_at' => now()->toIso8601String(),
            ],
            'message' => 'OK',
        ]);
    }
}
