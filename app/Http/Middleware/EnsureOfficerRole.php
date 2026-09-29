<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfficerRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowedRoles = [];
        foreach ($roles ?: ['officer'] as $role) {
            $allowedRoles = [...$allowedRoles, ...explode(',', $role)];
        }
        $user = $request->user();
        if (! $user || $user->is_active === false || ! in_array($user->role, $allowedRoles, true)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
