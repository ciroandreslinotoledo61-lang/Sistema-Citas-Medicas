<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Data\Config\ConexionPostgresql;

try {
    $conexion = ConexionPostgresql::obtenerConexionPostgresql();
    $conexion->beginTransaction();
    $conexion->query('SELECT pg_advisory_xact_lock(2026102101)');
    $tablaExistente = $conexion->query("SELECT to_regclass('public.roles')")->fetchColumn();
    if ($tablaExistente !== null && $tablaExistente !== false && $tablaExistente !== '') {
        $conexion->rollBack();
        fwrite(STDERR, "Las tablas ya existen. Esta migracion inicial solo se ejecuta una vez. No se modifico nada.\n");
        exit(1);
    }
    $sqlMigracion = file_get_contents(dirname(__DIR__) . '/src/data/migrations/001_crear_tablas_sistema.sql');
    if ($sqlMigracion === false) { throw new RuntimeException('No se encontro la migracion.'); }
    $conexion->exec($sqlMigracion);
    $conexion->commit();
    echo "Migracion 001 completada: 11 tablas creadas.\n";
} catch (Throwable $error) {
    if (isset($conexion) && $conexion->inTransaction()) { $conexion->rollBack(); }
    fwrite(STDERR, "No se pudo aplicar la migracion. Revisa la configuracion y usa una base vacia.\n");
    exit(1);
}
