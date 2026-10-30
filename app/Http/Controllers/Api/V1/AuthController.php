<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Events\CitizenRegistered;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureUserIsActive;
use App\Mail\ResetPasswordMail;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /** Masa berlaku tautan reset yang dikirim lewat email. */
    protected const RESET_EMAIL_MINUTES = 20;

    /** Masa berlaku tautan reset yang diserahkan langsung oleh pengurus RT. */
    protected const RESET_HANDOVER_MINUTES = 1440;

    public function __construct(
        protected NotifyService $notify,
        protected AuditService $audit,
    ) {}

    /**
     * Validasi langsung (real-time) NIK, No. KK, nama lengkap, dan username pada form registrasi.
     */
    public function checkCitizen(Request $request): JsonResponse
    {
        $nik = preg_replace('/\D/', '', (string) $request->input('nik', ''));
        $noKk = preg_replace('/\D/', '', (string) $request->input('no_kk', ''));
        $name = trim((string) $request->input('name', ''));
        $username = strtolower(trim((string) $request->input('username', '')));

        $checks = [];
        $status = 'EMPTY';
        $message = 'Silakan masukkan 16 digit NIK.';
        $citizen = null;

        if (strlen($nik) === 16) {
            $citizen = Citizen::query()->where('nik_hash', hash('sha256', $nik))->first();

            if ($citizen?->user_id !== null) {
                $status = 'ALREADY_REGISTERED';
                $message = 'NIK ini sudah terdaftar dan memiliki akun. Silakan masuk, atau gunakan Lupa Password.';
            } elseif ($citizen) {
                $status = $citizen->status_warga === 'pending_verification' ? 'PENDING_VERIFICATION' : 'PRE_REGISTERED_RT';
                $message = $status === 'PENDING_VERIFICATION'
                    ? 'Pengajuan untuk NIK ini masih diverifikasi pengurus RT.'
                    : 'NIK terdata di master warga RT. Isi nama persis sesuai KTP untuk aktivasi akun.';
            } else {
                $status = 'NOT_FOUND';
                $message = 'NIK belum terdata. Anda dapat mendaftar sebagai warga baru (menunggu verifikasi pengurus).';
            }
        } elseif ($nik !== '') {
            $status = 'INVALID';
            $message = 'NIK harus 16 digit angka.';
        }

        $checks['nik'] = ['ok' => in_array($status, ['NOT_FOUND', 'PRE_REGISTERED_RT'], true), 'message' => $message];

        $checks['no_kk'] = ['ok' => null, 'message' => ''];
        if ($noKk !== '') {
            $checks['no_kk'] = strlen($noKk) === 16
                ? ['ok' => true, 'message' => 'Format No. KK sesuai.']
                : ['ok' => false, 'message' => 'No. KK harus 16 digit angka.'];
        }

        $checks['name'] = ['ok' => null, 'message' => ''];
        if (strlen($name) >= 3 && strlen($noKk) === 16) {
            $card = FamilyCard::query()->where('no_kk_hash', hash('sha256', $noKk))->first();
            $duplicate = $card
                ? Citizen::query()
                    ->where('family_card_id', $card->id)
                    ->where('full_name_key', Citizen::nameKey($name))
                    ->when($citizen && $citizen->user_id === null, fn ($q) => $q->where('id', '!=', $citizen->id))
                    ->exists()
                : false;

            $checks['name'] = $duplicate
                ? ['ok' => false, 'message' => 'Nama dan No. KK ini sudah terdaftar. Tidak dapat mendaftar dua kali.']
                : ['ok' => true, 'message' => 'Kombinasi nama dan No. KK tersedia.'];
        }

        $checks['username'] = ['ok' => null, 'message' => ''];
        if ($username !== '') {
            if (! preg_match('/^[a-z0-9_.]{4,30}$/', $username)) {
                $checks['username'] = ['ok' => false, 'message' => 'Username 4-30 karakter: huruf kecil, angka, titik, atau garis bawah.'];
            } elseif (User::query()->where('username', $username)->exists()) {
                $checks['username'] = ['ok' => false, 'message' => 'Username sudah dipakai. Pilih username lain.'];
            } else {
                $checks['username'] = ['ok' => true, 'message' => 'Username tersedia.'];
            }
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'exists' => $citizen !== null,
            'allow_new_application' => $status === 'NOT_FOUND',
            'checks' => $checks,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->merge([
            'nik' => preg_replace('/\D/', '', (string) $request->input('nik')),
            'no_kk' => preg_replace('/\D/', '', (string) $request->input('no_kk')),
            'username' => strtolower(trim((string) $request->input('username'))),
            'name' => trim((string) preg_replace('/\s+/', ' ', (string) $request->input('name'))),
        ]);

        $validated = $request->validate([
            'nik' => ['required', 'digits:16'],
            'no_kk' => ['required', 'digits:16'],
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'username' => ['required', 'regex:/^[a-z0-9_.]{4,30}$/', 'unique:users,username'],
            'email' => ['nullable', 'string', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:25'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'ktp_file' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
        ], [
            'nik.digits' => 'NIK harus berjumlah tepat 16 digit.',
            'no_kk.digits' => 'No. KK harus berjumlah tepat 16 digit.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.regex' => 'Username 4-30 karakter: huruf kecil, angka, titik, atau garis bawah.',
            'username.unique' => 'Username sudah dipakai. Pilih username lain.',
            'email.unique' => 'Email sudah dipakai akun lain.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $nikHash = hash('sha256', $validated['nik']);
        $kkHash = hash('sha256', $validated['no_kk']);
        $nameKey = Citizen::nameKey($validated['name']);

        $ktpPath = $request->hasFile('ktp_file') ? $request->file('ktp_file')->store('citizens/ktp', 'local') : null;

        [$user, $citizen, $isNewApplicant] = DB::transaction(function () use ($validated, $nikHash, $kkHash, $nameKey, $ktpPath) {
            $citizen = Citizen::query()->where('nik_hash', $nikHash)->lockForUpdate()->first();

            if ($citizen?->user_id !== null) {
                throw ValidationException::withMessages(['nik' => ['NIK ini sudah terdaftar dan memiliki akun.']]);
            }

            $card = FamilyCard::query()->where('no_kk_hash', $kkHash)->first();

            if ($citizen) {
                if ($citizen->familyCard && $citizen->familyCard->no_kk_hash !== $kkHash) {
                    throw ValidationException::withMessages(['no_kk' => ['No. KK tidak sesuai dengan data warga pada NIK ini.']]);
                }
                if ($citizen->full_name_key !== $nameKey) {
                    throw ValidationException::withMessages(['name' => ['Nama tidak sesuai dengan data master RT untuk NIK ini. Periksa ejaan sesuai KTP.']]);
                }
                $isNew = false;
            } else {
                $duplicate = $card && Citizen::query()
                    ->where('family_card_id', $card->id)
                    ->where('full_name_key', $nameKey)
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages(['name' => ['Nama dan No. KK ini sudah terdaftar. Tidak dapat mendaftar dua kali.']]);
                }

                $card ??= FamilyCard::create([
                    'no_kk' => $validated['no_kk'],
                    'no_kk_hash' => $kkHash,
                    'address' => 'Gg. Bugis, RT 04 / RW 09, Kemayoran, Jakarta Pusat',
                    'rt' => '004',
                    'rw' => '009',
                    'city' => 'Jakarta Pusat',
                    'district' => 'Kemayoran',
                    'version' => 1,
                ]);

                [$gender, $dob] = $this->parseNik($validated['nik']);

                $citizen = Citizen::create([
                    'family_card_id' => $card->id,
                    'nik' => $validated['nik'],
                    'nik_hash' => $nikHash,
                    'full_name' => $validated['name'],
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'gender' => $gender,
                    'date_of_birth' => $dob,
                    'ktp_file_path' => $ktpPath,
                    'verification_notes' => 'Pengajuan mandiri warga baru (menunggu verifikasi pengurus RT).',
                    'status_warga' => 'pending_verification',
                    'is_active' => true,
                    'version' => 1,
                ]);
                $isNew = true;
            }

            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'password' => $validated['password'],
                'role' => UserRole::WARGA,
                'warga_id' => $citizen->id,
                'is_active' => true,
            ]);

            $citizen->update([
                'user_id' => $user->id,
                'email' => $citizen->email ?: ($validated['email'] ?? null),
                'phone' => $citizen->phone ?: ($validated['phone'] ?? null),
                'ktp_file_path' => $ktpPath ?: $citizen->ktp_file_path,
            ]);

            return [$user, $citizen, $isNew];
        });

        $this->audit->log('auth.registered', 'User', $user->id, null, ['username' => $user->username, 'pending' => $isNewApplicant]);

        $this->notify->toRoles(
            [...UserRole::superadminTier(), UserRole::SEKRETARIS, UserRole::ADMIN],
            $isNewApplicant ? 'Warga baru menunggu verifikasi' : 'Warga mengaktifkan akun',
            "{$user->name} (@{$user->username}) mendaftar lewat portal.",
            '/app/citizens',
            $isNewApplicant ? 'warning' : 'info',
        );
        $this->notify->to($user, 'Selamat datang di PRISMA RT 04', $isNewApplicant
            ? 'Pendaftaran diterima. Pengurus RT akan memverifikasi data Anda.'
            : 'Akun Anda sudah aktif.', '/dashboard', 'success');

        try {
            CitizenRegistered::dispatch($user);
        } catch (\Throwable $e) {
            Log::warning('Broadcast CitizenRegistered gagal: '.$e->getMessage());
        }

        if ($request->hasSession()) {
            auth()->guard('web')->login($user);
            $request->session()->regenerate();
        }

        return response()->json([
            'message' => $isNewApplicant
                ? 'Pendaftaran berhasil. Data Anda menunggu verifikasi pengurus RT.'
                : 'Registrasi berhasil. Akun Anda telah aktif.',
            'is_pending_verification' => $isNewApplicant,
            'redirect_url' => route('dashboard'),
            ...$this->tokenPayload($request, $user),
            'user' => $this->userPayload($user) + ['citizen' => ['id' => $citizen->id, 'status_warga' => $citizen->status_warga]],
        ], 201);
    }

    /**
     * Lupa password: identitas diverifikasi lewat username + NIK + No. KK. Tautan sekali pakai
     * TIDAK pernah dikirim ke browser: dikirim ke email akun, atau (tanpa email / SMTP gagal)
     * diteruskan ke pengurus RT agar diserahkan langsung setelah identitas warga dicek.
     * Respons selalu sama agar tidak membocorkan kecocokan data maupun ada tidaknya email.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:190'],
            'nik' => ['required', 'digits:16'],
            'no_kk' => ['required', 'digits:16'],
        ], [
            'nik.digits' => 'NIK harus 16 digit.',
            'no_kk.digits' => 'No. KK harus 16 digit.',
        ]);

        $generic = response()->json([
            'message' => 'Jika data cocok, tautan atur ulang password dikirim ke email akun Anda. Akun tanpa email: pengurus RT akan menyerahkan tautan secara langsung setelah mencocokkan KTP Anda.',
        ]);

        $user = User::findByLogin(strtolower($data['username']));
        $citizen = $user?->citizen()->with('familyCard')->first();

        $matches = $user && $citizen
            && hash_equals((string) $citizen->nik_hash, hash('sha256', $data['nik']))
            && $citizen->familyCard
            && hash_equals((string) $citizen->familyCard->no_kk_hash, hash('sha256', $data['no_kk']));

        if (! $matches || ! $user->is_active) {
            return $generic;
        }

        $token = Str::random(48);
        $url = route('password.reset', ['token' => $token]);
        $emailed = false;

        if ($user->email) {
            try {
                Mail::to($user->email)->send(new ResetPasswordMail($user->name, $url, self::RESET_EMAIL_MINUTES));
                $emailed = true;
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim email reset password: '.$e->getMessage());
            }
        }

        // Tanpa email: permintaan menunggu serah-terima oleh pengurus, jadi masa berlakunya lebih panjang.
        $minutes = $emailed ? self::RESET_EMAIL_MINUTES : self::RESET_HANDOVER_MINUTES;

        DB::table('password_reset_requests')->where('user_id', $user->id)->whereNull('used_at')->delete();
        DB::table('password_reset_requests')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'ip_address' => $request->ip(),
            'expires_at' => now()->addMinutes($minutes),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit->log('auth.password_reset_requested', 'User', $user->id, null, ['ip' => $request->ip(), 'channel' => $emailed ? 'email' : 'pengurus']);

        if (! $emailed) {
            $this->notify->toRoles(
                [...UserRole::superadminTier(), UserRole::SEKRETARIS],
                'Permintaan reset password warga',
                "{$user->name} (@{$user->username}) meminta reset password. Cocokkan KTP warga secara langsung sebelum menyerahkan tautan ini (sekali pakai, berlaku ".intdiv($minutes, 60)." jam): {$url}",
                $url,
                'warning',
            );
        }

        return $generic;
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], ['password.confirmed' => 'Konfirmasi password tidak cocok.']);

        $row = DB::table('password_reset_requests')
            ->where('token_hash', hash('sha256', $data['token']))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $row) {
            throw ValidationException::withMessages(['token' => ['Tautan reset tidak valid atau sudah kedaluwarsa. Ulangi permintaan lupa password.']]);
        }

        $user = User::query()->findOrFail($row->user_id);
        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        // Cabut semua token & sesi lama; sesi lain juga tertolak AuthenticateWebSession karena hash password berubah.
        EnsureUserIsActive::revokeAccess($user);

        DB::table('password_reset_requests')->where('id', $row->id)->update(['used_at' => now(), 'updated_at' => now()]);

        $this->audit->log('auth.password_reset', 'User', $user->id, null, null);
        $this->notify->to($user, 'Password diubah', 'Password akun Anda berhasil diatur ulang.', '/dashboard/profile', 'warning');

        return response()->json(['message' => 'Password berhasil diubah. Silakan masuk.', 'redirect_url' => route('login')]);
    }

    public function login(Request $request): JsonResponse
    {
        $user = $this->attempt($request);

        if ($request->hasSession() || auth()->guard('web')->check()) {
            auth()->guard('web')->login($user, $request->boolean('remember'));
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
        }

        $this->audit->log('auth.login', 'User', $user->id, null, ['ip' => $request->ip()]);

        return response()->json([
            ...$this->tokenPayload($request, $user),
            'redirect_url' => '/dashboard',
            'user' => $this->userPayload($user),
            'message' => 'Login berhasil.',
        ]);
    }

    public function webLogin(Request $request)
    {
        try {
            $user = $this->attempt($request);
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
            }

            return back()->withErrors($e->errors())->withInput($request->except('password'));
        }

        auth()->guard('web')->login($user, $request->boolean('remember'));
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $this->audit->log('auth.login', 'User', $user->id, null, ['ip' => $request->ip()]);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Login berhasil.',
                ...$this->tokenPayload($request, $user),
                'redirect_url' => route('dashboard'),
                'user' => $this->userPayload($user),
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function webLogout(Request $request)
    {
        auth()->guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['message' => 'Anda telah berhasil keluar.', 'redirect_url' => route('login')]);
        }

        return redirect('/login')->with('status', 'Anda telah berhasil keluar.');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } elseif ($request->hasSession()) {
            // Klien SPA (sesi cookie): TransientToken tidak bisa dihapus, jadi akhiri sesinya.
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Berhasil logout']);
    }

    protected function attempt(Request $request): User
    {
        $login = trim((string) ($request->input('login') ?? $request->input('username') ?? $request->input('email') ?? ''));

        $request->validate(['password' => ['required', 'string']]);

        if ($login === '') {
            throw ValidationException::withMessages(['login' => ['Username atau email wajib diisi.']]);
        }

        $throttleKey = 'login:'.strtolower($login);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages(['login' => ["Terlalu banyak percobaan gagal. Coba lagi dalam {$seconds} detik."]]);
        }

        $user = User::findByLogin(strtolower($login)) ?? User::findByLogin($login);

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 300);

            throw ValidationException::withMessages(['login' => ['Username atau password salah.']]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['login' => ['Akun ini dinonaktifkan. Silakan hubungi admin.']]);
        }

        RateLimiter::clear($throttleKey);
        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }

    /**
     * Token Bearer hanya untuk klien non-SPA (aplikasi pihak ketiga / mobile).
     * Web shell memakai cookie sesi sehingga tidak perlu token yang tidak pernah kedaluwarsa.
     *
     * @return array<string, string>
     */
    protected function tokenPayload(Request $request, User $user): array
    {
        if (EnsureFrontendRequestsAreStateful::fromFrontend($request)) {
            return [];
        }

        return [
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    protected function parseNik(string $nik): array
    {
        $day = (int) substr($nik, 6, 2);
        $gender = $day > 40 ? 'Perempuan' : 'Laki-laki';
        $day = $day > 40 ? $day - 40 : $day;
        $month = (int) substr($nik, 8, 2);
        $yy = (int) substr($nik, 10, 2);
        $year = $yy > (int) date('y') ? 1900 + $yy : 2000 + $yy;

        $dob = checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;

        return [$gender, $dob];
    }

    /**
     * @return array<string, mixed>
     */
    protected function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role->value,
            'tier' => $user->tier(),
            'is_active' => $user->is_active,
        ];
    }
}
