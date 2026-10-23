<?php
declare(strict_types=1);

function cargarVariablesEntornoFrontend(string $rutaArchivo): void
{
    if (!is_file($rutaArchivo)) { return; }
    foreach (file($rutaArchivo, FILE_IGNORE_NEW_LINES) ?: [] as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#')) { continue; }
        if (!preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $linea, $coincidencia)) {
            throw new RuntimeException('Formato .env no valido.');
        }
        $valorVariable = trim($coincidencia[2]);
        if (strlen($valorVariable) > 1 &&
            (($valorVariable[0] === '"' && str_ends_with($valorVariable, '"')) ||
             ($valorVariable[0] === "'" && str_ends_with($valorVariable, "'")))) {
            $valorVariable = substr($valorVariable, 1, -1);
        }
        if (getenv($coincidencia[1]) === false) { putenv($coincidencia[1] . '=' . $valorVariable); }
    }
}

function obtenerConfiguracionFrontend(string $nombreVariable, string $valorPredeterminado = ''): string
{
    $valorVariable = getenv($nombreVariable);
    return $valorVariable === false ? $valorPredeterminado : $valorVariable;
}

function escaparTextoHtml(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

cargarVariablesEntornoFrontend(dirname(__DIR__, 2) . '/.env');
date_default_timezone_set('America/La_Paz');
