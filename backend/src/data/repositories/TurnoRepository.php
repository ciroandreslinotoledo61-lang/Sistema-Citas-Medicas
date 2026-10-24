<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

final class TurnoRepository
{
    public function __construct(private readonly \PDO $conexionPostgresql)
    {
    }

    public function registrarTurnos(int $idHorario, array $turnos): void
    {
        $consulta = $this->conexionPostgresql->prepare(
            'INSERT INTO turnos (id_horario_medico, fecha_hora_inicio, fecha_hora_fin, duracion_turno_minutos)
             VALUES (:idHorario, :inicio, :fin, :duracion)'
        );
        foreach ($turnos as $turno) {
            $consulta->execute(['idHorario' => $idHorario] + $turno);
        }
    }

    public function listarTurnosDisponibles(int $idMedico, string $fecha, string $ahora): array
    {
        $consulta = $this->conexionPostgresql->prepare(
            "SELECT turnos.id_turno, turnos.fecha_hora_inicio, turnos.fecha_hora_fin FROM turnos
             JOIN horarios_medicos horarios ON horarios.id_horario_medico = turnos.id_horario_medico
             JOIN medicos ON medicos.id_medico = horarios.id_medico
             JOIN usuarios ON usuarios.id_usuario = medicos.id_usuario
             JOIN especialidades ON especialidades.id_especialidad = medicos.id_especialidad
             WHERE horarios.id_medico = :idMedico AND horarios.fecha_atencion = :fecha
             AND turnos.turno_habilitado AND usuarios.usuario_activo AND especialidades.especialidad_activa
             AND turnos.fecha_hora_inicio > :ahora
             AND turnos.duracion_turno_minutos = horarios.duracion_cita_minutos
             AND turnos.fecha_hora_inicio >= horarios.fecha_atencion + horarios.hora_inicio AND turnos.fecha_hora_fin <= horarios.fecha_atencion + horarios.hora_fin
             AND NOT EXISTS (SELECT 1 FROM citas c JOIN turnos t ON t.id_turno=c.id_turno JOIN horarios_medicos h ON h.id_horario_medico=t.id_horario_medico
                 WHERE h.id_medico=horarios.id_medico AND c.estado_cita NOT IN ('cancelada','reprogramada')
                 AND t.fecha_hora_inicio<turnos.fecha_hora_fin AND t.fecha_hora_fin>turnos.fecha_hora_inicio)
             ORDER BY turnos.fecha_hora_inicio"
        );
        $consulta->execute(['idMedico' => $idMedico, 'fecha' => $fecha, 'ahora' => $ahora]);
        return array_map(static fn (array $registro): array => ['idTurno' => (int) $registro['id_turno'],
            'fechaHoraInicio' => $registro['fecha_hora_inicio'], 'fechaHoraFin' => $registro['fecha_hora_fin']], $consulta->fetchAll());
    }

    public function obtenerTurnoPorId(int $idTurno, bool $bloquear = false): ?array
    {
        $consulta = $this->conexionPostgresql->prepare(
            'SELECT turnos.*, horarios.duracion_cita_minutos, horarios.id_medico, horarios.fecha_atencion, horarios.hora_inicio, horarios.hora_fin
             FROM turnos JOIN horarios_medicos horarios ON horarios.id_horario_medico = turnos.id_horario_medico
             WHERE turnos.id_turno = :idTurno' . ($bloquear ? ' FOR UPDATE OF turnos' : '')
        );
        $consulta->execute(['idTurno' => $idTurno]);
        return $consulta->fetch() ?: null;
    }
}
