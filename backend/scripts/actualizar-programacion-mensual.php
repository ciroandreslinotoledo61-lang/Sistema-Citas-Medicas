<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
use CitasMedicas\Data\Config\ConexionPostgresql;
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$conexion = null;
try {
    $conexion = ConexionPostgresql::obtenerConexionPostgresql();
    $conexion->beginTransaction();
    $conexion->exec('SELECT pg_advisory_xact_lock(2026102405)');
    // Falla antes de aplicar cambios si falta 004. No ejecuta migraciones anteriores.
    $conexion->query('SELECT duracion_cita_minutos FROM horarios_medicos LIMIT 0');
    $conexion->query('SELECT duracion_turno_minutos FROM turnos LIMIT 0');
    $conexion->exec(file_get_contents(dirname(__DIR__) . '/src/data/migrations/005_programacion_y_cita_activa.sql'));
    $pendientes = (int) $conexion->query("SELECT count(*) FROM (
        SELECT id_paciente FROM citas WHERE estado_cita IN ('pendiente','confirmada')
        GROUP BY id_paciente HAVING count(*)>1) pacientes_con_varias_citas")->fetchColumn();
    $conexion->commit();
    echo "Actualizacion 005 completada. No se modificaron los horarios ni los estados anteriores.\n";
    echo "Pacientes con varias citas activas previas: {$pendientes}. Resolverlas desde la agenda antes de nuevas reservas.\n";
} catch (Throwable $error) {
    if ($conexion?->inTransaction()) { $conexion->rollBack(); }
    fwrite(STDERR, "No se aplico 005. Revisa la conexion, los permisos y que 004 ya este instalada. No recrees la base.\n");
    exit(1);
}
