<?php
declare(strict_types=1);

// Operaciones de soporte CLI; no existe una ruta HTTP para estos cambios.
if (PHP_SAPI !== 'cli' || getenv('VIDASALUD_TEST_LOCAL') !== '1'
    || getenv('DB_HOST') !== '127.0.0.1' || getenv('APP_ENV') !== 'local'
    || !preg_match('/^[a-z0-9_]+_pruebas$/D', getenv('DB_NAME') ?: '')
    || !ctype_digit(getenv('DB_PORT') ?: '')) {
    throw new RuntimeException('Se requiere el cluster local aislado.');
}
require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;

$conexion = ConexionPostgresql::obtenerConexionPostgresql();
$usuarios = new UsuarioRepository($conexion);
$opcion = $argv[1] ?? '';
$correo = $argv[2] ?? '';
switch ($opcion) {
    case 'crear':
        $rol = (int) $conexion->query("SELECT id_rol FROM roles WHERE codigo_rol='recepcionista'")->fetchColumn();
        $id = $usuarios->registrarUsuario(['nombresUsuario' => 'Recuperacion', 'apellidosUsuario' => 'Prueba',
            'correoUsuario' => $correo], $rol, password_hash(getenv('SEED_USERS_PASSWORD'), PASSWORD_DEFAULT));
        echo json_encode(['idUsuario' => $id], JSON_THROW_ON_ERROR);
        break;
    case 'estado':
        $consulta = $conexion->prepare("SELECT r.token_hash, r.usado_en IS NOT NULL AS usado,
            r.expira_en > clock_timestamp() AS vigente,
            EXTRACT(EPOCH FROM (r.expira_en-r.creado_en))::int AS segundos,
            u.version_credenciales FROM usuarios u LEFT JOIN recuperaciones_contrasena r USING(id_usuario)
            WHERE u.correo_usuario=:correo");
        $consulta->execute(['correo' => $correo]);
        echo json_encode($consulta->fetch(), JSON_THROW_ON_ERROR);
        break;
    case 'caducar':
        $consulta = $conexion->prepare("UPDATE recuperaciones_contrasena SET creado_en=clock_timestamp()-INTERVAL '31 minutes',
            expira_en=clock_timestamp()-INTERVAL '1 minute' WHERE id_usuario=(SELECT id_usuario FROM usuarios WHERE correo_usuario=:correo)");
        $consulta->execute(['correo' => $correo]);
        break;
    case 'desactivar':
        $usuarios->cambiarEstadoUsuario($usuarios->obtenerUsuarioPorCorreo($correo)->idUsuario, false);
        break;
    case 'cambiar-correo':
        $usuario = $usuarios->obtenerUsuarioPorCorreo($correo);
        $usuarios->actualizarDatosUsuario($usuario->idUsuario, ['nombresUsuario' => $usuario->nombresUsuario,
            'apellidosUsuario' => $usuario->apellidosUsuario, 'correoUsuario' => $argv[3]]);
        break;
    default:
        throw new RuntimeException('Operacion de soporte desconocida.');
}
