<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\FamilyCardResource;
use App\Models\FamilyCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class FamilyCardController extends Controller
{
    /**
     * Display a listing of family cards.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_if(
            ! $request->user()?->hasPermission('citizen.read') &&
            ! $request->user()?->hasPermission('citizen.manage') &&
            ! $request->user()?->isSuperadmin(),
            Response::HTTP_FORBIDDEN,
            'Akses ditolak.'
        );

        $query = FamilyCard::query()->withCount('citizens');

        if ($search = $request->query('q')) {
            $searchHash = hash('sha256', $search);
            $query->where('no_kk_hash', $searchHash)
                ->orWhere('address', 'like', "%{$search}%");
        }

        $cards = $query->latest()->paginate(20);

        return FamilyCardResource::collection($cards);
    }

    /**
     * Store a newly created family card.
     */
    public function store(Request $request): JsonResponse
    {
        abort_if(
            ! $request->user()?->hasPermission('citizen.manage') &&
            ! $request->user()?->isSuperadmin(),
            Response::HTTP_FORBIDDEN,
            'Akses pembuatan Kartu Keluarga ditolak.'
        );

        $validated = $request->validate([
            'no_kk' => 'required|string|size:16',
            'address' => 'required|string|max:255',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'village' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
        ], [
            'no_kk.required' => 'Nomor Kartu Keluarga wajib diisi.',
            'no_kk.size' => 'Nomor Kartu Keluarga harus 16 digit.',
            'address.required' => 'Alamat domisili Kartu Keluarga wajib diisi.',
        ]);

        $kkHash = hash('sha256', $validated['no_kk']);
        if (FamilyCard::query()->where('no_kk_hash', '=', $kkHash)->exists()) {
            throw ValidationException::withMessages([
                'no_kk' => ['Nomor Kartu Keluarga ini sudah terdaftar.'],
            ]);
        }

        $validated['no_kk_hash'] = $kkHash;
        $validated['version'] = 1;

        $familyCard = FamilyCard::create($validated);

        return (new FamilyCardResource($familyCard))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified family card.
     */
    public function show(Request $request, string $id): FamilyCardResource
    {
        abort_if(
            ! $request->user()?->hasPermission('citizen.read') &&
            ! $request->user()?->hasPermission('citizen.manage') &&
            ! $request->user()?->isSuperadmin(),
            Response::HTTP_FORBIDDEN,
            'Akses ditolak.'
        );

        $familyCard = FamilyCard::with('citizens')->findOrFail($id);

        return new FamilyCardResource($familyCard);
    }

    /**
     * Update the specified family card with optimistic concurrency control.
     */
    public function update(Request $request, string $id): FamilyCardResource
    {
        abort_if(
            ! $request->user()?->hasPermission('citizen.manage') &&
            ! $request->user()?->isSuperadmin(),
            Response::HTTP_FORBIDDEN,
            'Akses perubahan Kartu Keluarga ditolak.'
        );

        $familyCard = FamilyCard::findOrFail($id);

        $validated = $request->validate([
            'address' => 'sometimes|required|string|max:255',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'village' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'version' => 'nullable|integer',
        ]);

        if (isset($validated['version'])) {
            if ((int) $validated['version'] !== (int) $familyCard->version) {
                abort(Response::HTTP_CONFLICT, 'Conflict: Data Kartu Keluarga telah diubah oleh pihak lain. Silakan muat ulang.');
            }
            $validated['version'] = (int) $familyCard->version + 1;
        } else {
            $validated['version'] = (int) ($familyCard->version ?? 1) + 1;
        }

        $familyCard->update($validated);

        return new FamilyCardResource($familyCard);
    }
}
