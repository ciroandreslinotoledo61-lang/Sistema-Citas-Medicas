<?php
declare(strict_types=1);

// Integracion real con PDO y PostgreSQL. Usar solo una base que termine en _pruebas.
require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Business\Services\PacienteService;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;

if (!str_ends_with(CargarVariablesEntorno::obtenerVariableEntorno('DB_NAME'), '_pruebas')) {
    fwrite(STDERR, "Usa una base separada cuyo nombre termine en _pruebas. Consulta docs/ai/pruebas.md.\n");
    exit(1);
}
$conexion = ConexionPostgresql::obtenerConexionPostgresql();
$pacienteService = new PacienteService($conexion);
$sufijoPrueba = bin2hex(random_bytes(5));
$correoPrimero = 'prueba-' . $sufijoPrueba . '@example.test';
$correoSegundo = 'duplicado-' . $sufijoPrueba . '@example.test';
$datos = [
    'nombresUsuario' => 'Prueba', 'apellidosUsuario' => 'Transaccion',
    'correoUsuario' => $correoPrimero, 'contrasenaUsuario' => 'FraseParaPrueba123',
    'confirmacionContrasena' => 'FraseParaPrueba123', 'cedulaIdentidad' => 'TEST-' . $sufijoPrueba,
    'fechaNacimiento' => '2000-01-01', 'telefonoPaciente' => '70000000',
    'direccionPaciente' => '', 'nombreContactoEmergencia' => '', 'telefonoContactoEmergencia' => '',
    'codigoRol' => 'administrador', // La entrada publica nunca debe asignar este rol.
];
try {
    $paciente = $pacienteService->registrarPacienteConHistoriaClinica($datos)->obtenerDatosPublicosPaciente();
    if ($paciente['idHistoriaClinica'] < 1) { throw new RuntimeException('No se creo la historia clinica.'); }
    $consultaRol = $conexion->prepare('SELECT roles.codigo_rol FROM usuarios INNER JOIN roles USING (id_rol) WHERE correo_usuario = :correo');
    $consultaRol->execute(['correo' => $correoPrimero]);
    if ($consultaRol->fetchColumn() !== 'paciente') { throw new RuntimeException('Se permitio escalar el rol.'); }
    try {
        $pacienteService->registrarPacienteConHistoriaClinica(array_replace($datos, ['correoUsuario' => $correoSegundo]));
        throw new RuntimeException('Se acepto una cedula duplicada.');
    } catch (PDOException $error) {
        if ((string) $error->getCode() !== '23505') { throw $error; }
    }
    $consultaHuerfano = $conexion->prepare('SELECT count(*) FROM usuarios WHERE correo_usuario = :correo');
    $consultaHuerfano->execute(['correo' => $correoSegundo]);
    if ((int) $consultaHuerfano->fetchColumn() !== 0) { throw new RuntimeException('Quedo un usuario sin paciente despues del error.'); }
    echo "OK: registro completo, rol fijo, historia obligatoria y rollback al duplicar CI.\n";
} finally {
    // Eliminar solamente los registros de esta ejecucion, incluso si la prueba falla.
    $consultaUsuarios = $conexion->prepare('SELECT id_usuario FROM usuarios WHERE correo_usuario IN (:correoPrimero, :correoSegundo)');
    $consultaUsuarios->execute(['correoPrimero' => $correoPrimero, 'correoSegundo' => $correoSegundo]);
    foreach ($consultaUsuarios->fetchAll(PDO::FETCH_COLUMN) as $idUsuario) {
        $eliminarHistoria = $conexion->prepare('DELETE FROM historias_clinicas WHERE id_paciente IN (SELECT id_paciente FROM pacientes WHERE id_usuario = :idUsuario)');
        $eliminarHistoria->execute(['idUsuario' => $idUsuario]);
        $eliminarPaciente = $conexion->prepare('DELETE FROM pacientes WHERE id_usuario = :idUsuario');
        $eliminarPaciente->execute(['idUsuario' => $idUsuario]);
        $eliminarUsuario = $conexion->prepare('DELETE FROM usuarios WHERE id_usuario = :idUsuario');
        $eliminarUsuario->execute(['idUsuario' => $idUsuario]);
    }
}
