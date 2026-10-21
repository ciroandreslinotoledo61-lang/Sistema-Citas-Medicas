<?php
declare(strict_types=1);

namespace CitasMedicas\Presentation\Middlewares;

use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class VerificarRolUsuario
{
    public static function exigirRolPermitido(Usuario $usuario, array $rolesPermitidos): void
    {
        if (!in_array($usuario->codigoRol, $rolesPermitidos, true)) {
            throw new ErrorAplicacion('No tienes permiso para realizar esta accion.', 403);
        }
    }
}
