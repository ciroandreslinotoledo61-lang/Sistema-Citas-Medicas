<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Shared\Config\CargarVariablesEntorno;

$pruebas = [
    'test_arquitectura.php',
    'test_validaciones.php',
    'test_permisos.php',
    'test_validaciones_especialidades_medicos.php',
    'test_validaciones_agenda.php',
    'test_validaciones_actualizacion.php',
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
    ]);
}
foreach ($pruebas as $prueba) {
    echo "\n" . $prueba . "\n";
    $proceso = proc_open([PHP_BINARY, __DIR__ . '/' . $prueba], [STDIN, STDOUT, STDERR], $tuberias);
    if (!is_resource($proceso) || proc_close($proceso) !== 0) {
        fwrite(STDERR, "Fallo la prueba. Se detuvo la ejecucion.\n");
        exit(1);
    }
}
echo "\nOK: " . count($pruebas) . " archivos de pruebas completados.\n";
