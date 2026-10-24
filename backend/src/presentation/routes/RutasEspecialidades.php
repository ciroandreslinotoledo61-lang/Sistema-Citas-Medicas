<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Presentation\Controllers\EspecialidadController;
use CitasMedicas\Presentation\Http\RutaHttp;
use CitasMedicas\Shared\Constants\RolesSistema;

final class RutasEspecialidades
{
    /** @return RutaHttp[] */
    public static function definir(): array
    {
        return [
            new RutaHttp(
                'GET',
                '/api/especialidades/activas',
                EspecialidadController::class,
                'listarEspecialidadesActivas',
                [RolesSistema::ADMINISTRADOR],
                [],
            ),
            new RutaHttp(
                'GET',
                '/api/especialidades',
                EspecialidadController::class,
                'listarEspecialidades',
                [RolesSistema::ADMINISTRADOR],
                [],
            ),
            new RutaHttp(
                'POST',
                '/api/especialidades',
                EspecialidadController::class,
                'registrarEspecialidad',
                [RolesSistema::ADMINISTRADOR],
                [],
            ),
            new RutaHttp(
                'GET',
                '/api/especialidades/{idEspecialidad}',
                EspecialidadController::class,
                'obtenerEspecialidadPorId',
                [RolesSistema::ADMINISTRADOR],
                ['idEspecialidad'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/especialidades/{idEspecialidad}',
                EspecialidadController::class,
                'actualizarDatosEspecialidad',
                [RolesSistema::ADMINISTRADOR],
                ['idEspecialidad'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/especialidades/{idEspecialidad}/estado',
                EspecialidadController::class,
                'cambiarEstadoEspecialidad',
                [RolesSistema::ADMINISTRADOR],
                ['idEspecialidad'],
            ),
        ];
    }
}
