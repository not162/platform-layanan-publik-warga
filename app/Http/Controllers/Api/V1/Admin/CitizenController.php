<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\StoreCitizenRequest;
use App\Http\Requests\Citizen\UpdateCitizenRequest;
use App\Http\Resources\V1\CitizenResource;
use App\Models\Citizen;
use App\Services\CitizenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CitizenController extends Controller
{
    public function __construct(protected CitizenService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_if(! $request->user()?->hasPermission('citizen.manage'), Response::HTTP_FORBIDDEN, 'Akses ditolak.');

        $query = Citizen::query()->with(['familyCard', 'user']);

        if ($search = $request->query('q')) {
            $query->where('full_name', 'like', "%{$search}%");
        }

        $citizens = $query->latest()->paginate(20);

        return CitizenResource::collection($citizens);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCitizenRequest $request): JsonResponse
    {
        $citizen = $this->service->create($request->validated());

        return (new CitizenResource($citizen))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): CitizenResource
    {
        abort_if(! $request->user()?->hasPermission('citizen.manage'), Response::HTTP_FORBIDDEN, 'Akses ditolak.');

        $citizen = Citizen::with(['familyCard', 'user'])->findOrFail($id);

        return new CitizenResource($citizen);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCitizenRequest $request, string $id): CitizenResource
    {
        $citizen = Citizen::findOrFail($id);
        $citizen = $this->service->update($citizen, $request->validated());

        return new CitizenResource($citizen);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): Response
    {
        abort_if(! $request->user()?->hasPermission('citizen.manage'), Response::HTTP_FORBIDDEN, 'Akses ditolak.');

        $citizen = Citizen::findOrFail($id);
        $this->service->delete($citizen);

        return response()->noContent();
    }
}
