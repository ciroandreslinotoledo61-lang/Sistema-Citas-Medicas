<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Presentation\Controllers\CitaController;
use CitasMedicas\Presentation\Http\RutaHttp;

final class RutasCitas
{
    /** @return RutaHttp[] */
    public static function definir(): array
    {
        return [
            new RutaHttp(
                'GET',
                '/api/agenda/medicos',
                CitaController::class,
                'listarMedicosParaAgenda',
                [],
                ['usuario'],
            ),
            new RutaHttp(
                'GET',
                '/api/citas/activas',
                CitaController::class,
                'listarCitasActivasPaciente',
                [],
                ['usuario'],
            ),
            new RutaHttp(
                'GET',
                '/api/citas',
                CitaController::class,
                'listarCitas',
                [],
                ['usuario'],
            ),
            new RutaHttp(
                'POST',
                '/api/citas',
                CitaController::class,
                'reservarCita',
                [],
                ['usuario'],
            ),
            new RutaHttp(
                'GET',
                '/api/citas/{idCita}',
                CitaController::class,
                'obtenerCita',
                [],
                ['usuario', 'idCita'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/citas/{idCita}/cancelacion',
                CitaController::class,
                'cancelarCita',
                [],
                ['usuario', 'idCita'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/citas/{idCita}/reprogramacion',
                CitaController::class,
                'reprogramarCita',
                [],
                ['usuario', 'idCita'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/citas/{idCita}/asistencia',
                CitaController::class,
                'registrarAsistencia',
                [],
                ['usuario', 'idCita'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/citas/{idCita}/atencion',
                CitaController::class,
                'marcarCitaAtendida',
                [],
                ['usuario', 'idCita'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/citas/{idCita}/inasistencia',
                CitaController::class,
                'marcarCitaInasistencia',
                [],
                ['usuario', 'idCita'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/citas/{idCita}/confirmacion',
                CitaController::class,
                'confirmarCita',
                [],
                ['usuario', 'idCita'],
            ),
        ];
    }
}
