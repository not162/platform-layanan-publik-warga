<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Http\Resources\V1\AnnouncementResource;
use App\Models\Announcement;
use App\Services\AnnouncementService;

class AnnouncementController extends Controller
{
    public function __construct(protected AnnouncementService $announcementService) {}

    public function index()
    {
        $announcements = Announcement::latest()->paginate(15);

        return AnnouncementResource::collection($announcements);
    }

    public function publicIndex()
    {
        $announcements = Announcement::where('is_published', true)->latest('published_at')->paginate(15);

        return AnnouncementResource::collection($announcements);
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $data = $request->validated();
        if (! empty($data['is_published'])) {
            $data['published_at'] = now();
        }

        $announcement = $this->announcementService->create($data);

        return new AnnouncementResource($announcement);
    }

    public function show($id)
    {
        $announcement = Announcement::findOrFail($id);

        return new AnnouncementResource($announcement);
    }

    public function update(UpdateAnnouncementRequest $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $announcement = $this->announcementService->update($announcement, $request->validated());

        return new AnnouncementResource($announcement);
    }

    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $this->announcementService->delete($announcement);

        return response()->json(['message' => 'Pengumuman berhasil dihapus.'], 200);
    }
}
