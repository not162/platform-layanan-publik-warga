<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\UpdateComplaintRequest;
use App\Http\Resources\V1\ComplaintResource;
use App\Models\Complaint;
use App\Services\ComplaintService;

class ComplaintController extends Controller
{
    public function __construct(protected ComplaintService $complaintService) {}

    public function index()
    {
        $complaints = Complaint::where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return ComplaintResource::collection($complaints);
    }

    public function adminIndex()
    {
        $complaints = Complaint::with('user')->latest()->paginate(15);

        return ComplaintResource::collection($complaints);
    }

    public function store(StoreComplaintRequest $request)
    {
        $data = $request->validated();

        // If not anonymous and logged in, set user_id
        if (empty($data['is_anonymous']) && auth()->check()) {
            $data['user_id'] = auth()->id();
        }

        $complaint = $this->complaintService->create($data);

        return new ComplaintResource($complaint);
    }

    public function update(UpdateComplaintRequest $request, $id)
    {
        $complaint = Complaint::findOrFail($id);
        $data = $request->validated();

        $complaint = $this->complaintService->updateStatus($complaint, $data['status'], $data);

        return new ComplaintResource($complaint);
    }
}
