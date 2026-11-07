<?php
declare(strict_types=1);

// Soporte exclusivo para el cluster efimero creado por test_autenticacion_http.php.
// No usa la conexion del proyecto ni permite hosts o nombres de produccion.
if (PHP_SAPI !== 'cli' || getenv('VIDASALUD_TEST_LOCAL') !== '1'
    || getenv('DB_HOST') !== '127.0.0.1'
    || !preg_match('/^[a-z0-9_]+_pruebas$/D', getenv('DB_NAME') ?: '')
    || !ctype_digit(getenv('DB_PORT') ?: '')) {
    throw new RuntimeException('Este soporte requiere el cluster local aislado de pruebas.');
}
require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Data\Seeders\DatosInicialesSeeder;

$base = getenv('DB_NAME');
$puerto = getenv('DB_PORT');
$opcion = $argv[1] ?? '';
$conectar = static fn(string $nombre): PDO => new PDO(
    "pgsql:host=127.0.0.1;port=$puerto;dbname=$nombre;sslmode=disable", 'postgres', '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
if ($opcion === 'preparar') {
    $conexionServidor = $conectar('postgres');
    $conexionServidor->exec('CREATE DATABASE ' . $base);
    $conexion = $conectar($base);
    $archivos = glob(dirname(__DIR__) . '/src/data/migrations/*.sql');
    sort($archivos);
    if (count($archivos) !== 13) {
        throw new RuntimeException('Se requieren exactamente las migraciones 001-013.');
    }
    foreach ($archivos as $archivo) {
        $conexion->beginTransaction();
        $conexion->exec(file_get_contents($archivo));
        $conexion->commit();
    }
    DatosInicialesSeeder::cargarDatosIniciales($conexion, getenv('SEED_USERS_PASSWORD'));
    if (($argv[2] ?? '') === 'solo-seeder') {
        echo "OK: base aislada con migraciones y seeder.\n";
        exit;
    }
    $usuarios = new UsuarioRepository($conexion);
    $cuentas = [];
    foreach (['validaciones', 'limitecambio', 'temporal', 'caducada', 'inactiva', 'limitelogin', 'limiteadmin'] as $nombre) {
        $id = $usuarios->registrarUsuario([
            'nombresUsuario' => 'Prueba', 'apellidosUsuario' => ucfirst($nombre),
            'correoUsuario' => $nombre . '@auth.example.test',
        ], $nombre === 'limiteadmin' ? 1 : 2, password_hash(getenv('SEED_USERS_PASSWORD'), PASSWORD_DEFAULT));
        if ($nombre === 'inactiva') {
            $usuarios->cambiarEstadoUsuario($id, false);
        }
        $cuentas[$nombre] = $id;
    }
    echo json_encode($cuentas, JSON_THROW_ON_ERROR);
    exit;
}
$conexion = $conectar($base);
switch ($opcion) {
    case 'caducar-temporal':
        $consulta = $conexion->prepare('UPDATE usuarios SET contrasena_temporal_expira = CURRENT_TIMESTAMP - INTERVAL \'1 minute\' WHERE id_usuario = :id AND requiere_cambio_contrasena');
        $consulta->execute(['id' => (int) ($argv[2] ?? 0)]);
        break;
    case 'reiniciar-limites':
        // Separa escenarios: las verificaciones del limite no llaman este comando entre intentos.
        $conexion->exec('DELETE FROM intentos_autenticacion');
        break;
    case 'auditoria':
        $consulta = $conexion->prepare("SELECT id_usuario, id_registro, accion, fecha_hora, descripcion FROM bitacora_sistema WHERE accion = 'restablecer_contrasena' AND id_registro = :id");
        $consulta->execute(['id' => (int) ($argv[2] ?? 0)]);
        echo json_encode($consulta->fetchAll(), JSON_THROW_ON_ERROR);
        break;
    case 'sin-secretos':
        // Devuelve solo metadatos ya publicos; nunca hashes, claves ni tokens.
        echo json_encode($conexion->query('SELECT modulo, accion, descripcion FROM bitacora_sistema')->fetchAll(), JSON_THROW_ON_ERROR);
        break;
    case 'hashes-seguros':
        foreach ($conexion->query('SELECT contrasena_hash FROM usuarios')->fetchAll(PDO::FETCH_COLUMN) as $hash) {
            if (password_get_info($hash)['algo'] === null) {
                throw new RuntimeException('Se encontro una credencial sin hash reconocido.');
            }
        }
        echo "OK: todas las credenciales tienen hash reconocido.\n";
        break;
    default:
        throw new RuntimeException('Operacion de soporte desconocida.');
}
