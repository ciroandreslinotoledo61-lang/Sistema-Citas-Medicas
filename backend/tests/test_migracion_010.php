<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/CorreoPrueba.php';
exigirEntornoRecuperacionAislado();
if (PHP_SAPI !== 'cli' || !ctype_digit(getenv('DB_PORT') ?: '')) {
    throw new RuntimeException('Se requiere el cluster CLI aislado.');
}

use CitasMedicas\Data\Seeders\DatosInicialesSeeder;

$cantidad = 0;
$verificar = static function (bool $ok, string $mensaje) use (&$cantidad): void {
    if (!$ok) { throw new RuntimeException($mensaje); }
    $cantidad++;
};
$base = 'migracion010_' . bin2hex(random_bytes(6)) . '_pruebas';
$puerto = getenv('DB_PORT');
$conexionServidor = new PDO("pgsql:host=127.0.0.1;port=$puerto;dbname=postgres;sslmode=disable", 'postgres', '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$conexionServidor->exec('CREATE DATABASE ' . $base);
$conexion = new PDO("pgsql:host=127.0.0.1;port=$puerto;dbname=$base;sslmode=disable", 'postgres', '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$anteriores = glob(dirname(__DIR__) . '/src/data/migrations/00*.sql');
sort($anteriores);
$verificar(count($anteriores) === 9, 'Faltan migraciones anteriores.');
foreach ($anteriores as $archivo) {
    $conexion->beginTransaction();
    $conexion->exec(file_get_contents($archivo));
    $conexion->commit();
}
DatosInicialesSeeder::cargarDatosIniciales($conexion, 'MigracionLocal' . bin2hex(random_bytes(10)));
$snapshot = static function () use ($conexion): array {
    $datos = [];
    foreach (['usuarios','pacientes','medicos','citas','historias_clinicas','consultas','tratamientos','bitacora_sistema'] as $tabla) {
        $datos[$tabla] = $conexion->query('SELECT * FROM ' . $tabla)->fetchAll();
    }
    return $datos;
};
$antes = $snapshot();
$variables = getenv();
$variables['DB_NAME'] = $base;
$script = dirname(__DIR__) . '/scripts/actualizar-recuperacion-contrasena.php';
$ejecutar = static function (array $argumentos, array $cambios = []) use ($variables, $script): array {
    $proceso = proc_open([PHP_BINARY, $script, ...$argumentos],
        [0 => ['pipe','r'],1 => ['pipe','w'],2 => ['pipe','w']], $tuberias,
        dirname(__DIR__, 2), array_replace($variables, $cambios));
    if (!is_resource($proceso)) { throw new RuntimeException('No inicio el actualizador local.'); }
    fclose($tuberias[0]);
    $salida = stream_get_contents($tuberias[1]); fclose($tuberias[1]);
    $error = stream_get_contents($tuberias[2]); fclose($tuberias[2]);
    return [proc_close($proceso), $salida, $error];
};
[$codigo,$salida] = $ejecutar([], ['DB_HOST'=>'prohibido.example.test','APP_ENV'=>'production']);
$verificar($codigo === 0 && str_contains($salida, 'Vista previa'), 'La vista previa no es independiente de la conexion.');
foreach ([['DB_HOST'=>'prohibido.example.test'],['APP_ENV'=>'production'],['DB_NAME'=>'sin_sufijo']] as $cambio) {
    [$codigo] = $ejecutar(['--aplicar'], $cambio);
    $verificar($codigo === 1, 'El actualizador acepto una configuracion fuera de pruebas.');
}
$verificar(!$conexion->query("SELECT to_regclass('public.recuperaciones_contrasena')")->fetchColumn(), 'Los rechazos modificaron la base.');
[$codigo] = $ejecutar(['--aplicar']);
$verificar($codigo === 0, 'No se pudo aplicar 010 sobre 001-009.');
$verificar((bool) $conexion->query("SELECT to_regclass('public.recuperaciones_contrasena')")->fetchColumn(), 'Falta la tabla de recuperacion.');
$verificar($snapshot() === $antes, '010 altero usuarios, credenciales o datos clinicos existentes.');
[$codigo] = $ejecutar(['--aplicar']);
$verificar($codigo === 1 && $snapshot() === $antes, 'Repetir 010 no fue rechazado sin cambios.');
$verificar($conexion->query('SHOW server_encoding')->fetchColumn() === 'UTF8', 'La base de pruebas no usa UTF8.');
$verificar($conexion->query('SHOW client_encoding')->fetchColumn() === 'UTF8', 'La conexion de pruebas no usa UTF8.');

$id = (int) $conexion->query('SELECT min(id_usuario) FROM usuarios')->fetchColumn();
$consulta = $conexion->prepare("INSERT INTO recuperaciones_contrasena
    (id_usuario,token_hash,correo_hash,version_credenciales,expira_en)
    VALUES (:id,:token,:correo,1,clock_timestamp()+INTERVAL '30 minutes')");
$parametros = ['id'=>$id,'token'=>hash('sha256',random_bytes(32)),'correo'=>hash('sha256','migracion@example.test')];
$consulta->execute($parametros);
$verificar((int) $conexion->query('SELECT count(*) FROM recuperaciones_contrasena')->fetchColumn() === 1, 'La tabla no admite un token valido.');
try {
    $consulta->execute($parametros);
    throw new RuntimeException('La tabla admitio dos solicitudes para una cuenta.');
} catch (PDOException $error) { $verificar((string)$error->getCode() === '23505', 'No protege la unicidad de la cuenta.'); }
$consultaInvalida = $conexion->prepare('UPDATE recuperaciones_contrasena SET token_hash=:token WHERE id_usuario=:id');
try {
    $consultaInvalida->execute(['token'=>'texto-sin-hash','id'=>$id]);
    throw new RuntimeException('Se acepto un token sin hash.');
} catch (PDOException $error) { $verificar((string)$error->getCode() === '23514', 'No protege el formato del hash.'); }
echo "OK: $cantidad comprobaciones de migracion 010, script local, preservacion de datos y UTF-8.\n";
// La base sintetica queda dentro del cluster efimero, que el runner detiene.
