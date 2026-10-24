<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Presentation\Controllers\EstadoController;
use CitasMedicas\Presentation\Controllers\TokenCsrfController;
use CitasMedicas\Presentation\Http\RutaHttp;

final class RutasSistema
{
    /** @return RutaHttp[] */
    public static function definir(): array
    {
        return [
            new RutaHttp(
                'GET',
                '/api/estado',
                EstadoController::class,
                'obtenerEstado',
                null,
                [],
            ),
            new RutaHttp(
                'GET',
                '/api/autenticacion/token',
                TokenCsrfController::class,
                'obtenerToken',
                null,
                [],
            ),
        ];
    }
}
