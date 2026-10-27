<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function __construct(protected AuditService $audit) {}

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'regex:/^[a-z0-9_.]{4,30}$/', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:25'],
        ]);

        $user->update([
            'name' => $data['name'],
            'username' => strtolower($data['username']),
            'email' => $data['email'] ?? null,
        ]);

        if ($user->citizen && array_key_exists('phone', $data)) {
            $user->citizen->update(['phone' => $data['phone']]);
        }

        $this->audit->log('account.updated', 'User', $user->id, null, ['username' => $user->username]);

        return response()->json(['message' => 'Profil berhasil diperbarui.', 'data' => $this->payload($request)]);
    }

    public function photo(Request $request): JsonResponse
    {
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']], [
            'photo.image' => 'Berkas harus berupa gambar JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $user = $request->user();
        if ($user->avatar_url) {
            Storage::disk('local')->delete($user->avatar_url);
        }

        $user->update(['avatar_url' => $request->file('photo')->store('avatars', 'local')]);
        $this->audit->log('account.photo_changed', 'User', $user->id);

        return response()->json(['message' => 'Foto profil diperbarui.', 'data' => ['avatar' => url('/api/v1/files/avatar/'.$user->id).'?v='.time()]]);
    }

    public function removePhoto(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->avatar_url) {
            Storage::disk('local')->delete($user->avatar_url);
            $user->update(['avatar_url' => null]);
        }

        return response()->json(['message' => 'Foto profil dihapus.']);
    }

    public function password(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], ['password.confirmed' => 'Konfirmasi password tidak cocok.']);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['Password saat ini salah.']]);
        }

        $user->update(['password' => $data['password']]);
        $this->audit->log('account.password_changed', 'User', $user->id);

        return response()->json(['message' => 'Password berhasil diganti.']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(Request $request): array
    {
        $user = $request->user()->fresh('citizen');

        return [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->citizen?->phone,
            'role' => $user->role->value,
            'tier' => $user->tier(),
        ];
    }
}
