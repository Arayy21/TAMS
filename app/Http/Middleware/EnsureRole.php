<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Pengelola diperlakukan sebagai tingkat admin
        $role = $user?->isAdmin() ? 'admin' : $user?->role;

        if (! $user || ! in_array($role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}