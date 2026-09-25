<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        if ($usuario->esAdmin()) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if ($usuario->tienePermiso($permission) || ($usuario->rol && $usuario->rol->nombre_rol === $permission)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No tiene permisos suficientes para acceder a este recurso.',
        ], 403);
    }
}
