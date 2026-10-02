<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Http\Resources\V1\AnnouncementResource;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AnnouncementController extends Controller
{
    public function __construct(protected AnnouncementService $announcementService) {}

    public function index(): AnonymousResourceCollection
    {
        $announcements = Announcement::query()->latest()->paginate(15);

        return AnnouncementResource::collection($announcements);
    }

    public function publicIndex(): AnonymousResourceCollection
    {
        $announcements = Announcement::query()->where('is_published', true)->latest('published_at')->paginate(15);

        return AnnouncementResource::collection($announcements);
    }

    public function store(StoreAnnouncementRequest $request): AnnouncementResource
    {
        $data = $request->validated();
        if (! empty($data['is_published'])) {
            $data['published_at'] = now();
        }

        $announcement = $this->announcementService->create($data);

        return new AnnouncementResource($announcement);
    }

    public function show(string|int $id): AnnouncementResource
    {
        $announcement = Announcement::query()->findOrFail($id);

        return new AnnouncementResource($announcement);
    }

    public function update(UpdateAnnouncementRequest $request, string|int $id): AnnouncementResource
    {
        $announcement = Announcement::query()->findOrFail($id);

        $announcement = $this->announcementService->update($announcement, $request->validated());

        return new AnnouncementResource($announcement);
    }

    public function destroy(string|int $id): JsonResponse
    {
        $announcement = Announcement::query()->findOrFail($id);
        $this->announcementService->delete($announcement);

        return response()->json(['message' => 'Pengumuman berhasil dihapus.'], 200);
    }
}
