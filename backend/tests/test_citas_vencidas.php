<?php
declare(strict_types=1);
if(getenv('VIDASALUD_TEST_LOCAL')!=='1'||getenv('DB_HOST')!=='127.0.0.1'||!str_ends_with(getenv('DB_NAME')?:'','_pruebas'))throw new RuntimeException('Solo se ejecuta en el cluster local aislado.');
require dirname(__DIR__).'/src/bootstrap.php';
require __DIR__.'/SoportePruebasAgenda.php';
require_once __DIR__.'/SoporteProcesosPrueba.php';
use CitasMedicas\Business\Services\{CitaService,HorarioMedicoService,ConsultaService,SalaEsperaService,CitasVencidasService};
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Data\Seeders\DatosInicialesSeeder;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

if(($argv[1]??'')==='--trabajador'){
    $d=json_decode($argv[2],true,512,JSON_THROW_ON_ERROR);
    $db=\CitasMedicas\Data\Config\ConexionPostgresql::obtenerConexionPostgresql();
    file_put_contents($d['barrera'].'.'.$d['numero'].'.listo','1');$limite=microtime(true)+20;
    while(!is_file($d['barrera'])){if(microtime(true)>$limite)throw new RuntimeException('Barrera agotada.');usleep(10000);}
    echo json_encode((new CitasVencidasService($db,new DateTimeImmutable($d['ahora'])))->revisar(),JSON_THROW_ON_ERROR);exit;
}
$port=getenv('DB_PORT');if(!ctype_digit($port?:''))throw new RuntimeException('Falta puerto local explícito.');
$base='cierre_'.bin2hex(random_bytes(6)).'_pruebas';$opciones=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
$servidor=new PDO("pgsql:host=127.0.0.1;port=$port;dbname=postgres;sslmode=disable",'postgres','',$opciones);$servidor->exec('CREATE DATABASE '.$base);
$db=new PDO("pgsql:host=127.0.0.1;port=$port;dbname=$base;sslmode=disable",'postgres','',$opciones);$db->exec("SET TIME ZONE 'America/La_Paz'");
foreach(glob(dirname(__DIR__).'/src/data/migrations/*.sql') as $archivo){$db->beginTransaction();$db->exec(file_get_contents($archivo));$db->commit();}
DatosInicialesSeeder::cargarDatosIniciales($db,'SoloPruebas'.bin2hex(random_bytes(12)));
$anterior=getenv('DB_NAME');$tolerancia=getenv('CITAS_TOLERANCIA_MINUTOS');putenv('DB_NAME='.$base);
$n=0;
function verificarCierre(bool $v,string $mensaje):void{global $n;if(!$v)throw new RuntimeException($mensaje);$n++;}
function rechazarCierre(callable $f,int $codigo):void{try{$f();}catch(ErrorAplicacion $e){verificarCierre($e->codigoHttp===$codigo,$e->getMessage());return;}throw new RuntimeException('Operación no rechazada.');}
function carreraCierre(string $ahora):array{
    $dir=dirname(__DIR__,2).'/.tmp-auth/carrera-cierre-'.bin2hex(random_bytes(5));mkdir($dir,0700,true);$barrera=$dir.'/inicio';$procesos=[];$salidas=[];
    try{
        for($i=0;$i<2;$i++)$procesos[]=SoporteProcesosPrueba::iniciar([PHP_BINARY,__FILE__,'--trabajador',json_encode(['barrera'=>$barrera,'numero'=>$i,'ahora'=>$ahora],JSON_THROW_ON_ERROR)],dirname(__DIR__,2),getenv(),$dir.'/'.$i.'.log');
        $limite=microtime(true)+30;
        while(!is_file($barrera.'.0.listo')||!is_file($barrera.'.1.listo')){if(microtime(true)>$limite)throw new RuntimeException('Barrera incompleta.');usleep(10000);}
        file_put_contents($barrera,'1');
        foreach($procesos as $i=>$p){do{$estado=proc_get_status($p);if(!$estado['running'])break;if(microtime(true)>$limite)throw new RuntimeException('Trabajador agotado.');usleep(10000);}while(true);
            if($estado['exitcode']!==0)throw new RuntimeException('Trabajador de cierre falló: '.file_get_contents($dir.'/'.$i.'.log'));
            $salidas[]=json_decode(file_get_contents($dir.'/'.$i.'.log'),true,512,JSON_THROW_ON_ERROR);
        }
        return $salidas;
    }finally{foreach($procesos as $p)SoporteProcesosPrueba::detener($p);}
}
try{
    putenv('CITAS_TOLERANCIA_MINUTOS=30');$pacientes=$medicos=$perfiles=[];
    for($i=0;$i<4;$i++){$soporte=new SoportePruebasAgenda($db);$soporte->crearDatos();array_push($pacientes,...$soporte->usuariosPacientes);array_push($medicos,...$soporte->usuariosMedicos);array_push($perfiles,...$soporte->medicos);}
    $usuarios=new UsuarioRepository($db);$admin=$usuarios->obtenerUsuarioPorCorreo('administrador@vidasalud.test');$recepcion=$usuarios->obtenerUsuarioPorCorreo('recepcionista@vidasalud.test');
    $fecha=(new DateTimeImmutable('+7 days'))->format('Y-m-d');$antes=new DateTimeImmutable($fecha.' 07:00');$inicio=new DateTimeImmutable($fecha.' 08:00');$cierre=new DateTimeImmutable($fecha.' 09:00');
    $h=new HorarioMedicoService($db,$antes);$reservas=new CitaService($db,$antes);$sala=new SalaEsperaService($db,$inicio);$ids=[];
    foreach($perfiles as $i=>$perfil){$tarde=$i===7;$h->registrarHorario($admin,['idMedico'=>$perfil->idMedico,'fechaAtencion'=>$fecha,'horaInicio'=>$tarde?'09:00':'08:00','horaFin'=>$tarde?'09:30':'08:30']);
        $turnos=$h->listarTurnosDisponibles($pacientes[$i],['idMedico'=>$perfil->idMedico,'fecha'=>$fecha]);$ids[]=$reservas->reservarCita($pacientes[$i],['idTurno'=>$turnos[0]['idTurno']])['idCita'];}
    // 0 ausente; 1 presente; 2 médico ausente; 3 atendida; 4 cancelada;
    // 5 en consulta; 6 consulta registrada; 7 aún dentro del margen/horario.
    foreach([1,2,5] as $i)$sala->cambiar($recepcion,$ids[$i],['accion'=>'llegada','version'=>0]);
    $sala->cambiar($medicos[5],$ids[5],['accion'=>'iniciar','version'=>1]);
    (new CitaService($db,$inicio))->marcarCitaAtendida($medicos[3],$ids[3]);
    $reservas->cancelarCita($pacientes[4],$ids[4],['motivoCancelacion'=>'Cambio ficticio']);
    (new ConsultaService($db,$inicio))->registrarConsulta($medicos[6],['idCita'=>$ids[6],'motivoConsulta'=>'Ficticio','diagnostico'=>'Contenido sintético','tratamientos'=>[]]);
    $citas=new CitaService($db,$cierre);
    rechazarCierre(fn()=>$citas->marcarCitaInasistencia($recepcion,$ids[1]),409);
    verificarCierre(!$citas->obtenerCita($recepcion,$ids[1])['puedeRegistrarInasistencia'],'No ofrecer ausencia para paciente presente.');
    rechazarCierre(fn()=>$citas->cancelarCita($pacientes[2],$ids[2],['tipoCancelacion'=>'medico_ausente','motivoCancelacion'=>'Prueba']),403);
    rechazarCierre(fn()=>$citas->cancelarCita($medicos[2],$ids[2],['tipoCancelacion'=>'medico_ausente','motivoCancelacion'=>'Prueba']),403);
    rechazarCierre(fn()=>$citas->cancelarCita($recepcion,$ids[2],['tipoCancelacion'=>'medico_ausente','motivoCancelacion'=>'']),422);
    $incidente=$citas->cancelarCita($recepcion,$ids[2],['tipoCancelacion'=>'medico_ausente','motivoCancelacion'=>'El profesional no se presentó']);
    verificarCierre($incidente['estadoCita']==='cancelada'&&$incidente['asistenciaRegistrada']==='pendiente'&&str_starts_with($incidente['motivoCancelacion'],'Ausencia del médico:'),'Incidencia sin penalizar paciente.');
    rechazarCierre(fn()=>$citas->cancelarCita($admin,$ids[5],['tipoCancelacion'=>'medico_ausente','motivoCancelacion'=>'Prueba']),409);
    verificarCierre($reservas->obtenerCita($pacientes[0],$ids[0])['puedeCancelar'],'Cancelación ordinaria habilitada antes del inicio.');
    $pasada=$citas->obtenerCita($pacientes[0],$ids[0]);verificarCierre(!$pasada['puedeCancelar']&&$pasada['motivoNoCancelacion']!==''&&$pasada['explicacionAcciones']!=='','El turno pasado explica bloqueo y resolución.');
    verificarCierre($citas->obtenerCita($recepcion,$ids[1])['puedeCancelarPorAusenciaMedico'],'Administración puede resolver paciente presente sin atención.');
    putenv('CITAS_TOLERANCIA_MINUTOS=60');verificarCierre((new CitasVencidasService($db,$cierre))->revisar()['noAsistieron']===0,'Tolerancia configurable.');putenv('CITAS_TOLERANCIA_MINUTOS=30');
    verificarCierre((new CitasVencidasService($db,$cierre->modify('-1 second')))->revisar()['noAsistieron']===0,'No cerrar antes del margen.');
    $r=(new CitasVencidasService($db,$cierre))->revisar();verificarCierre($r['noAsistieron']===1&&$r['requierenRevision']===1,'Solo ausente se cierra; presente se deriva a revisión.');
    foreach(['no_asistio','programada','cancelada','atendida','cancelada','programada','atendida','programada'] as $i=>$estado)verificarCierre($citas->obtenerCita($admin,$ids[$i])['estadoCita']===$estado,'Estado protegido: '.$i);
    $cerrada=$citas->obtenerCita($pacientes[0],$ids[0]);$evento=end($cerrada['historialCambios']);
    verificarCierre($evento['idUsuario']===null&&$evento['nombreUsuario']==='Sistema'&&$evento['estadoNuevo']==='no_asistio','Historial atribuido al sistema.');
    verificarCierre(count($cerrada['historialCambios'])===2,'Un solo cambio automático.');
    verificarCierre($citas->listarCitas($pacientes[0],['vista'=>'proximas'])['elementos']===[]&&$citas->listarCitasActivasPaciente($pacientes[0],[])===[],'No sigue como próxima o activa.');
    rechazarCierre(fn()=>$citas->cancelarCita($admin,$ids[0],['tipoCancelacion'=>'medico_ausente','motivoCancelacion'=>'No sobrescribir finales']),409);
    $r=(new CitasVencidasService($db,$cierre))->revisar();verificarCierre($r['noAsistieron']===0&&$r['requierenRevision']===0,'Ejecución idempotente.');
    $auditoria=$db->query("SELECT accion,codigo_rol,id_usuario FROM bitacora_sistema WHERE accion IN ('inasistencia_automatica','revision_paciente_presente','ausencia_medico') ORDER BY id_bitacora")->fetchAll();
    verificarCierre(count($auditoria)===3,'Una auditoría por resultado/incidencia.');
    foreach($auditoria as $a)if($a['accion']!=='ausencia_medico')verificarCierre($a['codigo_rol']==='sistema'&&$a['id_usuario']===null,'Bitácora de sistema sin cuenta inventada.');
    $futuro=$fecha.' 11:00';$db->beginTransaction();$db->query('SELECT id_cita FROM citas WHERE id_cita='.$ids[7].' FOR UPDATE');
    $r=carreraCierre($futuro);verificarCierre(array_sum(array_column($r,'noAsistieron'))===0,'No competir con una cita bloqueada por atención.');$db->rollBack();
    $r=carreraCierre($futuro);verificarCierre(array_sum(array_column($r,'noAsistieron'))===1,'Dos procesos no duplican el cierre.');
    verificarCierre((int)$db->query("SELECT count(*) FROM bitacora_sistema WHERE accion='inasistencia_automatica' AND id_registro=".$ids[7])->fetchColumn()===1,'Una auditoría concurrente.');
    $script=dirname(__DIR__).'/scripts/revisar-citas-vencidas.php';$dir=dirname(__DIR__,2).'/.tmp-auth/cli-cierre-'.bin2hex(random_bytes(5));mkdir($dir,0700,true);
    $salida=SoporteProcesosPrueba::ejecutar([PHP_BINARY,$script],dirname(__DIR__,2),array_replace(getenv(),['DB_HOST'=>'prohibido.example.test']),$dir.'/ayuda.log');verificarCierre(str_contains($salida,'sin conexión'),'CLI por defecto no conecta.');
    $salida=SoporteProcesosPrueba::ejecutar([PHP_BINARY,$script,'--aplicar'],dirname(__DIR__,2),array_replace(getenv(),['CITAS_AUTOMATICAS_HABILITADAS'=>'true']),$dir.'/aplicar.log');
    $r=json_decode($salida,true,512,JSON_THROW_ON_ERROR);verificarCierre(isset($r['noAsistieron'],$r['requierenRevision']),'CLI real verificada solo en BD aislada.');
}finally{if($db->inTransaction())$db->rollBack();putenv('DB_NAME='.$anterior);putenv($tolerancia===false?'CITAS_TOLERANCIA_MINUTOS':'CITAS_TOLERANCIA_MINUTOS='.$tolerancia);}
echo "OK: $n comprobaciones de ausente, presente, médico ausente, atendida, cancelada, consulta iniciada, tolerancia, CLI, historial, bitácora y dos procesos concurrentes.\n";
