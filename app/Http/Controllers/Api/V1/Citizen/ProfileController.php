<?php

namespace App\Http\Controllers\Api\V1\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\UpdateProfileRequest;
use App\Http\Resources\V1\CitizenResource;
use App\Http\Resources\V1\UserResource;
use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\DuePayment;
use App\Models\Letter;
use App\Models\ResidentDue;
use App\Models\SecurityReport;
use App\Models\User;
use App\Services\CitizenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function __construct(protected CitizenService $citizenService) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // Load the citizen profile and family card
        $citizen = Citizen::query()->where('user_id', $user->id)->with('familyCard')->first();

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'citizen' => $citizen ? new CitizenResource($citizen) : null,
            ],
            'meta' => [
                'version' => $citizen?->version ?? 1,
                'updated_at' => $citizen?->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            ],
            'message' => 'Profil berhasil dimuat.',
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $citizen = Citizen::query()->where('user_id', $user->id)->firstOrFail();

        $citizen = $this->saveProfile($citizen, $user, $request->validated());

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'citizen' => new CitizenResource($citizen),
            ],
            'meta' => [
                'version' => $citizen->version,
                'updated_at' => $citizen->updated_at?->toIso8601String(),
            ],
            'message' => 'Profil warga berhasil diperbarui.',
        ]);
    }

    public function updateWeb(UpdateProfileRequest $request): RedirectResponse
    {
        $citizen = Citizen::query()->where('user_id', $request->user()->id)->firstOrFail();
        $this->saveProfile($citizen, $request->user(), $request->validated());

        return back()->with('status', 'Profil warga berhasil diperbarui. Nomor telepon baru perlu diverifikasi melalui SMS.');
    }

    public function export(Request $request): JsonResponse
    {
        $user = $request->user();
        $citizen = Citizen::query()->where('user_id', $user->id)->firstOrFail();

        $data = [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'role' => $user->role->value,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'citizen' => (new CitizenResource($citizen))->resolve($request),
            'letters' => Letter::query()
                ->where('citizen_id', $citizen->id)
                ->get(['ticket_number', 'type', 'keperluan', 'status', 'created_at', 'updated_at'])
                ->toArray(),
            'complaints' => Complaint::query()
                ->where('user_id', $user->id)
                ->get(['ticket_number', 'kategori', 'title', 'description', 'lokasi', 'status', 'priority', 'created_at', 'updated_at'])
                ->toArray(),
            'security_reports' => SecurityReport::query()
                ->where('reporter_user_id', $user->id)
                ->get(['ticket_number', 'category', 'severity', 'title', 'description', 'location', 'incident_at', 'status', 'resolution', 'created_at', 'updated_at'])
                ->toArray(),
            'dues' => ResidentDue::query()
                ->where('citizen_id', $citizen->id)
                ->with(['payments' => fn ($query) => $query->select([
                    'id', 'resident_due_id', 'amount_paid_idr', 'paid_at', 'payment_method', 'receipt_number', 'status', 'created_at',
                ])])
                ->get(['id', 'period_year', 'period_month', 'amount_due_idr', 'due_date', 'status', 'notes', 'created_at'])
                ->toArray(),
            'payments' => DuePayment::query()
                ->whereHas('due', fn ($query) => $query->where('citizen_id', $citizen->id))
                ->get(['resident_due_id', 'amount_paid_idr', 'paid_at', 'payment_method', 'receipt_number', 'status', 'notes', 'created_at'])
                ->toArray(),
        ];

        return response()->json(['data' => $data], 200, [
            'Content-Disposition' => 'attachment; filename="data-warga.json"',
        ]);
    }

    private function saveProfile(Citizen $citizen, User $user, array $data): Citizen
    {
        return DB::transaction(function () use ($citizen, $user, $data): Citizen {
            if (array_key_exists('email', $data) && $data['email'] !== null) {
                if ($data['email'] !== $user->email) {
                    $user->forceFill([
                        'email' => $data['email'],
                        'email_verified_at' => null,
                    ])->save();
                }

                $data['email'] = $user->email;
            }

            if (array_key_exists('phone', $data) && $data['phone'] !== $citizen->phone) {
                $data['phone_verified_at'] = null;
            }

            return $this->citizenService->update($citizen, $data);
        });
    }
}
