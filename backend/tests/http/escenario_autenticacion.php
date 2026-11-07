<?php
declare(strict_types=1);

require_once __DIR__ . '/ClienteHttpPruebas.php';

/** Contrato de autenticacion y credenciales: conserva los escenarios HTTP previos. */
function probarAutenticacionHttp(string $base, string $front, string $clave, array $cuentas, callable $soporte): array
{
    $resultados = [];
    $secretos = [$clave];
    $cliente = static function () use ($base, &$resultados, &$secretos): ClienteHttpPruebas {
        return new ClienteHttpPruebas($base, $resultados, $secretos);
    };
    $nueva = 'PersonalNueva' . bin2hex(random_bytes(8));
    $secretos[] = $nueva;
    $cambio = ['contrasenaActual' => $clave, 'nuevaContrasena' => $nueva, 'confirmacionContrasena' => $nueva];
    $anon = $cliente();
    $anon->request('POST', '/autenticacion/cambiar-contrasena', $cambio, 401);
    $anon->request('POST', '/usuarios/1/restablecer-contrasena', [], 401);
    $anon->request('POST', '/autenticacion/cambiar-contrasena', $cambio, 419, csrf: false);
    $anon->request('POST', '/autenticacion/cerrar-sesion');
    $clientes = [];
    $usuarios = [];
    foreach (['administrador', 'recepcionista', 'medico', 'paciente'] as $rol) {
        $actual = $cliente();
        $otra = $cliente();
        $correo = $rol . '@vidasalud.test';
        $usuarios[$rol] = $actual->login($correo, $clave)['datos'];
        $otra->login($correo, $clave);
        $cookieAnterior = $actual->cookie();
        $csrfAnterior = $actual->request('GET', '/autenticacion/token')['datos']['tokenCsrf'];
        $actual->request('POST', '/autenticacion/cambiar-contrasena', $cambio);
        comprobarHttp($actual->cookie() !== $cookieAnterior, 'El cambio no regenero el identificador de sesion.');
        comprobarHttp($actual->request('GET', '/autenticacion/token')['datos']['tokenCsrf'] !== $csrfAnterior,
            'El cambio no renovo el token CSRF.');
        $actual->request('GET', '/autenticacion/sesion');
        $otra->request('GET', '/autenticacion/sesion', esperado: 401);
        $cliente()->login($correo, $clave, 401);
        $cliente()->login($correo, $nueva);
        $actual->request('POST', '/autenticacion/cambiar-contrasena', $cambio, 419, token: $csrfAnterior);
        [$url, $html] = $actual->vista($front, '/cambiar-contrasena');
        comprobarHttp(str_ends_with($url, '/cambiar-contrasena'), 'El formulario no esta disponible para el rol.');
        comprobarHttp(str_contains($html, 'contrasenaActual') && str_contains($html, 'confirmacionContrasena'),
            'Faltan los campos de cambio personal.');
        if ($rol !== 'administrador') {
            comprobarHttp(!str_contains($html, 'Restablecer contrase') && !str_contains($html, 'href="/usuarios"'),
                'Una cuenta sin permiso ve acciones administrativas.');
        }
        $clientes[$rol] = $actual;
    }
    echo "OK: cuatro roles, cambio propio, nueva clave, revocacion, cookie y CSRF renovados.\n";

    $admin = $clientes['administrador'];
    $idPaciente = $usuarios['paciente']['idUsuario'];
    $datosAdmin = ['contrasenaTemporal' => bin2hex(random_bytes(12)), 'confirmarRestablecimiento' => true];
    $secretos[] = $datosAdmin['contrasenaTemporal'];
    foreach (['recepcionista', 'medico', 'paciente'] as $rol) {
        $clientes[$rol]->request('GET', '/usuarios', esperado: 403);
        $clientes[$rol]->request('POST', "/usuarios/$idPaciente/restablecer-contrasena", $datosAdmin, 403);
    }
    $clientes['paciente']->request('POST', '/autenticacion/cambiar-contrasena', [
        'contrasenaActual' => $nueva, 'nuevaContrasena' => $clave, 'confirmacionContrasena' => $clave,
        'idUsuario' => $usuarios['administrador']['idUsuario'],
    ], 422);
    $validaciones = $cliente();
    $validaciones->login('validaciones@auth.example.test', $clave);
    $validaciones->request('POST', '/autenticacion/cambiar-contrasena', array_replace($cambio, ['contrasenaActual' => 'ErroneaPrueba123']), 422);
    $validaciones->request('POST', '/autenticacion/cambiar-contrasena', array_replace($cambio, ['confirmacionContrasena' => 'DiferentePrueba123']), 422);
    $validaciones->request('POST', '/autenticacion/cambiar-contrasena', array_replace($cambio,
        ['nuevaContrasena' => 'corta', 'confirmacionContrasena' => 'corta']), 422);
    $validaciones->request('POST', '/autenticacion/cambiar-contrasena', array_replace($cambio,
        ['nuevaContrasena' => $clave, 'confirmacionContrasena' => $clave]), 422);
    echo "OK: permisos, manipulacion de identidad y validaciones del servidor.\n";

    $soporte('reiniciar-limites');
    $admin->request('POST', '/usuarios/' . $usuarios['administrador']['idUsuario'] . '/restablecer-contrasena', $datosAdmin, 409);
    $admin->request('POST', '/usuarios/' . $cuentas['inactiva'] . '/restablecer-contrasena', $datosAdmin, 409);
    $admin->request('POST', "/usuarios/$idPaciente/restablecer-contrasena", array_replace($datosAdmin, ['confirmarRestablecimiento' => false]), 422);
    $admin->request('POST', "/usuarios/$idPaciente/restablecer-contrasena", $datosAdmin, 419, csrf: false);
    $soporte('reiniciar-limites');
    $admin->request('POST', "/usuarios/$idPaciente/restablecer-contrasena", $datosAdmin);
    $clientes['paciente']->request('GET', '/autenticacion/sesion', esperado: 401);
    $cliente()->login('paciente@vidasalud.test', $nueva, 401);
    $temporal = $cliente();
    comprobarHttp($temporal->login('paciente@vidasalud.test', $datosAdmin['contrasenaTemporal'])['datos']['requiereCambioContrasena'] === true,
        'La clave temporal no exige cambio obligatorio.');
    $temporal->request('GET', '/autenticacion/sesion');
    $temporal->request('GET', '/citas', esperado: 403);
    [$url, $html] = $temporal->vista($front, '/panel');
    comprobarHttp(str_ends_with($url, '/cambiar-contrasena'), 'No redirige al cambio obligatorio.');
    $cliente()->login('paciente@vidasalud.test', $datosAdmin['contrasenaTemporal'], 401);
    $personal = 'PersonalDefinitiva' . bin2hex(random_bytes(8));
    $secretos[] = $personal;
    $temporal->request('POST', '/autenticacion/cambiar-contrasena', [
        'contrasenaActual' => $datosAdmin['contrasenaTemporal'], 'nuevaContrasena' => $personal,
        'confirmacionContrasena' => $personal,
    ]);
    comprobarHttp($temporal->request('GET', '/autenticacion/sesion')['datos']['requiereCambioContrasena'] === false,
        'No se retiro el cambio obligatorio despues de elegir clave personal.');
    $temporal->request('GET', '/citas');
    $cliente()->login('paciente@vidasalud.test', $personal);
    $cliente()->login('paciente@vidasalud.test', $datosAdmin['contrasenaTemporal'], 401);
    $eventos = json_decode($soporte('auditoria', (string) $idPaciente), true, 512, JSON_THROW_ON_ERROR);
    $eventoCorrecto = false;
    foreach ($eventos as $evento) {
        if ($evento['id_usuario'] === $usuarios['administrador']['idUsuario']
            && $evento['id_registro'] === $idPaciente && !empty($evento['fecha_hora'])) {
            $eventoCorrecto = true;
        }
    }
    comprobarHttp($eventoCorrecto, 'No se registro actor, cuenta y fecha del restablecimiento.');
    echo "OK: restablecimiento autorizado, uso unico, cambio obligatorio y bitacora.\n";

    $soporte('reiniciar-limites');
    foreach (['caducada', 'temporal'] as $nombre) {
        $claveTemporal = bin2hex(random_bytes(12));
        $secretos[] = $claveTemporal;
        $admin->request('POST', '/usuarios/' . $cuentas[$nombre] . '/restablecer-contrasena', [
            'contrasenaTemporal' => $claveTemporal, 'confirmarRestablecimiento' => true,
        ]);
        if ($nombre === 'caducada') {
            $soporte('caducar-temporal', (string) $cuentas[$nombre]);
            $cliente()->login($nombre . '@auth.example.test', $claveTemporal, 401);
        } else {
            $usado = $cliente();
            $usado->login($nombre . '@auth.example.test', $claveTemporal);
            $soporte('caducar-temporal', (string) $cuentas[$nombre]);
            $usado->request('POST', '/autenticacion/cambiar-contrasena', [
                'contrasenaActual' => $claveTemporal, 'nuevaContrasena' => $personal,
                'confirmacionContrasena' => $personal,
            ], 401);
        }
    }
    echo "OK: expiracion temporal antes y despues del inicio de sesion.\n";

    $soporte('reiniciar-limites');
    $segundoAdmin = $cliente();
    $segundoAdmin->login('limiteadmin@auth.example.test', $clave);
    foreach (['administrador', 'recepcionista', 'medico', 'paciente'] as $rol) {
        $temporalRol = bin2hex(random_bytes(12));
        $definitivaRol = 'DefinitivaRol' . bin2hex(random_bytes(8));
        array_push($secretos, $temporalRol, $definitivaRol);
        $segundoAdmin->request('POST', '/usuarios/' . $usuarios[$rol]['idUsuario'] . '/restablecer-contrasena', [
            'contrasenaTemporal' => $temporalRol, 'confirmarRestablecimiento' => true,
        ]);
        $sesionAnterior = $rol === 'paciente' ? $temporal : $clientes[$rol];
        $sesionAnterior->request('GET', '/autenticacion/sesion', esperado: 401);
        $restringido = $cliente();
        $usuario = $restringido->login($rol . '@vidasalud.test', $temporalRol)['datos'];
        comprobarHttp($usuario['requiereCambioContrasena'] === true && $usuario['codigoRol'] === $rol,
            'El restablecimiento altero el rol o no exigio cambio.');
        $restringido->request('GET', '/citas', esperado: 403);
        if ($rol === 'administrador') {
            $restringido->request('POST', "/usuarios/$idPaciente/restablecer-contrasena", $datosAdmin, 403);
        }
        $restringido->request('POST', '/autenticacion/cambiar-contrasena', [
            'contrasenaActual' => $temporalRol, 'nuevaContrasena' => $definitivaRol,
            'confirmacionContrasena' => $definitivaRol,
        ]);
        $usuario = $restringido->request('GET', '/autenticacion/sesion')['datos'];
        comprobarHttp($usuario['requiereCambioContrasena'] === false && $usuario['codigoRol'] === $rol,
            'La clave definitiva no conserva el rol o sigue restringida.');
        $restringido->request('GET', '/citas');
        $restringido->request('POST', '/autenticacion/cerrar-sesion');
        $restringido->request('GET', '/autenticacion/sesion', esperado: 401);
        $restringido->login($rol . '@vidasalud.test', $definitivaRol);
    }
    echo "OK: restablecimiento y recuperacion de acceso de los cuatro roles, sin cambiar permisos.\n";

    $soporte('reiniciar-limites');
    for ($intento = 0; $intento < 6; $intento++) {
        $cliente()->login('limitelogin@auth.example.test', 'ErroneaPrueba123', $intento < 5 ? 401 : 429);
    }
    $limite = $cliente();
    $limite->login('limitecambio@auth.example.test', $clave);
    for ($intento = 0; $intento < 6; $intento++) {
        $limite->request('POST', '/autenticacion/cambiar-contrasena', array_replace($cambio,
            ['contrasenaActual' => 'ErroneaPrueba123']), $intento < 5 ? 422 : 429);
    }
    $limiteAdmin = $cliente();
    $limiteAdmin->login('limiteadmin@auth.example.test', $clave);
    for ($intento = 0; $intento < 6; $intento++) {
        $claveTemporal = bin2hex(random_bytes(12));
        $secretos[] = $claveTemporal;
        $limiteAdmin->request('POST', '/usuarios/' . $cuentas['temporal'] . '/restablecer-contrasena', [
            'contrasenaTemporal' => $claveTemporal, 'confirmarRestablecimiento' => true,
        ], $intento < 5 ? 200 : 429);
    }
    $soporte('reiniciar-limites');
    for ($intento = 0; $intento < 51; $intento++) {
        $cliente()->login("inexistente$intento@auth.example.test", 'ErroneaPrueba123', $intento < 50 ? 401 : 429);
    }
    echo "OK: limites persistentes por cuenta, operacion e IP, incluso cambiando cookies.\n";

    $soporte('hashes-seguros');
    $bitacora = $soporte('sin-secretos');
    foreach ($secretos as $secreto) {
        comprobarHttp(!str_contains($bitacora, $secreto), 'La bitacora contiene una credencial.');
    }
    comprobarHttp(count($resultados) === 330, 'Cambio inesperado de la cobertura HTTP de autenticacion.');
    echo 'OK: ' . count($resultados) . " comprobaciones HTTP de autenticacion; ninguna respuesta o bitacora expone credenciales.\n";
    return $secretos;
}
