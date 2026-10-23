<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Repositories;

use CitasMedicas\Data\Models\Cita;
use CitasMedicas\Data\Models\Usuario;

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

    public function __construct(private readonly \PDO $conexionPostgresql) {}

    public function obtenerCitaPorId(int $idCita, bool $bloquear = false): ?Cita
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_CITA .
            ' WHERE citas.id_cita = :idCita' . ($bloquear ? ' FOR UPDATE OF citas' : ''));
        $consulta->execute(['idCita' => $idCita]);
        $registro = $consulta->fetch();
        return $registro ? new Cita($registro) : null;
    }

    public function listarCitas(Usuario $usuario, array $filtros, string $ahora): array
    {
        $condiciones = []; $parametros = ['ahora' => $ahora];
        if ($usuario->codigoRol === 'paciente') { $condiciones[] = 'pacientes.id_usuario = :idPropietario'; $parametros['idPropietario'] = $usuario->idUsuario; }
        if ($usuario->codigoRol === 'medico') { $condiciones[] = 'medicos.id_usuario = :idPropietario'; $parametros['idPropietario'] = $usuario->idUsuario; }
        if ($filtros['fecha'] !== '') { $condiciones[] = 'horarios.fecha_atencion = :fecha'; $parametros['fecha'] = $filtros['fecha']; }
        if ($filtros['estado'] !== '') { $condiciones[] = 'citas.estado_cita = :estado'; $parametros['estado'] = $filtros['estado']; }
        $condiciones[] = "concat_ws(' ', usuario_paciente.nombres_usuario, usuario_paciente.apellidos_usuario,
            pacientes.cedula_identidad, usuario_medico.nombres_usuario, usuario_medico.apellidos_usuario,
            especialidades.nombre_especialidad) ILIKE :busqueda";
        $parametros['busqueda'] = '%' . $filtros['busqueda'] . '%';
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_CITA . ' WHERE ' . implode(' AND ', $condiciones) .
            " ORDER BY CASE WHEN turnos.fecha_hora_inicio > :ahora AND citas.estado_cita IN ('pendiente','confirmada') THEN 0 ELSE 1 END,
              turnos.fecha_hora_inicio, citas.id_cita LIMIT 21 OFFSET :desplazamiento");
        foreach ($parametros as $nombre => $valor) { $consulta->bindValue(':' . $nombre, $valor); }
        $consulta->bindValue(':desplazamiento', ($filtros['pagina'] - 1) * 20, \PDO::PARAM_INT);
        $consulta->execute();
        return array_map(static fn (array $registro): Cita => new Cita($registro), $consulta->fetchAll());
    }

    public function bloquearPaciente(int $idPaciente): ?array
    {
        $consulta = $this->conexionPostgresql->prepare(
            'SELECT pacientes.id_paciente, pacientes.id_usuario, usuarios.usuario_activo
             FROM pacientes JOIN usuarios ON usuarios.id_usuario = pacientes.id_usuario
             WHERE pacientes.id_paciente = :idPaciente FOR UPDATE OF pacientes, usuarios'
        );
        $consulta->execute(['idPaciente' => $idPaciente]);
        return $consulta->fetch() ?: null;
    }

    public function obtenerIdPacientePorUsuario(int $idUsuario): ?int
    {
        $consulta = $this->conexionPostgresql->prepare('SELECT id_paciente FROM pacientes WHERE id_usuario = :idUsuario');
        $consulta->execute(['idUsuario' => $idUsuario]);
        $resultado = $consulta->fetchColumn();
        return $resultado === false ? null : (int) $resultado;
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
             AND citas.estado_cita NOT IN ('cancelada', 'reprogramada')
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
             AND citas.estado_cita IN ('pendiente', 'confirmada') LIMIT 1"
        );
        $consulta->execute(['idMedico' => $idMedico, 'ahora' => $ahora]);
        return $consulta->fetchColumn() !== false;
    }

    public function registrarCita(int $idPaciente, int $idTurno, string $motivo, int $idUsuario, ?int $idCitaOrigen = null, ?int $idEspecialidad = null): int
    {
        $consulta = $this->conexionPostgresql->prepare(
            "INSERT INTO citas (id_paciente, id_turno, motivo_cita, estado_cita, id_usuario_registro, id_cita_origen, id_especialidad_reserva)
             VALUES (:idPaciente, :idTurno, :motivo, 'confirmada', :idUsuario, :idCitaOrigen, :idEspecialidad) RETURNING id_cita"
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
}
