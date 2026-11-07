<?php
declare(strict_types=1);
require_once __DIR__.'/http/ClienteHttpPruebas.php';
function probarModulosHttp(string $base,string $front,string $clave,array $fixture): void
{
    $resultados=[];$secretos=[$clave,'ClaveDePruebaAgenda123'];
    $cliente=static function()use($base,&$resultados,&$secretos):ClienteHttpPruebas{return new ClienteHttpPruebas($base,$resultados,$secretos);};
    $anon=$cliente();foreach(['/recetas','/sala-espera','/pagos-demo'] as $ruta)$anon->request('GET',$ruta,esperado:401);
    $admin=$cliente();$recepcion=$cliente();$admin->login('administrador@vidasalud.test',$clave);$recepcion->login('recepcionista@vidasalud.test',$clave);
    $p=$m=[];
    foreach($fixture as $f){$a=$cliente();$a->login($f['correoPaciente'],'ClaveDePruebaAgenda123');$p[]=$a;$a=$cliente();$a->login($f['correoMedico'],'ClaveDePruebaAgenda123');$m[]=$a;}
    $payload=['idConsulta'=>$fixture[0]['idConsulta'],'medicamentos'=>[['medicamento'=>'Fármaco sintético','dosis'=>'Prueba','frecuencia'=>'Prueba','duracion'=>'Prueba','indicaciones'=>'No usar clínicamente']]];
    $m[0]->request('POST','/recetas',$payload,419,csrf:false);
    $p[0]->request('POST','/recetas',$payload,403);$m[1]->request('POST','/recetas',$payload,404);
    $r=$m[0]->request('POST','/recetas',$payload,201)['datos'];$id=$r['idReceta'];
    $p[0]->request('GET',"/recetas/$id");$p[1]->request('GET',"/recetas/$id",esperado:404);$m[1]->request('GET',"/recetas/$id",esperado:404);
    foreach([$admin,$recepcion] as $a){$a->request('GET',"/recetas/$id",esperado:403);$a->request('GET',"/recetas/$id/pdf",esperado:403);}
    $p[1]->request('GET',"/recetas/$id/pdf",esperado:404);
    $pdf=$p[0]->obtenerRespuesta('GET',$base."/recetas/$id/pdf",binario:true);
    comprobarHttp($pdf['codigo']===200&&str_contains($pdf['headers']['cache-control'],'no-store'),'PDF privado sin caché.');
    $m[0]->request('PATCH',"/recetas/$id/anulacion",['motivo'=>'Corrección sintética']);
    $m[0]->request('POST','/recetas',$payload+['idRecetaAnterior'=>$id],201);
    $p[0]->request('GET',"/recetas?idConsulta=".$fixture[0]['idConsulta']);
    [, $html]=$p[0]->vista($front,"/recetas/$id");comprobarHttp(str_contains($html,'Descargar PDF'),'Vista de receta sin descarga.');
    foreach([$admin,$recepcion] as $a)$a->vista($front,'/recetas',403);
    $p[0]->request('GET','/sala-espera',esperado:403);$p[0]->vista($front,'/sala-espera',403);
    // Preparación independiente del escenario anterior de privacidad.
    $anterior=$p[0]->request('GET','/citas/'.$fixture[0]['idCitaProxima'])['datos'];
    if($anterior['estadoCita']==='programada')$p[0]->request('PATCH','/citas/'.$anterior['idCita'].'/cancelacion',[]);
    $c=$p[0]->request('POST','/citas',['idTurno'=>$fixture[0]['idTurnoDisponible']],201)['datos'];$cid=$c['idCita'];
    $recepcion->request('PATCH',"/sala-espera/$cid",['accion'=>'llegada','version'=>0],419,csrf:false);
    $recepcion->request('PATCH',"/sala-espera/$cid",['accion'=>'llegada','version'=>0]);
    $recepcion->request('PATCH',"/sala-espera/$cid",['accion'=>'llegada','version'=>0],409);
    $m[1]->request('PATCH',"/sala-espera/$cid",['accion'=>'iniciar','version'=>1],404);
    $recepcion->request('PATCH',"/sala-espera/$cid",['accion'=>'iniciar','version'=>1],403);
    $m[0]->request('PATCH',"/sala-espera/$cid",['accion'=>'iniciar','version'=>1],409); // Turno futuro.
    $recepcion->request('GET',"/sala-espera/$cid/historial");$m[1]->request('GET',"/sala-espera/$cid/historial",esperado:404);
    $operativo=$recepcion->request('GET','/sala-espera?fecha='.substr($c['fechaHoraInicio'],0,10))['datos'];
    comprobarHttp(!str_contains(json_encode($operativo),'Diagnostico privado'),'Sala expuso datos clínicos.');
    $m[0]->request('GET','/pagos-demo',esperado:403);$m[0]->vista($front,'/pagos-demo',403);
    $p[0]->request('POST','/pagos-demo',['idCita'=>$c['idCita'],'metodo'=>'qr_demo'],419,csrf:false);
    $p[1]->request('POST','/pagos-demo',['idCita'=>$c['idCita'],'metodo'=>'qr_demo'],404);
    $pago=$p[0]->request('POST','/pagos-demo',['idCita'=>$c['idCita'],'metodo'=>'qr_demo'],201)['datos'];$pid=$pago['idPago'];
    $p[1]->request('GET',"/pagos-demo/$pid",esperado:404);$admin->request('GET',"/pagos-demo/$pid");
    $recepcion->request('PATCH',"/pagos-demo/$pid",['accion'=>'confirmar','confirmarSimulacion'=>true],403);
    $p[0]->request('PATCH',"/pagos-demo/$pid",['accion'=>'confirmar','confirmarSimulacion'=>true]);
    $p[0]->request('PATCH',"/pagos-demo/$pid",['accion'=>'confirmar','confirmarSimulacion'=>true],409);
    $paypal=$p[0]->request('POST','/pagos-demo',['idCita'=>$fixture[0]['idCitaAtendida'],'metodo'=>'paypal_demo'],201)['datos'];
    comprobarHttp($paypal['metodo']==='paypal_demo','El método PayPal simulado no se conservó.');
    $p[0]->request('PATCH','/pagos-demo/'.$paypal['idPago'],['accion'=>'confirmar','confirmarSimulacion'=>true]);
    $p[0]->request('GET','/pagos-demo/reporte',esperado:403);$recepcion->request('GET','/pagos-demo/reporte');
    foreach([$admin,$recepcion] as $a){$a->vista($front,'/sala-espera');$a->vista($front,'/pagos-demo');}
    $p[0]->vista($front,'/pagos-demo/nuevo?idCita='.$cid);$m[0]->vista($front,'/recetas/nueva?idConsulta='.$fixture[0]['idConsulta']);
    echo 'OK: '.count($resultados)." solicitudes HTTP de recetas/PDF, sala, pagos, IDs, roles y CSRF; solo entorno aislado.\n";
}
