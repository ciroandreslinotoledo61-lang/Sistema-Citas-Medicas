<?php
declare(strict_types=1);
if(getenv('VIDASALUD_TEST_LOCAL')!=='1'||getenv('DB_HOST')!=='127.0.0.1'||!str_ends_with(getenv('DB_NAME')?:'','_pruebas'))throw new RuntimeException('Usa --completo con PostgreSQL aislado.');
require dirname(__DIR__).'/src/bootstrap.php';
require __DIR__.'/SoportePruebasAgenda.php';
use CitasMedicas\Business\Services\{CitaService,ConsultaService,HorarioMedicoService,SalaEsperaService,RecetaService,PagoDemostracionService};
use CitasMedicas\Data\Config\ConexionPostgresql;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
$db=ConexionPostgresql::obtenerConexionPostgresql();$datos=new SoportePruebasAgenda($db);$datos->crearDatos();
$usuarios=new UsuarioRepository($db);$admin=$usuarios->obtenerUsuarioPorCorreo('administrador@vidasalud.test');$recepcion=$usuarios->obtenerUsuarioPorCorreo('recepcionista@vidasalud.test');
[$p1,$p2]=$datos->usuariosPacientes;[$m1,$m2]=$datos->usuariosMedicos;
$n=0;$ok=static function(bool $v,string $mensaje='')use(&$n):void{if(!$v)throw new RuntimeException($mensaje?:'Comprobación fallida.');$n++;};
$rechazar=static function(callable $f,int $codigo)use($ok):void{try{$f();}catch(ErrorAplicacion $e){$ok($e->codigoHttp===$codigo,$e->getMessage());return;}throw new RuntimeException('Operación no rechazada.');};
$fecha=(new DateTimeImmutable('+5 days'))->format('Y-m-d');$antes=new DateTimeImmutable($fecha.' 07:00');$inicio=new DateTimeImmutable($fecha.' 08:00');
$horarios=new HorarioMedicoService($db,$antes);$citas=new CitaService($db,$antes);$sala=new SalaEsperaService($db,$inicio);$temprana=new SalaEsperaService($db,$antes);
$reservas=[];
foreach([0,1] as $i){$horarios->registrarHorario($admin,['idMedico'=>$datos->medicos[$i]->idMedico,'fechaAtencion'=>$fecha,'horaInicio'=>'08:00','horaFin'=>'10:00']);
    $t=$horarios->listarTurnosDisponibles($datos->usuariosPacientes[$i],['idMedico'=>$datos->medicos[$i]->idMedico,'fecha'=>$fecha]);$reservas[]=$citas->reservarCita($datos->usuariosPacientes[$i],['idTurno'=>$t[0]['idTurno']]);}
$id=$reservas[0]['idCita'];$otro=$reservas[1]['idCita'];
$ok($sala->listar($m1,['fecha'=>$fecha])['elementos'][0]['estado']==='pendiente_llegada');
$rechazar(fn()=>$sala->listar($p1,[]),403);$rechazar(fn()=>$sala->cambiar($m1,$id,['accion'=>'llegada','version'=>0]),403);
$rechazar(fn()=>$sala->cambiar($m1,$id,['accion'=>'finalizar','version'=>0]),409);
$sala->cambiar($recepcion,$id,['accion'=>'llegada','version'=>0]);
$rechazar(fn()=>$sala->cambiar($recepcion,$id,['accion'=>'llegada','version'=>0]),409);
$rechazar(fn()=>$sala->cambiar($m2,$id,['accion'=>'iniciar','version'=>1]),404);
$rechazar(fn()=>$temprana->cambiar($m1,$id,['accion'=>'iniciar','version'=>1]),409);
$sala->cambiar($m1,$id,['accion'=>'iniciar','version'=>1]);
$consulta=(new ConsultaService($db,$inicio))->registrarConsulta($m1,['idCita'=>$id,'motivoConsulta'=>'Ficticio','diagnostico'=>'Diagnóstico sintético privado','tratamientos'=>[]]);
$ok(count($sala->historial($recepcion,$id))===3,'La consulta sincroniza sala y registra tres eventos.');
$ok($sala->listar($m1,['fecha'=>$fecha])['elementos'][0]['estado']==='atendido');
$rechazar(fn()=>$sala->historial($m2,$id),404);
$sala->cambiar($recepcion,$otro,['accion'=>'llegada','version'=>0]);$sala->cambiar($m2,$otro,['accion'=>'iniciar','version'=>1]);$sala->cambiar($m2,$otro,['accion'=>'finalizar','version'=>2]);
$ok($citas->obtenerCita($p2,$otro)['estadoCita']==='atendida');
$rechazar(fn()=>$sala->cambiar($m2,$otro,['accion'=>'finalizar','version'=>3]),409);
$proyeccion=json_encode($sala->listar($recepcion,['fecha'=>$fecha]),JSON_THROW_ON_ERROR);$ok(!str_contains($proyeccion,'diagnostico')&&!str_contains($proyeccion,'Diagnóstico sintético privado'));
$recetas=new RecetaService($db);$medicamentos=[['medicamento'=>'Medicamento ficticio','dosis'=>'Ficticia','frecuencia'=>'Ficticia','duracion'=>'Ficticia','indicaciones'=>'No usar clínicamente']];
$payload=['idConsulta'=>$consulta['idConsulta'],'medicamentos'=>$medicamentos];
$rechazar(fn()=>$recetas->emitir($p1,$payload),403);$rechazar(fn()=>$recetas->emitir($m2,$payload),404);
// JSONB conserva los valores, no el orden de claves de cada objeto.
$r=$recetas->emitir($m1,$payload);$ok($recetas->obtener($p1,$r['idReceta'])['medicamentos']==$medicamentos);
$rechazar(fn()=>$recetas->obtener($p2,$r['idReceta']),404);$rechazar(fn()=>$recetas->obtener($admin,$r['idReceta']),403);$rechazar(fn()=>$recetas->obtener($m2,$r['idReceta']),404);
try{$db->exec("UPDATE recetas_medicas SET medicamentos='[]'::jsonb WHERE id_receta=".$r['idReceta']);throw new RuntimeException('Edición silenciosa admitida.');}catch(PDOException $e){$ok((string)$e->getCode()==='P2011');}
$rechazar(fn()=>$recetas->emitir($m1,$payload+['idRecetaAnterior'=>$r['idReceta']]),409);
$recetas->anular($m1,$r['idReceta'],['motivo'=>'Corrección explícita ficticia']);
$corregida=$recetas->emitir($m1,$payload+['idRecetaAnterior'=>$r['idReceta']]);$ok($corregida['idRecetaAnterior']===$r['idReceta']);
$rechazar(fn()=>$recetas->emitir($m1,$payload+['idRecetaAnterior'=>$r['idReceta']]),409);
$ok($recetas->obtener($p1,$r['idReceta'])['medicamentos']==$medicamentos);
for($i=0;$i<20;$i++)$recetas->emitir($m1,$payload);
$ok($recetas->listar($p1,[])['hayPaginaSiguiente']&&count($recetas->listar($p1,['pagina'=>'2'])['elementos'])===2,'Recetas paginadas.');
$pagos=new PagoDemostracionService($db);$tarifa=getenv('DEMO_TARIFA_BOB');
try{
    putenv('DEMO_TARIFA_BOB');$ok($pagos->configuracion($p1)['habilitado']===false);$rechazar(fn()=>$pagos->crear($p1,['idCita'=>$id,'metodo'=>'qr_demo']),409);
    putenv('DEMO_TARIFA_BOB=12.34');$rechazar(fn()=>$pagos->crear($p2,['idCita'=>$id,'metodo'=>'qr_demo']),404);
    $rechazar(fn()=>$pagos->crear($recepcion,['idCita'=>$id,'metodo'=>'qr_demo']),403);
    $rechazar(fn()=>$pagos->crear($p1,['idCita'=>$id,'metodo'=>'qr_demo','monto'=>1]),422);
    $p=$pagos->crear($p1,['idCita'=>$id,'metodo'=>'qr_demo']);$ok($p['montoCentavos']===1234&&$p['esSimulado']);
    $ok($pagos->crear($p1,['idCita'=>$id,'metodo'=>'qr_demo'])['idPago']===$p['idPago']);
    $rechazar(fn()=>$pagos->obtener($p2,$p['idPago']),404);$rechazar(fn()=>$pagos->obtener($m1,$p['idPago']),403);
    $rechazar(fn()=>$pagos->finalizar($p1,$p['idPago'],['accion'=>'confirmar','confirmarSimulacion'=>false]),422);
    $pagos->finalizar($p1,$p['idPago'],['accion'=>'cancelar','confirmarSimulacion'=>true]);
    $segundo=$pagos->crear($p1,['idCita'=>$id,'metodo'=>'paypal_demo']);$ok($segundo['idPago']!==$p['idPago']);
    putenv('DEMO_TARIFA_BOB=99.99');$pagado=$pagos->finalizar($p1,$segundo['idPago'],['accion'=>'confirmar','confirmarSimulacion'=>true]);$ok($pagado['montoCentavos']===1234&&$pagado['estado']==='pagado');
    $rechazar(fn()=>$pagos->finalizar($p1,$segundo['idPago'],['accion'=>'confirmar','confirmarSimulacion'=>true]),409);
    $rechazar(fn()=>$pagos->crear($p1,['idCita'=>$id,'metodo'=>'qr_demo']),409);
    $reporte=$pagos->reporte($recepcion,[]);$ok($reporte['esSimulado']&&$reporte['elementos'][0]['total_centavos']==='1234');
    $rechazar(fn()=>$pagos->reporte($p1,[]),403);
}finally{putenv($tarifa===false?'DEMO_TARIFA_BOB':'DEMO_TARIFA_BOB='.$tarifa);}
// No se borran documentos/eventos inmutables. Todo pertenece al cluster desechable.
echo "OK: $n comprobaciones aisladas de recetas, sala, pagos, privacidad y concurrencia optimista.\n";
