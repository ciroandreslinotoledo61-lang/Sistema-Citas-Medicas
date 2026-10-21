<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Shared\Config\CargarVariablesEntorno;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Seeders\DatosInicialesSeeder;

if (CargarVariablesEntorno::obtenerVariableEntorno('APP_ENV', 'local') !== 'local') {
    fwrite(STDERR, "Los datos de prueba solo se cargan con APP_ENV=local.\n");
    exit(1);
}
try {
    DatosInicialesSeeder::cargarDatosIniciales(
        ConexionPostgresql::obtenerConexionPostgresql(),
        CargarVariablesEntorno::obtenerVariableEntorno('SEED_USERS_PASSWORD')
    );
    echo "Se crearon 4 roles, 3 especialidades y 4 cuentas de prueba.\n";
    echo "La clave de las cuentas es la que configuraste en SEED_USERS_PASSWORD.\n";
} catch (Throwable $error) {
    $mensaje = $error instanceof PDOException ? 'Revisa la base de datos y aplica primero la migracion.' : $error->getMessage();
    fwrite(STDERR, 'No se cargaron los datos: ' . $mensaje . PHP_EOL);
    exit(1);
}
