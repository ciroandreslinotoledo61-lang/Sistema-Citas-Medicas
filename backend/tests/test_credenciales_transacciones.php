<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Business\Services\CredencialService;
use CitasMedicas\Business\Services\IntentosAutenticacionService;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SesionUsuario;

if (PHP_SAPI !== 'cli'
    || !str_ends_with(CargarVariablesEntorno::obtenerVariableEntorno('DB_NAME'), '_pruebas')
    || !in_array(CargarVariablesEntorno::obtenerVariableEntorno('DB_HOST'), ['127.0.0.1', 'localhost', '::1'], true)
    || CargarVariablesEntorno::obtenerVariableEntorno('APP_ENV', 'local') === 'production') {
    throw new RuntimeException('Usa PHP CLI y una base local independiente terminada en _pruebas.');
}

$conexion = ConexionPostgresql::obtenerConexionPostgresql();
$usuarios = new UsuarioRepository($conexion);

// Los procesos compiten por consumir la misma version; no reciben ninguna clave.
if (($argv[1] ?? '') === '--consumir-temporal') {
    $datos = json_decode($argv[2], true, 16, JSON_THROW_ON_ERROR);
    file_put_contents($datos['barrera'] . '.' . $datos['numero'] . '.listo', 'listo');
    $limite = microtime(true) + 20;
    while (!is_file($datos['barrera'])) {
        if (microtime(true) > $limite) {
            throw new RuntimeException('No llego la barrera de consumo temporal.');
        }
        usleep(20000);
    }
    echo json_encode($usuarios->consumirContrasenaTemporal($datos['idUsuario'], $datos['version']), JSON_THROW_ON_ERROR);
    exit;
}

/** Dos conexiones de procesos distintos ejecutan el UPDATE atomico al mismo tiempo. */
function consumirTemporalConcurrentemente(int $idUsuario, int $version): array
{
    $directorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'credenciales-concurrencia-' . bin2hex(random_bytes(8));
    if (!mkdir($directorio, 0700)) {
        throw new RuntimeException('No se pudo crear la barrera temporal.');
    }
    $barrera = $directorio . DIRECTORY_SEPARATOR . 'iniciar';
    $procesos = [];
    $resultados = [];
    try {
        for ($numero = 0; $numero < 2; $numero++) {
            $argumento = json_encode(compact('idUsuario', 'version', 'barrera', 'numero'), JSON_THROW_ON_ERROR);
            $proceso = proc_open([PHP_BINARY, __FILE__, '--consumir-temporal', $argumento],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias, dirname(__DIR__, 2));
            if (!is_resource($proceso)) {
                throw new RuntimeException('No se pudo iniciar el proceso de consumo temporal.');
            }
            fclose($tuberias[0]);
            $procesos[] = ['proceso' => $proceso, 'salida' => $tuberias[1], 'error' => $tuberias[2]];
        }
        $limite = microtime(true) + 25;
        while (!is_file($barrera . '.0.listo') || !is_file($barrera . '.1.listo')) {
            if (microtime(true) > $limite) {
                throw new RuntimeException('Los procesos no llegaron a la barrera temporal.');
            }
            usleep(20000);
        }
        file_put_contents($barrera, 'iniciar');
        foreach ($procesos as $trabajador) {
            do {
                $estado = proc_get_status($trabajador['proceso']);
                if (!$estado['running']) {
                    break;
                }
                if (microtime(true) > $limite) {
                    throw new RuntimeException('Tiempo agotado esperando el consumo temporal.');
                }
                usleep(20000);
            } while (true);
            $salida = stream_get_contents($trabajador['salida']);
            $errores = stream_get_contents($trabajador['error']);
            if ($estado['exitcode'] !== 0 || $errores !== '') {
                throw new RuntimeException('Fallo un proceso de consumo temporal.');
            }
            $resultados[] = json_decode($salida, true, 16, JSON_THROW_ON_ERROR);
        }
        return $resultados;
    } finally {
        foreach ($procesos as $trabajador) {
            if (proc_get_status($trabajador['proceso'])['running']) {
                proc_terminate($trabajador['proceso']);
            }
            fclose($trabajador['salida']);
            fclose($trabajador['error']);
            proc_close($trabajador['proceso']);
        }
        foreach ([$barrera, $barrera . '.0.listo', $barrera . '.1.listo'] as $archivo) {
            if (is_file($archivo)) {
                unlink($archivo);
            }
        }
        rmdir($directorio);
    }
}

$cantidad = 0;
$verificar = static function (bool $condicion, string $mensaje) use (&$cantidad): void {
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    $cantidad++;
};
$exigirFalloAuditoria = static function (callable $operacion) use ($verificar): void {
    try {
        $operacion();
    } catch (PDOException $error) {
        $verificar((string) $error->getCode() === 'P3009', 'El fallo no procedia de la auditoria inducida.');
        return;
    }
    throw new RuntimeException('La operacion debio fallar al registrar su auditoria.');
};
$estadoCredenciales = static function (int $idUsuario) use ($conexion): array {
    $consulta = $conexion->prepare('SELECT contrasena_hash, version_credenciales, requiere_cambio_contrasena,
        contrasena_temporal_expira, contrasena_temporal_usada FROM usuarios WHERE id_usuario = :id');
    $consulta->execute(['id' => $idUsuario]);
    return $consulta->fetch();
};
$contarAuditoria = static function (int $idUsuario) use ($conexion): int {
    $consulta = $conexion->prepare("SELECT count(*) FROM bitacora_sistema WHERE modulo = 'usuarios' AND id_registro = :id");
    $consulta->execute(['id' => $idUsuario]);
    return (int) $consulta->fetchColumn();
};

SesionUsuario::iniciarSesionSegura();
$identificador = bin2hex(random_bytes(6));
$nombreFallo = 'prueba_fallo_credenciales_' . $identificador;
$ids = [];
$clavesLimite = [hash('sha256', 'prueba-expiracion:' . $identificador)];
$credenciales = new CredencialService($conexion);
try {
    $roles = $conexion->query('SELECT codigo_rol, id_rol FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!isset($roles['administrador'], $roles['recepcionista'])) {
        throw new RuntimeException('Carga los roles iniciales en la base local de pruebas.');
    }
    $claveInicial = 'InicialPrueba' . bin2hex(random_bytes(8));
    foreach (['administrador', 'recepcionista'] as $rol) {
        $ids[$rol] = $usuarios->registrarUsuario([
            'nombresUsuario' => 'Transaccion', 'apellidosUsuario' => 'Credenciales',
            'correoUsuario' => $rol . '-' . $identificador . '@credenciales.example.test',
        ], (int) $roles[$rol], password_hash($claveInicial, PASSWORD_DEFAULT));
    }
    $idAdmin = $ids['administrador'];
    $idDestino = $ids['recepcionista'];
    $clavesLimite[] = hash('sha256', 'restablecer-contrasena:' . $idAdmin);
    $clavesLimite[] = hash('sha256', 'cambiar-contrasena:' . $idDestino);

    // Comenzar con temporal ya utilizada permite comprobar tambien su restauracion.
    $temporalAnterior = bin2hex(random_bytes(12));
    $usuarios->actualizarContrasena($idDestino, password_hash($temporalAnterior, PASSWORD_DEFAULT), true);
    $version = $usuarios->obtenerUsuarioPorId($idDestino)->versionCredenciales;
    $verificar($usuarios->consumirContrasenaTemporal($idDestino, $version), 'No se preparo la sesion temporal.');
    $antes = $estadoCredenciales($idDestino);
    $eventosAntes = $contarAuditoria($idDestino);
    SesionUsuario::establecerUsuarioAutenticado($idDestino, (int) $antes['version_credenciales']);
    $sesionAntes = session_id();
    $tokenAntes = SesionUsuario::obtenerTokenCsrf();

    // El UPDATE de usuarios y su trigger ya ocurrieron cuando falla el evento explicito.
    // Solo se afecta la cuenta creada por esta prueba y se retira el trigger en finally.
    $conexion->exec("CREATE FUNCTION $nombreFallo() RETURNS trigger LANGUAGE plpgsql AS \$\$
        BEGIN
            IF NEW.modulo = 'usuarios' AND NEW.id_registro = $idDestino
                AND NEW.accion IN ('cambiar_contrasena', 'restablecer_contrasena') THEN
                RAISE EXCEPTION 'Fallo deliberado de auditoria de credenciales' USING ERRCODE = 'P3009';
            END IF;
            RETURN NEW;
        END \$\$");
    $conexion->exec("CREATE TRIGGER $nombreFallo BEFORE INSERT ON bitacora_sistema
        FOR EACH ROW EXECUTE FUNCTION $nombreFallo()");
    $nueva = 'PersonalPrueba' . bin2hex(random_bytes(8));
    $cambio = ['contrasenaActual' => $temporalAnterior, 'nuevaContrasena' => $nueva, 'confirmacionContrasena' => $nueva];
    $exigirFalloAuditoria(fn() => $credenciales->cambiarContrasena($cambio));
    $verificar(!$conexion->inTransaction(), 'El cambio dejo una transaccion abierta.');
    $verificar($estadoCredenciales($idDestino) === $antes, 'El cambio fallido altero hash, version o estado temporal.');
    $verificar($contarAuditoria($idDestino) === $eventosAntes, 'El cambio fallido dejo auditoria parcial.');
    $verificar(session_id() === $sesionAntes && SesionUsuario::obtenerTokenCsrf() === $tokenAntes
        && SesionUsuario::obtenerVersionCredenciales() === (int) $antes['version_credenciales'],
        'El cambio fallido altero la sesion que debia conservarse.');

    SesionUsuario::establecerUsuarioAutenticado($idAdmin, $usuarios->obtenerUsuarioPorId($idAdmin)->versionCredenciales);
    $restablecimiento = ['contrasenaTemporal' => bin2hex(random_bytes(12)), 'confirmarRestablecimiento' => true];
    $exigirFalloAuditoria(fn() => $credenciales->restablecerContrasena($idDestino, $restablecimiento));
    $verificar(!$conexion->inTransaction(), 'El restablecimiento dejo una transaccion abierta.');
    $verificar($estadoCredenciales($idDestino) === $antes, 'El restablecimiento fallido altero hash, version o estado temporal.');
    $verificar($contarAuditoria($idDestino) === $eventosAntes, 'El restablecimiento fallido dejo auditoria parcial.');

    $conexion->exec("DROP TRIGGER $nombreFallo ON bitacora_sistema");
    $conexion->exec("DROP FUNCTION $nombreFallo()");
    SesionUsuario::establecerUsuarioAutenticado($idDestino, (int) $antes['version_credenciales']);
    $actualizado = $credenciales->cambiarContrasena($cambio);
    $verificar($actualizado->verificarContrasenaUsuario($nueva) && !$actualizado->verificarContrasenaUsuario($temporalAnterior),
        'El cambio no pudo reintentarse despues de restaurar la auditoria.');
    $verificar(!$actualizado->requiereCambioContrasena && !$actualizado->contrasenaTemporalUsada
        && $actualizado->contrasenaTemporalExpira === null
        && $actualizado->versionCredenciales === (int) $antes['version_credenciales'] + 1,
        'El cambio confirmado no limpio la temporal o incremento mal la version.');
    $verificar($contarAuditoria($idDestino) === $eventosAntes + 2, 'El cambio confirmado no audito UPDATE y cambio de clave.');

    SesionUsuario::establecerUsuarioAutenticado($idAdmin, $usuarios->obtenerUsuarioPorId($idAdmin)->versionCredenciales);
    $credenciales->restablecerContrasena($idDestino, $restablecimiento);
    $restablecido = $usuarios->obtenerUsuarioPorId($idDestino);
    $verificar($restablecido->requiereCambioContrasena && !$restablecido->contrasenaTemporalUsada
        && $restablecido->verificarContrasenaUsuario($restablecimiento['contrasenaTemporal'])
        && $restablecido->versionCredenciales === $actualizado->versionCredenciales + 1,
        'El restablecimiento no pudo reintentarse despues de restaurar la auditoria.');
    $verificar($contarAuditoria($idDestino) === $eventosAntes + 4, 'El restablecimiento confirmado no dejo su auditoria.');

    $resultados = consumirTemporalConcurrentemente($idDestino, $restablecido->versionCredenciales);
    sort($resultados);
    $verificar($resultados === [false, true], 'Dos procesos consumieron la misma temporal o ninguno la consumio.');
    $consumido = $usuarios->obtenerUsuarioPorId($idDestino);
    $verificar($consumido->contrasenaTemporalUsada && $consumido->requiereCambioContrasena
        && $consumido->versionCredenciales === $restablecido->versionCredenciales + 1,
        'El consumo simultaneo dejo una version o estado incorrectos.');

    $intentos = new IntentosAutenticacionService($conexion);
    for ($numero = 0; $numero < 5; $numero++) {
        $intentos->exigirIntentoDisponible('prueba-expiracion', $identificador);
    }
    try {
        (new IntentosAutenticacionService($conexion))->exigirIntentoDisponible('prueba-expiracion', $identificador);
        throw new RuntimeException('No se bloqueo el sexto intento persistente.');
    } catch (ErrorAplicacion $error) {
        $verificar($error->codigoHttp === 429, 'Codigo inesperado al alcanzar el limite.');
    }
    // Adelantar el escenario en SQL evita una espera real de cinco minutos.
    $consulta = $conexion->prepare("UPDATE intentos_autenticacion
        SET inicio_ventana = clock_timestamp() - INTERVAL '301 seconds' WHERE clave = :clave");
    $consulta->execute(['clave' => $clavesLimite[0]]);
    $intentos->exigirIntentoDisponible('prueba-expiracion', $identificador);
    $consulta = $conexion->prepare("SELECT intentos, inicio_ventana > clock_timestamp() - INTERVAL '10 seconds' AS reciente
        FROM intentos_autenticacion WHERE clave = :clave");
    $consulta->execute(['clave' => $clavesLimite[0]]);
    $ventana = $consulta->fetch();
    $verificar((int) $ventana['intentos'] === 1 && in_array($ventana['reciente'], [true, 't', '1', 1], true),
        'La ventana vencida no recupero el acceso con un contador nuevo.');

    echo "OK: $cantidad comprobaciones de rollback de cambio/restablecimiento, reintento, consumo temporal concurrente y expiracion de limites.\n";
} finally {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    $conexion->exec("DROP TRIGGER IF EXISTS $nombreFallo ON bitacora_sistema");
    $conexion->exec("DROP FUNCTION IF EXISTS $nombreFallo()");
    $consulta = $conexion->prepare('DELETE FROM intentos_autenticacion WHERE clave = :clave');
    foreach ($clavesLimite as $claveLimite) {
        $consulta->execute(['clave' => $claveLimite]);
    }
    $consulta = $conexion->prepare('DELETE FROM usuarios WHERE id_usuario = :id');
    foreach ($ids as $idUsuario) {
        $consulta->execute(['id' => $idUsuario]);
    }
    // La bitacora conserva sus metadatos historicos, como exige su inmutabilidad.
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_destroy();
    }
}
