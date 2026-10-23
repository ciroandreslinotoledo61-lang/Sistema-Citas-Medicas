<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Repositories;

use CitasMedicas\Data\Models\HorarioMedico;
use CitasMedicas\Data\Models\Usuario;

final class HorarioMedicoRepository
{
    public function __construct(private readonly \PDO $conexionPostgresql) {}

    public function listarMedicosDisponibles(): array
    {
        $consulta = $this->conexionPostgresql->query(
            "SELECT medicos.id_medico, concat_ws(' ', usuarios.nombres_usuario, usuarios.apellidos_usuario) AS nombre_medico,
                especialidades.id_especialidad, especialidades.nombre_especialidad
             FROM medicos JOIN usuarios ON usuarios.id_usuario = medicos.id_usuario
             JOIN especialidades ON especialidades.id_especialidad = medicos.id_especialidad
             WHERE usuarios.usuario_activo AND especialidades.especialidad_activa
             ORDER BY especialidades.nombre_especialidad, nombre_medico, medicos.id_medico"
        );
        return array_map(static fn (array $registro): array => [
            'idMedico' => (int) $registro['id_medico'], 'nombreMedico' => $registro['nombre_medico'],
            'idEspecialidad' => (int) $registro['id_especialidad'], 'nombreEspecialidad' => $registro['nombre_especialidad'],
        ], $consulta->fetchAll());
    }

    public function listarHorarios(Usuario $usuario, array $filtros): array
    {
        $condiciones = []; $parametros = [];
        if ($usuario->codigoRol === 'medico') {
            $condiciones[] = 'medicos.id_usuario = :idUsuarioMedico';
            $parametros['idUsuarioMedico'] = $usuario->idUsuario;
        }
        if ($filtros['fecha'] !== '') { $condiciones[] = 'horarios.fecha_atencion = :fecha'; $parametros['fecha'] = $filtros['fecha']; }
        if ($filtros['idMedico'] !== null) { $condiciones[] = 'horarios.id_medico = :idMedico'; $parametros['idMedico'] = $filtros['idMedico']; }
        $condiciones[] = "concat_ws(' ', usuarios.nombres_usuario, usuarios.apellidos_usuario) ILIKE :busqueda";
        $parametros['busqueda'] = '%' . $filtros['busqueda'] . '%';
        $consulta = $this->conexionPostgresql->prepare(
            "SELECT horarios.*, concat_ws(' ', usuarios.nombres_usuario, usuarios.apellidos_usuario) AS nombre_medico,
                (SELECT count(*) FROM turnos WHERE turnos.id_horario_medico = horarios.id_horario_medico AND turnos.turno_habilitado AND turnos.duracion_turno_minutos = horarios.duracion_cita_minutos) AS cantidad_turnos
             FROM horarios_medicos horarios JOIN medicos ON medicos.id_medico = horarios.id_medico
             JOIN usuarios ON usuarios.id_usuario = medicos.id_usuario
             WHERE " . implode(' AND ', $condiciones) .
            ' ORDER BY horarios.fecha_atencion DESC, horarios.hora_inicio, horarios.id_horario_medico LIMIT 21 OFFSET :desplazamiento'
        );
        foreach ($parametros as $nombre => $valor) { $consulta->bindValue(':' . $nombre, $valor); }
        $consulta->bindValue(':desplazamiento', ($filtros['pagina'] - 1) * 20, \PDO::PARAM_INT);
        $consulta->execute();
        return array_map(static fn (array $registro): HorarioMedico => new HorarioMedico($registro), $consulta->fetchAll());
    }

    public function existeCruceHorario(array $datos): bool
    {
        $consulta = $this->conexionPostgresql->prepare(
            'SELECT id_horario_medico FROM horarios_medicos WHERE id_medico = :idMedico AND fecha_atencion = :fechaAtencion
             AND hora_inicio < :horaFin AND hora_fin > :horaInicio LIMIT 1'
        );
        $consulta->execute(array_intersect_key($datos, array_flip(['idMedico','fechaAtencion','horaInicio','horaFin'])));
        return $consulta->fetchColumn() !== false;
    }

    public function registrarHorarioConTurnos(array $datos): array
    {
        $consulta = $this->conexionPostgresql->prepare(
            'INSERT INTO horarios_medicos (id_medico, fecha_atencion, hora_inicio, hora_fin, duracion_cita_minutos)
             VALUES (:idMedico, :fechaAtencion, :horaInicio, :horaFin, :duracionCitaMinutos) RETURNING id_horario_medico'
        );
        $consulta->execute($datos);
        $idHorario = (int) $consulta->fetchColumn();
        $insertarTurno = $this->conexionPostgresql->prepare(
            'INSERT INTO turnos (id_horario_medico, fecha_hora_inicio, fecha_hora_fin, duracion_turno_minutos)
             VALUES (:idHorario, :inicio, :fin, :duracion)'
        );
        $inicio = new \DateTimeImmutable($datos['fechaAtencion'] . ' ' . $datos['horaInicio']);
        $fin = new \DateTimeImmutable($datos['fechaAtencion'] . ' ' . $datos['horaFin']);
        $cantidadTurnos = 0;
        for ($momento = $inicio; $momento < $fin; $momento = $momento->modify('+' . $datos['duracionCitaMinutos'] . ' minutes')) {
            $insertarTurno->execute(['duracion' => $datos['duracionCitaMinutos'], 'idHorario' => $idHorario, 'inicio' => $momento->format('Y-m-d H:i:s'),
                'fin' => $momento->modify('+' . $datos['duracionCitaMinutos'] . ' minutes')->format('Y-m-d H:i:s')]);
            $cantidadTurnos++;
        }
        return ['idHorarioMedico' => $idHorario, 'cantidadTurnos' => $cantidadTurnos] + $datos;
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
