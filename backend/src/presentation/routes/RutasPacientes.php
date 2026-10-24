<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Presentation\Controllers\PacienteController;
use CitasMedicas\Presentation\Http\RutaHttp;
use CitasMedicas\Shared\Constants\RolesSistema;

final class RutasPacientes
{
    /** @return RutaHttp[] */
    public static function definir(): array
    {
        return [
            new RutaHttp(
                'GET',
                '/api/pacientes/mi-perfil',
                PacienteController::class,
                'obtenerPerfilPaciente',
                [RolesSistema::PACIENTE],
                ['idUsuarioAutenticado'],
            ),
            new RutaHttp(
                'GET',
                '/api/pacientes',
                PacienteController::class,
                'listarPacientes',
                [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA],
                [],
            ),
            new RutaHttp(
                'POST',
                '/api/pacientes',
                PacienteController::class,
                'registrarPacienteConHistoriaClinica',
                [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA],
                [],
            ),
            new RutaHttp(
                'GET',
                '/api/pacientes/{idPaciente}',
                PacienteController::class,
                'obtenerPacientePorId',
                [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA],
                ['idPaciente'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/pacientes/{idPaciente}',
                PacienteController::class,
                'actualizarDatosPaciente',
                [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA],
                ['usuario', 'idPaciente'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/pacientes/{idPaciente}/estado',
                PacienteController::class,
                'cambiarEstadoPaciente',
                [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA],
                ['usuario', 'idPaciente'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/pacientes/{idPaciente}/contacto',
                PacienteController::class,
                'actualizarContactoPaciente',
                [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA],
                ['idPaciente'],
            ),
        ];
    }
}
