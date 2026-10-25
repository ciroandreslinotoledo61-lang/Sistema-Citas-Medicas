<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Models;
final class Tratamiento {
 public function __construct(private readonly array $registro){}
 public function obtenerDatosTratamiento():array {
  $r=$this->registro;return ['idTratamiento'=>(int)$r['id_tratamiento'],'medicamento'=>$r['medicamento_tratamiento'],
   'dosis'=>$r['dosis_tratamiento'],'frecuencia'=>$r['frecuencia_tratamiento'],'duracion'=>$r['duracion_tratamiento'],'indicaciones'=>$r['indicaciones_tratamiento']];
 }
}
