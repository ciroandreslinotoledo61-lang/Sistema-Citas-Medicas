<?php

declare(strict_types=1);

namespace CitasMedicas\Shared\Utils;

final class RespuestaJson
{
    public static function enviarRespuestaJson(array $contenido, int $codigoHttp = 200): never
    {
        http_response_code($codigoHttp);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($contenido, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }
}
