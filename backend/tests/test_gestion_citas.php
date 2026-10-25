<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/SoportePruebasAgenda.php';
use CitasMedicas\Business\Services\CitaService;
use CitasMedicas\Business\Services\HorarioMedicoService;
use CitasMedicas\Business\Services\MedicoService;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
if (!str_ends_with(CargarVariablesEntorno::obtenerVariableEntorno('DB_NAME'), '_pruebas')) { fwrite(STDERR, "Usa una base separada terminada en _pruebas.\n"); exit(1); }
$comprobaciones = 0;
function comprobarCitas(bool $resultado, string $mensaje): void { global $comprobaciones; if (!$resultado) { throw new RuntimeException($mensaje); } $comprobaciones++; }
function rechazarCitas(callable $operacion, int $codigo): void {
    try { $operacion(); } catch (ErrorAplicacion $error) { comprobarCitas($error->codigoHttp === $codigo, 'Codigo inesperado: ' . $error->getMessage()); return; }
    throw new RuntimeException('La operacion debio rechazarse con ' . $codigo);
}
$conexion = ConexionPostgresql::obtenerConexionPostgresql();
$datosPrueba = new SoportePruebasAgenda($conexion);
try {
    $datosPrueba->crearDatos();
    [$pacientePrimero, $pacienteSegundo] = $datosPrueba->usuariosPacientes;
    [$medicoPrimero, $medicoSegundo] = $datosPrueba->usuariosMedicos;
    $usuarios = new UsuarioRepository($conexion);
    $administrador = $usuarios->obtenerUsuarioPorCorreo('administrador@vidasalud.test');
    $recepcionista = $usuarios->obtenerUsuarioPorCorreo('recepcionista@vidasalud.test');
    if (!$administrador || !$recepcionista) { throw new RuntimeException('Carga primero los datos iniciales.'); }
    $fecha = (new DateTimeImmutable('+7 days'))->format('Y-m-d');
    $ahora = new DateTimeImmutable($fecha . ' 08:00:00');
    $horarios = new HorarioMedicoService($conexion, $ahora);
    $citas = new CitaService($conexion, $ahora);
    $bloque = ['idMedico' => $datosPrueba->medicos[0]->idMedico, 'fechaAtencion' => $fecha, 'horaInicio' => '09:00', 'horaFin' => '11:00'];
    foreach ([$pacientePrimero, $recepcionista, $medicoPrimero] as $actor) { rechazarCitas(fn() => $horarios->registrarHorario($actor, $bloque), 403); }
    $creado = $horarios->registrarHorario($administrador, $bloque);
    comprobarCitas($creado['cantidadTurnos'] === 4, 'Dos horas deben producir cuatro turnos.');
    rechazarCitas(fn() => $horarios->registrarHorario($administrador, array_replace($bloque, ['horaInicio' => '09:30'])), 409);
    $horarios->registrarHorario($administrador, array_replace($bloque, ['horaInicio' => '11:00', 'horaFin' => '11:30']));
    $horarios->registrarHorario($administrador, array_replace($bloque, ['idMedico' => $datosPrueba->medicos[1]->idMedico]));
    $turnosPrimero = $horarios->listarTurnosDisponibles($pacientePrimero, ['idMedico' => $bloque['idMedico'], 'fecha' => $fecha]);
    $turnosSegundo = $horarios->listarTurnosDisponibles($pacientePrimero, ['idMedico' => $datosPrueba->medicos[1]->idMedico, 'fecha' => $fecha]);
    comprobarCitas(count($turnosPrimero) === 5, 'Bloques contiguos permitidos.');
    comprobarCitas(count($horarios->listarHorarios($medicoSegundo, ['fecha' => $fecha])['elementos']) === 1, 'El medico solo ve sus horarios.');
    comprobarCitas($horarios->listarHorarios($medicoSegundo, ['idMedico' => $bloque['idMedico']])['elementos'] === [], 'Un filtro no elimina el alcance propio.');
    rechazarCitas(fn() => $horarios->listarHorarios($pacientePrimero, []), 403);
    rechazarCitas(fn() => $horarios->listarMedicosDisponibles($medicoPrimero), 403);
    $reserva = ['idTurno' => $turnosPrimero[0]['idTurno'], 'motivoCita' => '<script>Ejemplo</script>'];
    rechazarCitas(fn() => $citas->reservarCita($medicoPrimero, $reserva), 403);
    rechazarCitas(fn() => $citas->reservarCita($pacientePrimero, $reserva + ['idPaciente' => $datosPrueba->pacientes[1]['idPaciente']]), 403);
    $primera = $citas->reservarCita($pacientePrimero, $reserva);
    comprobarCitas($primera['idPaciente'] === $datosPrueba->pacientes[0]['idPaciente'], 'El propietario procede de la sesion.');
    comprobarCitas($primera['puedeCancelar'] && !$primera['puedeReprogramar'], 'Acciones del paciente.');
    comprobarCitas(count($horarios->listarTurnosDisponibles($pacientePrimero, ['idMedico' => $bloque['idMedico'], 'fecha' => $fecha])) === 4, 'El turno reservado deja de ofrecerse.');
    // La restriccion SQL tambien impide duplicados si se evita el servicio.
    try {
        $insercionDirecta = $conexion->prepare('INSERT INTO citas (id_paciente, id_turno) VALUES (:idPaciente, :idTurno)');
        $insercionDirecta->execute(['idPaciente' => $datosPrueba->pacientes[1]['idPaciente'], 'idTurno' => $primera['idTurno']]);
        throw new RuntimeException('El indice SQL permitio doble ocupacion.');
    } catch (PDOException $error) { comprobarCitas($error->getCode() === '23505', 'Indice unico parcial activo.'); }
    rechazarCitas(fn() => $citas->reservarCita($pacienteSegundo, $reserva), 409);
    rechazarCitas(fn() => $citas->reservarCita($pacientePrimero, ['idTurno' => $turnosSegundo[0]['idTurno']]), 409);
    rechazarCitas(fn() => $citas->obtenerCita($pacienteSegundo, $primera['idCita']), 404);
    rechazarCitas(fn() => $citas->cancelarCita($pacienteSegundo, $primera['idCita'], []), 404);
    rechazarCitas(fn() => $citas->obtenerCita($medicoSegundo, $primera['idCita']), 404);
    comprobarCitas($citas->obtenerCita($medicoPrimero, $primera['idCita'])['nombrePaciente'] !== '', 'Datos basicos para el medico asignado.');
    comprobarCitas($citas->listarCitas($pacienteSegundo, ['busqueda' => 'Prueba'])['elementos'] === [], 'Buscar no expone otras citas.');
    comprobarCitas($citas->listarCitas($medicoSegundo, [])['elementos'] === [], 'Agenda limitada al medico.');
    rechazarCitas(fn() => $citas->reprogramarCita($pacientePrimero, $primera['idCita'], ['idTurno' => $turnosPrimero[1]['idTurno']]), 403);
    rechazarCitas(fn() => $citas->registrarAsistencia($medicoPrimero, $primera['idCita'], ['asistenciaRegistrada' => 'asistio']), 403);
    rechazarCitas(fn() => $citas->registrarAsistencia($recepcionista, $primera['idCita'], ['asistenciaRegistrada' => 'asistio']), 409);
    rechazarCitas(fn() => (new MedicoService($conexion))->cambiarEstadoMedico($bloque['idMedico'], ['medicoActivo' => false]), 409);
    $ocupada = $citas->reservarCita($recepcionista, ['idPaciente' => $datosPrueba->pacientes[1]['idPaciente'], 'idTurno' => $turnosPrimero[1]['idTurno']]);
    rechazarCitas(fn() => $citas->reprogramarCita($recepcionista, $primera['idCita'], ['idTurno' => $ocupada['idTurno']]), 409);
    comprobarCitas($citas->obtenerCita($pacientePrimero, $primera['idCita'])['estadoCita'] === 'programada', 'Un fallo conserva la reserva original.');
    // Forzar un fallo DESPUES de modificar la cita original permite comprobar el rollback completo.
    $restriccionPrueba = 'prueba_reprogramacion_' . $datosPrueba->identificador;
    $conexion->exec('ALTER TABLE citas ADD CONSTRAINT ' . $restriccionPrueba . ' CHECK (id_cita_origen IS NULL OR id_cita_origen <> ' . (int) $primera['idCita'] . ') NOT VALID');
    try {
        try {
            $citas->reprogramarCita($recepcionista, $primera['idCita'], ['idTurno' => $turnosSegundo[0]['idTurno']]);
            throw new RuntimeException('La restriccion de prueba no se ejecuto.');
        } catch (PDOException $error) { comprobarCitas($error->getCode() === '23514', 'Fallo forzado en la nueva reserva.'); }
        comprobarCitas($citas->obtenerCita($pacientePrimero, $primera['idCita'])['estadoCita'] === 'programada', 'Rollback restaura la cita despues de modificarla.');
        comprobarCitas(count($horarios->listarTurnosDisponibles($pacientePrimero, ['idMedico' => $datosPrueba->medicos[1]->idMedico, 'fecha' => $fecha])) === 4, 'Rollback no ocupa el turno nuevo.');
    } finally { $conexion->exec('ALTER TABLE citas DROP CONSTRAINT ' . $restriccionPrueba); }
    $nueva = $citas->reprogramarCita($recepcionista, $primera['idCita'], ['idTurno' => $turnosSegundo[0]['idTurno']]);
    comprobarCitas($nueva['idCitaOrigen'] === $primera['idCita'], 'Trazabilidad de reprogramacion.');
    comprobarCitas($citas->obtenerCita($pacientePrimero, $primera['idCita'])['estadoCita'] === 'reprogramada', 'Estado de cita original.');
    $anulada = $citas->cancelarCita($pacientePrimero, $nueva['idCita'], ['motivoCancelacion' => 'Cambio de planes']);
    comprobarCitas($anulada['estadoCita'] === 'cancelada' && !$anulada['puedeCancelar'], 'Anulacion sin borrado.');
    rechazarCitas(fn() => $citas->cancelarCita($pacientePrimero, $nueva['idCita'], []), 409);
    $reutilizada = $citas->reservarCita($pacientePrimero, ['idTurno' => $nueva['idTurno']]);
    comprobarCitas($reutilizada['idCita'] !== $nueva['idCita'], 'El turno anulado se libera.');
    $alInicio = new CitaService($conexion, new DateTimeImmutable($fecha . ' 09:00:00'));
    rechazarCitas(fn() => $alInicio->cancelarCita($pacientePrimero, $reutilizada['idCita'], []), 409);
    rechazarCitas(fn() => $alInicio->registrarAsistencia($recepcionista, $reutilizada['idCita'], ['asistenciaRegistrada' => 'no_asistio']), 409);
    $asistencia = $alInicio->registrarAsistencia($recepcionista, $reutilizada['idCita'], ['asistenciaRegistrada' => 'asistio']);
    comprobarCitas($asistencia['asistenciaRegistrada'] === 'asistio' && $asistencia['estadoCita'] === 'atendida', 'Asistio cambia el estado a Atendida.');
    rechazarCitas(fn() => $alInicio->registrarAsistencia($recepcionista, $reutilizada['idCita'], ['asistenciaRegistrada' => 'asistio']), 409);
    $alFin = new CitaService($conexion, new DateTimeImmutable($fecha . ' 10:00:00'));
    comprobarCitas($alFin->registrarAsistencia($administrador, $ocupada['idCita'], ['asistenciaRegistrada' => 'no_asistio'])['estadoCita'] === 'no_asistio', 'Inasistencia al finalizar el turno.');
    rechazarCitas(fn() => $alFin->reservarCita($pacientePrimero, ['idTurno' => $turnosPrimero[0]['idTurno']]), 409);
    $inhabilitar = $conexion->prepare('UPDATE turnos SET turno_habilitado = false WHERE id_turno = :idTurno');
    $inhabilitar->execute(['idTurno' => $turnosPrimero[3]['idTurno']]);
    rechazarCitas(fn() => $citas->reservarCita($pacientePrimero, ['idTurno' => $turnosPrimero[3]['idTurno']]), 409);
    // Usar un medico sin citas futuras para probar desactivacion y catalogo.
    rechazarCitas(fn() => $citas->cancelarCita($pacienteSegundo, $ocupada['idCita'], []), 409);
    (new MedicoService($conexion))->cambiarEstadoMedico($bloque['idMedico'], ['medicoActivo' => false]);
    rechazarCitas(fn() => $citas->reservarCita($pacientePrimero, ['idTurno' => $turnosPrimero[4]['idTurno']]), 409);
    comprobarCitas($horarios->listarTurnosDisponibles($pacientePrimero, ['idMedico' => $bloque['idMedico'], 'fecha' => $fecha]) === [], 'Medico inactivo sin disponibilidad.');
    rechazarCitas(fn() => $horarios->registrarHorario($administrador, array_replace($bloque, ['horaInicio' => '12:00', 'horaFin' => '13:00'])), 409);
} finally { $datosPrueba->eliminarDatos(); }
echo "OK: $comprobaciones comprobaciones de agenda, alcance por rol, reservas y transacciones.\n";
