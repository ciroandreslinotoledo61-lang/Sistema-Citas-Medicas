<?php
declare(strict_types=1);

$rutaSolicitada = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (str_starts_with($rutaSolicitada, '/assets/')) {
    $rutaRecurso = realpath(__DIR__ . '/' . substr($rutaSolicitada, strlen('/assets/')));
    $extensionRecurso = $rutaRecurso ? pathinfo($rutaRecurso, PATHINFO_EXTENSION) : '';
    if ($rutaRecurso && str_starts_with($rutaRecurso, __DIR__ . DIRECTORY_SEPARATOR) &&
        in_array($extensionRecurso, ['css', 'js'], true) && is_file($rutaRecurso)) {
        header('Content-Type: ' . ($extensionRecurso === 'css' ? 'text/css' : 'text/javascript') . '; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        readfile($rutaRecurso);
        return;
    }
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/public/index.php';
