<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Seeders\DemostracionSeeder;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;

try {
    // Obligatorio antes de abrir una conexión: por defecto no se ejecuta.
    DemostracionSeeder::validarEntorno();
    $clave = CargarVariablesEntorno::obtenerVariableEntorno('DEMO_SEED_PASSWORD');
    if ($clave === '') { throw new RuntimeException('Configura DEMO_SEED_PASSWORD fuera del repositorio.'); }
    $resultado = DemostracionSeeder::cargarDatosDemostracion(ConexionPostgresql::obtenerConexionPostgresql(), $clave);
    foreach ($resultado as $cuenta) {
        echo $cuenta['correo'] . ': ' . $cuenta['estado'] . ' (usuario #' . $cuenta['idUsuario'] . ')' . PHP_EOL;
    }
    echo "No se actualizaron contraseñas ni cuentas existentes. Las altas son transaccionales por cuenta.\n";
} catch (Throwable $error) {
    $mensaje = $error instanceof PDOException ? 'Conflicto de datos o esquema incompleto en la base de pruebas.' : $error->getMessage();
    fwrite(STDERR, 'Seed detenido: ' . $mensaje . " Las altas anteriores, si las hubo, se conservan; puede repetirse sin duplicarlas.\n");
    exit(1);
}
