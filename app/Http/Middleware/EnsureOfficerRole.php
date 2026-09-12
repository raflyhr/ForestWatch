<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOfficerRole
{
    public function handle(Request $request, Closure $next, string $role = 'officer'): Response
    {
        if (! $request->user() || $request->user()->role !== $role) {
            abort(403, 'Unauthorized');
        }
        return $next($request);
    }
}