<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Repositories;
use CitasMedicas\Data\Models\Receta;

final class RecetaRepository
{
    private const SELECT_RECETA='SELECT r.* FROM recetas_medicas r JOIN pacientes p USING(id_paciente) JOIN medicos m USING(id_medico)';
    public function __construct(private readonly \PDO $conexion) {}
    public function obtener(int $id, int $usuario, bool $paciente, bool $bloquear=false): ?Receta
    {
        $q=$this->conexion->prepare(self::SELECT_RECETA.' WHERE r.id_receta=:id AND '.($paciente?'p':'m').'.id_usuario=:usuario'.($bloquear?' FOR UPDATE OF r':''));
        $q->execute(compact('id','usuario')); $r=$q->fetch(); return $r?new Receta($r):null;
    }
    public function listar(int $usuario, bool $paciente, array $f, ?int $consulta): array
    {
        $q=$this->conexion->prepare(self::SELECT_RECETA.' WHERE '.($paciente?'p':'m').'.id_usuario=:usuario'.($consulta!==null?' AND r.id_consulta=:consulta':'').' ORDER BY r.id_receta DESC LIMIT 21 OFFSET :offset');
        $q->bindValue(':usuario',$usuario,\PDO::PARAM_INT);
        if($consulta!==null) $q->bindValue(':consulta',$consulta,\PDO::PARAM_INT);
        $q->bindValue(':offset',($f['pagina']-1)*20,\PDO::PARAM_INT);$q->execute();
        return array_map(static fn(array $r):Receta=>new Receta($r),$q->fetchAll());
    }
    public function obtenerContexto(int $consulta, int $usuario): ?array
    {
        $q=$this->conexion->prepare("SELECT c.id_paciente,m.id_medico,m.matricula_profesional,
            concat_ws(' ',up.nombres_usuario,up.apellidos_usuario) AS paciente,p.cedula_identidad,
            concat_ws(' ',um.nombres_usuario,um.apellidos_usuario) AS medico
            FROM consultas co JOIN citas c USING(id_cita) JOIN turnos t USING(id_turno) JOIN horarios_medicos h USING(id_horario_medico)
            JOIN medicos m USING(id_medico) JOIN usuarios um ON um.id_usuario=m.id_usuario
            JOIN pacientes p ON p.id_paciente=c.id_paciente JOIN usuarios up ON up.id_usuario=p.id_usuario
            WHERE co.id_consulta=:consulta AND m.id_usuario=:usuario FOR UPDATE OF co");
        $q->execute(compact('consulta','usuario'));return $q->fetch()?:null;
    }
    public function registrar(array $d,array $contexto,array $emision): int
    {
        $q=$this->conexion->prepare('INSERT INTO recetas_medicas(id_consulta,id_paciente,id_medico,id_receta_anterior,datos_emision,medicamentos) VALUES(:consulta,:paciente,:medico,:anterior,:emision,:medicamentos) RETURNING id_receta');
        $q->execute(['consulta'=>$d['idConsulta'],'paciente'=>$contexto['id_paciente'],'medico'=>$contexto['id_medico'],
            'anterior'=>$d['idRecetaAnterior'],'emision'=>json_encode($emision,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),
            'medicamentos'=>json_encode($d['medicamentos'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
        return (int)$q->fetchColumn();
    }
    public function anular(int $id,int $usuario,string $motivo): void
    {
        $q=$this->conexion->prepare("UPDATE recetas_medicas SET estado='anulada',motivo_anulacion=:motivo,anulada_por=:usuario,fecha_anulacion=clock_timestamp() WHERE id_receta=:id AND estado='emitida'");
        $q->execute(compact('id','usuario','motivo'));
    }
}
