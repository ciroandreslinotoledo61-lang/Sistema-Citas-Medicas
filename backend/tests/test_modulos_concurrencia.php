<?php
declare(strict_types=1);
if(getenv('VIDASALUD_TEST_LOCAL')!=='1'||getenv('DB_HOST')!=='127.0.0.1'||!str_ends_with(getenv('DB_NAME')?:'','_pruebas'))throw new RuntimeException('Solo cluster aislado.');
require dirname(__DIR__).'/src/bootstrap.php';
require __DIR__.'/SoportePruebasAgenda.php';
use CitasMedicas\Business\Services\{CitaService,HorarioMedicoService,PagoDemostracionService,SalaEsperaService};
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
$db=ConexionPostgresql::obtenerConexionPostgresql();$usuarios=new UsuarioRepository($db);
if(($argv[1]??'')==='--trabajador'){
    $d=json_decode($argv[2],true,512,JSON_THROW_ON_ERROR);$u=$usuarios->obtenerUsuarioPorId($d['usuario']);
    file_put_contents($d['barrera'].'.'.$d['numero'].'.listo','1');$limite=microtime(true)+20;
    while(!is_file($d['barrera'])){if(microtime(true)>$limite)throw new RuntimeException('Tiempo agotado en barrera.');usleep(10000);}
    try{
        if($d['tipo']==='pago')(new PagoDemostracionService($db))->finalizar($u,$d['id'],['accion'=>'confirmar','confirmarSimulacion'=>true]);
        else (new SalaEsperaService($db))->cambiar($u,$d['id'],['accion'=>'llegada','version'=>0]);
        echo '200';
    }catch(ErrorAplicacion $e){echo $e->codigoHttp;}exit;
}
$datos=new SoportePruebasAgenda($db);$datos->crearDatos();$admin=$usuarios->obtenerUsuarioPorCorreo('administrador@vidasalud.test');$recepcion=$usuarios->obtenerUsuarioPorCorreo('recepcionista@vidasalud.test');
$fecha=(new DateTimeImmutable('+12 days'))->format('Y-m-d');$ahora=new DateTimeImmutable($fecha.' 07:00');
$h=new HorarioMedicoService($db,$ahora);$h->registrarHorario($admin,['idMedico'=>$datos->medicos[0]->idMedico,'fechaAtencion'=>$fecha,'horaInicio'=>'08:00','horaFin'=>'09:00']);
$turnos=$h->listarTurnosDisponibles($datos->usuariosPacientes[0],['idMedico'=>$datos->medicos[0]->idMedico,'fecha'=>$fecha]);
$cita=(new CitaService($db,$ahora))->reservarCita($datos->usuariosPacientes[0],['idTurno'=>$turnos[0]['idTurno']]);
$tarifa=getenv('DEMO_TARIFA_BOB');putenv('DEMO_TARIFA_BOB=1.23');
try{$pago=(new PagoDemostracionService($db))->crear($datos->usuariosPacientes[0],['idCita'=>$cita['idCita'],'metodo'=>'qr_demo']);}
finally{putenv($tarifa===false?'DEMO_TARIFA_BOB':'DEMO_TARIFA_BOB='.$tarifa);}
$directorio=dirname(__DIR__,2).'/.tmp-auth/concurrencia-modulos-'.bin2hex(random_bytes(6));mkdir($directorio,0700,true);
foreach([['tipo'=>'pago','id'=>$pago['idPago'],'usuario'=>$datos->usuariosPacientes[0]->idUsuario],['tipo'=>'sala','id'=>$cita['idCita'],'usuario'=>$recepcion->idUsuario]] as $caso){
    $barrera=$directorio.'/'.$caso['tipo'];$procesos=[];
    try{
        for($i=0;$i<2;$i++){
            $argumento=json_encode($caso+['barrera'=>$barrera,'numero'=>$i],JSON_THROW_ON_ERROR);
            $p=proc_open([PHP_BINARY,__FILE__,'--trabajador',$argumento],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));
            if(!is_resource($p))throw new RuntimeException('No se inició trabajador.');fclose($pipes[0]);$procesos[]=[$p,$pipes];
        }
        $limite=microtime(true)+25;
        while(!is_file($barrera.'.0.listo')||!is_file($barrera.'.1.listo')){if(microtime(true)>$limite)throw new RuntimeException('Barrera incompleta.');usleep(20000);}
        file_put_contents($barrera,'1');$codigos=[];
        foreach($procesos as [$p,$pipes]){
            do{$estado=proc_get_status($p);if(!$estado['running'])break;if(microtime(true)>$limite)throw new RuntimeException('Trabajador agotado.');usleep(20000);}while(true);
            $salida=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);
            if($estado['exitcode']!==0||$error!=='')throw new RuntimeException('Trabajador falló: '.$error.$salida);$codigos[]=(int)$salida;
        }
        sort($codigos);if($codigos!==[200,409])throw new RuntimeException('Concurrencia inválida: '.json_encode($codigos));
    }finally{foreach($procesos as [$p,$pipes]){if(proc_get_status($p)['running'])proc_terminate($p);fclose($pipes[1]);fclose($pipes[2]);proc_close($p);}}
}
echo "OK: dos conexiones reales confirman un pago y registran una llegada; una aceptación y un conflicto en cada carrera.\n";
