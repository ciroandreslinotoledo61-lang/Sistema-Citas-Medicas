<?php
declare(strict_types=1);

namespace CitasMedicas\Presentation\Middlewares;

use CitasMedicas\Shared\Config\CargarVariablesEntorno;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class VerificarOrigenSolicitud
{
    public static function aplicarPoliticaOrigen(): void
    {
        $origenPermitido = rtrim(CargarVariablesEntorno::obtenerVariableEntorno('FRONTEND_URL', 'http://127.0.0.1:8080'), '/');
        $origenSolicitud = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origenSolicitud !== '' && $origenSolicitud !== $origenPermitido) {
            throw new ErrorAplicacion('El origen de la solicitud no esta permitido.', 403);
        }
        if ($origenSolicitud !== '') {
            header('Access-Control-Allow-Origin: ' . $origenPermitido);
            header('Access-Control-Allow-Credentials: true');
            header('Vary: Origin');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
            header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
