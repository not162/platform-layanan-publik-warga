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

    /**
     * Approve and verify a pending new citizen application.
     */
    public function verifyCitizen(Request $request, string $id): JsonResponse
    {
        abort_if(! $request->user()?->hasPermission('citizen.manage') && ! $request->user()?->isSuperadmin() && ! $request->user()?->isSekretaris() && ! $request->user()?->isKetuaRt(), Response::HTTP_FORBIDDEN, 'Akses verifikasi warga ditolak.');

        $citizen = Citizen::findOrFail($id);
        $citizen->update([
            'status_warga' => 'tetap',
            'verification_notes' => 'Telah diverifikasi dan disahkan oleh pengurus RT: '.($request->user()?->name ?? 'Admin RT'),
        ]);

        return response()->json([
            'message' => "Data warga {$citizen->full_name} berhasil diverifikasi dan disahkan.",
            'data' => new CitizenResource($citizen),
        ]);
    }

    /**
     * Reject a pending citizen application.
     */
    public function rejectCitizen(Request $request, string $id): JsonResponse
    {
        abort_if(! $request->user()?->hasPermission('citizen.manage') && ! $request->user()?->isSuperadmin() && ! $request->user()?->isSekretaris() && ! $request->user()?->isKetuaRt(), Response::HTTP_FORBIDDEN, 'Akses penolakan warga ditolak.');

        $citizen = Citizen::findOrFail($id);
        $reason = $request->input('reason', 'Berkas identitas tidak valid atau bukan merupakan warga lingkungan RT 01.');
        $citizen->update([
            'status_warga' => 'ditolak',
            'verification_notes' => 'Ditolak: '.$reason,
        ]);

        return response()->json([
            'message' => "Pengajuan warga {$citizen->full_name} telah ditolak.",
            'data' => new CitizenResource($citizen),
        ]);
    }
}
