<?php
declare(strict_types=1);

$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$raiz = dirname(__DIR__, 2);
if ($ruta === '/api' || str_starts_with($ruta, '/api/')) {
    require $raiz . '/backend/public/index.php';
} else {
    require $raiz . '/frontend/src/server.php';
}
