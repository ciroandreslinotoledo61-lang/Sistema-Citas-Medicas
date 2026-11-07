<?php
declare(strict_types=1);

// Router exclusivo de php -S iniciado por el runner; no se publica en public/.
if (PHP_SAPI !== 'cli-server' || ($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/CorreoPrueba.php';
exigirEntornoRecuperacionAislado();
$archivo = getenv('RECUPERACION_BUZON_PRUEBAS');
if (!$archivo) { throw new RuntimeException('Falta el buzon local de pruebas.'); }
$fabricaControladores = new CitasMedicas\Presentation\Http\FabricaControladores(new CorreoPrueba($archivo));
require dirname(__DIR__) . '/src/app.php';
