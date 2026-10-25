<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Repositories;
use CitasMedicas\Data\Models\Tratamiento;
final class TratamientoRepository {
 public function __construct(private readonly \PDO $conexionPostgresql){}
 public function listarTratamientosConsulta(int $idConsulta):array {
  $q=$this->conexionPostgresql->prepare('SELECT * FROM tratamientos WHERE id_consulta=:id ORDER BY id_tratamiento');$q->execute(['id'=>$idConsulta]);return array_map(static fn(array $r):Tratamiento=>new Tratamiento($r),$q->fetchAll());
 }
 /** La version previa debe guardarse antes de reemplazar tratamientos, en la misma transaccion. */
 public function guardarTratamientosConsulta(int $idConsulta,array $tratamientos):void {
  $q=$this->conexionPostgresql->prepare('DELETE FROM tratamientos WHERE id_consulta=:id');$q->execute(['id'=>$idConsulta]);
  $q=$this->conexionPostgresql->prepare('INSERT INTO tratamientos(id_consulta,medicamento_tratamiento,dosis_tratamiento,frecuencia_tratamiento,duracion_tratamiento,indicaciones_tratamiento) VALUES(:id,:medicamento,:dosis,:frecuencia,:duracion,:indicaciones)');
  foreach($tratamientos as $t)$q->execute(['id'=>$idConsulta]+$t);
 }
}
