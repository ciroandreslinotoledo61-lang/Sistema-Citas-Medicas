<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';require __DIR__.'/SoportePruebasAgenda.php';
use CitasMedicas\Business\Services\{CitaService,ConsultaService,HistoriaClinicaService,HorarioMedicoService,BitacoraService,AutenticacionService};
use CitasMedicas\Business\Validators\{ConsultaValidator,BitacoraValidator,CitaValidator};
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SesionUsuario;
$c=ConexionPostgresql::obtenerConexionPostgresql();$soporte=new SoportePruebasAgenda($c);$total=0;
function verificarClinica(bool $ok,string $mensaje):void{global $total;if(!$ok)throw new RuntimeException($mensaje);$total++;}
function rechazarClinica(callable $op,int $codigo):void{try{$op();}catch(ErrorAplicacion $e){verificarClinica($e->codigoHttp===$codigo,$e->getMessage());return;}throw new RuntimeException('Falto rechazo '.$codigo);}
try{
 $soporte->crearDatos();[$medico,$otroMedico]=$soporte->usuariosMedicos;[$paciente,$otroPaciente]=$soporte->usuariosPacientes;
 $usuarios=new UsuarioRepository($c);$admin=$usuarios->obtenerUsuarioPorCorreo('administrador@vidasalud.test');$recepcion=$usuarios->obtenerUsuarioPorCorreo('recepcionista@vidasalud.test');$bitacora=new BitacoraService($c);$bitacora->establecerUsuarioResponsable($admin);
 $fecha=(new DateTimeImmutable('+10 days'))->format('Y-m-d');$antes=new DateTimeImmutable($fecha.' 08:00');$durante=new DateTimeImmutable($fecha.' 09:00');
 $horarios=new HorarioMedicoService($c,$antes);$horarios->registrarHorario($admin,['idMedico'=>$soporte->medicos[0]->idMedico,'fechaAtencion'=>$fecha,'horaInicio'=>'09:00','horaFin'=>'11:00']);
 $turnos=$horarios->listarTurnosDisponibles($paciente,['idMedico'=>$soporte->medicos[0]->idMedico,'fecha'=>$fecha]);$bitacora->establecerUsuarioResponsable($paciente);
 $citas=new CitaService($c,$antes);$cita=$citas->reservarCita($paciente,['idTurno'=>$turnos[0]['idTurno']]);verificarClinica($cita['estadoCita']==='programada','Estado real.');
 $datos=['idCita'=>$cita['idCita'],'motivoConsulta'=>"Motivo\nSegunda linea",'diagnostico'=>'DIAGNOSTICO_PRIVADO','observaciones'=>'OBSERVACION_PRIVADA','tratamientos'=>[['medicamento'=>'TRATAMIENTO_PRIVADO','dosis'=>'Ejemplo','frecuencia'=>'Ejemplo','duracion'=>'Ejemplo','indicaciones'=>'Ejemplo ficticio']]];
 $consultas=new ConsultaService($c,$durante);$historias=new HistoriaClinicaService($c);
 foreach([$admin,$recepcion,$paciente] as $actor)rechazarClinica(fn()=>$consultas->registrarConsulta($actor,$datos),403);
 rechazarClinica(fn()=>(new ConsultaService($c,$antes))->registrarConsulta($medico,$datos),409);rechazarClinica(fn()=>$consultas->registrarConsulta($otroMedico,$datos),404);
 rechazarClinica(fn()=>$consultas->registrarConsulta($medico,$datos+['idPaciente'=>$soporte->pacientes[1]['idPaciente']]),422);
 foreach(['confirmada','pendiente'] as $estado)rechazarClinica(fn()=>CitaValidator::validarFiltrosCitas(['estado'=>$estado]),422);
 rechazarClinica(fn()=>ConsultaValidator::validarConsulta(array_replace($datos,['diagnostico'=>str_repeat('x',5001)])),422);
 rechazarClinica(fn()=>ConsultaValidator::validarConsulta(array_replace($datos,['diagnostico'=>"mal\0dato"])),422);
 rechazarClinica(fn()=>ConsultaValidator::validarConsulta(array_replace($datos,['tratamientos'=>array_fill(0,21,$datos['tratamientos'][0])])),422);
 rechazarClinica(fn()=>BitacoraValidator::validarFiltrosBitacora(['fechaDesde'=>'2030-01-02','fechaHasta'=>'2030-01-01']),422);
 $consulta=$consultas->registrarConsulta($medico,$datos);$id=$consulta['idConsulta'];
 verificarClinica($consulta['idPaciente']===$soporte->pacientes[0]['idPaciente'],'Paciente de la cita.');verificarClinica(count($consulta['tratamientos'])===1,'Tratamientos guardados.');
 verificarClinica($citas->obtenerCita($paciente,$cita['idCita'])['estadoCita']==='atendida','Atencion atomica.');rechazarClinica(fn()=>$consultas->registrarConsulta($medico,$datos),409);
 foreach([$admin,$recepcion] as $actor)rechazarClinica(fn()=>$consultas->obtenerConsulta($actor,$id),403);
 foreach([$otroMedico,$otroPaciente] as $actor)rechazarClinica(fn()=>$consultas->obtenerConsulta($actor,$id),404);
 verificarClinica(!$consultas->obtenerConsulta($paciente,$id)['puedeEditar'],'Paciente solo lectura.');
 verificarClinica($consultas->obtenerHistorial($paciente,null,[])['consultas']['elementos'][0]['idConsulta']===$id,'Historial propio.');rechazarClinica(fn()=>$consultas->obtenerHistorial($otroPaciente,$consulta['idPaciente'],[]),404);
 $edicion=$datos;unset($edicion['idCita']);$edicion['versionConsulta']=1;$edicion['diagnostico']='CORRECCION_PRIVADA';$edicion['tratamientos']=[];
 rechazarClinica(fn()=>$consultas->actualizarConsulta($otroMedico,$id,$edicion),404);rechazarClinica(fn()=>$consultas->actualizarConsulta($paciente,$id,$edicion),403);
 $nueva=$consultas->actualizarConsulta($medico,$id,$edicion);verificarClinica($nueva['versionConsulta']===2,'Version actual.');
 verificarClinica($nueva['versionesAnteriores'][0]['diagnostico_consulta']===$datos['diagnostico']&&count($nueva['versionesAnteriores'][0]['tratamientos'])===1,'Diagnostico y tratamientos previos conservados.');rechazarClinica(fn()=>$consultas->actualizarConsulta($medico,$id,$edicion),409);
 $h=$historias->actualizarHistoria($medico,$consulta['idPaciente'],['tipoSangre'=>'O+','alergias'=>'ALERGIA_PRIVADA','antecedentes'=>'ANTECEDENTE_PRIVADO','versionHistoria'=>1]);verificarClinica($h['versionHistoria']===2,'Version de historia.');
 rechazarClinica(fn()=>$historias->actualizarHistoria($paciente,$consulta['idPaciente'],['versionHistoria'=>2]),403);rechazarClinica(fn()=>$historias->actualizarHistoria($medico,$consulta['idPaciente'],['versionHistoria'=>1]),409);
 // Fallo inducido despues de crear la consulta: estado, tratamientos y auditoria deben revertirse.
 $cita2=$citas->reservarCita($paciente,['idTurno'=>$turnos[2]['idTurno']]);$eventos=(int)$c->query('SELECT count(*) FROM bitacora_sistema')->fetchColumn();
 $c->exec("CREATE FUNCTION fallo_clinico_prueba() RETURNS trigger LANGUAGE plpgsql AS \$\$ BEGIN RAISE EXCEPTION 'fallo'; END \$\$; CREATE TRIGGER tr_fallo_clinico_prueba BEFORE INSERT ON tratamientos FOR EACH ROW EXECUTE FUNCTION fallo_clinico_prueba()");
 try{(new ConsultaService($c,new DateTimeImmutable($fecha.' 10:00')))->registrarConsulta($medico,array_replace($datos,['idCita'=>$cita2['idCita']]));throw new RuntimeException('Falto fallo');}catch(PDOException $e){verificarClinica((string)$e->getCode()==='P0001','Fallo inducido.');}finally{$c->exec('DROP TRIGGER tr_fallo_clinico_prueba ON tratamientos; DROP FUNCTION fallo_clinico_prueba()');}
 verificarClinica((int)$c->query('SELECT count(*) FROM bitacora_sistema')->fetchColumn()===$eventos,'Rollback auditoria.');verificarClinica((int)$c->query('SELECT count(*) FROM consultas WHERE id_cita='.$cita2['idCita'])->fetchColumn()===0,'Rollback consulta.');verificarClinica($citas->obtenerCita($paciente,$cita2['idCita'])['estadoCita']==='programada','Rollback estado.');
 try{$c->exec('UPDATE consultas SET id_historia_clinica='.$soporte->pacientes[1]['idHistoriaClinica'].' WHERE id_consulta='.$id);throw new RuntimeException('Paciente alterado');}catch(PDOException $e){verificarClinica((string)$e->getCode()==='P2003','Integridad en SQL.');}
 foreach([$paciente,$recepcion,$medico] as $actor)rechazarClinica(fn()=>$bitacora->listarBitacora($actor,[]),403);
 verificarClinica(count($bitacora->listarBitacora($admin,['idUsuario'=>$medico->idUsuario,'modulo'=>'consultas','accion'=>'editar'])['elementos'])===1,'Filtros de bitacora.');
 $texto=json_encode($c->query('SELECT * FROM bitacora_sistema')->fetchAll());foreach(['DIAGNOSTICO_PRIVADO','CORRECCION_PRIVADA','TRATAMIENTO_PRIVADO','OBSERVACION_PRIVADA','ALERGIA_PRIVADA','ANTECEDENTE_PRIVADO','ClaveDePruebaAgenda123'] as $secreto)verificarClinica(!str_contains($texto,$secreto),'Auditoria sin '.$secreto);
 foreach(['UPDATE bitacora_sistema SET descripcion=descripcion','DELETE FROM bitacora_sistema','TRUNCATE bitacora_sistema'] as $sql){try{$c->exec($sql);throw new RuntimeException('Bitacora modificable');}catch(PDOException $e){verificarClinica((string)$e->getCode()==='42501','Bitacora inmutable.');}}
 $pagina=$bitacora->listarBitacora($admin,[]);verificarClinica(count($pagina['elementos'])===20&&$pagina['hayPaginaSiguiente'],'Paginacion.');
 SesionUsuario::iniciarSesionSegura();$auth=new AutenticacionService($usuarios,$bitacora);rechazarClinica(fn()=>$auth->iniciarSesionUsuario(['correoUsuario'=>$medico->correoUsuario,'contrasenaUsuario'=>'ClaveErronea123']),401);$auth->iniciarSesionUsuario(['correoUsuario'=>$medico->correoUsuario,'contrasenaUsuario'=>'ClaveDePruebaAgenda123']);$auth->cerrarSesionUsuario();
 verificarClinica(count($bitacora->listarBitacora($admin,['idUsuario'=>$medico->idUsuario,'modulo'=>'autenticacion'])['elementos'])===2,'Login y logout auditados.');
 echo "OK: $total comprobaciones clinicas y de auditoria.\n";
}finally{$soporte->eliminarDatos();}
