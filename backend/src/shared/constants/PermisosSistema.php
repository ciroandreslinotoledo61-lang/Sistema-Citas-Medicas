<?php
declare(strict_types=1);
namespace CitasMedicas\Shared\Constants;

use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class PermisosSistema
{
    public const ROLES_POR_OPERACION = [
        'usuarios.gestionar' => ['administrador'],
        'pacientes.gestionar' => ['administrador', 'recepcionista'],
        'especialidades.gestionar' => ['administrador'],
        'medicos.gestionar' => ['administrador'],
        'horarios.crear' => ['administrador'],
        'horarios.consultar' => ['administrador', 'medico'],
        'disponibilidad.consultar' => ['administrador', 'recepcionista', 'paciente'],
        'citas.consultar' => ['administrador', 'recepcionista', 'medico', 'paciente'],
        'citas.reservar' => ['administrador', 'recepcionista', 'paciente'],
        'citas.cancelar' => ['administrador', 'recepcionista', 'medico', 'paciente'],
        'citas.reprogramar' => ['administrador', 'recepcionista'],
        'reportes.consultar' => ['administrador'],
        'agenda.filtrar' => ['administrador', 'recepcionista', 'medico'],
        'citas.atender' => ['administrador', 'recepcionista', 'medico'],
        'citas.confirmar' => ['administrador', 'recepcionista'],
        'citas.inasistencia' => ['administrador', 'recepcionista', 'medico'],
        'citas.asistencia' => ['administrador', 'recepcionista'],
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
