<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

use CitasMedicas\Data\Models\Cita;

final class CitaRepository
{
    private const CONSULTA_CITA = "SELECT citas.*, turnos.fecha_hora_inicio, turnos.fecha_hora_fin,
        medicos.id_medico, pacientes.id_usuario AS id_usuario_paciente, medicos.id_usuario AS id_usuario_medico,
        concat_ws(' ', usuario_paciente.nombres_usuario, usuario_paciente.apellidos_usuario) AS nombre_paciente,
        concat_ws(' ', usuario_medico.nombres_usuario, usuario_medico.apellidos_usuario) AS nombre_medico,
        pacientes.cedula_identidad, pacientes.fecha_nacimiento, especialidades.nombre_especialidad
        FROM citas JOIN pacientes ON pacientes.id_paciente = citas.id_paciente
        JOIN usuarios usuario_paciente ON usuario_paciente.id_usuario = pacientes.id_usuario
        JOIN turnos ON turnos.id_turno = citas.id_turno
        JOIN horarios_medicos horarios ON horarios.id_horario_medico = turnos.id_horario_medico
        JOIN medicos ON medicos.id_medico = horarios.id_medico
        JOIN usuarios usuario_medico ON usuario_medico.id_usuario = medicos.id_usuario
        JOIN especialidades ON especialidades.id_especialidad = COALESCE(citas.id_especialidad_reserva, medicos.id_especialidad)";

    public function __construct(private readonly \PDO $conexionPostgresql)
    {
    }

    public function obtenerCitaPorId(int $idCita, bool $bloquear = false): ?Cita
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_CITA .
            ' WHERE citas.id_cita = :idCita' . ($bloquear ? ' FOR UPDATE OF citas' : ''));
        $consulta->execute(['idCita' => $idCita]);
        $registro = $consulta->fetch();
        return $registro ? new Cita($registro) : null;
    }

    public function listarCitas(array $filtros, string $ahora, ?int $idUsuarioPaciente = null, ?int $idUsuarioMedico = null): array
    {
        $condiciones = [];
        $parametros = ['ahora' => $ahora];
        if ($idUsuarioPaciente !== null) {
            $condiciones[] = 'pacientes.id_usuario = :idUsuarioPaciente';
            $parametros['idUsuarioPaciente'] = $idUsuarioPaciente;
        }
        if ($idUsuarioMedico !== null) {
            $condiciones[] = 'medicos.id_usuario = :idUsuarioMedico';
            $parametros['idUsuarioMedico'] = $idUsuarioMedico;
        }
        if ($filtros['fecha'] !== '') {
            $condiciones[] = 'horarios.fecha_atencion = :fecha';
            $parametros['fecha'] = $filtros['fecha'];
        }
        if ($filtros['estado'] !== '') {
            $condiciones[] = 'citas.estado_cita = :estado';
            $parametros['estado'] = $filtros['estado'];
        }
        foreach (['idMedico' => 'medicos.id_medico', 'idPaciente' => 'pacientes.id_paciente'] as $campo => $columna) {
            if ($filtros[$campo] !== null) {
                $condiciones[] = $columna . ' = :' . $campo;
                $parametros[$campo] = $filtros[$campo];
            }
        }
        $condiciones[] = "concat_ws(' ', usuario_paciente.nombres_usuario, usuario_paciente.apellidos_usuario,
            pacientes.cedula_identidad, usuario_medico.nombres_usuario, usuario_medico.apellidos_usuario,
            especialidades.nombre_especialidad) ILIKE :busqueda";
        $parametros['busqueda'] = '%' . $filtros['busqueda'] . '%';
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_CITA . ' WHERE ' . implode(' AND ', $condiciones) .
            " ORDER BY CASE WHEN turnos.fecha_hora_inicio > :ahora AND citas.estado_cita IN ('programada') THEN 0 ELSE 1 END,
              turnos.fecha_hora_inicio, citas.id_cita LIMIT 21 OFFSET :desplazamiento");
        foreach ($parametros as $nombre => $valor) {
            $consulta->bindValue(':' . $nombre, $valor);
        }
        $consulta->bindValue(':desplazamiento', ($filtros['pagina'] - 1) * 20, \PDO::PARAM_INT);
        $consulta->execute();
        return array_map(static fn (array $registro): Cita => new Cita($registro), $consulta->fetchAll());
    }

    public function turnoEstaOcupado(int $idTurno): bool
    {
        $consulta = $this->conexionPostgresql->prepare("SELECT id_cita FROM citas WHERE id_turno = :idTurno AND estado_cita NOT IN ('cancelada','reprogramada') LIMIT 1");
        $consulta->execute(['idTurno' => $idTurno]);
        return $consulta->fetchColumn() !== false;
    }

    public function pacienteTieneCruce(int $idPaciente, string $inicio, string $fin, int $idCitaExcluida = 0): bool
    {
        $consulta = $this->conexionPostgresql->prepare(
            "SELECT citas.id_cita FROM citas JOIN turnos ON turnos.id_turno = citas.id_turno
             WHERE citas.id_paciente = :idPaciente AND citas.id_cita <> :idCitaExcluida
             AND citas.estado_cita IN ('programada')
             AND turnos.fecha_hora_inicio < :fin AND turnos.fecha_hora_fin > :inicio LIMIT 1"
        );
        $consulta->execute(['idPaciente' => $idPaciente, 'idCitaExcluida' => $idCitaExcluida, 'inicio' => $inicio, 'fin' => $fin]);
        return $consulta->fetchColumn() !== false;
    }

    public function medicoTieneCitasVigentes(int $idMedico, string $ahora): bool
    {
        $consulta = $this->conexionPostgresql->prepare(
            "SELECT citas.id_cita FROM citas JOIN turnos ON turnos.id_turno = citas.id_turno
             JOIN horarios_medicos horarios ON horarios.id_horario_medico = turnos.id_horario_medico
             WHERE horarios.id_medico = :idMedico AND turnos.fecha_hora_fin > :ahora
             AND citas.estado_cita IN ('programada') LIMIT 1"
        );
        $consulta->execute(['idMedico' => $idMedico, 'ahora' => $ahora]);
        return $consulta->fetchColumn() !== false;
    }

    public function registrarCita(int $idPaciente, int $idTurno, string $motivo, int $idUsuario, ?int $idCitaOrigen = null, ?int $idEspecialidad = null): int
    {
        $consulta = $this->conexionPostgresql->prepare(
            "INSERT INTO citas (id_paciente, id_turno, motivo_cita, estado_cita, id_usuario_registro, id_cita_origen, id_especialidad_reserva)
             VALUES (:idPaciente, :idTurno, :motivo, 'programada', :idUsuario, :idCitaOrigen, :idEspecialidad) RETURNING id_cita"
        );
        $consulta->execute(['idPaciente' => $idPaciente, 'idTurno' => $idTurno, 'motivo' => $motivo,
            'idUsuario' => $idUsuario, 'idCitaOrigen' => $idCitaOrigen, 'idEspecialidad' => $idEspecialidad]);
        return (int) $consulta->fetchColumn();
    }

    public function actualizarEstadoCita(int $idCita, string $estado, int $idUsuario, string $motivoCancelacion = ''): void
    {
        $consulta = $this->conexionPostgresql->prepare(
            'UPDATE citas SET estado_cita = :estado, motivo_cancelacion = :motivoCancelacion,
             id_usuario_actualizacion = :idUsuario, fecha_actualizacion_cita = CURRENT_TIMESTAMP WHERE id_cita = :idCita'
        );
        $consulta->execute(['idCita' => $idCita, 'estado' => $estado, 'idUsuario' => $idUsuario, 'motivoCancelacion' => $motivoCancelacion]);
    }

    public function registrarAsistencia(int $idCita, string $asistencia, string $estado, int $idUsuario): void
    {
        $consulta = $this->conexionPostgresql->prepare(
            'UPDATE citas SET asistencia_registrada = :asistencia, estado_cita = :estado,
             id_usuario_actualizacion = :idUsuario, fecha_actualizacion_cita = CURRENT_TIMESTAMP WHERE id_cita = :idCita'
        );
        $consulta->execute(['idCita' => $idCita, 'asistencia' => $asistencia, 'estado' => $estado, 'idUsuario' => $idUsuario]);
    }

    public function medicoTieneCruce(int $idMedico, string $inicio, string $fin, int $idCitaExcluida = 0): bool
    {
        $consulta = $this->conexionPostgresql->prepare("SELECT c.id_cita FROM citas c JOIN turnos t USING(id_turno)
            JOIN horarios_medicos h USING(id_horario_medico) WHERE h.id_medico=:idMedico AND c.id_cita<>:idCitaExcluida
            AND c.estado_cita NOT IN ('cancelada','reprogramada') AND t.fecha_hora_inicio<:fin AND t.fecha_hora_fin>:inicio LIMIT 1");
        $consulta->execute(compact('idMedico', 'inicio', 'fin', 'idCitaExcluida'));
        return $consulta->fetchColumn() !== false;
    }

    public function pacienteTieneCitaActiva(int $idPaciente, int $idCitaExcluida = 0): bool
    {
        $consulta = $this->conexionPostgresql->prepare("SELECT id_cita FROM citas WHERE id_paciente = :idPaciente
            AND id_cita <> :idCitaExcluida AND estado_cita IN ('programada') LIMIT 1");
        $consulta->execute(compact('idPaciente', 'idCitaExcluida'));
        return $consulta->fetchColumn() !== false;
    }

    public function listarCitasActivasPaciente(int $idPaciente): array
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_CITA .
            " WHERE citas.id_paciente = :idPaciente AND citas.estado_cita IN ('programada') ORDER BY turnos.fecha_hora_inicio, citas.id_cita");
        $consulta->execute(['idPaciente' => $idPaciente]);
        return array_map(static fn (array $registro): Cita => new Cita($registro), $consulta->fetchAll());
    }
    /** Llamar despues de autorizar la cita inicial. La ruta evita ciclos historicos. */
    public function obtenerResultadoReprogramacion(int $idCita): ?array
    {
        $q=$this->conexionPostgresql->prepare("WITH RECURSIVE cadena AS (
            SELECT id_cita,estado_cita,id_turno,ARRAY[id_cita] camino,0 nivel FROM citas WHERE id_cita=:id
            UNION ALL SELECT c.id_cita,c.estado_cita,c.id_turno,x.camino||c.id_cita,x.nivel+1
            FROM citas c JOIN cadena x ON c.id_cita_origen=x.id_cita WHERE NOT c.id_cita=ANY(x.camino))
            SELECT c.id_cita,c.estado_cita,t.fecha_hora_inicio,t.fecha_hora_fin FROM cadena c JOIN turnos t USING(id_turno)
            WHERE nivel>0 ORDER BY nivel DESC,id_cita DESC LIMIT 1");
        $q->execute(['id'=>$idCita]);$r=$q->fetch();
        return $r?['idCita'=>(int)$r['id_cita'],'estadoCita'=>$r['estado_cita'],'fechaHoraInicio'=>$r['fecha_hora_inicio'],'fechaHoraFin'=>$r['fecha_hora_fin']]:null;
    }
}
