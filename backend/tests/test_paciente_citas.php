<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' || getenv('VIDASALUD_TEST_LOCAL') !== '1'
    || getenv('DB_HOST') !== '127.0.0.1' || !str_ends_with(getenv('DB_NAME') ?: '', '_pruebas')) {
    throw new RuntimeException('Usa el runner con PostgreSQL local aislado.');
}
require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/SoportePruebasAgenda.php';

use CitasMedicas\Business\Services\CitaService;
use CitasMedicas\Business\Services\HorarioMedicoService;
use CitasMedicas\Business\Validators\CitaValidator;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

$total = 0;
function comprobarPacienteCitas(bool $ok, string $mensaje): void {
    global $total;
    if (!$ok) { throw new RuntimeException($mensaje); }
    $total++;
}
function rechazarPacienteCitas(callable $operacion, int $codigo): void {
    try { $operacion(); } catch (ErrorAplicacion $error) {
        comprobarPacienteCitas($error->codigoHttp === $codigo, 'Codigo HTTP incorrecto.');
        return;
    }
    throw new RuntimeException('Falto rechazar una operacion no permitida.');
}

$conexion = ConexionPostgresql::obtenerConexionPostgresql();
$datos = new SoportePruebasAgenda($conexion);
try {
    $datos->crearDatos();
    [$paciente, $otroPaciente] = $datos->usuariosPacientes;
    [$medico, $otroMedico] = $datos->usuariosMedicos;
    $usuarios = new UsuarioRepository($conexion);
    $admin = $usuarios->obtenerUsuarioPorCorreo('administrador@vidasalud.test');
    $recepcion = $usuarios->obtenerUsuarioPorCorreo('recepcionista@vidasalud.test');
    $fecha = (new DateTimeImmutable('+12 days'))->format('Y-m-d');
    $ahora = new DateTimeImmutable($fecha . ' 08:00:00');
    $horarios = new HorarioMedicoService($conexion, $ahora);
    $servicio = new CitaService($conexion, $ahora);
    $horarios->registrarHorario($admin, ['idMedico' => $datos->medicos[0]->idMedico,
        'fechaAtencion' => $fecha, 'horaInicio' => '09:00', 'horaFin' => '11:00']);
    $turnos = $horarios->listarTurnosDisponibles($paciente,
        ['idMedico' => $datos->medicos[0]->idMedico, 'fecha' => $fecha]);
    comprobarPacienteCitas(count($turnos) === 4, 'Se conservan turnos de treinta minutos.');
    foreach (['proximas', 'historial'] as $vista) {
        $vacia = $servicio->listarCitas($paciente, ['vista' => $vista]);
        comprobarPacienteCitas($vacia['elementos'] === [] && !$vacia['hayPaginaSiguiente'], 'Lista vacia correcta.');
    }
    // Mas de una pagina de citas propias, sin saltarse ninguna regla de negocio.
    // Cada cancelacion libera el turno y permite reservar otra vez el mismo dia.
    $canceladas = [];
    for ($i = 0; $i < 23; $i++) {
        $cita = $servicio->reservarCita($paciente, ['idTurno' => $turnos[0]['idTurno']]);
        $servicio->cancelarCita($paciente, $cita['idCita'], ['motivoCancelacion' => 'Prueba de paginacion']);
        $canceladas[] = $cita['idCita'];
    }
    $proxima = $servicio->reservarCita($paciente, ['idTurno' => $turnos[0]['idTurno']]);
    $ajena = $servicio->reservarCita($otroPaciente, ['idTurno' => $turnos[1]['idTurno']]);
    $listado = $servicio->listarCitas($paciente, ['vista' => 'proximas']);
    comprobarPacienteCitas(array_column($listado['elementos'], 'idCita') === [$proxima['idCita']], 'Proximas filtra antes de paginar.');
    comprobarPacienteCitas(!$listado['hayPaginaSiguiente'], 'Proximas no hereda paginas del historial.');
    comprobarPacienteCitas($listado['elementos'][0]['puedeCancelar'] && !$listado['elementos'][0]['puedeReprogramar'], 'Permisos existentes de cancelacion/reprogramacion.');
    $pagina1 = $servicio->listarCitas($paciente, ['vista' => 'historial', 'pagina' => '1']);
    $pagina2 = $servicio->listarCitas($paciente, ['vista' => 'historial', 'pagina' => '2']);
    comprobarPacienteCitas(count($pagina1['elementos']) === 20 && $pagina1['hayPaginaSiguiente'], 'Primera pagina con veinte resultados.');
    comprobarPacienteCitas(count($pagina2['elementos']) === 3 && !$pagina2['hayPaginaSiguiente'], 'Segunda pagina completa.');
    $ids = array_merge(array_column($pagina1['elementos'], 'idCita'), array_column($pagina2['elementos'], 'idCita'));
    comprobarPacienteCitas($ids === array_reverse($canceladas), 'Orden descendente estable sin duplicados ni perdidas.');
    comprobarPacienteCitas($servicio->listarCitas($paciente, ['vista' => 'historial', 'pagina' => '3'])['elementos'] === [], 'Pagina posterior vacia.');
    foreach ($pagina1['elementos'] as $fila) {
        comprobarPacienteCitas($fila['idPaciente'] === $datos->pacientes[0]['idPaciente'] && !$fila['puedeCancelar'], 'Historial propio sin cancelar estados finales.');
    }
    foreach (['proximas', 'historial'] as $vista) {
        comprobarPacienteCitas($servicio->listarCitas($paciente, ['vista' => $vista, 'idPaciente' => $datos->pacientes[1]['idPaciente']])['elementos'] === [], 'Un filtro no cambia el propietario de la sesion.');
        comprobarPacienteCitas($servicio->listarCitas($paciente, ['vista' => $vista, 'fecha' => '2001-01-01'])['elementos'] === [], 'Filtro fecha sin resultados.');
    }
    comprobarPacienteCitas(count($servicio->listarCitas($paciente, ['vista' => 'historial', 'fecha' => $fecha])['elementos']) === 20, 'Filtro fecha conserva paginacion.');
    comprobarPacienteCitas(count($servicio->listarCitas($paciente, ['vista' => 'proximas', 'busqueda' => $proxima['nombreEspecialidad']])['elementos']) === 1, 'Busqueda por especialidad propia.');
    comprobarPacienteCitas($servicio->listarCitas($paciente, ['vista' => 'historial', 'busqueda' => $datos->pacientes[0]['cedulaIdentidad']])['elementos'] === [], 'El buscador del paciente no busca personas por CI.');
    rechazarPacienteCitas(fn() => $servicio->obtenerCita($paciente, $ajena['idCita']), 404);
    rechazarPacienteCitas(fn() => $servicio->cancelarCita($paciente, $ajena['idCita'], []), 404);
    rechazarPacienteCitas(fn() => $servicio->reservarCita($paciente, ['idTurno' => $turnos[2]['idTurno'], 'idPaciente' => $datos->pacientes[1]['idPaciente']]), 403);
    rechazarPacienteCitas(fn() => $servicio->reservarCita($paciente, ['idTurno' => $turnos[2]['idTurno']]), 409);
    comprobarPacienteCitas(count($servicio->listarCitas($medico, ['vista' => 'proximas'])['elementos']) === 2, 'Agenda del medico conserva citas asignadas.');
    comprobarPacienteCitas($servicio->listarCitas($otroMedico, ['vista' => 'proximas'])['elementos'] === [], 'Medico ajeno sin citas.');
    foreach ([$admin, $recepcion] as $actor) {
        comprobarPacienteCitas($servicio->listarCitas($actor, ['idPaciente' => $datos->pacientes[1]['idPaciente']])['elementos'][0]['idCita'] === $ajena['idCita'], 'Tabla administrativa conserva filtros por paciente.');
    }
    $alInicio = new CitaService($conexion, new DateTimeImmutable($fecha . ' 09:00:00'));
    comprobarPacienteCitas($alInicio->listarCitas($paciente, ['vista' => 'proximas'])['elementos'] === [], 'El inicio exacto excluye la cita de proximas.');
    $historialInicio = $alInicio->listarCitas($paciente, ['vista' => 'historial'])['elementos'];
    comprobarPacienteCitas($historialInicio[0]['idCita'] === $proxima['idCita'] && !$historialInicio[0]['puedeCancelar'], 'Programada vencida aparece en historial sin cancelacion.');
    rechazarPacienteCitas(fn() => $alInicio->cancelarCita($paciente, $proxima['idCita'], []), 409);
    $servicio->cancelarCita($paciente, $proxima['idCita'], []);
    $mismaFecha = $servicio->reservarCita($paciente, ['idTurno' => $turnos[2]['idTurno']]);
    comprobarPacienteCitas(substr($mismaFecha['fechaHoraInicio'], 0, 10) === $fecha, 'No se agrega una restriccion diaria.');
    comprobarPacienteCitas(strtotime($mismaFecha['fechaHoraFin']) - strtotime($mismaFecha['fechaHoraInicio']) === 1800, 'Duracion conservada.');
    foreach (['administrativa', [], 1] as $vistaInvalida) {
        rechazarPacienteCitas(fn() => CitaValidator::validarFiltrosCitas(['vista' => $vistaInvalida]), 422);
    }
    echo "OK: $total comprobaciones de citas propias, pestanas, paginacion, filtros y cancelacion.\n";
} finally {
    $datos->eliminarDatos();
}
