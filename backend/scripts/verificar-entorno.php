<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/src/bootstrap.php';

echo 'PHP: ' . PHP_VERSION . PHP_EOL;
echo 'php.ini: ' . (php_ini_loaded_file() ?: 'No se cargo ningun archivo') . PHP_EOL;
$entornoCorrecto = PHP_VERSION_ID >= 80200;
foreach (['pdo', 'pdo_pgsql', 'mbstring', 'curl', 'session', 'ctype'] as $extension) {
    $disponible = extension_loaded($extension);
    echo $extension . ': ' . ($disponible ? 'OK' : 'FALTA') . PHP_EOL;
    $entornoCorrecto = $entornoCorrecto && $disponible;
}
if (in_array('--conexion', $argv, true)) {
    try {
        $conexion = \CitasMedicas\Data\Config\ConexionPostgresql::obtenerConexionPostgresql();
        $conexion->query('SELECT 1');
        echo 'Conexion PostgreSQL: OK' . PHP_EOL;
    } catch (Throwable $error) {
        echo 'Conexion PostgreSQL: FALLO. Revisa DB_* y que la base exista.' . PHP_EOL;
        $entornoCorrecto = false;
    }
}
exit($entornoCorrecto ? 0 : 1);
