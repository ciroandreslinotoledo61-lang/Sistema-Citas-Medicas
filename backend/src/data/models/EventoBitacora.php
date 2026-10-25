<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Models;
final class EventoBitacora {
 public function __construct(private readonly array $r) {}
 public function obtenerDatosEvento(): array {
  $r=$this->r;return ['idBitacora'=>(int)$r['id_bitacora'],'idUsuario'=>$r['id_usuario']===null?null:(int)$r['id_usuario'],
   'nombreUsuario'=>$r['nombre_usuario'],'codigoRol'=>$r['codigo_rol'],'modulo'=>$r['modulo'],'accion'=>$r['accion'],
   'idRegistro'=>$r['id_registro']===null?null:(int)$r['id_registro'],'fechaHora'=>$r['fecha_hora'],'resultado'=>$r['resultado'],'descripcion'=>$r['descripcion']];
 }
}
