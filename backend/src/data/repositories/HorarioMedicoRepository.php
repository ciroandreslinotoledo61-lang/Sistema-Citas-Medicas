<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

use CitasMedicas\Data\Models\HorarioMedico;

final class HorarioMedicoRepository
{
    public function __construct(private readonly \PDO $conexionPostgresql)
    {
    }

    public function listarHorarios(array $filtros, ?int $idUsuarioMedico = null): array
    {
        $condiciones = [];
        $parametros = [];
        if ($idUsuarioMedico !== null) {
            $condiciones[] = 'medicos.id_usuario = :idUsuarioMedico';
            $parametros['idUsuarioMedico'] = $idUsuarioMedico;
        }
        if ($filtros['fecha'] !== '') {
            $condiciones[] = 'horarios.fecha_atencion = :fecha';
            $parametros['fecha'] = $filtros['fecha'];
        }
        if ($filtros['idMedico'] !== null) {
            $condiciones[] = 'horarios.id_medico = :idMedico';
            $parametros['idMedico'] = $filtros['idMedico'];
        }
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
        foreach ($parametros as $nombre => $valor) {
            $consulta->bindValue(':' . $nombre, $valor);
        }
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

    public function registrarHorario(array $datos): int
    {
        $consulta = $this->conexionPostgresql->prepare(
            'INSERT INTO horarios_medicos (id_medico, fecha_atencion, hora_inicio, hora_fin, duracion_cita_minutos)
             VALUES (:idMedico, :fechaAtencion, :horaInicio, :horaFin, :duracionCitaMinutos) RETURNING id_horario_medico'
        );
        $consulta->execute($datos);
        $idHorario = (int) $consulta->fetchColumn();
        return $idHorario;
    }

    public function existeHorarioEquivalente(array $datos): bool
    {
        $consulta = $this->conexionPostgresql->prepare('SELECT id_horario_medico FROM horarios_medicos
            WHERE id_medico = :idMedico AND fecha_atencion = :fechaAtencion AND hora_inicio = :horaInicio
            AND hora_fin = :horaFin AND duracion_cita_minutos = :duracionCitaMinutos LIMIT 1');
        $consulta->execute($datos);
        return $consulta->fetchColumn() !== false;
    }
}
