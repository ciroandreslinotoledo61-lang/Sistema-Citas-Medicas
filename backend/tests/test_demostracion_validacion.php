<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Data\Seeders\DemostracionSeeder;

$campos = ['APP_ENV', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DEMO_SEED_ENABLE'];
$anteriores = [];
foreach ($campos as $campo) { $anteriores[$campo] = getenv($campo); }
$valido = ['APP_ENV'=>'local', 'DB_HOST'=>'127.0.0.1', 'DB_PORT'=>'5432', 'DB_NAME'=>'vidasalud_demo_pruebas', 'DEMO_SEED_ENABLE'=>'1'];
$casos = [
    ['APP_ENV'=>'production'], ['APP_ENV'=>'staging'], ['APP_ENV'=>''], ['DEMO_SEED_ENABLE'=>''],
    ['DB_HOST'=>'ep-ejemplo.neon.tech'], ['DB_HOST'=>'192.168.1.2'], ['DB_HOST'=>'127.0.0.1;host=remoto'],
    ['DB_NAME'=>'citas_medicas'], ['DB_NAME'=>'produccion_pruebas;host=remoto'], ['DB_NAME'=>''],
    ['DB_PORT'=>''], ['DB_PORT'=>'0'], ['DB_PORT'=>'65536'], ['DB_PORT'=>'5432;host=remoto'],
];
try {
    foreach ($casos as $cambio) {
        foreach (array_replace($valido, $cambio) as $campo=>$valor) { putenv($campo . '=' . $valor); }
        try { DemostracionSeeder::validarEntorno(); }
        catch (RuntimeException) { continue; }
        throw new RuntimeException('El seed aceptó un destino no autorizado: ' . json_encode($cambio));
    }
    foreach ($valido as $campo=>$valor) { putenv($campo . '=' . $valor); }
    if (DemostracionSeeder::validarEntorno() !== $valido['DB_NAME']) { throw new RuntimeException('Destino local inválido.'); }
} finally {
    foreach ($anteriores as $campo=>$valor) { putenv($valor === false ? $campo : $campo . '=' . $valor); }
}
echo "OK: 14 configuraciones inseguras rechazadas sin conexión y destino local explícito admitido.\n";
