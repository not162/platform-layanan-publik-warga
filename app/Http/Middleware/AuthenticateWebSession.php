<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keluarkan sesi web yang dibuat sebelum password diganti (perangkat lain).
 *
 * Mirip AuthenticateSession bawaan Laravel, tetapi selalu memakai guard 'web' secara eksplisit
 * (guard default bisa berubah menjadi 'sanctum' setelah auth:sanctum) dan menyimpan id pengguna
 * bersama hash password agar pergantian akun di sesi yang sama tidak dianggap sesi basi.
 */
class AuthenticateWebSession
{
    protected const KEY = 'web_session_password';

    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');

        if (! $request->hasSession() || ! $guard instanceof SessionGuard || ! $guard->user()) {
            return $next($request);
        }

        $user = $guard->user();
        $stored = $request->session()->get(self::KEY);

        if (is_array($stored)
            && (string) ($stored['id'] ?? '') === (string) $user->getAuthIdentifier()
            && ! hash_equals((string) ($stored['hash'] ?? ''), self::fingerprint($guard))) {
            $guard->logoutCurrentDevice();
            $request->session()->flush();

            throw new AuthenticationException('Sesi berakhir karena password akun telah diganti.', ['web']);
        }

        return tap($next($request), fn () => self::remember($request));
    }

    /**
     * Simpan sidik password terkini ke sesi ini (dipanggil juga setelah pengguna mengganti
     * password sendiri, agar perangkat yang sedang dipakai tidak ikut dikeluarkan).
     */
    public static function remember(Request $request): void
    {
        $guard = Auth::guard('web');

        if ($request->hasSession() && $guard instanceof SessionGuard && $guard->hasUser() && $guard->user()) {
            $request->session()->put(self::KEY, [
                'id' => $guard->user()->getAuthIdentifier(),
                'hash' => self::fingerprint($guard),
            ]);
        }
    }

    protected static function fingerprint(SessionGuard $guard): string
    {
        return $guard->hashPasswordForCookie((string) $guard->user()->getAuthPassword());
    }
}
