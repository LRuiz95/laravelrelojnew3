<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireModulePermission
{
    public function handle(Request $request, Closure $next, string $moduleSlug, string $action = 'view'): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->isAdmin() || $user->canAccessModule($moduleSlug, $action)) {
            return $next($request);
        }

        abort(403, 'No tienes permisos para acceder a este módulo.');
    }
}
