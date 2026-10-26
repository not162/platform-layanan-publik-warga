<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = $user->notifications()
            ->latest()
            ->limit(min((int) $request->input('limit', 30), 100))
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notifikasi',
                'body' => $n->data['body'] ?? '',
                'url' => $n->data['url'] ?? null,
                'level' => $n->data['level'] ?? 'info',
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        return response()->json([
            'data' => $items,
            'meta' => [
                'unread_count' => $user->unreadNotifications()->count(),
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    public function read(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->whereKey($id)->update(['read_at' => now()]);

        return response()->json(['message' => 'Ditandai sudah dibaca.']);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->whereKey($id)->delete();

        return response()->json(['message' => 'Notifikasi dihapus.']);
    }

    public function clear(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json(['message' => 'Semua notifikasi dihapus.']);
    }
}
