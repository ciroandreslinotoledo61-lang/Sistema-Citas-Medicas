<?php
declare(strict_types=1);

namespace CitasMedicas\Shared\Config;

final class CargarVariablesEntorno
{
    public static function cargarArchivoEntorno(string $rutaArchivo): void
    {
        if (!is_file($rutaArchivo)) {
            return;
        }
        foreach (file($rutaArchivo, FILE_IGNORE_NEW_LINES) ?: [] as $numeroLinea => $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }
            if (!preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $linea, $coincidencia)) {
                throw new \RuntimeException('Linea invalida en .env: ' . ($numeroLinea + 1));
            }
            $nombreVariable = $coincidencia[1];
            $valorVariable = trim($coincidencia[2]);
            if (strlen($valorVariable) >= 2 &&
                (($valorVariable[0] === '"' && str_ends_with($valorVariable, '"')) ||
                 ($valorVariable[0] === "'" && str_ends_with($valorVariable, "'")))) {
                $valorVariable = substr($valorVariable, 1, -1);
            }
            if (getenv($nombreVariable) === false) {
                putenv($nombreVariable . '=' . $valorVariable);
            }
        }
    }

    public static function obtenerVariableEntorno(string $nombreVariable, string $valorPredeterminado = ''): string
    {
        $valorVariable = getenv($nombreVariable);
        return $valorVariable === false ? $valorPredeterminado : $valorVariable;
    }
}
