<?php

declare(strict_types=1);

namespace CitasMedicas\Shared\Utils;

final class RespuestaJson
{
    public static function enviarRespuestaJson(array $contenido, int $codigoHttp = 200): never
    {
        http_response_code($codigoHttp);
        if ($codigoHttp === 419) {
            // Apache necesita la frase explicita para conservar este codigo no estandar.
            header('HTTP/1.1 419 Page Expired', true, 419);
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($contenido, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }
}
