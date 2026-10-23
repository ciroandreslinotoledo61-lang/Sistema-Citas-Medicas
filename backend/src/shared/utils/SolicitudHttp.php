<?php
declare(strict_types=1);

namespace CitasMedicas\Shared\Utils;

use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class SolicitudHttp
{
    public static function obtenerCuerpoJson(): array
    {
        if (!str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
            throw new ErrorAplicacion('Usa Content-Type application/json.', 415);
        }
        $contenido = file_get_contents('php://input', false, null, 0, 65537);
        if ($contenido === false || strlen($contenido) > 65536) {
            throw new ErrorAplicacion('El formulario supera el tamano permitido.', 413);
        }
        try {
            $datos = json_decode($contenido, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new ErrorAplicacion('El cuerpo JSON no es valido.', 400);
        }
        // Distinguir {} de []: ambos se decodifican como un arreglo PHP vacio.
        if (!is_array($datos) || !str_starts_with(ltrim($contenido), '{')) {
            throw new ErrorAplicacion('Se esperaba un objeto JSON.', 400);
        }
        return $datos;
    }

    public static function obtenerIdPositivo(string $valor): int
    {
        $identificador = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($identificador === false) {
            throw new ErrorAplicacion('El identificador no es valido.', 422);
        }
        return $identificador;
    }
}
