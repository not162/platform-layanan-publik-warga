<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->is_active) {
            abort(403, 'Akun Anda dinonaktifkan.');
        }

        if (! $user->hasPermission($permission)) {
            abort(403, "Akses ditolak: Membutuhkan izin '{$permission}'.");
        }

        return $next($request);
    }
}
