<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Seeders\DemostracionSeeder;

if (getenv('VIDASALUD_TEST_LOCAL') !== '1' || getenv('DB_HOST') !== '127.0.0.1' || !str_ends_with(getenv('DB_NAME') ?: '', '_pruebas')) {
    throw new RuntimeException('Usa --completo: este ensayo solo admite su base sintética local.');
}
$conexion = ConexionPostgresql::obtenerConexionPostgresql();
$anteriores = $conexion->query('SELECT * FROM usuarios ORDER BY id_usuario')->fetchAll(PDO::FETCH_UNIQUE);
$habilitado = getenv('DEMO_SEED_ENABLE');
try {
    putenv('DEMO_SEED_ENABLE=1');
    $clave = 'DemoAislada' . bin2hex(random_bytes(12));
    $primero = DemostracionSeeder::cargarDatosDemostracion($conexion, $clave);
    $segundo = DemostracionSeeder::cargarDatosDemostracion($conexion, 'OtraClave' . bin2hex(random_bytes(12)));
    if (count($primero) !== 6 || array_unique(array_column($primero, 'estado')) !== ['creada'] || array_unique(array_column($segundo, 'estado')) !== ['conservada']) {
        throw new RuntimeException('El seed no creó o conservó las seis cuentas.');
    }
    if (array_column($primero, 'idUsuario') !== array_column($segundo, 'idUsuario')) { throw new RuntimeException('Duplicación de cuentas.'); }
    $actuales = $conexion->query('SELECT * FROM usuarios ORDER BY id_usuario')->fetchAll(PDO::FETCH_UNIQUE);
    foreach ($anteriores as $id=>$fila) {
        if ($actuales[$id] !== $fila) { throw new RuntimeException('Se modificó una cuenta preexistente.'); }
    }
    foreach ($primero as $cuenta) {
        if (!password_verify($clave, $actuales[$cuenta['idUsuario']]['contrasena_hash'])) { throw new RuntimeException('Se reemplazó una contraseña en la segunda ejecución.'); }
    }
    $medicos = $conexion->query("SELECT m.matricula_profesional, r.codigo_rol, e.nombre_especialidad FROM medicos m JOIN usuarios u USING(id_usuario) JOIN roles r USING(id_rol) JOIN especialidades e USING(id_especialidad) WHERE u.correo_usuario LIKE '%@demo.vidasalud.test' ORDER BY m.matricula_profesional")->fetchAll();
    $pacientes = $conexion->query("SELECT p.cedula_identidad, r.codigo_rol, h.id_historia_clinica FROM pacientes p JOIN usuarios u USING(id_usuario) JOIN roles r USING(id_rol) JOIN historias_clinicas h USING(id_paciente) WHERE u.correo_usuario LIKE '%@demo.vidasalud.test'")->fetchAll();
    if (count($medicos) !== 3 || count($pacientes) !== 3 || array_unique(array_column($medicos, 'codigo_rol')) !== ['medico'] || array_unique(array_column($pacientes, 'codigo_rol')) !== ['paciente']) { throw new RuntimeException('Perfiles o roles incorrectos.'); }
    $nombres = array_map(fn($fila)=>strtr(mb_strtolower($fila['nombre_especialidad']), ['í'=>'i', 'ó'=>'o']), $medicos);
    if ($nombres !== ['medicina general', 'pediatria', 'dermatologia']) { throw new RuntimeException('Especialidades incorrectas.'); }
    // Estas seis cuentas se conservan solo en el cluster desechable del ejecutor.
} finally { putenv($habilitado === false ? 'DEMO_SEED_ENABLE' : 'DEMO_SEED_ENABLE='.$habilitado); }
echo "OK: seis cuentas, roles, especialidades e historias; repetición sin duplicados ni cambios de claves o cuentas anteriores.\n";
