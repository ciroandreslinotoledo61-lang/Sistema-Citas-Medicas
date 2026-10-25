<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Repositories;
use CitasMedicas\Data\Models\EventoBitacora;
final class BitacoraRepository {
 public function __construct(private readonly \PDO $conexionPostgresql) {}
 public function establecerUsuarioResponsable(?int $idUsuario):void {
  $q=$this->conexionPostgresql->prepare("SELECT set_config('clinica.id_usuario',:usuario,false)");$q->execute(['usuario'=>$idUsuario===null?'':(string)$idUsuario]);
 }
 public function registrarEvento(?int $idUsuario,string $modulo,string $accion,?int $idRegistro,string $resultado,string $descripcion):void {
  $q=$this->conexionPostgresql->prepare('SELECT registrar_evento_bitacora(CAST(:usuario AS bigint),:modulo,:accion,CAST(:registro AS bigint),:resultado,:descripcion)');
  $q->execute(['usuario'=>$idUsuario,'modulo'=>$modulo,'accion'=>$accion,'registro'=>$idRegistro,'resultado'=>$resultado,'descripcion'=>$descripcion]);
 }
 public function listarEventos(array $f):array {
  $where=[];$params=[];
  foreach(['idUsuario'=>'id_usuario','modulo'=>'modulo','accion'=>'accion'] as $campo=>$columna)if($f[$campo]!==null&&$f[$campo]!==''){$where[]=$columna.'=:'.$campo;$params[$campo]=$f[$campo];}
  if($f['fechaDesde']!==''){$where[]="fecha_hora>=CAST(:fechaDesde AS date)::timestamp AT TIME ZONE 'America/La_Paz'";$params['fechaDesde']=$f['fechaDesde'];}
  if($f['fechaHasta']!==''){$where[]="fecha_hora<(CAST(:fechaHasta AS date)+INTERVAL '1 day') AT TIME ZONE 'America/La_Paz'";$params['fechaHasta']=$f['fechaHasta'];}
  if($f['busqueda']!==''){$where[]="concat_ws(' ',nombre_usuario,descripcion) ILIKE :busqueda";$params['busqueda']='%'.$f['busqueda'].'%';}
  $q=$this->conexionPostgresql->prepare('SELECT * FROM bitacora_sistema'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY fecha_hora DESC,id_bitacora DESC LIMIT 21 OFFSET :desplazamiento');
  foreach($params as $k=>$v)$q->bindValue(':'.$k,$v);$q->bindValue(':desplazamiento',($f['pagina']-1)*20,\PDO::PARAM_INT);$q->execute();
  return array_map(static fn(array $r):EventoBitacora=>new EventoBitacora($r),$q->fetchAll());
 }
 public function obtenerCatalogosFiltros():array {
  return ['usuarios'=>$this->conexionPostgresql->query('SELECT DISTINCT id_usuario AS "idUsuario",nombre_usuario AS "nombreUsuario" FROM bitacora_sistema WHERE id_usuario IS NOT NULL ORDER BY "nombreUsuario","idUsuario"')->fetchAll(),
   'modulos'=>$this->conexionPostgresql->query('SELECT DISTINCT modulo FROM bitacora_sistema ORDER BY modulo')->fetchAll(\PDO::FETCH_COLUMN),
   'acciones'=>$this->conexionPostgresql->query('SELECT DISTINCT accion FROM bitacora_sistema ORDER BY accion')->fetchAll(\PDO::FETCH_COLUMN)];
 }
}
