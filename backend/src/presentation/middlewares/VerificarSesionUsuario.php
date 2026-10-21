<?php
declare(strict_types=1);

namespace CitasMedicas\Presentation\Middlewares;

use CitasMedicas\Business\Services\AutenticacionService;
use CitasMedicas\Data\Models\Usuario;

final class VerificarSesionUsuario
{
    public static function exigirUsuarioAutenticado(AutenticacionService $autenticacionService): Usuario
    {
        return $autenticacionService->obtenerUsuarioAutenticado();
    }
}
