<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$errores = [];
foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $nombre) {
    if (getenv($nombre) === false || getenv($nombre) === '') {
        $errores[] = 'Falta configurar ' . $nombre . ' en Environment de Render.';
    }
}
$puerto = getenv('DB_PORT') ?: '';
if (!ctype_digit($puerto) || (int) $puerto < 1 || (int) $puerto > 65535) {
    $errores[] = 'DB_PORT debe ser un numero entre 1 y 65535.';
}
if (!in_array(getenv('DB_SSLMODE'), ['require', 'verify-ca', 'verify-full'], true)) {
    $errores[] = 'Configura DB_SSLMODE=require para PostgreSQL en Render.';
}
$url = getenv('FRONTEND_URL') ?: '';
$partes = parse_url($url);
if (
    !filter_var($url, FILTER_VALIDATE_URL) || ($partes['scheme'] ?? '') !== 'https' ||
    empty($partes['host']) || !in_array($partes['path'] ?? '', ['', '/'], true) ||
    isset($partes['user']) || isset($partes['pass']) || isset($partes['query']) || isset($partes['fragment'])
) {
    $errores[] = 'FRONTEND_URL debe ser el origen HTTPS, por ejemplo https://mi-clinica.onrender.com.';
}
foreach ($errores as $error) {
    fwrite(STDERR, $error . PHP_EOL);
}
exit($errores === [] ? 0 : 1);
