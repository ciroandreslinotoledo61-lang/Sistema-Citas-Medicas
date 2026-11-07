<?php
declare(strict_types=1);

// Verifica el arranque sin abrir conexiones ni utilizar configuracion privada.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$opciones = getopt('', ['php:']);
$php = $opciones['php'] ?? PHP_BINARY;
$script = dirname(__DIR__) . '/verificar-configuracion.php';
$entorno = array_replace(getenv(), [
    'DB_HOST' => 'postgres.example.test', 'DB_PORT' => '5432',
    'DB_NAME' => 'ejemplo', 'DB_USER' => 'ejemplo', 'DB_PASS' => 'NoEsUnaClaveReal',
    'DB_SSLMODE' => 'require', 'FRONTEND_URL' => 'https://clinica.example.test',
]);
$casos = [
    [[], 0], [['DB_HOST' => ''], 1], [['DB_PASS' => ''], 1],
    [['DB_PORT' => 'no-es-un-puerto'], 1], [['DB_PORT' => '65536'], 1],
    [['DB_SSLMODE' => 'disable'], 1], [['FRONTEND_URL' => 'http://clinica.example.test'], 1],
    [['FRONTEND_URL' => 'https://usuario:clave@clinica.example.test'], 1],
    [['FRONTEND_URL' => 'https://clinica.example.test/ruta'], 1],
    [['FRONTEND_URL' => 'https://clinica.example.test?clave=secreto'], 1],
];
foreach ($casos as $indice => [$cambios, $esperado]) {
    $proceso = proc_open([$php, $script],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias,
        dirname(__DIR__, 2), array_replace($entorno, $cambios), ['bypass_shell' => true]);
    if (!is_resource($proceso)) { throw new RuntimeException('No se pudo iniciar el verificador de configuracion.'); }
    fclose($tuberias[0]);
    try {
        $limite = microtime(true) + 10;
        do {
            $estado = proc_get_status($proceso);
            if (!$estado['running']) { break; }
            if (microtime(true) >= $limite) {
                proc_terminate($proceso);
                throw new RuntimeException('El verificador de configuracion excedio diez segundos.');
            }
            usleep(10000);
        } while (true);
        $salida = stream_get_contents($tuberias[1]);
        $errores = stream_get_contents($tuberias[2]);
        if ($estado['exitcode'] !== $esperado) {
            throw new RuntimeException('Resultado inesperado en el caso de configuracion ' . ($indice + 1) . '.');
        }
        if (str_contains($salida . $errores, $entorno['DB_PASS'])) {
            throw new RuntimeException('El verificador expuso la clave sintetica.');
        }
    } finally {
        if (proc_get_status($proceso)['running']) { proc_terminate($proceso); }
        fclose($tuberias[1]); fclose($tuberias[2]); proc_close($proceso);
    }
}
echo 'OK: ' . count($casos) . " casos de configuracion; ningun secreto en los mensajes.\n";
