<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/CorreoPrueba.php';
exigirEntornoRecuperacionAislado();
if (PHP_SAPI !== 'cli') { throw new RuntimeException('Usa exclusivamente PHP CLI.'); }

use CitasMedicas\Business\Services\RecuperacionContrasenaService;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SesionUsuario;

$conexion = ConexionPostgresql::obtenerConexionPostgresql();
$usuarios = new UsuarioRepository($conexion);
SesionUsuario::iniciarSesionSegura();

// Las credenciales sinteticas viajan por stdin, nunca por argumentos ni logs.
if (($argv[1] ?? '') === '--confirmar') {
    $datos = json_decode(stream_get_contents(STDIN), true, 32, JSON_THROW_ON_ERROR);
    file_put_contents($datos['barrera'] . '.' . $datos['numero'], 'listo');
    $limite = microtime(true) + 20;
    while (!is_file($datos['barrera'])) {
        if (microtime(true) > $limite) { throw new RuntimeException('No llego la barrera de concurrencia.'); }
        usleep(20000);
    }
    try {
        (new RecuperacionContrasenaService($conexion, new CorreoPrueba()))->confirmarRecuperacion(
            $datos['formulario'], 'concurrente-' . $datos['numero']);
        echo '200';
    } catch (ErrorAplicacion $error) { echo (string) $error->codigoHttp; }
    exit;
}

$cantidad = 0;
$verificar = static function (bool $ok, string $mensaje) use (&$cantidad): void {
    if (!$ok) { throw new RuntimeException($mensaje); }
    $cantidad++;
};
$sufijo = bin2hex(random_bytes(6));
$correo = 'transaccion-' . $sufijo . '@recuperacion.example.test';
$clave = 'PersonalInicial' . bin2hex(random_bytes(10));
$nueva = 'PersonalNueva' . bin2hex(random_bytes(10));
$idRol = (int) $conexion->query("SELECT id_rol FROM roles WHERE codigo_rol='recepcionista'")->fetchColumn();
$id = $usuarios->registrarUsuario(['nombresUsuario' => 'Recuperacion', 'apellidosUsuario' => 'Transaccion',
    'correoUsuario' => $correo], $idRol, password_hash($clave, PASSWORD_DEFAULT));
$nombreFallo = 'prueba_fallo_recuperacion_' . $sufijo;
$correoPrueba = new CorreoPrueba();
$servicio = new RecuperacionContrasenaService($conexion, $correoPrueba);
$estado = static function () use ($conexion, $id): array {
    $q = $conexion->prepare('SELECT u.contrasena_hash,u.version_credenciales,r.token_hash,r.usado_en
        FROM usuarios u JOIN recuperaciones_contrasena r USING(id_usuario) WHERE u.id_usuario=:id');
    $q->execute(['id' => $id]);
    return $q->fetch();
};
$procesos = [];
$barrera = null;
try {
    $servicio->solicitarRecuperacion(['correoUsuario' => $correo], 'solicitud-' . $sufijo);
    $verificar(count($correoPrueba->mensajes) === 1, 'No se capturo el correo simulado.');
    parse_str(parse_url($correoPrueba->mensajes[0]['enlace'], PHP_URL_FRAGMENT), $fragmento);
    $token = $fragmento['token'];
    $antes = $estado();
    $verificar(strlen($token) === 64 && ctype_xdigit($token) && $antes['token_hash'] === hash('sha256', $token),
        'El token no corresponde con su hash.');
    $formulario = ['token' => $token, 'nuevaContrasena' => $nueva, 'confirmacionContrasena' => $nueva];
    $eventosAntes = (int) $conexion->query('SELECT count(*) FROM bitacora_sistema')->fetchColumn();
    $conexion->exec("CREATE FUNCTION $nombreFallo() RETURNS trigger LANGUAGE plpgsql AS \$\$
        BEGIN
            IF NEW.accion='recuperar_contrasena' AND NEW.id_registro=$id THEN
                RAISE EXCEPTION 'Fallo deliberado de recuperacion' USING ERRCODE='P3010';
            END IF;
            RETURN NEW;
        END \$\$");
    $conexion->exec("CREATE TRIGGER $nombreFallo BEFORE INSERT ON bitacora_sistema FOR EACH ROW EXECUTE FUNCTION $nombreFallo()");
    try {
        $servicio->confirmarRecuperacion($formulario, 'rollback-' . $sufijo);
        throw new RuntimeException('Falto el fallo de auditoria inducido.');
    } catch (PDOException $error) {
        $verificar((string) $error->getCode() === 'P3010', 'Fallo SQL inesperado.');
    }
    $verificar(!$conexion->inTransaction(), 'Quedo una transaccion abierta.');
    $verificar($estado() === $antes, 'El rollback no restituyo token, hash y version.');
    $verificar((int) $conexion->query('SELECT count(*) FROM bitacora_sistema')->fetchColumn() === $eventosAntes,
        'El fallo dejo auditoria parcial.');
    $conexion->exec("DROP TRIGGER $nombreFallo ON bitacora_sistema");
    $conexion->exec("DROP FUNCTION $nombreFallo()");

    $carpeta = dirname(__DIR__, 2) . '/.tmp-auth/concurrencia-recuperacion-' . $sufijo;
    if (!mkdir($carpeta, 0700, true)) { throw new RuntimeException('Fallo la barrera local.'); }
    $barrera = $carpeta . '/iniciar';
    for ($numero = 0; $numero < 2; $numero++) {
        $proceso = proc_open([PHP_BINARY, __FILE__, '--confirmar'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias, dirname(__DIR__, 2));
        if (!is_resource($proceso)) { throw new RuntimeException('No se pudo iniciar la concurrencia.'); }
        fwrite($tuberias[0], json_encode(compact('barrera', 'numero', 'formulario'), JSON_THROW_ON_ERROR));
        fclose($tuberias[0]);
        $procesos[] = [$proceso, $tuberias[1], $tuberias[2]];
    }
    $limite = microtime(true) + 25;
    while (!is_file($barrera . '.0') || !is_file($barrera . '.1')) {
        if (microtime(true) > $limite) { throw new RuntimeException('Los procesos no llegaron a la barrera.'); }
        usleep(20000);
    }
    file_put_contents($barrera, 'iniciar');
    $resultados = [];
    foreach ($procesos as [$proceso, $salida, $errores]) {
        do {
            $estadoProceso = proc_get_status($proceso);
            if (!$estadoProceso['running']) { break; }
            if (microtime(true) > $limite) { throw new RuntimeException('Concurrencia excedio el tiempo.'); }
            usleep(20000);
        } while (true);
        $verificar($estadoProceso['exitcode'] === 0 && stream_get_contents($errores) === '', 'Un proceso de confirmacion fallo.');
        $resultados[] = (int) stream_get_contents($salida);
    }
    sort($resultados);
    $verificar($resultados === [200, 422], 'Un token fue aceptado por dos procesos o por ninguno.');
    $despues = $estado();
    $verificar((int) $despues['version_credenciales'] === (int) $antes['version_credenciales'] + 1,
        'La version de credenciales no avanzo exactamente una vez.');
    $verificar($despues['usado_en'] !== null && password_verify($nueva, $despues['contrasena_hash'])
        && !password_verify($clave, $despues['contrasena_hash']), 'El token o la nueva clave no quedaron confirmados.');
    $auditoria = json_encode($conexion->query('SELECT modulo,accion,descripcion FROM bitacora_sistema')->fetchAll(), JSON_THROW_ON_ERROR);
    foreach ([$token, $clave, $nueva] as $secreto) {
        $verificar(!str_contains($auditoria, $secreto), 'La auditoria expuso un secreto.');
    }
    echo "OK: $cantidad comprobaciones de recuperacion transaccional, rollback, concurrencia real y auditoria sin secretos.\n";
} finally {
    if ($conexion->inTransaction()) { $conexion->rollBack(); }
    $conexion->exec("DROP TRIGGER IF EXISTS $nombreFallo ON bitacora_sistema");
    $conexion->exec("DROP FUNCTION IF EXISTS $nombreFallo()");
    foreach ($procesos as [$proceso, $salida, $errores]) {
        if (proc_get_status($proceso)['running']) { proc_terminate($proceso); }
        fclose($salida); fclose($errores); proc_close($proceso);
    }
    if ($barrera !== null) {
        foreach ([$barrera, $barrera . '.0', $barrera . '.1'] as $archivo) {
            if (is_file($archivo)) { unlink($archivo); }
        }
        rmdir(dirname($barrera));
    }
    // Se conservan los datos sinteticos en el cluster efimero para inspeccion.
}
