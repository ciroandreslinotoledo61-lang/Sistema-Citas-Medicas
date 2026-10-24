<?php

declare(strict_types=1);

namespace CitasMedicas\Shared\Constants;

use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class PermisosSistema
{
    public const ROLES_POR_OPERACION = [
        'usuarios.gestionar' => [RolesSistema::ADMINISTRADOR],
        'pacientes.gestionar' => [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA],
        'especialidades.gestionar' => [RolesSistema::ADMINISTRADOR],
        'medicos.gestionar' => [RolesSistema::ADMINISTRADOR],
        'horarios.crear' => [RolesSistema::ADMINISTRADOR],
        'horarios.consultar' => [RolesSistema::ADMINISTRADOR, RolesSistema::MEDICO],
        'disponibilidad.consultar' => [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA, RolesSistema::PACIENTE],
        'citas.consultar' => [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA, RolesSistema::MEDICO, RolesSistema::PACIENTE],
        'citas.reservar' => [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA, RolesSistema::PACIENTE],
        'citas.cancelar' => [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA, RolesSistema::MEDICO, RolesSistema::PACIENTE],
        'citas.reprogramar' => [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA],
        'reportes.consultar' => [RolesSistema::ADMINISTRADOR],
        'agenda.filtrar' => [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA, RolesSistema::MEDICO],
        'citas.atender' => [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA, RolesSistema::MEDICO],
        'citas.confirmar' => [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA],
        'citas.inasistencia' => [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA, RolesSistema::MEDICO],
        'citas.asistencia' => [RolesSistema::ADMINISTRADOR, RolesSistema::RECEPCIONISTA],
    ];

    public static function tienePermiso(string $codigoRol, string $operacion): bool
    {
        return in_array($codigoRol, self::ROLES_POR_OPERACION[$operacion] ?? [], true);
    }

    public static function exigirPermiso(Usuario $usuario, string $operacion): void
    {
        if (!$usuario->usuarioActivo || !self::tienePermiso($usuario->codigoRol, $operacion)) {
            throw new ErrorAplicacion('Tu cuenta no tiene permiso para realizar esta operacion.', 403);
        }
    }
}
