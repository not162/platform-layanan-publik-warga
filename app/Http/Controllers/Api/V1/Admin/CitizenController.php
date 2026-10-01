<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\StoreCitizenRequest;
use App\Http\Requests\Citizen\UpdateCitizenRequest;
use App\Http\Resources\V1\CitizenResource;
use App\Models\Citizen;
use App\Services\CitizenService;

class CitizenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $citizens = Citizen::with('familyCard', 'user')->paginate(20);

        return CitizenResource::collection($citizens);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCitizenRequest $request, CitizenService $service)
    {
        $citizen = $service->create($request->validated());

        return new CitizenResource($citizen);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $citizen = Citizen::with('familyCard', 'user')->findOrFail($id);

        return new CitizenResource($citizen);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCitizenRequest $request, string $id, CitizenService $service)
    {
        $citizen = Citizen::findOrFail($id);
        $citizen = $service->update($citizen, $request->validated());

        return new CitizenResource($citizen);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $citizen = Citizen::findOrFail($id);
        $citizen->delete();

        return response()->noContent();
    }
}
