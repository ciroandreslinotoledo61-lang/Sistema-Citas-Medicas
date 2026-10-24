<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Presentation\Controllers\DisponibilidadController;
use CitasMedicas\Presentation\Http\RutaHttp;

final class RutasDisponibilidad
{
    /** @return RutaHttp[] */
    public static function definir(): array
    {
        return [
            new RutaHttp(
                'GET',
                '/api/disponibilidad/configuracion',
                DisponibilidadController::class,
                'obtenerConfiguracionAgenda',
                [],
                ['usuario'],
            ),
            new RutaHttp(
                'GET',
                '/api/disponibilidad/medicos',
                DisponibilidadController::class,
                'listarMedicosDisponibles',
                [],
                ['usuario'],
            ),
            new RutaHttp(
                'GET',
                '/api/disponibilidad/turnos',
                DisponibilidadController::class,
                'listarTurnosDisponibles',
                [],
                ['usuario'],
            ),
        ];
    }
}
