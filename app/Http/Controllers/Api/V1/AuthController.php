<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Events\CitizenRegistered;
use App\Http\Controllers\Controller;
use App\Models\Citizen;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Real-time NIK & Citizen pre-validation endpoint.
     * Detects existing records, registered accounts, and similar names in the RT system.
     */
    public function checkCitizen(Request $request): JsonResponse
    {
        $nik = trim((string) $request->input('nik', ''));
        $name = trim((string) $request->input('name', ''));

        $data = [
            'exists' => false,
            'has_account' => false,
            'status' => 'EMPTY',
            'message' => 'Silakan masukkan 16 digit NIK.',
            'allow_new_application' => false,
            'registered_name' => null,
            'registered_status' => null,
            'similar_citizens' => [],
        ];

        // 1. Cek NIK jika formatnya 16 digit
        if (strlen($nik) === 16 && ctype_digit($nik)) {
            $nikHash = hash('sha256', $nik);
            $citizen = Citizen::query()->where('nik_hash', $nikHash)->first();

            if ($citizen) {
                $data['exists'] = true;
                $data['registered_name'] = $citizen->full_name;
                $data['registered_status'] = $citizen->status_warga;

                if ($citizen->user_id !== null) {
                    $data['has_account'] = true;
                    $data['status'] = 'ALREADY_REGISTERED';
                    $data['allow_new_application'] = false; // NIK sudah terdaftar & tervalidasi: TIDAK MUNCUL button "Ajukan Warga Baru"
                    $data['message'] = "Data NIK ini SUDAH TERDAFTAR dan memiliki akun aktif di sistem RT atas nama {$citizen->full_name}. Silakan langsung masuk (login).";
                } elseif ($citizen->status_warga === 'pending_verification') {
                    $data['has_account'] = false;
                    $data['status'] = 'PENDING_VERIFICATION';
                    $data['allow_new_application'] = false; // TIDAK MUNCUL button "Ajukan Warga Baru"
                    $data['message'] = 'Pengajuan warga baru untuk NIK ini sudah diajukan sebelumnya dan saat ini sedang dalam proses verifikasi pengurus RT.';
                } else {
                    $data['has_account'] = false;
                    $data['status'] = 'PRE_REGISTERED_RT';
                    $data['allow_new_application'] = false; // NIK sudah ada di master data RT: TIDAK MUNCUL button "Ajukan Warga Baru", melainkan aktivasi akun biasa
                    $data['message'] = "NIK terdata resmi dalam master kependudukan RT 01 atas nama {$citizen->full_name}. Anda dapat langsung melakukan Aktivasi Akun.";
                }
            } else {
                $data['exists'] = false;
                $data['has_account'] = false;
                $data['status'] = 'NOT_FOUND';
                $data['allow_new_application'] = true; // NIK belum ada di sistem: MUNCUL button "Ajukan Warga Baru"
                $data['message'] = 'Nomor NIK belum tercatat dalam data kependudukan RT 01. Anda dapat mengajukan pendaftaran sebagai warga baru (menunggu verifikasi pengurus RT).';
            }
        }

        // 2. Cek data yang sama atau mirip di database kependudukan RT
        if (strlen($name) >= 3) {
            $similarCitizens = Citizen::query()
                ->where('full_name', 'LIKE', '%'.$name.'%')
                ->limit(3)
                ->get();

            foreach ($similarCitizens as $sim) {
                if ($data['exists'] && isset($nikHash) && $sim->nik_hash === $nikHash) {
                    continue;
                }
                $rawNik = (string) ($sim->nik ?? '');
                $maskedNik = strlen($rawNik) >= 8 ? substr($rawNik, 0, 4).'********'.substr($rawNik, -4) : 'Data Terenkripsi';

                $data['similar_citizens'][] = [
                    'name' => $sim->full_name,
                    'masked_nik' => $maskedNik,
                    'has_account' => $sim->user_id !== null,
                    'status_warga' => $sim->status_warga,
                ];
            }
        }

        return response()->json($data);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'required|string|size:16',
            'name' => 'required|string|max:150',
            'email' => 'required|string|email|max:190|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'ktp_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'notes' => 'nullable|string|max:500',
        ], [
            'nik.required' => 'NIK wajib diisi untuk verifikasi data warga.',
            'nik.size' => 'NIK harus berjumlah tepat 16 digit.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'ktp_file.mimes' => 'Format berkas KTP harus berupa JPG, PNG, atau PDF.',
            'ktp_file.max' => 'Ukuran berkas KTP maksimal 5MB.',
        ]);

        $nikHash = hash('sha256', $validated['nik']);
        $citizen = Citizen::query()->where('nik_hash', '=', $nikHash)->first();

        $ktpPath = null;
        if ($request->hasFile('ktp_file')) {
            $ktpPath = $request->file('ktp_file')->store('citizens/ktp', 'local');
        }

        $isNewApplicant = false;

        // Pendaftaran Pengajuan Warga Baru (Pending Verification):
        if (! $citizen) {
            $isNewApplicant = true;
            $dayRaw = (int) substr($validated['nik'], 6, 2);
            $gender = ($dayRaw > 40) ? 'Perempuan' : 'Laki-laki';
            $birthDay = ($dayRaw > 40) ? ($dayRaw - 40) : $dayRaw;
            $birthMonth = (int) substr($validated['nik'], 8, 2);
            $birthYear2Digit = (int) substr($validated['nik'], 10, 2);
            $currentYear2Digit = (int) date('y');
            $fullYear = ($birthYear2Digit > $currentYear2Digit) ? (1900 + $birthYear2Digit) : (2000 + $birthYear2Digit);

            $dob = null;
            if ($birthMonth >= 1 && $birthMonth <= 12 && $birthDay >= 1 && $birthDay <= 31 && checkdate($birthMonth, $birthDay, $fullYear)) {
                $dob = sprintf('%04d-%02d-%02d', $fullYear, $birthMonth, $birthDay);
            }

            $citizen = Citizen::create([
                'nik' => $validated['nik'],
                'nik_hash' => $nikHash,
                'full_name' => $validated['name'],
                'email' => $validated['email'],
                'gender' => $gender,
                'place_of_birth' => 'Jakarta',
                'date_of_birth' => $dob,
                'ktp_file_path' => $ktpPath,
                'verification_notes' => $validated['notes'] ?? 'Pengajuan mandiri warga baru (menunggu verifikasi berkas oleh RT).',
                'status_warga' => 'pending_verification',
                'is_active' => true,
                'version' => 1,
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
        $citizen->update([
            'user_id' => $user->id,
            'full_name' => $citizen->full_name ?: $validated['name'],
            'email' => $citizen->email ?: $validated['email'],
            'ktp_file_path' => $ktpPath ?: $citizen->ktp_file_path,
        ]);

        // Broadcast Notifikasi Warga Baru ke Pusher
        CitizenRegistered::dispatch($user);

        // Jika dipanggil dari web browser dengan session, aktifkan login web session
        if ($request->hasSession() || auth()->guard('web')->check()) {
            auth()->guard('web')->login($user);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $successMessage = $isNewApplicant
            ? 'Pengajuan pendaftaran warga baru berhasil dikirim dan sedang menunggu verifikasi berkas oleh pengurus RT.'
            : 'Registrasi dan Verifikasi Warga Berhasil. Akun Anda telah aktif.';

        return response()->json([
            'message' => $successMessage,
            'is_pending_verification' => $isNewApplicant,
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
            ->whereRaw('LOWER(email) = ?', [strtolower($loginInput)], 'and')
            ->orWhere('name', '=', $loginInput)
            ->first();

        if (! $user) {
            $citizen = Citizen::query()->where('phone', '=', $loginInput, 'and')->first();
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
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Alamat email atau nomor telepon wajib diisi.',
                    'errors' => ['email' => ['Alamat email atau nomor telepon wajib diisi.']],
                ], 422);
            }

            return back()->withErrors(['email' => 'Alamat email atau nomor telepon wajib diisi.'])->withInput();
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($loginInput)], 'and')
            ->orWhere('name', '=', $loginInput)
            ->first();

        if (! $user) {
            $citizen = Citizen::query()->where('phone', '=', $loginInput, 'and')->first();
            if ($citizen && $citizen->user) {
                $user = $citizen->user;
            }
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
                    'errors' => ['email' => ['Kredensial yang diberikan tidak cocok dengan data kami.']],
                ], 422);
            }

            return back()->withErrors(['email' => 'Kredensial yang diberikan tidak cocok dengan data kami.'])->withInput();
        }

        if (! $user->is_active) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Akun ini dinonaktifkan. Silakan hubungi admin.',
                    'errors' => ['email' => ['Akun ini dinonaktifkan. Silakan hubungi admin.']],
                ], 403);
            }

            return back()->withErrors(['email' => 'Akun ini dinonaktifkan. Silakan hubungi admin.'])->withInput();
        }

        $user->update(['last_login_at' => now()]);

        auth()->guard('web')->login($user, $request->boolean('remember'));
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Login berhasil.',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'redirect_url' => route('dashboard'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role,
                    'is_active' => $user->is_active,
                ],
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function webLogout(Request $request)
    {
        if ($request->user() && method_exists($request->user(), 'currentAccessToken')) {
            $token = $request->user()->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }
        }

        auth()->guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Anda telah berhasil keluar.',
                'redirect_url' => route('login'),
            ]);
        }

        return redirect('/login')->with('status', 'Anda telah berhasil keluar.');
    }

    public function logout(Request $request)
    {
        if ($request->user() && method_exists($request->user(), 'currentAccessToken')) {
            $token = $request->user()->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }
        }

        if ($request->hasSession()) {
            auth()->guard('web')->logout();
        }

        return response()->json([
            'message' => 'Berhasil logout',
        ]);
    }
}
