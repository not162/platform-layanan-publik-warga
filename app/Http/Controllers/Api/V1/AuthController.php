<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Events\CitizenRegistered;
use App\Http\Controllers\Controller;
use App\Models\Citizen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'required|string|size:16',
            'name' => 'required|string|max:150',
            'email' => 'required|string|email|max:190|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'nik.required' => 'NIK wajib diisi untuk verifikasi data warga.',
            'nik.size' => 'NIK harus berjumlah tepat 16 digit.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        // Verifikasi NIK (Cari di tabel citizens berdasarkan nik_hash)
        $nikHash = hash('sha256', $validated['nik']);
        $citizen = Citizen::where('nik_hash', $nikHash)->first();

        if (! $citizen) {
            throw ValidationException::withMessages([
                'nik' => ['NIK tidak terdaftar dalam data RT. Hubungi pengurus.'],
            ]);
        }

        if ($citizen->user_id !== null) {
            throw ValidationException::withMessages([
                'nik' => ['NIK ini sudah terhubung dengan akun lain.'],
            ]);
        }

        // Buat User
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::WARGA,
            'warga_id' => $citizen->id,
            'is_active' => true,
        ]);

        // Hubungkan Citizen dengan User ini
        $citizen->update(['user_id' => $user->id]);

        // Broadcast Notifikasi Warga Baru ke Pusher
        CitizenRegistered::dispatch($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi dan Verifikasi Warga Berhasil.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'citizen' => [
                    'id' => $citizen->id,
                    'full_name' => $citizen->full_name,
                    'status_warga' => $citizen->status_warga,
                ],
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $loginInput = trim((string) ($request->input('email') ?? $request->input('login') ?? $request->input('username') ?? ''));

        if ($loginInput === '') {
            throw ValidationException::withMessages([
                'email' => ['Alamat email atau nomor telepon wajib diisi.'],
            ]);
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($loginInput)])
            ->orWhere('name', $loginInput)
            ->first();

        if (! $user) {
            $citizen = Citizen::query()->where('phone', $loginInput)->first();
            if ($citizen && $citizen->user) {
                $user = $citizen->user;
            }
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok dengan data kami.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun ini dinonaktifkan. Silakan hubungi admin.'],
            ]);
        }

        $user->update(['last_login_at' => now()]);

        // If called from browser with session, also authenticate web guard
        if ($request->hasSession() || auth()->guard('web')->check()) {
            auth()->guard('web')->login($user, $request->boolean('remember'));
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'redirect_url' => '/dashboard',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ],
            'message' => 'Login berhasil.',
        ]);
    }

    public function webLogin(Request $request)
    {
        $loginInput = trim((string) ($request->input('email') ?? $request->input('login') ?? $request->input('username') ?? ''));

        if ($loginInput === '') {
            return back()->withErrors(['email' => 'Alamat email atau nomor telepon wajib diisi.'])->withInput();
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($loginInput)])
            ->orWhere('name', $loginInput)
            ->first();

        if (! $user) {
            $citizen = Citizen::query()->where('phone', $loginInput)->first();
            if ($citizen && $citizen->user) {
                $user = $citizen->user;
            }
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Kredensial yang diberikan tidak cocok dengan data kami.'])->withInput();
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Akun ini dinonaktifkan. Silakan hubungi admin.'])->withInput();
        }

        $user->update(['last_login_at' => now()]);
        auth()->guard('web')->login($user, $request->boolean('remember'));

        return redirect()->intended('/dashboard');
    }

    public function webLogout(Request $request)
    {
        auth()->guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('status', 'Anda telah berhasil keluar.');
    }

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        if ($request->hasSession()) {
            auth()->guard('web')->logout();
        }

        return response()->json([
            'message' => 'Berhasil logout',
        ]);
    }
}
