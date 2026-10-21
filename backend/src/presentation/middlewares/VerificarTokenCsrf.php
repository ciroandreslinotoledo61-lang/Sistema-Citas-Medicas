<?php
declare(strict_types=1);

namespace CitasMedicas\Presentation\Middlewares;

use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SesionUsuario;

final class VerificarTokenCsrf
{
    public static function exigirTokenCsrfValido(): void
    {
        $tokenRecibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if ($tokenRecibido === '' || !hash_equals(SesionUsuario::obtenerTokenCsrf(), $tokenRecibido)) {
            throw new ErrorAplicacion('La sesion del formulario vencio. Recarga la pagina.', 419);
        }
    }
}
