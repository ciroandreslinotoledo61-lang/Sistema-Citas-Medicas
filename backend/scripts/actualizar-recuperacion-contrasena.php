<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

// La vista previa no carga configuracion privada ni abre conexiones.
echo "Migracion 010: tokens de recuperacion por correo, hash, vencimiento y uso unico.\n";
echo "Requiere 001-009; conserva usuarios, contrasenas y datos clinicos.\n";
if (!in_array('--aplicar', $argv, true)) {
    echo "Vista previa: ninguna base modificada. --aplicar solo admite PostgreSQL local y DB_NAME terminado en _pruebas.\n";
    exit;
}

require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;

if (CargarVariablesEntorno::obtenerVariableEntorno('APP_ENV', 'local') === 'production' ||
    !in_array(CargarVariablesEntorno::obtenerVariableEntorno('DB_HOST'), ['127.0.0.1', 'localhost', '::1'], true) ||
    !str_ends_with(CargarVariablesEntorno::obtenerVariableEntorno('DB_NAME'), '_pruebas')) {
    fwrite(STDERR, "Detenido: solo se admite una base local de pruebas; este script no migra Neon ni produccion.\n");
    exit(1);
}

try {
    $conexion = ConexionPostgresql::obtenerConexionPostgresql();
    $conexion->beginTransaction();
    $conexion->query('SELECT pg_advisory_xact_lock(2026110110)');
    if ($conexion->query("SELECT to_regclass('public.recuperaciones_contrasena')")->fetchColumn()) {
        throw new RuntimeException('La migracion 010 ya esta presente. Revisa su estructura; no se ha repetido.');
    }
    if (!$conexion->query("SELECT to_regclass('public.intentos_autenticacion')")->fetchColumn() ||
        !(int) $conexion->query("SELECT count(*) FROM information_schema.columns WHERE table_schema='public' AND table_name='usuarios' AND column_name='contrasena_temporal_usada'")->fetchColumn()) {
        throw new RuntimeException('Instala primero las migraciones 001-009 en esta base local.');
    }
    $sql = file_get_contents(dirname(__DIR__) . '/src/data/migrations/010_recuperacion_contrasena.sql');
    if ($sql === false) { throw new RuntimeException('No se pudo leer la migracion 010.'); }
    $conexion->exec($sql);
    $conexion->commit();
    echo "Migracion 010 aplicada a la base local de pruebas.\n";
} catch (Throwable $error) {
    if (isset($conexion) && $conexion->inTransaction()) { $conexion->rollBack(); }
    fwrite(STDERR, $error instanceof RuntimeException && !$error instanceof PDOException
        ? $error->getMessage() . PHP_EOL
        : "No se aplico la migracion. Revisa la conexion local y las migraciones previas.\n");
    exit(1);
}
