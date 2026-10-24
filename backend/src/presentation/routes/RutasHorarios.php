<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Presentation\Controllers\HorarioMedicoController;
use CitasMedicas\Presentation\Http\RutaHttp;

final class RutasHorarios
{
    /** @return RutaHttp[] */
    public static function definir(): array
    {
        return [
            new RutaHttp(
                'GET',
                '/api/horarios',
                HorarioMedicoController::class,
                'listarHorarios',
                [],
                ['usuario'],
            ),
            new RutaHttp(
                'POST',
                '/api/horarios',
                HorarioMedicoController::class,
                'registrarHorario',
                [],
                ['usuario'],
            ),
            new RutaHttp(
                'POST',
                '/api/horarios/programacion',
                HorarioMedicoController::class,
                'programarHorariosPorPeriodo',
                [],
                ['usuario'],
            ),
        ];
    }
}
