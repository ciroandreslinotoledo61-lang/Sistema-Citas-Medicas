<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
require __DIR__ . '/SoportePruebasAgenda.php';
use CitasMedicas\Business\Services\CitaService;
use CitasMedicas\Business\Services\HorarioMedicoService;
use CitasMedicas\Business\Services\PacienteService;
use CitasMedicas\Business\Services\ReporteService;
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
$conexion=ConexionPostgresql::obtenerConexionPostgresql();
$datosPrueba=new SoportePruebasAgenda($conexion);
$cantidad=0;
function comprobarReporte(bool $resultado,string $mensaje): void { global $cantidad; if (!$resultado) { throw new RuntimeException($mensaje); } $cantidad++; }
function rechazarReporte(callable $operacion,int $codigo): void {
    try { $operacion(); } catch (ErrorAplicacion $error) { comprobarReporte($error->codigoHttp===$codigo,$error->getMessage()); return; }
    throw new RuntimeException('Falto rechazar la operacion.');
}
$duracionAnterior=getenv('CITA_DURACION_MINUTOS'); putenv('CITA_DURACION_MINUTOS=30');
try {
    $datosPrueba->crearDatos();
    $usuarios=new UsuarioRepository($conexion);
    $administrador=$usuarios->obtenerUsuarioPorCorreo('administrador@vidasalud.test');
    $recepcionista=$usuarios->obtenerUsuarioPorCorreo('recepcionista@vidasalud.test');
    if (!$administrador || !$recepcionista) { throw new RuntimeException('Carga primero los datos iniciales.'); }
    [$paciente,$otroPaciente]=$datosPrueba->usuariosPacientes; [$medico,$otroMedico]=$datosPrueba->usuariosMedicos;
    $idPaciente=$datosPrueba->pacientes[0]['idPaciente']; $idMedico=$datosPrueba->medicos[0]->idMedico;
    $fecha=(new DateTimeImmutable('+10 days'))->format('Y-m-d'); $ahora=new DateTimeImmutable($fecha.' 08:00');
    $horarios=new HorarioMedicoService($conexion,$ahora); $citas=new CitaService($conexion,$ahora);
    $pacientes=new PacienteService($conexion); $reportes=new ReporteService($conexion);
    $bloque=$horarios->registrarHorario($administrador,['idMedico'=>$idMedico,'fechaAtencion'=>$fecha,'horaInicio'=>'09:00','horaFin'=>'20:00']);
    comprobarReporte($bloque['cantidadTurnos']===22,'Once horas deben generar 22 turnos.');
    $turnos=$horarios->listarTurnosDisponibles($paciente,['idMedico'=>$idMedico,'fecha'=>$fecha]); $reservas=[];
    foreach ($turnos as $turno) { $reservas[]=$citas->reservarCita($paciente,['idTurno'=>$turno['idTurno']]); }
    rechazarReporte(fn()=>$citas->marcarCitaAtendida($medico,$reservas[0]['idCita']),409);
    $alInicio=new CitaService($conexion,new DateTimeImmutable($fecha.' 09:00'));
    rechazarReporte(fn()=>$alInicio->marcarCitaAtendida($otroMedico,$reservas[0]['idCita']),404);
    rechazarReporte(fn()=>$alInicio->marcarCitaAtendida($paciente,$reservas[0]['idCita']),403);
    comprobarReporte($alInicio->marcarCitaAtendida($medico,$reservas[0]['idCita'])['estadoCita']==='atendida','El medico atiende su propia cita.');
    rechazarReporte(fn()=>$alInicio->marcarCitaAtendida($medico,$reservas[0]['idCita']),409);
    $citas->cancelarCita($recepcionista,$reservas[1]['idCita'],[]);
    (new CitaService($conexion,new DateTimeImmutable($fecha.' 10:30')))->registrarAsistencia($recepcionista,$reservas[2]['idCita'],['asistenciaRegistrada'=>'no_asistio']);
    $filtros=['fechaDesde'=>$fecha,'fechaHasta'=>$fecha,'idMedico'=>$idMedico];
    foreach ([$recepcionista,$medico,$paciente] as $actor) { rechazarReporte(fn()=>$reportes->obtenerReporteAdministrativo($actor,$filtros),403); }
    $reporte=$reportes->obtenerReporteAdministrativo($administrador,$filtros);
    comprobarReporte($reporte['totales']['total']===22 && count($reporte['citas']['elementos'])===20 && $reporte['citas']['hayPaginaSiguiente'],'Totales independientes de la pagina.');
    comprobarReporte($reporte['totales']['atendidas']===1 && $reporte['totales']['canceladas']===1 && $reporte['totales']['inasistencias']===1,'Estados incluidos en los totales.');
    $segunda=$reportes->obtenerReporteAdministrativo($administrador,$filtros+['paginaCitas'=>2]);
    comprobarReporte(count($segunda['citas']['elementos'])===2 && !$segunda['citas']['hayPaginaSiguiente'] && $segunda['totales']['total']===22,'Segunda pagina.');
    comprobarReporte($reportes->obtenerReporteAdministrativo($administrador,$filtros+['estado'=>'atendida'])['totales']['total']===1,'Filtro de estado.');
    comprobarReporte($reportes->obtenerReporteAdministrativo($administrador,array_replace($filtros,['idMedico'=>$datosPrueba->medicos[1]->idMedico]))['totales']['total']===0,'Filtro de medico.');
    comprobarReporte($reportes->obtenerReporteAdministrativo($administrador,$filtros+['idEspecialidad'=>$datosPrueba->medicos[0]->idEspecialidad])['totales']['total']===22,'Filtro de especialidad.');
    comprobarReporte($citas->listarCitas($medico,['idMedico'=>$datosPrueba->medicos[1]->idMedico])['elementos']===[],'El filtro conserva el alcance propio.');
    $datos=$pacientes->obtenerPacientePorId($idPaciente)->obtenerDatosPublicosPaciente();
    $edicion=array_replace($datos,['nombresUsuario'=>'Nombre actualizado','sexoPaciente'=>'femenino','telefonoPaciente'=>'71111111']);
    rechazarReporte(fn()=>$pacientes->actualizarDatosPaciente($medico,$idPaciente,$edicion),403);
    $editado=$pacientes->actualizarDatosPaciente($recepcionista,$idPaciente,$edicion)->obtenerDatosPublicosPaciente();
    comprobarReporte($editado['nombresUsuario']==='Nombre actualizado' && $editado['sexoPaciente']==='femenino' && $editado['idHistoriaClinica']===$datos['idHistoriaClinica'],'Edicion completa conserva historia.');
    $cuenta=$usuarios->obtenerUsuarioPorId($paciente->idUsuario);
    comprobarReporte($cuenta->codigoRol==='paciente' && $cuenta->verificarContrasenaUsuario('ClaveDePruebaAgenda123'),'Clave y rol conservados.');
    rechazarReporte(fn()=>$pacientes->actualizarDatosPaciente($administrador,$idPaciente,array_replace($edicion,['nombresUsuario'=>'Cambio rechazado','cedulaIdentidad'=>$datosPrueba->pacientes[1]['cedulaIdentidad']])),409);
    comprobarReporte($pacientes->obtenerPacientePorId($idPaciente)->obtenerDatosPublicosPaciente()['nombresUsuario']==='Nombre actualizado','Rollback ante CI duplicada.');
    rechazarReporte(fn()=>$pacientes->cambiarEstadoPaciente($paciente,$idPaciente,['pacienteActivo'=>false]),403);
    $inactivo=$pacientes->cambiarEstadoPaciente($recepcionista,$idPaciente,['pacienteActivo'=>false])->obtenerDatosPublicosPaciente();
    comprobarReporte(!$inactivo['pacienteActivo'] && $inactivo['idHistoriaClinica']===$datos['idHistoriaClinica'],'Desactivar conserva perfil.');
    comprobarReporte($usuarios->obtenerUsuarioPorId($paciente->idUsuario)->versionCredenciales>$cuenta->versionCredenciales,'Se revocan las sesiones anteriores.');
    rechazarReporte(fn()=>$citas->reservarCita($recepcionista,['idPaciente'=>$idPaciente,'idTurno'=>$turnos[1]['idTurno']]),409);
    comprobarReporte($reportes->obtenerReporteAdministrativo($administrador,$filtros)['totales']['total']===22,'Desactivar conserva todas las citas.');
    $registro=$conexion->prepare('UPDATE usuarios SET fecha_registro_usuario=:fecha WHERE id_usuario=:idUsuario');
    $registro->execute(['fecha'=>$fecha.' 23:59:59-04','idUsuario'=>$paciente->idUsuario]);
    $registro->execute(['fecha'=>(new DateTimeImmutable($fecha))->modify('+1 day')->format('Y-m-d').' 00:00:00-04','idUsuario'=>$otroPaciente->idUsuario]);
    comprobarReporte($reportes->obtenerReporteAdministrativo($administrador,$filtros)['totales']['pacientesRegistrados']===1,'Limites inclusivos del periodo en Bolivia.');
    comprobarReporte($reportes->obtenerReporteAdministrativo($administrador,array_replace($filtros,['estado'=>'atendida']))['totales']['pacientesRegistrados']===1,'Registros de pacientes dependen solo de fechas.');
    $pacientes->cambiarEstadoPaciente($administrador,$idPaciente,['pacienteActivo'=>true]);
    putenv('CITA_DURACION_MINUTOS=45'); $otroDia=(new DateTimeImmutable($fecha))->modify('+1 day')->format('Y-m-d');
    comprobarReporte($horarios->registrarHorario($administrador,['idMedico'=>$idMedico,'fechaAtencion'=>$otroDia,'horaInicio'=>'09:00','horaFin'=>'10:30'])['cantidadTurnos']===2,'Duracion configurable para bloques nuevos.');
    $conservada=$citas->reservarCita($recepcionista,['idPaciente'=>$idPaciente,'idTurno'=>$turnos[1]['idTurno']]);
    comprobarReporte(strtotime($conservada['fechaHoraFin'])-strtotime($conservada['fechaHoraInicio'])===1800,'Configurar no altera bloques anteriores.');
} finally {
    putenv($duracionAnterior===false ? 'CITA_DURACION_MINUTOS' : 'CITA_DURACION_MINUTOS='.$duracionAnterior);
    $datosPrueba->eliminarDatos();
}
echo "OK: $cantidad comprobaciones de reportes, perfiles, atencion y duracion.\n";
