<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public const MESSAGE = 'Akun Anda dinonaktifkan. Hubungi pengurus RT.';

    /**
     * Tolak sesi/token milik akun yang sudah dinonaktifkan (mis. pendaftaran
     * warga ditolak atau akun dinonaktifkan pengurus), lalu cabut aksesnya.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->is_active) {
            return $next($request);
        }

        self::revokeAccess($user);

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            abort(Response::HTTP_FORBIDDEN, self::MESSAGE);
        }

        return redirect()->route('login')->withErrors(['login' => self::MESSAGE]);
    }

    /**
     * Cabut seluruh token API dan sesi (driver database) milik pengguna.
     * Jika $keep diberikan, token & sesi yang sedang dipakai request tersebut dipertahankan
     * (dipakai saat pengguna mengganti password sendiri).
     */
    public static function revokeAccess(User $user, ?Request $keep = null): void
    {
        $current = $keep?->user()?->currentAccessToken();

        $user->tokens()
            ->when($current instanceof PersonalAccessToken, fn ($q) => $q->whereKeyNot($current->getKey()))
            ->delete();

        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->when($keep?->hasSession(), fn ($q) => $q->where('id', '!=', $keep->session()->getId()))
            ->delete();
    }
}
