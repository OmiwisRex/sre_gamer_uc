<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotAdministrator
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->user()?->usuario?->rol?->nombre;

        abort_if(in_array($role, ['Administrador', 'Master'], true), 403);
        abort_if($role === null, 403);

        return $next($request);
    }
}
