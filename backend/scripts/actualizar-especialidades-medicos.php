<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Data\Config\ConexionPostgresql;

try {
    $conexionPostgresql = ConexionPostgresql::obtenerConexionPostgresql();
    $conexionPostgresql->beginTransaction();
    $conexionPostgresql->query('SELECT pg_advisory_xact_lock(2026102101)');
    $sqlMigracion = file_get_contents(dirname(__DIR__) . '/src/data/migrations/002_unicidad_especialidades_medicos.sql');
    if ($sqlMigracion === false) { throw new RuntimeException('No se encontro la migracion 002.'); }
    $conexionPostgresql->exec($sqlMigracion);
    $conexionPostgresql->commit();
    echo "Migracion 002 completada: nombres de especialidad y matriculas protegidos contra duplicados.\n";
    echo "Se conservaron las tablas y los registros existentes.\n";
} catch (Throwable $error) {
    if (isset($conexionPostgresql) && $conexionPostgresql->inTransaction()) { $conexionPostgresql->rollBack(); }
    if ($error instanceof PDOException && (string) $error->getCode() === '23505') {
        fwrite(STDERR, "Hay especialidades o matriculas repetidas al ignorar mayusculas y espacios.\n");
        fwrite(STDERR, "Revisa docs/ai/actualizacion-especialidades-medicos.md. No se modificaron los datos.\n");
    } else {
        fwrite(STDERR, "No se pudo aplicar la migracion 002. Revisa la conexion y que la migracion 001 ya este aplicada.\n");
    }
    exit(1);
}
