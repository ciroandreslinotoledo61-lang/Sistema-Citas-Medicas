<?php
declare(strict_types=1);

require_once __DIR__ . '/http/ClienteHttpPruebas.php';

/** Flujo HTTP real; solo el transporte de correo se sustituye en el router aislado. */
function probarRecuperacionHttp(string $base, string $front, string $clave, callable $soporte, callable $reiniciar, string $buzon): array
{
    $resultados = [];
    $secretos = [$clave];
    $cliente = static function () use ($base, &$resultados, &$secretos): ClienteHttpPruebas {
        return new ClienteHttpPruebas($base, $resultados, $secretos);
    };
    $anon = $cliente();
    $sufijo = bin2hex(random_bytes(5));
    $correos = $cuentas = [];
    foreach (['valida', 'otra', 'expirada', 'reemitida', 'limite', 'confirmar', 'inactiva', 'fallo', 'correo', 'cambio', 'admin'] as $nombre) {
        $correos[$nombre] = 'rec-' . $nombre . '-' . $sufijo . '@example.test';
        $cuentas[$nombre] = json_decode($soporte('crear', $correos[$nombre]), true, 512, JSON_THROW_ON_ERROR);
    }
    $soporte('desactivar', $correos['inactiva']);
    $noexiste = 'desconocida-' . $sufijo . '@example.test';

    $mensajes = static fn(): array => is_file($buzon)
        ? json_decode(file_get_contents($buzon), true, 512, JSON_THROW_ON_ERROR) : [];
    $ultimo = static function (string $nombre) use ($mensajes, $correos, &$secretos): string {
        $enlaces = array_values(array_filter($mensajes(), static fn(array $m): bool => $m['destinatario'] === $correos[$nombre]));
        comprobarHttp($enlaces !== [], 'No se capturo el mensaje de recuperacion.');
        $url = parse_url($enlaces[array_key_last($enlaces)]['enlace']);
        comprobarHttp(($url['scheme'] ?? '') === 'http' && ($url['host'] ?? '') === '127.0.0.1', 'El enlace salio del servidor local.');
        comprobarHttp(($url['path'] ?? '') === '/recuperar-contrasena/confirmar' && empty($url['query']), 'El token figura en la ruta o query.');
        parse_str($url['fragment'] ?? '', $fragmento);
        $token = $fragmento['token'] ?? '';
        comprobarHttp(is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token) === 1, 'Formato o longitud del token inseguro.');
        $secretos[] = $token;
        return $token;
    };
    $pedir = static function (string $nombre) use ($anon, $correos, $ultimo): array {
        $respuesta = $anon->request('POST', '/autenticacion/recuperar-contrasena', ['correoUsuario' => $correos[$nombre]], 202);
        return [$ultimo($nombre), $respuesta];
    };
    $confirmar = static function (string $token, ?string $nueva = null, int $esperado = 200,
        ?ClienteHttpPruebas $actor = null, array $extras = []) use ($anon, &$secretos): string {
        $nueva ??= 'NuevaPersonal' . bin2hex(random_bytes(10));
        $secretos[] = $nueva;
        ($actor ?? $anon)->request('POST', '/autenticacion/confirmar-recuperacion', array_replace([
            'token' => $token, 'nuevaContrasena' => $nueva, 'confirmacionContrasena' => $nueva,
        ], $extras), $esperado);
        return $nueva;
    };

    foreach (['/autenticacion/recuperar-contrasena', '/autenticacion/confirmar-recuperacion'] as $ruta) {
        $anon->request('GET', $ruta, esperado: 404);
        $anon->request('POST', $ruta, [], 419, csrf: false);
        $anon->request('PUT', $ruta, [], 404);
    }
    $anon->request('POST', '/autenticacion/recuperar-contrasena', ['correoUsuario' => $correos['valida'], 'idUsuario' => 1], 422);
    // La ruta publica conserva la politica de origen del resto de la API.
    $respuestaOrigen = $anon->obtenerRespuesta('POST', $base . '/autenticacion/recuperar-contrasena',
        ['Origin' => 'https://origen-no-autorizado.example', 'Content-Type' => 'application/json'], '{}');
    comprobarHttp($respuestaOrigen['codigo'] === 403, 'Se acepto un origen no autorizado.');
    $resultados[] = ['POST', 'origen-externo', $respuestaOrigen['codigo']];
    $reiniciar();

    $anterior = $cliente();
    $anterior2 = $cliente();
    $otra = $cliente();
    $anterior->login($correos['valida'], $clave);
    $anterior2->login($correos['valida'], $clave);
    $otra->login($correos['otra'], $clave);
    $inicio = hrtime(true);
    [$token, $generica] = $pedir('valida');
    comprobarHttp((hrtime(true) - $inicio) / 1e9 >= 2.3, 'La solicitud conocida no mantiene el piso temporal.');
    $cantidad = count($mensajes());
    foreach ([$noexiste, $correos['inactiva']] as $correo) {
        $inicio = hrtime(true);
        $respuesta = $anon->request('POST', '/autenticacion/recuperar-contrasena', ['correoUsuario' => $correo], 202);
        comprobarHttp($respuesta === $generica && (hrtime(true) - $inicio) / 1e9 >= 2.3, 'La solicitud revela existencia o estado de la cuenta.');
        comprobarHttp(count($mensajes()) === $cantidad, 'Se envio correo para una cuenta inexistente o inactiva.');
    }
    $estado = json_decode($soporte('estado', $correos['valida']), true, 512, JSON_THROW_ON_ERROR);
    comprobarHttp($estado['token_hash'] === hash('sha256', $token) && $estado['token_hash'] !== $token, 'El token no se almacena exclusivamente como hash.');
    comprobarHttp($estado['segundos'] >= 1799 && $estado['segundos'] <= 1801 && $estado['vigente'] && !$estado['usado'], 'Vigencia inicial distinta de treinta minutos.');
    $anterior->request('GET', '/autenticacion/sesion'); // Pedir enlace no revoca sesiones.
    $confirmar($token, esperado: 422, extras: ['idUsuario' => $cuentas['otra']['idUsuario']]);
    $confirmar($token, nueva: $clave, esperado: 422);
    $nueva = $confirmar($token);
    comprobarHttp(json_decode($soporte('estado', $correos['valida']), true, 512, JSON_THROW_ON_ERROR)['usado'] === true, 'El token no quedo consumido.');
    $anon->request('GET', '/autenticacion/sesion', esperado: 401);
    $anterior->request('GET', '/autenticacion/sesion', esperado: 401);
    $anterior2->request('GET', '/autenticacion/sesion', esperado: 401);
    $otra->request('GET', '/autenticacion/sesion');
    $cliente()->login($correos['valida'], $clave, 401);
    $cliente()->login($correos['valida'], $nueva);
    $confirmar($token, esperado: 422);
    $confirmar(str_repeat('f', 64), esperado: 422);
    $confirmar('invalido', esperado: 422);
    echo "OK: recuperacion HTTP, token hash/30 minutos, identidad, CSRF, origen, no enumeracion y sesiones revocadas.\n";

    $reiniciar();
    [$primero] = $pedir('reemitida');
    [$segundo] = $pedir('reemitida');
    comprobarHttp($primero !== $segundo, 'La reemision reutilizo el mismo token.');
    $confirmar($primero, esperado: 422);
    $confirmar($segundo);
    [$vencido] = $pedir('expirada');
    $soporte('caducar', $correos['expirada']);
    $confirmar($vencido, esperado: 422);
    [$cambiado] = $pedir('correo');
    $soporte('cambiar-correo', $correos['correo'], 'nuevo-' . $sufijo . '@example.test');
    $confirmar($cambiado, esperado: 422);
    [$cancelado] = $pedir('otra');
    $soporte('desactivar', $correos['otra']);
    $confirmar($cancelado, esperado: 422);
    echo "OK: reemision revoca enlace anterior; rechazo de vencidos, desactivados y correo modificado.\n";

    $reiniciar();
    $personal = $cliente();
    $personal->login($correos['cambio'], $clave);
    [$anteriorToken] = $pedir('cambio');
    $personalNueva = 'CambioPersonal' . bin2hex(random_bytes(10));
    $secretos[] = $personalNueva;
    $personal->request('POST', '/autenticacion/cambiar-contrasena', [
        'contrasenaActual' => $clave, 'nuevaContrasena' => $personalNueva, 'confirmacionContrasena' => $personalNueva,
    ]);
    $confirmar($anteriorToken, esperado: 422);
    $admin = $cliente();
    $admin->login('administrador@vidasalud.test', $clave);
    [$tokenAdmin] = $pedir('admin');
    $temporal = bin2hex(random_bytes(12));
    $secretos[] = $temporal;
    $admin->request('POST', '/usuarios/' . $cuentas['admin']['idUsuario'] . '/restablecer-contrasena', [
        'contrasenaTemporal' => $temporal, 'confirmarRestablecimiento' => true,
    ]);
    $confirmar($tokenAdmin, esperado: 422);
    [$tokenAdmin] = $pedir('admin');
    $final = $confirmar($tokenAdmin);
    $usuario = $cliente()->login($correos['admin'], $final)['datos'];
    comprobarHttp($usuario['requiereCambioContrasena'] === false, 'La recuperacion mantuvo el cambio obligatorio de la temporal.');

    $reiniciar();
    for ($i = 0; $i < 3; $i++) {
        $pedir('limite');
    }
    $cantidad = count($mensajes());
    $cliente()->request('POST', '/autenticacion/recuperar-contrasena', ['correoUsuario' => $correos['limite']], 429);
    comprobarHttp(count($mensajes()) === $cantidad, 'El limite de solicitudes no impidio otro envio.');
    $reiniciar();
    [$limitado] = $pedir('confirmar');
    for ($i = 0; $i < 5; $i++) {
        $confirmar($limitado, nueva: $clave, esperado: 422);
    }
    $confirmar($limitado, esperado: 429, actor: $cliente());
    $reiniciar();
    // La IP se limita incluso con correo invalido y cookies nuevas; no se envian mensajes.
    for ($i = 0; $i < 20; $i++) {
        $cliente()->request('POST', '/autenticacion/recuperar-contrasena', ['correoUsuario' => 'invalido'], 422);
    }
    $cliente()->request('POST', '/autenticacion/recuperar-contrasena', ['correoUsuario' => $noexiste], 429);
    $reiniciar();
    for ($i = 0; $i < 30; $i++) {
        $confirmar(bin2hex(random_bytes(32)), esperado: 422, actor: $cliente());
    }
    $confirmar(bin2hex(random_bytes(32)), esperado: 429, actor: $cliente());
    echo "OK: cambio personal/admin invalidan enlaces; limites persistentes por cuenta, token e IP.\n";

    $reiniciar();
    $cantidad = count($mensajes());
    $fallo = $buzon . '.fallar';
    comprobarHttp(file_put_contents($fallo, 'fallo-simulado') !== false, 'No se pudo inducir el fallo del transporte.');
    try {
        $respuesta = $anon->request('POST', '/autenticacion/recuperar-contrasena', ['correoUsuario' => $correos['fallo']], 202);
        comprobarHttp($respuesta === $generica && count($mensajes()) === $cantidad, 'El fallo del proveedor revela la cuenta o afirma un envio.');
        comprobarHttp(json_decode($soporte('estado', $correos['fallo']), true, 512, JSON_THROW_ON_ERROR)['usado'] === true, 'El token de un correo fallido sigue vigente.');
    } finally {
        if (is_file($fallo)) { unlink($fallo); }
    }
    foreach (['/recuperar-contrasena', '/recuperar-contrasena/confirmar'] as $ruta) {
        $respuesta = $anon->obtenerRespuesta('GET', $front . $ruta);
        comprobarHttp($respuesta['codigo'] === 200, 'La pantalla de recuperacion no esta disponible.');
        comprobarHttp(($respuesta['headers']['referrer-policy'] ?? '') === 'no-referrer', 'La pantalla de recuperacion permite Referer.');
        comprobarHttp(str_contains($respuesta['headers']['cache-control'] ?? '', 'no-store'), 'La pantalla de recuperacion permite cache.');
        comprobarHttp(mb_check_encoding($respuesta['contenido'], 'UTF-8') && str_contains($respuesta['contenido'], 'contrase'), 'La pantalla de recuperacion no contiene texto UTF-8 valido.');
    }
    comprobarHttp(count($resultados) === 214, 'Cambio inesperado en las 214 comprobaciones HTTP de recuperacion.');
    echo 'OK: ' . count($resultados) . " comprobaciones HTTP de recuperacion; solo correo simulado, sin Resend.\n";
    $reiniciar();
    return $secretos;
}
