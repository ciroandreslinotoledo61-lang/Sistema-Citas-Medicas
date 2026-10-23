<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/src/bootstrap.php';
use CitasMedicas\Data\Config\ConexionPostgresql;

try {
    $conexionPostgresql = ConexionPostgresql::obtenerConexionPostgresql();
    $conexionPostgresql->beginTransaction();
    $conexionPostgresql->query('SELECT pg_advisory_xact_lock(2026102203)');
    $sqlMigracion = file_get_contents(dirname(__DIR__) . '/src/data/migrations/003_responsabilidades_citas.sql');
    if ($sqlMigracion === false) { throw new RuntimeException('No se encontro la migracion 003.'); }
    $conexionPostgresql->exec($sqlMigracion);
    $conexionPostgresql->commit();
    echo "Migracion 003 completada: asistencia y trazabilidad de citas.\n";
    echo "Se conservaron los registros y las cuentas existentes.\n";
} catch (Throwable $error) {
    if (isset($conexionPostgresql) && $conexionPostgresql->inTransaction()) { $conexionPostgresql->rollBack(); }
    fwrite(STDERR, "No se pudo aplicar la migracion 003. Revisa la conexion y las migraciones anteriores.\n");
    exit(1);
}
