<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Repositories;
use CitasMedicas\Data\Models\Consulta;
final class ConsultaRepository {
 private const SELECT_CONSULTA="SELECT co.*,c.id_paciente,m.id_usuario AS id_usuario_medico,t.fecha_hora_inicio,
  concat_ws(' ',up.nombres_usuario,up.apellidos_usuario) AS nombre_paciente,concat_ws(' ',um.nombres_usuario,um.apellidos_usuario) AS nombre_medico
  FROM consultas co JOIN citas c USING(id_cita) JOIN historias_clinicas hc ON hc.id_historia_clinica=co.id_historia_clinica AND hc.id_paciente=c.id_paciente
  JOIN turnos t USING(id_turno) JOIN horarios_medicos h USING(id_horario_medico) JOIN medicos m USING(id_medico)
  JOIN usuarios um ON um.id_usuario=m.id_usuario JOIN pacientes p ON p.id_paciente=c.id_paciente JOIN usuarios up ON up.id_usuario=p.id_usuario";
 public function __construct(private readonly \PDO $conexionPostgresql){}
 public function obtenerConsultaPorId(int $idConsulta,bool $bloquear=false):?Consulta {
  $q=$this->conexionPostgresql->prepare(self::SELECT_CONSULTA.' WHERE co.id_consulta=:id'.($bloquear?' FOR UPDATE OF co':''));$q->execute(['id'=>$idConsulta]);$r=$q->fetch();return $r?new Consulta($r):null;
 }
 public function obtenerIdConsultaPorCita(int $idCita):?int {$q=$this->conexionPostgresql->prepare('SELECT id_consulta FROM consultas WHERE id_cita=:id');$q->execute(['id'=>$idCita]);$r=$q->fetchColumn();return $r===false?null:(int)$r;}
 public function listarConsultas(array $f,?int $idUsuarioMedico=null,?int $idPaciente=null):array {
  if($idUsuarioMedico===null&&$idPaciente===null)throw new \InvalidArgumentException('Falta el alcance de la consulta.');
  $w=[];$p=[];if($idUsuarioMedico!==null){$w[]='m.id_usuario=:medico';$p['medico']=$idUsuarioMedico;}if($idPaciente!==null){$w[]='c.id_paciente=:paciente';$p['paciente']=$idPaciente;}
  if($f['fechaDesde']!==''){$w[]='t.fecha_hora_inicio>=CAST(:desde AS date)';$p['desde']=$f['fechaDesde'];}if($f['fechaHasta']!==''){$w[]="t.fecha_hora_inicio<CAST(:hasta AS date)+INTERVAL '1 day'";$p['hasta']=$f['fechaHasta'];}
  $w[]="concat_ws(' ',up.nombres_usuario,up.apellidos_usuario,p.cedula_identidad) ILIKE :busqueda";$p['busqueda']='%'.$f['busqueda'].'%';
  $q=$this->conexionPostgresql->prepare(self::SELECT_CONSULTA.' WHERE '.implode(' AND ',$w).' ORDER BY t.fecha_hora_inicio DESC,co.id_consulta DESC LIMIT 21 OFFSET :desplazamiento');
  foreach($p as $k=>$v)$q->bindValue(':'.$k,$v);$q->bindValue(':desplazamiento',($f['pagina']-1)*20,\PDO::PARAM_INT);$q->execute();return array_map(static fn(array $r):Consulta=>new Consulta($r),$q->fetchAll());
 }
 public function registrarConsulta(int $idCita,int $idHistoria,array $d,int $idUsuario):int {
  $q=$this->conexionPostgresql->prepare('INSERT INTO consultas(id_cita,id_historia_clinica,motivo_consulta,diagnostico_consulta,observaciones_consulta,id_usuario_actualizacion) VALUES(:cita,:historia,:motivo,:diagnostico,:observaciones,:usuario) RETURNING id_consulta');
  $q->execute(['cita'=>$idCita,'historia'=>$idHistoria,'motivo'=>$d['motivoConsulta'],'diagnostico'=>$d['diagnostico'],'observaciones'=>$d['observaciones'],'usuario'=>$idUsuario]);return (int)$q->fetchColumn();
 }
 public function actualizarConsulta(int $idConsulta,array $d,int $idUsuario):bool {
  $q=$this->conexionPostgresql->prepare('UPDATE consultas SET motivo_consulta=:motivo,diagnostico_consulta=:diagnostico,observaciones_consulta=:observaciones,id_usuario_actualizacion=:usuario WHERE id_consulta=:id AND version_consulta=:version RETURNING id_consulta');
  $q->execute(['id'=>$idConsulta,'motivo'=>$d['motivoConsulta'],'diagnostico'=>$d['diagnostico'],'observaciones'=>$d['observaciones'],'usuario'=>$idUsuario,'version'=>$d['versionConsulta']]);return $q->fetchColumn()!==false;
 }
}
