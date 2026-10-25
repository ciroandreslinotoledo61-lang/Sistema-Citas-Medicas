<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use CitasMedicas\Business\Services\EspecialidadService;
use CitasMedicas\Business\Services\MedicoService;
use CitasMedicas\Business\Services\UsuarioService;
use CitasMedicas\Business\Services\AutenticacionService;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SesionUsuario;

if (!str_ends_with(CargarVariablesEntorno::obtenerVariableEntorno('DB_NAME'), '_pruebas')) {
    fwrite(STDERR, "Usa una base separada cuyo nombre termine en _pruebas. Lee docs/ai/pruebas.md.\n");
    exit(1);
}

function exigirCondicionGestion(bool $condicion, string $mensaje): void
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
}

function exigirErrorGestion(callable $operacion, int $codigoEsperado): void
{
    try { $operacion(); } catch (ErrorAplicacion $error) {
        exigirCondicionGestion($error->codigoHttp === $codigoEsperado, 'Codigo de error inesperado: ' . $error->getMessage());
        return;
    }
    throw new RuntimeException('Falto rechazar una operacion no permitida.');
}

SesionUsuario::iniciarSesionSegura();
$conexion = ConexionPostgresql::obtenerConexionPostgresql();
$especialidadService = new EspecialidadService($conexion);
$medicoService = new MedicoService($conexion);
$usuarioService = new UsuarioService($conexion);
$usuarioRepository = new UsuarioRepository($conexion);
$autenticacionService = new AutenticacionService($usuarioRepository,new \CitasMedicas\Business\Services\BitacoraService($conexion));
$identificadorPrueba = bin2hex(random_bytes(5));
$nombrePrimera = 'Especialidad prueba A ' . $identificadorPrueba;
$nombreSegunda = 'Especialidad prueba B ' . $identificadorPrueba;
$correoPrimero = 'medico-' . $identificadorPrueba . '@example.test';
$correoSegundo = 'rechazado-' . $identificadorPrueba . '@example.test';
$datosMedico = [
    'nombresUsuario' => 'Medico', 'apellidosUsuario' => 'Prueba',
    'correoUsuario' => $correoPrimero,
    'contrasenaUsuario' => 'FraseParaPrueba123', 'confirmacionContrasena' => 'FraseParaPrueba123',
    'telefonoMedico' => '70000001', 'matriculaProfesional' => 'TEST-' . $identificadorPrueba, 'codigoRol' => 'administrador',
];
try {
    $primera = $especialidadService->registrarEspecialidad(['nombreEspecialidad' => $nombrePrimera]);
    $segunda = $especialidadService->registrarEspecialidad(['nombreEspecialidad' => $nombreSegunda]);
    exigirErrorGestion(fn() => $especialidadService->registrarEspecialidad(['nombreEspecialidad' => strtolower($nombrePrimera)]), 409);
    $especialidadService->actualizarDatosEspecialidad($primera->idEspecialidad, [
        'nombreEspecialidad' => $nombrePrimera, 'descripcionEspecialidad' => 'Servicio de prueba',
    ]);
    $especialidadService->cambiarEstadoEspecialidad($segunda->idEspecialidad, ['especialidadActiva' => false]);
    $datosMedico['idEspecialidad'] = $segunda->idEspecialidad;
    exigirErrorGestion(fn() => $medicoService->registrarMedicoConUsuario($datosMedico), 409);
    exigirCondicionGestion($usuarioRepository->obtenerUsuarioPorCorreo($correoPrimero) === null, 'Se creo una cuenta para una especialidad inactiva.');

    $datosMedico['idEspecialidad'] = $primera->idEspecialidad;
    $medico = $medicoService->registrarMedicoConUsuario($datosMedico);
    exigirCondicionGestion($usuarioRepository->obtenerUsuarioPorId($medico->idUsuario)->codigoRol === 'medico', 'El rol no debe tomarse del formulario.');
    exigirCondicionGestion($medico->medicoActivo, 'El medico nuevo debe estar activo.');
    exigirCondicionGestion(!array_key_exists('contrasenaHash', $medico->obtenerDatosPublicosMedico()), 'No exponer el hash.');
    exigirErrorGestion(fn() => $especialidadService->cambiarEstadoEspecialidad($primera->idEspecialidad, ['especialidadActiva' => false]), 409);
    exigirErrorGestion(fn() => $medicoService->registrarMedicoConUsuario(array_replace($datosMedico, ['correoUsuario' => $correoSegundo])), 409);
    exigirCondicionGestion($usuarioRepository->obtenerUsuarioPorCorreo($correoSegundo) === null, 'Quedo una cuenta tras una matricula duplicada.');
    exigirErrorGestion(fn() => $medicoService->registrarMedicoConUsuario(array_replace($datosMedico, ['telefonoMedico' => '70000001', 'matriculaProfesional' => 'OTRA-' . $identificadorPrueba])), 409);

    $autenticacionService->iniciarSesionUsuario(['correoUsuario' => $correoPrimero, 'contrasenaUsuario' => 'FraseParaPrueba123']);
    $medicoService->cambiarEstadoMedico($medico->idMedico, ['medicoActivo' => false]);
    exigirErrorGestion(fn() => $autenticacionService->obtenerUsuarioAutenticado(), 401);
    exigirErrorGestion(fn() => $autenticacionService->iniciarSesionUsuario(['correoUsuario' => $correoPrimero, 'contrasenaUsuario' => 'FraseParaPrueba123']), 401);
    $especialidadService->cambiarEstadoEspecialidad($primera->idEspecialidad, ['especialidadActiva' => false]);
    exigirErrorGestion(fn() => $medicoService->cambiarEstadoMedico($medico->idMedico, ['medicoActivo' => true]), 409);

    $administrador = $usuarioRepository->obtenerUsuarioPorCorreo('administrador@vidasalud.test')
        ?? throw new RuntimeException('Carga primero los datos iniciales en la base de pruebas.');
    exigirErrorGestion(fn() => $usuarioService->cambiarEstadoUsuario($medico->idUsuario, ['usuarioActivo' => true], $administrador), 409);

    $datosMedico['nombresUsuario'] = 'Medico editado';
    $medicoService->actualizarDatosMedico($medico->idMedico, $datosMedico);
    exigirErrorGestion(fn() => $medicoService->actualizarDatosMedico($medico->idMedico, array_replace($datosMedico, ['idEspecialidad' => $segunda->idEspecialidad])), 409);
    $especialidadService->cambiarEstadoEspecialidad($segunda->idEspecialidad, ['especialidadActiva' => true]);
    $datosMedico['idEspecialidad'] = $segunda->idEspecialidad;
    $medicoService->actualizarDatosMedico($medico->idMedico, $datosMedico);
    $medicoService->cambiarEstadoMedico($medico->idMedico, ['medicoActivo' => true]);
    $medicoActualizado = $medicoService->obtenerMedicoPorId($medico->idMedico);
    exigirCondicionGestion($medicoActualizado->nombresUsuario === 'Medico editado' && $medicoActualizado->idEspecialidad === $segunda->idEspecialidad, 'No se actualizaron los datos.');
    exigirCondicionGestion(count($medicoService->listarMedicos(['busqueda' => strtoupper('TEST-' . $identificadorPrueba)])['elementos']) === 1, 'Busqueda por matricula.');
    exigirCondicionGestion(count($especialidadService->listarEspecialidades(['busqueda' => $identificadorPrueba])['elementos']) === 2, 'Busqueda de especialidades.');
    echo "OK: registro, edicion, duplicados, estados, especialidad activa, rol fijo e invalidacion de sesion.\n";
} finally {
    if ($conexion->inTransaction()) { $conexion->rollBack(); }
    $eliminarMedicos = $conexion->prepare('DELETE FROM medicos WHERE id_usuario IN (SELECT id_usuario FROM usuarios WHERE correo_usuario IN (:correoPrimero, :correoSegundo))');
    $eliminarMedicos->execute(['correoPrimero' => $correoPrimero, 'correoSegundo' => $correoSegundo]);
    $eliminarUsuarios = $conexion->prepare('DELETE FROM usuarios WHERE correo_usuario IN (:correoPrimero, :correoSegundo)');
    $eliminarUsuarios->execute(['correoPrimero' => $correoPrimero, 'correoSegundo' => $correoSegundo]);
    $eliminarEspecialidades = $conexion->prepare('DELETE FROM especialidades WHERE nombre_especialidad IN (:nombrePrimera, :nombreSegunda)');
    $eliminarEspecialidades->execute(['nombrePrimera' => $nombrePrimera, 'nombreSegunda' => $nombreSegunda]);
}
