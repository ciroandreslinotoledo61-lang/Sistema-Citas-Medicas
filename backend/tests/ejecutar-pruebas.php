<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (in_array('--completo', $argv, true)) {
    require __DIR__ . '/test_autenticacion_http.php';
    try { ejecutarPruebasHttpAisladas($argv); }
    catch (Throwable $error) { fwrite(STDERR, $error->getMessage() . PHP_EOL); exit(1); }
    exit;
}
require dirname(__DIR__) . '/src/bootstrap.php';
require_once __DIR__ . '/SoporteProcesosPrueba.php';

use CitasMedicas\Shared\Config\CargarVariablesEntorno;

$pruebas = [
    'test_arquitectura.php',
    'test_validaciones.php',
    'test_permisos.php',
    'test_credenciales_validacion.php',
    'test_recuperacion_validacion.php',
    'test_validaciones_especialidades_medicos.php',
    'test_validaciones_agenda.php',
    'test_validaciones_actualizacion.php',
    'test_demostracion_validacion.php',
    'test_modulos_validacion.php',
    'test_validaciones_cierre.php',
];
if (in_array('--integracion', $argv, true)) {
    if (!str_ends_with(CargarVariablesEntorno::obtenerVariableEntorno('DB_NAME'), '_pruebas')) {
        fwrite(STDERR, "Configura una base independiente terminada en _pruebas.\n");
        exit(1);
    }
    $pruebas = array_merge($pruebas, [
        'test_registro_paciente.php',
        'test_gestion_especialidades_medicos.php',
        'test_gestion_citas.php',
        'test_reportes_perfiles.php',
        'test_programacion_periodos.php',
        'test_migracion_005.php',
        'test_migracion_006.php',
        'test_consultas_bitacora.php',
        'test_reserva_concurrente.php',
        'test_transacciones_capas.php',
        'test_credenciales_transacciones.php',
        'test_recuperacion_transacciones.php',
        'test_migracion_010.php',
        'test_paciente_citas.php',
        'test_correcciones_agenda.php',
        'test_demostracion_seed.php',
        'test_modulos_atencion.php',
        'test_modulos_migraciones.php',
        'test_modulos_concurrencia.php',
        'test_citas_vencidas.php',
    ]);
}
$proyecto = dirname(__DIR__, 2);
$artefactos = $proyecto . '/.tmp-auth/suites-php-' . bin2hex(random_bytes(6));
if (!mkdir($artefactos, 0700, true)) {
    fwrite(STDERR, "No se pudo preparar la salida de las pruebas.\n");
    exit(1);
}
echo 'Resultados por suite: ' . substr($artefactos, strlen($proyecto) + 1) . PHP_EOL;
foreach ($pruebas as $prueba) {
    echo "\n" . $prueba . "\n";
    // Cada hijo escribe su propio archivo: evita que STDOUT heredado sobrescriba
    // la salida anterior al ejecutar PHP anidado en Windows.
    try {
        echo SoporteProcesosPrueba::ejecutar([PHP_BINARY, __DIR__ . '/' . $prueba],
            $proyecto, getenv(), $artefactos . '/' . $prueba . '.log');
    } catch (Throwable $error) {
        fwrite(STDERR, $error->getMessage() . ' Artefactos: ' . $artefactos . PHP_EOL);
        exit(1);
    }
}
echo "\nOK: " . count($pruebas) . " archivos de pruebas completados.\n";
