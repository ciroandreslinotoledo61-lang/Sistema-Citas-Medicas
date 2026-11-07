<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
use CitasMedicas\Business\Validators\{RecetaValidator,SalaEsperaValidator};
use CitasMedicas\Shared\Config\ConfiguracionPagosDemo;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\RecetaPdf;
$n=0;
$verificar=static function(bool $valor)use(&$n):void{if(!$valor)throw new RuntimeException('Validación de nuevos módulos fallida.');$n++;};
$rechazar=static function(callable $f)use($verificar):void{try{$f();}catch(ErrorAplicacion $e){$verificar($e->codigoHttp===422);return;}throw new RuntimeException('Se aceptaron datos inválidos.');};
$m=['medicamento'=>'Fármaco ficticio (prueba) \\', 'dosis'=>'Dosis ficticia','frecuencia'=>'Solo prueba','duracion'=>'Sin uso clínico','indicaciones'=>'Datos sintéticos'];
$d=['idConsulta'=>1,'medicamentos'=>[$m]];
$verificar(RecetaValidator::validar($d)['medicamentos'][0]===$m);
foreach([['medicamentos'=>[]],['idConsulta'=>0],['idPaciente'=>99],['medicamentos'=>array_fill(0,21,$m)],['medicamentos'=>[['medicamento'=>[]]]],['medicamentos'=>[$m+['precio'=>1]]]] as $c)$rechazar(fn()=>RecetaValidator::validar(array_replace($d,$c)));
foreach([['accion'=>'iniciar','version'=>-1],['accion'=>'atendido','version'=>1],['accion'=>'llegada','version'=>'0'],['accion'=>'llegada','version'=>0,'idMedico'=>1]] as $p)$rechazar(fn()=>SalaEsperaValidator::validar($p));
$verificar(SalaEsperaValidator::validar(['accion'=>'llegada','version'=>0])['version']===0);
$original=getenv('DEMO_TARIFA_BOB');
try{
    putenv('DEMO_TARIFA_BOB');$verificar(ConfiguracionPagosDemo::obtenerCentavos()===null);
    foreach(['0','-1','1.001','NaN','1e3','1,50','99999999'] as $valor){putenv('DEMO_TARIFA_BOB='.$valor);try{ConfiguracionPagosDemo::obtenerCentavos();}catch(RuntimeException){$n++;continue;}throw new RuntimeException('Tarifa inválida aceptada.');}
    foreach(['1'=>100,'1.01'=>101,'12.3'=>1230,'0.01'=>1] as $valor=>$esperado){putenv('DEMO_TARIFA_BOB='.$valor);$verificar(ConfiguracionPagosDemo::obtenerCentavos()===$esperado);}
}finally{putenv($original===false?'DEMO_TARIFA_BOB':'DEMO_TARIFA_BOB='.$original);}
$r=['idReceta'=>1,'estado'=>'anulada','fechaEmision'=>'2030-01-01','fechaAnulacion'=>'2030-01-02','motivoAnulacion'=>'Corrección explícita','idRecetaAnterior'=>null,
    'datosEmision'=>['clinica'=>'Clínica Vida Salud','paciente'=>'Paciente Ficticio','cedulaIdentidad'=>'DEMO','medico'=>'Médico Ficticio','matricula'=>'DEMO','direccionClinica'=>'','telefonoClinica'=>''],
    'medicamentos'=>array_fill(0,20,$m)];
$pdf=RecetaPdf::generar($r);$verificar(str_starts_with($pdf,'%PDF-1.4')&&str_ends_with($pdf,"%%EOF\n"));
$verificar(substr_count($pdf,'/Type /Page ')>1&&str_contains($pdf,'ANULADA'));
preg_match('/startxref\n([0-9]+)/',$pdf,$match);$verificar(substr($pdf,(int)$match[1],4)==='xref');
preg_match_all('/([0-9]+) 0 obj\n/',$pdf,$objetos,PREG_OFFSET_CAPTURE);
foreach($objetos[0] as $objeto){$verificar(str_contains($pdf,sprintf('%010d 00000 n ',$objeto[1])));}
$r['datosEmision']['paciente']='Paciente 😀';$rechazar(fn()=>RecetaPdf::generar($r));
echo "OK: $n comprobaciones de recetas, estados, tarifas y PDF multipágina/offsets.\n";
