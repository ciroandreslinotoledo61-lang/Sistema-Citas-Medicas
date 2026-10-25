<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Validators;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
final class ConsultaValidator {
 public static function validarTextoClinico(array $datos,string $campo,int $maximo,bool $obligatorio=true):string {
  $t=$datos[$campo]??'';
  if(!is_string($t)||($obligatorio&&trim($t)==='')||mb_strlen($t)>$maximo||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$t))throw new ErrorAplicacion('Revisa los datos clinicos.',422,[$campo=>'Completa el campo sin superar '.$maximo.' caracteres.']);
  return trim(str_replace("\r\n","\n",$t));
 }
 public static function validarConsulta(array $datos,bool $edicion=false):array {
  foreach(['idPaciente','idMedico','idHistoriaClinica'] as $c)if(array_key_exists($c,$datos))throw new ErrorAplicacion('Paciente y medico se obtienen de la cita.',422);
  $id=$edicion?'versionConsulta':'idCita';$d=[$id=>ValidarCamposFormulario::validarIdentificador($datos,$id)];
  foreach(['motivoConsulta'=>1000,'diagnostico'=>5000,'observaciones'=>5000] as $c=>$n)$d[$c]=self::validarTextoClinico($datos,$c,$n,$c!=='observaciones');
  $lista=$datos['tratamientos']??[];
  if(!is_array($lista)||!array_is_list($lista)||count($lista)>20)throw new ErrorAplicacion('Registra hasta 20 tratamientos.',422);
  $d['tratamientos']=[];
  foreach($lista as $t){if(!is_array($t))throw new ErrorAplicacion('Tratamiento no valido.',422);$v=[];foreach(['medicamento'=>150,'dosis'=>100,'frecuencia'=>100,'duracion'=>100,'indicaciones'=>1000] as $c=>$n)$v[$c]=self::validarTextoClinico($t,$c,$n,$c!=='indicaciones');$d['tratamientos'][]=$v;}
  return $d;
 }
 public static function validarHistoria(array $d):array {
  $tipo=$d['tipoSangre']??'';if(!in_array($tipo,['','A+','A-','B+','B-','AB+','AB-','O+','O-'],true))throw new ErrorAplicacion('Tipo de sangre no valido.',422);
  return ['tipoSangre'=>$tipo===''?null:$tipo,'alergias'=>self::validarTextoClinico($d,'alergias',5000,false),'antecedentes'=>self::validarTextoClinico($d,'antecedentes',5000,false),'versionHistoria'=>ValidarCamposFormulario::validarIdentificador($d,'versionHistoria')];
 }
 public static function validarFiltrosConsultas(array $d):array {
  $f=ValidarCamposFormulario::validarParametrosBusqueda($d);
  foreach(['fechaDesde','fechaHasta'] as $c)$f[$c]=ValidarCamposFormulario::validarFecha($d[$c]??'',true);
  if($f['fechaDesde']!==''&&$f['fechaHasta']!==''&&$f['fechaHasta']<$f['fechaDesde'])throw new ErrorAplicacion('Rango de fechas no valido.',422);return $f;
 }
}
