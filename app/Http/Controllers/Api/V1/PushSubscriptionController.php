<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePushSubscriptionRequest;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $endpoint = $request->validated('endpoint');
        $endpointHash = hash('sha256', $endpoint);

        $subscription = PushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => $endpointHash],
            [
                'user_id' => $request->user()?->id,
                'endpoint' => $endpoint,
                'public_key' => $request->validated('public_key'),
                'auth_token' => $request->validated('auth_token'),
                'content_encoding' => $request->validated('content_encoding', 'aes128gcm'),
            ]
        );

        return response()->json([
            'message' => 'Langganan notifikasi berhasil didaftarkan.',
            'data' => [
                'id' => $subscription->id,
                'endpoint_hash' => $subscription->endpoint_hash,
            ],
        ], 201);
    }
}
