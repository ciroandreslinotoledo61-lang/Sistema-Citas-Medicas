<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Repositories;
use CitasMedicas\Data\Models\EstadoSala;
final class SalaEsperaRepository
{
    public function __construct(private readonly \PDO $conexion) {}
    public function listar(string $fecha,int $pagina,?int $usuarioMedico): array
    {
        $q=$this->conexion->prepare("SELECT c.id_cita,c.estado_cita,t.fecha_hora_inicio,
            concat_ws(' ',up.nombres_usuario,up.apellidos_usuario) AS nombre_paciente,
            concat_ws(' ',um.nombres_usuario,um.apellidos_usuario) AS nombre_medico,
            CASE WHEN c.estado_cita='atendida' THEN 'atendido' ELSE coalesce(s.estado,'pendiente_llegada') END AS estado,
            coalesce(s.version_espera,0) AS version_espera
            FROM citas c JOIN pacientes p USING(id_paciente) JOIN usuarios up ON up.id_usuario=p.id_usuario
            JOIN turnos t USING(id_turno) JOIN horarios_medicos h USING(id_horario_medico)
            JOIN medicos m USING(id_medico) JOIN usuarios um ON um.id_usuario=m.id_usuario
            LEFT JOIN sala_espera s USING(id_cita)
            WHERE h.fecha_atencion=:fecha".($usuarioMedico!==null?' AND m.id_usuario=:usuario':'').
            ' ORDER BY t.fecha_hora_inicio,c.id_cita LIMIT 21 OFFSET :offset');
        $q->bindValue(':fecha',$fecha);if($usuarioMedico!==null)$q->bindValue(':usuario',$usuarioMedico,\PDO::PARAM_INT);
        $q->bindValue(':offset',($pagina-1)*20,\PDO::PARAM_INT);$q->execute();
        return array_map(static fn(array $r):EstadoSala=>new EstadoSala($r),$q->fetchAll());
    }
    public function obtenerEstado(int $id): ?array
    {
        $q=$this->conexion->prepare('SELECT estado,version_espera FROM sala_espera WHERE id_cita=:id FOR UPDATE');$q->execute(compact('id'));return $q->fetch()?:null;
    }
    public function llegada(int $id,int $usuario): void
    {
        $q=$this->conexion->prepare("INSERT INTO sala_espera(id_cita,estado,id_usuario_actualizacion) VALUES(:id,'en_espera',:usuario)");$q->execute(compact('id','usuario'));
    }
    public function iniciar(int $id,int $usuario): void
    {
        $q=$this->conexion->prepare("UPDATE sala_espera SET estado='en_consulta',version_espera=version_espera+1,id_usuario_actualizacion=:usuario WHERE id_cita=:id");$q->execute(compact('id','usuario'));
    }
    public function eventos(int $id): array
    {
        $q=$this->conexion->prepare('SELECT estado_anterior,estado_nuevo,evento,id_usuario,fecha FROM eventos_sala_espera WHERE id_cita=:id ORDER BY id_evento');$q->execute(compact('id'));return $q->fetchAll();
    }
}
