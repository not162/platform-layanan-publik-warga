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
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->is_active) {
            abort(403, 'Akun Anda dinonaktifkan.');
        }

        if ($user->isSuperadmin()) {
            return $next($request);
        }

        $hasAny = false;
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                $hasAny = true;
                break;
            }
        }

        if (! $hasAny) {
            $required = implode(' atau ', $permissions);
            abort(403, "Akses ditolak: Membutuhkan izin '{$required}'.");
        }

        return $next($request);
    }
}
