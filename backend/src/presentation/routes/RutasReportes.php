<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Presentation\Controllers\ReporteController;
use CitasMedicas\Presentation\Http\RutaHttp;

final class RutasReportes
{
    /** @return RutaHttp[] */
    public static function definir(): array
    {
        return [
            new RutaHttp(
                'GET',
                '/api/reportes',
                ReporteController::class,
                'obtenerReporteAdministrativo',
                [],
                ['usuario'],
            ),
        ];
    }
}
