<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Impide cargar configuracion privada: este ejecutor solo acepta el cluster aislado.
if (getenv('VIDASALUD_TEST_LOCAL') !== '1' || getenv('DB_HOST') !== '127.0.0.1'
    || !str_ends_with(getenv('DB_NAME') ?: '', '_pruebas')) {
    fwrite(STDERR, "Usa ejecutar-pruebas.php --completo con PostgreSQL local aislado.\n"); exit(1);
}
require dirname(__DIR__) . '/src/bootstrap.php';
require_once __DIR__ . '/SoporteProcesosPrueba.php';
require_once __DIR__ . '/http/ClienteHttpPruebas.php';
require_once __DIR__ . '/http/escenario_http.php';

$conexion = CitasMedicas\Data\Config\ConexionPostgresql::obtenerConexionPostgresql();
comprobarHttp((int) $conexion->query('SELECT count(*) FROM usuarios')->fetchColumn() === 4,
    'El contrato requiere una base nueva con solo las cuatro cuentas del seeder.');
$clave = getenv('SEED_USERS_PASSWORD');
comprobarHttp(is_string($clave) && $clave !== '', 'Falta la clave sintetica del seeder.');
$proyecto = dirname(__DIR__, 2);
$temporal = $proyecto . '/.tmp-auth/contrato-php-' . bin2hex(random_bytes(6));
mkdir($temporal, 0700, true);
$apiPuerto = SoporteProcesosPrueba::puertoLibre();
do { $frontPuerto = SoporteProcesosPrueba::puertoLibre(); } while ($apiPuerto === $frontPuerto);
$api = "http://127.0.0.1:$apiPuerto/api";
$front = "http://127.0.0.1:$frontPuerto";
$entorno = array_replace(getenv(), ['API_URL' => $api, 'API_INTERNAL_URL' => $api, 'FRONTEND_URL' => $front]);
$servidores = [];
try {
    foreach (['backend' => $apiPuerto, 'frontend' => $frontPuerto] as $carpeta => $puerto) {
        $proceso = SoporteProcesosPrueba::iniciar([PHP_BINARY, '-S', "127.0.0.1:$puerto", '-t', 'public', 'src/server.php'],
            $proyecto . '/' . $carpeta, $entorno, $temporal . '/' . $carpeta . '.log');
        $servidores[] = $proceso;
        SoporteProcesosPrueba::esperarServidor($proceso, $puerto);
    }
    $resultados = ejecutarContratoHttp($api, $front, $clave);
    comprobarHttp(count($resultados) === 137, 'Cambio inesperado en la cobertura del contrato HTTP.');
    echo 'OK: ' . count($resultados) . " comprobaciones HTTP; contrato previo y vistas por rol.\n";
} finally {
    foreach (array_reverse($servidores) as $proceso) { SoporteProcesosPrueba::detener($proceso); }
}
