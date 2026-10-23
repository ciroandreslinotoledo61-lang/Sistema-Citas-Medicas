<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
use CitasMedicas\Data\Config\ConexionPostgresql;
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$conexion = null;
try {
    $conexion = ConexionPostgresql::obtenerConexionPostgresql();
    $conexion->beginTransaction();
    $conexion->exec('SELECT pg_advisory_xact_lock(2026102304)');
    $conexion->exec(file_get_contents(dirname(__DIR__) . '/src/data/migrations/004_agenda_30_minutos_y_perfiles.sql'));
    $conexion->commit();
    echo "Actualizacion 004 completada. Se conservaron las reservas y sus horarios.\n";
} catch (Throwable $error) {
    if ($conexion?->inTransaction()) { $conexion->rollBack(); }
    fwrite(STDERR, "No se aplico la actualizacion. Revisa conexion, permisos y migraciones 001 a 003. No recrees la base.\n");
    exit(1);
}
