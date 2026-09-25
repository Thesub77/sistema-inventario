<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Rechazo esperado de una regla de cajas, con su mensaje y estado HTTP original.
 *
 * Se lanza una excepción para que DB::transaction revierta cualquier escritura.
 * La representación JSON se centraliza en bootstrap/app.php; los servicios no
 * deben retornar una respuesta de error que pueda confirmar una transacción.
 */
class CajaException extends HttpException
{
    /** Exige que se cumpla una condición necesaria para continuar la operación. */
    public static function exigir(bool $condicion, int $estadoHttp, string $mensaje): void
    {
        if (! $condicion) {
            throw new self($estadoHttp, $mensaje);
        }
    }

    /** Rechaza una situación inválida sin alterar la regla de negocio evaluada. */
    public static function rechazarSi(bool $condicion, int $estadoHttp, string $mensaje): void
    {
        self::exigir(! $condicion, $estadoHttp, $mensaje);
    }
}
