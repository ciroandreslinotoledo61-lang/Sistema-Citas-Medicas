<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Presentation\Controllers\MedicoController;
use CitasMedicas\Presentation\Http\RutaHttp;
use CitasMedicas\Shared\Constants\RolesSistema;

final class RutasMedicos
{
    /** @return RutaHttp[] */
    public static function definir(): array
    {
        return [
            new RutaHttp(
                'GET',
                '/api/medicos',
                MedicoController::class,
                'listarMedicos',
                [RolesSistema::ADMINISTRADOR],
                [],
            ),
            new RutaHttp(
                'POST',
                '/api/medicos',
                MedicoController::class,
                'registrarMedicoConUsuario',
                [RolesSistema::ADMINISTRADOR],
                [],
            ),
            new RutaHttp(
                'GET',
                '/api/medicos/{idMedico}',
                MedicoController::class,
                'obtenerMedicoPorId',
                [RolesSistema::ADMINISTRADOR],
                ['idMedico'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/medicos/{idMedico}',
                MedicoController::class,
                'actualizarDatosMedico',
                [RolesSistema::ADMINISTRADOR],
                ['idMedico'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/medicos/{idMedico}/estado',
                MedicoController::class,
                'cambiarEstadoMedico',
                [RolesSistema::ADMINISTRADOR],
                ['idMedico'],
            ),
        ];
    }
}
