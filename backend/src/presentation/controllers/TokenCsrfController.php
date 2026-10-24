<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Controllers;

use CitasMedicas\Shared\Utils\RespuestaJson;
use CitasMedicas\Shared\Utils\SesionUsuario;

final class TokenCsrfController
{
    public function obtenerToken(): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => ['tokenCsrf' => SesionUsuario::obtenerTokenCsrf()]]);
    }
}
