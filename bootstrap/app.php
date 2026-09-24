<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'permission' => CheckPermission::class,
            'role' => CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Unifica errores esperados de la API, incluidos permisos y validaciones.
        // Se modifica la respuesta existente para conservar el estado, las cabeceras
        // (por ejemplo Retry-After) y los detalles de validación en "errors".
        $exceptions->respond(function (Response $response): Response {
            $estado = $response->getStatusCode();
            if (! request()->is('api/*') || ! $response instanceof JsonResponse || $estado < 400 || $estado >= 500) {
                return $response;
            }

            $datos = $response->getData(true);
            $datos['success'] = false;
            $datos['message'] ??= Response::$statusTexts[$estado] ?? 'La solicitud no pudo completarse.';
            $response->setData($datos);

            return $response;
        });
    })->create();
