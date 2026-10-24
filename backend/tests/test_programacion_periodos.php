<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/SoportePruebasAgenda.php';
use CitasMedicas\Business\Services\CitaService;
use CitasMedicas\Business\Services\HorarioMedicoService;
use CitasMedicas\Business\Services\MedicoService;
use CitasMedicas\Business\Validators\AgendaValidator;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
$conexion = ConexionPostgresql::obtenerConexionPostgresql();
$datosPrueba = new SoportePruebasAgenda($conexion);
$comprobaciones = 0;
function comprobarPeriodo(bool $resultado, string $mensaje): void {
    global $comprobaciones;
    if (!$resultado) { throw new RuntimeException($mensaje); }
    $comprobaciones++;
}
function rechazarPeriodo(callable $operacion, int $codigo = 422): void {
    try { $operacion(); } catch (ErrorAplicacion $error) { comprobarPeriodo($error->codigoHttp === $codigo, $error->getMessage()); return; }
    throw new RuntimeException('Falto rechazar una operacion.');
}
try {
    $datosPrueba->crearDatos();
    $usuarios = new UsuarioRepository($conexion);
    $administrador = $usuarios->obtenerUsuarioPorCorreo('administrador@vidasalud.test');
    $recepcionista = $usuarios->obtenerUsuarioPorCorreo('recepcionista@vidasalud.test');
    [$paciente, $otroPaciente] = $datosPrueba->usuariosPacientes;
    [$medico, $otroMedico] = $datosPrueba->usuariosMedicos;
    $idPaciente = $datosPrueba->pacientes[0]['idPaciente'];
    $inicioMes = new DateTimeImmutable('first day of next month 00:00');
    $ahora = $inicioMes->modify('-1 day')->setTime(7, 0);
    $horarios = new HorarioMedicoService($conexion, $ahora);
    $periodo = ['idMedico' => $datosPrueba->medicos[0]->idMedico,
        'fechaInicial' => $inicioMes->format('Y-m-d'), 'fechaFinal' => $inicioMes->modify('last day of this month')->format('Y-m-d'),
        'diasSemana' => [1,2,3,4,5], 'horaInicio' => '08:00', 'horaFin' => '17:00'];
    $laborales = [];
    for ($dia = $inicioMes; $dia->format('Y-m-d') <= $periodo['fechaFinal']; $dia = $dia->modify('+1 day')) {
        if ((int) $dia->format('N') <= 5) { $laborales[] = $dia->format('Y-m-d'); }
    }
    foreach ([$recepcionista, $medico, $paciente] as $actor) { rechazarPeriodo(fn() => $horarios->programarHorariosPorPeriodo($actor, $periodo), 403); }
    $resumen = $horarios->programarHorariosPorPeriodo($administrador, $periodo);
    comprobarPeriodo($resumen['fechasProcesadas'] === count($laborales) && $resumen['turnosCreados'] === count($laborales)*18, 'Mes completo con 18 turnos por dia laboral.');
    comprobarPeriodo(array_column($resumen['detalle'], 'fecha') === $laborales, 'Solo los dias seleccionados.');
    $repetido = $horarios->programarHorariosPorPeriodo($administrador, $periodo);
    comprobarPeriodo($repetido['turnosCreados'] === 0 && $repetido['fechasExistentes'] === count($laborales), 'Repetir no duplica registros.');
    $cruces = $horarios->programarHorariosPorPeriodo($administrador, array_replace($periodo, ['horaInicio' => '09:00']));
    comprobarPeriodo($cruces['turnosCreados'] === 0 && $cruces['conflictosDetectados'] === count($laborales), 'Los bloques superpuestos se conservan sin cambios.');
    $fecha = $laborales[0];
    $turnos = $horarios->listarTurnosDisponibles($paciente, ['idMedico' => $periodo['idMedico'], 'fecha' => $fecha]);
    comprobarPeriodo(count($turnos) === 18 && substr($turnos[0]['fechaHoraInicio'],11,5) === '08:00' && substr($turnos[17]['fechaHoraFin'],11,5) === '17:00', 'Jornada completa.');
    for ($i=0; $i<18; $i++) {
        comprobarPeriodo(strtotime($turnos[$i]['fechaHoraFin']) - strtotime($turnos[$i]['fechaHoraInicio']) === 1800, 'Duracion de cada turno.');
        if ($i) { comprobarPeriodo($turnos[$i-1]['fechaHoraFin'] === $turnos[$i]['fechaHoraInicio'], 'Sin huecos entre turnos.'); }
    }
    foreach ([['horaInicio'=>'07:30'], ['horaFin'=>'17:30'], ['horaInicio'=>'08:05'], ['horaFin'=>'16:15'], ['horaFin'=>'08:00'], ['diasSemana'=>[]], ['diasSemana'=>[0]], ['diasSemana'=>['1']], ['fechaFinal'=>'2026-02-30'], ['fechaFinal'=>$inicioMes->modify('-1 day')->format('Y-m-d')]] as $cambio) {
        rechazarPeriodo(fn() => $horarios->programarHorariosPorPeriodo($administrador, array_replace($periodo, $cambio)));
    }
    $pasado = $ahora->modify('-2 days')->format('Y-m-d');
    $omitido = $horarios->programarHorariosPorPeriodo($administrador, array_replace($periodo, ['fechaInicial'=>$pasado,'fechaFinal'=>$pasado,'diasSemana'=>[1,2,3,4,5,6,7]]));
    comprobarPeriodo($omitido['fechasPasadas'] === 1 && $omitido['turnosCreados'] === 0, 'No genera fechas pasadas.');
    $enCurso = (new HorarioMedicoService($conexion, new DateTimeImmutable($fecha.' 08:00')))->programarHorariosPorPeriodo($administrador, array_replace($periodo,['fechaInicial'=>$fecha,'fechaFinal'=>$fecha,'diasSemana'=>[1,2,3,4,5,6,7]]));
    comprobarPeriodo($enCurso['fechasPasadas'] === 1, 'No genera el bloque si su inicio ya llego.');
    $segundo = array_replace($periodo, ['idMedico'=>$datosPrueba->medicos[1]->idMedico,'fechaInicial'=>$fecha,'fechaFinal'=>$fecha,'horaFin'=>'12:00']);
    comprobarPeriodo($horarios->programarHorariosPorPeriodo($administrador,$segundo)['turnosCreados'] === 8, 'Media jornada con ocho turnos.');
    $mixto=$horarios->programarHorariosPorPeriodo($administrador,array_replace($segundo,
        ['fechaFinal'=>$laborales[2],'horaInicio'=>'09:00','horaFin'=>'13:00']));
    comprobarPeriodo($mixto['conflictosDetectados']===1 && $mixto['fechasCreadas']===2 && $mixto['turnosCreados']===16,'Un periodo parcial conserva la fecha en conflicto y crea las otras dos.');
    $turnosOtro = $horarios->listarTurnosDisponibles($paciente, ['idMedico'=>$segundo['idMedico'],'fecha'=>$fecha]);
    $citas = new CitaService($conexion, $ahora);
    $cita = $citas->reservarCita($paciente, ['idTurno'=>$turnos[0]['idTurno']]);
    $idCita = $cita['idCita'];
    comprobarPeriodo(count($cita['historialCambios']) === 1 && $cita['historialCambios'][0]['idUsuario'] === $paciente->idUsuario, 'Auditoria de reserva con actor.');
    $horarios->programarHorariosPorPeriodo($administrador, $periodo);
    comprobarPeriodo($citas->obtenerCita($paciente,$idCita)['idTurno'] === $turnos[0]['idTurno'], 'Repetir la programacion conserva las citas.');
    rechazarPeriodo(fn()=>$citas->reservarCita($paciente,['idTurno'=>$turnosOtro[6]['idTurno']]),409);
    rechazarPeriodo(fn()=>$citas->reservarCita($recepcionista,['idPaciente'=>$idPaciente,'idTurno'=>$turnos[4]['idTurno']]),409);
    // El SQL debe impedir una segunda cita activa incluso evitando CitaService.
    try {
        $consulta=$conexion->prepare('INSERT INTO citas(id_paciente,id_turno) VALUES(:paciente,:turno)');
        $consulta->execute(['paciente'=>$idPaciente,'turno'=>$turnosOtro[6]['idTurno']]);
        throw new RuntimeException('El SQL admitio una segunda cita activa.');
    } catch (PDOException $error) { comprobarPeriodo(in_array((string)$error->getCode(),['P2001','23505'],true),'Defensa PostgreSQL.'); }
    rechazarPeriodo(fn()=>$citas->listarCitasActivasPaciente($paciente,['idPaciente'=>$datosPrueba->pacientes[1]['idPaciente']]),403);
    comprobarPeriodo(count($citas->listarCitasActivasPaciente($paciente,[])) === 1,'Consulta de la cita activa propia.');
    rechazarPeriodo(fn()=>$citas->cancelarCita($medico,$idCita,[]));
    rechazarPeriodo(fn()=>$citas->cancelarCita($otroMedico,$idCita,['motivoCancelacion'=>'Emergencia']),404);
    rechazarPeriodo(fn()=>$citas->marcarCitaAtendida($medico,$idCita),409);
    rechazarPeriodo(fn()=>$citas->marcarCitaInasistencia($medico,$idCita),409);
    $cancelada=$citas->cancelarCita($medico,$idCita,['motivoCancelacion'=>'Emergencia del medico']);
    comprobarPeriodo($cancelada['estadoCita']==='cancelada' && $cancelada['motivoCancelacion']==='Emergencia del medico','Medico cancela con motivo.');
    comprobarPeriodo(count($cancelada['historialCambios'])===2 && $cancelada['historialCambios'][1]['idUsuario']===$medico->idUsuario && $cancelada['historialCambios'][1]['fechaHora']!=='','Actor y fecha de cancelacion.');
    $cita=$citas->reservarCita($paciente,['idTurno'=>$turnos[0]['idTurno']]);
    $alInicio=new CitaService($conexion,new DateTimeImmutable($fecha.' 08:00'));
    rechazarPeriodo(fn()=>$alInicio->cancelarCita($medico,$cita['idCita'],['motivoCancelacion'=>'Tarde']),409);
    rechazarPeriodo(fn()=>$alInicio->marcarCitaInasistencia($medico,$cita['idCita']),409);
    rechazarPeriodo(fn()=>$alInicio->marcarCitaAtendida($otroMedico,$cita['idCita']),404);
    comprobarPeriodo($alInicio->marcarCitaAtendida($medico,$cita['idCita'])['estadoCita']==='atendida','El medico atiende su cita desde el inicio.');
    rechazarPeriodo(fn()=>$alInicio->marcarCitaAtendida($medico,$idCita),409);
    rechazarPeriodo(fn()=>$alInicio->marcarCitaInasistencia($medico,$cita['idCita']),409);
    $siguiente=$alInicio->reservarCita($paciente,['idTurno'=>$turnos[1]['idTurno']]);
    comprobarPeriodo($siguiente['estadoCita']==='confirmada','Atendida libera la regla de cita activa.');
    $alFin=new CitaService($conexion,new DateTimeImmutable($fecha.' 09:00'));
    rechazarPeriodo(fn()=>$alFin->marcarCitaInasistencia($otroMedico,$siguiente['idCita']),404);
    comprobarPeriodo($alFin->marcarCitaInasistencia($medico,$siguiente['idCita'])['estadoCita']==='no_asistio','Inasistencia al final del turno.');
    $administrativa=$alFin->reservarCita($recepcionista,['idPaciente'=>$idPaciente,'idTurno'=>$turnos[4]['idTurno']]);
    comprobarPeriodo($administrativa['estadoCita']==='confirmada','Inasistencia libera la regla de cita activa.');
    $aLasDiez=new CitaService($conexion,new DateTimeImmutable($fecha.' 10:00'));
    comprobarPeriodo($aLasDiez->registrarAsistencia($recepcionista,$administrativa['idCita'],['asistenciaRegistrada'=>'asistio'])['estadoCita']==='atendida','Recepcion conserva asistencia.');
    $administrativa=$aLasDiez->reservarCita($recepcionista,['idPaciente'=>$idPaciente,'idTurno'=>$turnos[6]['idTurno']]);
    $aLasDoce=new CitaService($conexion,new DateTimeImmutable($fecha.' 12:00'));
    comprobarPeriodo($aLasDoce->obtenerCita($paciente,$administrativa['idCita'])['estadoCita']==='confirmada','No se atiende automaticamente al pasar la hora.');
    comprobarPeriodo($aLasDoce->registrarAsistencia($recepcionista,$administrativa['idCita'],['asistenciaRegistrada'=>'no_asistio'])['estadoCita']==='no_asistio','Recepcion conserva inasistencia.');
    // Una cita programada preexistente se puede confirmar sin una nueva reserva.
    $consulta=$conexion->prepare('INSERT INTO citas(id_paciente,id_turno,id_usuario_registro) VALUES(:paciente,:turno,:usuario) RETURNING id_cita');
    $consulta->execute(['paciente'=>$idPaciente,'turno'=>$turnos[12]['idTurno'],'usuario'=>$recepcionista->idUsuario]);
    $programada=(int)$consulta->fetchColumn();
    rechazarPeriodo(fn()=>$aLasDoce->confirmarCita($medico,$programada),403);
    comprobarPeriodo($aLasDoce->confirmarCita($recepcionista,$programada)['estadoCita']==='confirmada','Programada pasa a confirmada.');
    rechazarPeriodo(fn()=>$aLasDoce->confirmarCita($recepcionista,$programada),409);
    $aLasDoce->cancelarCita($paciente,$programada,[]);
    $historial=$citas->listarCitas($paciente,[])['elementos'];
    comprobarPeriodo(count($historial)===6,'Se conservan los seis registros del paciente.');
    foreach ($historial as $registro) { comprobarPeriodo(!$registro['puedeCancelar'] && !$registro['puedeMarcarAtendida'],'Estados finales sin acciones.'); }
    try { $conexion->exec("UPDATE citas SET estado_cita='confirmada' WHERE id_cita=".$idCita); throw new RuntimeException('Reactivacion SQL permitida.'); }
    catch (PDOException $error) { comprobarPeriodo((string)$error->getCode()==='P2002','SQL impide reactivar una cita final.'); }
    // Una atencion terminada antes del fin nominal no bloquea otra especialidad.
    $fechaLarga=$inicioMes->modify('+2 months')->format('Y-m-d');
    $datosLargos=['idMedico'=>$periodo['idMedico'],'fechaAtencion'=>$fechaLarga,'horaInicio'=>'08:00','horaFin'=>'10:00'];
    $duracionAnterior=getenv('CITA_DURACION_MINUTOS');
    try {
        putenv('CITA_DURACION_MINUTOS=60'); $horarios->registrarHorario($administrador,$datosLargos);
        putenv('CITA_DURACION_MINUTOS=30'); $horarios->registrarHorario($administrador,array_replace($datosLargos,['idMedico'=>$segundo['idMedico']]));
    } finally { putenv($duracionAnterior===false?'CITA_DURACION_MINUTOS':'CITA_DURACION_MINUTOS='.$duracionAnterior); }
    $turnosLargos=$horarios->listarTurnosDisponibles($paciente,['idMedico'=>$periodo['idMedico'],'fecha'=>$fechaLarga]);
    $turnosCortos=$horarios->listarTurnosDisponibles($paciente,['idMedico'=>$segundo['idMedico'],'fecha'=>$fechaLarga]);
    $larga=$citas->reservarCita($paciente,['idTurno'=>$turnosLargos[0]['idTurno']]);
    $temprano=new CitaService($conexion,new DateTimeImmutable($fechaLarga.' 08:01'));
    $temprano->marcarCitaAtendida($medico,$larga['idCita']);
    comprobarPeriodo($temprano->reservarCita($paciente,['idTurno'=>$turnosCortos[1]['idTurno']])['estadoCita']==='confirmada','Atendida no bloquea al paciente aunque quede tiempo nominal.');
    $otraActiva=$citas->listarCitasActivasPaciente($paciente,[])[0];
    $temprano->cancelarCita($paciente,$otraActiva['idCita'],[]);
    (new MedicoService($conexion))->cambiarEstadoMedico($segundo['idMedico'],['medicoActivo'=>false]);
    rechazarPeriodo(fn()=>$horarios->programarHorariosPorPeriodo($administrador,$segundo),409);
} finally { $datosPrueba->eliminarDatos(); }
echo "OK: $comprobaciones comprobaciones de periodos, reservas, estados, permisos y trazabilidad.\n";
