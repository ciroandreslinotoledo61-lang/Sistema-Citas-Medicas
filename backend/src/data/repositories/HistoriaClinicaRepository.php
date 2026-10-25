<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

use CitasMedicas\Data\Models\HistoriaClinica;

final class HistoriaClinicaRepository
{
    public function __construct(private readonly \PDO $conexionPostgresql)
    {
    }

    public function crearHistoriaClinicaParaPaciente(int $idPaciente): HistoriaClinica
    {
        $consulta = $this->conexionPostgresql->prepare(
            'INSERT INTO historias_clinicas (id_paciente) VALUES (:idPaciente) RETURNING id_historia_clinica'
        );
        $consulta->execute(['idPaciente' => $idPaciente]);
        return new HistoriaClinica((int) $consulta->fetchColumn(), $idPaciente);
    }
 public function obtenerHistoriaPorPaciente(int $idPaciente):?HistoriaClinica {
  $q=$this->conexionPostgresql->prepare("SELECT h.*,concat_ws(' ',u.nombres_usuario,u.apellidos_usuario) AS nombre_paciente FROM historias_clinicas h JOIN pacientes p USING(id_paciente) JOIN usuarios u USING(id_usuario) WHERE h.id_paciente=:id");
  $q->execute(['id'=>$idPaciente]);$r=$q->fetch();return $r?new HistoriaClinica((int)$r['id_historia_clinica'],(int)$r['id_paciente'],$r):null;
 }
 public function medicoTienePacienteAsignado(int $idUsuarioMedico,int $idPaciente):bool {
  $q=$this->conexionPostgresql->prepare("SELECT 1 FROM citas c JOIN turnos t USING(id_turno) JOIN horarios_medicos h USING(id_horario_medico) JOIN medicos m USING(id_medico) WHERE c.id_paciente=:paciente AND m.id_usuario=:medico AND c.estado_cita IN ('programada','atendida') LIMIT 1");
  $q->execute(['paciente'=>$idPaciente,'medico'=>$idUsuarioMedico]);return $q->fetchColumn()!==false;
 }
 public function actualizarAntecedentes(int $idPaciente,array $d):bool {
  $q=$this->conexionPostgresql->prepare('UPDATE historias_clinicas SET tipo_sangre=:tipo,alergias=:alergias,antecedentes_medicos=:antecedentes WHERE id_paciente=:id AND version_historia=:version RETURNING id_historia_clinica');
  $q->execute(['id'=>$idPaciente,'tipo'=>$d['tipoSangre'],'alergias'=>$d['alergias'],'antecedentes'=>$d['antecedentes'],'version'=>$d['versionHistoria']]);return $q->fetchColumn()!==false;
 }
}
