<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Validators;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
final class BitacoraValidator {
 public static function validarFiltrosBitacora(array $datos): array {
  $f=ValidarCamposFormulario::validarParametrosBusqueda($datos);
  $f['idUsuario']=!isset($datos['idUsuario'])||$datos['idUsuario']===''?null:ValidarCamposFormulario::validarIdentificador($datos,'idUsuario');
  foreach(['modulo','accion'] as $campo){$f[$campo]=ValidarCamposFormulario::validarTexto($datos,$campo,'El filtro',40,false);if($f[$campo]!==''&&!preg_match('/^[a-z_]+$/',$f[$campo]))throw new ErrorAplicacion('Filtro no valido.',422);}
  foreach(['fechaDesde','fechaHasta'] as $campo)$f[$campo]=ValidarCamposFormulario::validarFecha($datos[$campo]??'',true);
  if($f['fechaDesde']!==''&&$f['fechaHasta']!==''&&$f['fechaHasta']<$f['fechaDesde'])throw new ErrorAplicacion('Rango de fechas no valido.',422);
  return $f;
 }
}
