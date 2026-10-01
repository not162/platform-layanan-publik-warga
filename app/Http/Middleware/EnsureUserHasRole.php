<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->is_active) {
            abort(403, 'Akun Anda dinonaktifkan.');
        }

        $userRoleValue = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        // Normalize roles to uppercase
        $allowedRoles = array_map('strtoupper', $roles);

        if (! in_array(strtoupper($userRoleValue), $allowedRoles, true)) {
            abort(403, 'Akses tidak diizinkan untuk peran Anda.');
        }

        return $next($request);
    }
}
