<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

final class ReporteRepository
{
    private const ORIGEN_CITAS = " FROM citas c JOIN turnos t ON t.id_turno=c.id_turno JOIN horarios_medicos h ON h.id_horario_medico=t.id_horario_medico
        JOIN medicos m ON m.id_medico=h.id_medico JOIN usuarios um ON um.id_usuario=m.id_usuario
        JOIN especialidades e ON e.id_especialidad=COALESCE(c.id_especialidad_reserva,m.id_especialidad)
        JOIN pacientes p ON p.id_paciente=c.id_paciente JOIN usuarios up ON up.id_usuario=p.id_usuario ";
    private const FILTRO_REGISTROS = " FROM pacientes p JOIN usuarios u ON u.id_usuario=p.id_usuario
        WHERE u.fecha_registro_usuario >= CAST(:fechaDesde AS date)::timestamp AT TIME ZONE 'America/La_Paz'
        AND u.fecha_registro_usuario < (CAST(:fechaHasta AS date)+INTERVAL '1 day') AT TIME ZONE 'America/La_Paz'";
    public function __construct(private readonly \PDO $conexionPostgresql)
    {
    }

    public function obtenerResumenCitas(array $filtros): array
    {
        [$where,$parametros] = $this->construirFiltrosCitas($filtros);
        $consulta = $this->conexionPostgresql->prepare("SELECT count(*) AS total,
            count(*) FILTER(WHERE c.estado_cita='pendiente') AS programadas,
            count(*) FILTER(WHERE c.estado_cita='confirmada') AS confirmadas,
            count(*) FILTER(WHERE c.estado_cita='atendida') AS atendidas,
            count(*) FILTER(WHERE c.estado_cita='cancelada') AS canceladas,
            count(*) FILTER(WHERE c.estado_cita='no_asistio') AS inasistencias,
            count(*) FILTER(WHERE c.estado_cita='reprogramada') AS reprogramadas" . self::ORIGEN_CITAS . $where);
        $consulta->execute($parametros);
        return array_map('intval', $consulta->fetch());
    }

    public function obtenerCitasAgrupadas(array $filtros, string $grupo): array
    {
        [$id,$etiqueta] = match ($grupo) {
            'medico'=>['m.id_medico',"concat_ws(' ',um.nombres_usuario,um.apellidos_usuario)"],
            'especialidad'=>['e.id_especialidad','e.nombre_especialidad'],
            'estado'=>['c.estado_cita','c.estado_cita'],
            default=>throw new \InvalidArgumentException('Agrupacion no disponible.'),
        };
        [$where,$parametros] = $this->construirFiltrosCitas($filtros);
        $consulta = $this->conexionPostgresql->prepare('SELECT ' . $id . ' AS identificador,' . $etiqueta . ' AS etiqueta,count(*) AS cantidad' . self::ORIGEN_CITAS . $where . ' GROUP BY ' . $id . ',' . $etiqueta . ' ORDER BY cantidad DESC,etiqueta,identificador');
        $consulta->execute($parametros);
        return array_map(static fn(array $fila): array=>['identificador' => $fila['identificador'],'etiqueta' => $fila['etiqueta'],'cantidad' => (int)$fila['cantidad']], $consulta->fetchAll());
    }

    public function listarCitasReporte(array $filtros): array
    {
        [$where,$parametros] = $this->construirFiltrosCitas($filtros);
        $consulta = $this->conexionPostgresql->prepare("SELECT c.id_cita,c.estado_cita,t.fecha_hora_inicio,t.fecha_hora_fin,e.nombre_especialidad,
            concat_ws(' ',um.nombres_usuario,um.apellidos_usuario) AS nombre_medico,concat_ws(' ',up.nombres_usuario,up.apellidos_usuario) AS nombre_paciente"
            . self::ORIGEN_CITAS . $where . ' ORDER BY t.fecha_hora_inicio,c.id_cita LIMIT 21 OFFSET :desplazamiento');
        foreach ($parametros as $campo => $valor) {
            $consulta->bindValue(':' . $campo, $valor);
        }
        $consulta->bindValue(':desplazamiento', ($filtros['paginaCitas'] - 1) * 20, \PDO::PARAM_INT);
        $consulta->execute();
        return array_map(static fn(array $fila): array=>['idCita' => (int)$fila['id_cita'],'estadoCita' => $fila['estado_cita'],'fechaHoraInicio' => $fila['fecha_hora_inicio'],'fechaHoraFin' => $fila['fecha_hora_fin'],'nombreMedico' => $fila['nombre_medico'],'nombrePaciente' => $fila['nombre_paciente'],'nombreEspecialidad' => $fila['nombre_especialidad']], $consulta->fetchAll());
    }

    public function contarPacientesRegistrados(array $filtros): int
    {
        $consulta = $this->conexionPostgresql->prepare('SELECT count(*)' . self::FILTRO_REGISTROS);
        $consulta->execute(['fechaDesde' => $filtros['fechaDesde'],'fechaHasta' => $filtros['fechaHasta']]);
        return (int)$consulta->fetchColumn();
    }

    public function listarPacientesRegistrados(array $filtros): array
    {
        $consulta = $this->conexionPostgresql->prepare("SELECT p.id_paciente,p.cedula_identidad,u.usuario_activo,concat_ws(' ',u.nombres_usuario,u.apellidos_usuario) AS nombre_paciente,
            (u.fecha_registro_usuario AT TIME ZONE 'America/La_Paz')::date AS fecha_registro" . self::FILTRO_REGISTROS . ' ORDER BY u.fecha_registro_usuario DESC,p.id_paciente DESC LIMIT 21 OFFSET :desplazamiento');
        $consulta->bindValue(':fechaDesde', $filtros['fechaDesde']);
        $consulta->bindValue(':fechaHasta', $filtros['fechaHasta']);
        $consulta->bindValue(':desplazamiento', ($filtros['paginaPacientes'] - 1) * 20, \PDO::PARAM_INT);
        $consulta->execute();
        return array_map(static fn(array $fila): array=>['idPaciente' => (int)$fila['id_paciente'],'nombrePaciente' => $fila['nombre_paciente'],'cedulaIdentidad' => $fila['cedula_identidad'],'fechaRegistro' => $fila['fecha_registro'],'pacienteActivo' => in_array($fila['usuario_activo'], [true,1,'1','t','true'], true)], $consulta->fetchAll());
    }

    public function listarEspecialidadesReporte(): array
    {
        return array_map(static fn(array $fila): array=>['idEspecialidad' => (int)$fila['id_especialidad'],'nombreEspecialidad' => $fila['nombre_especialidad']], $this->conexionPostgresql->query('SELECT id_especialidad,nombre_especialidad FROM especialidades ORDER BY nombre_especialidad,id_especialidad')->fetchAll());
    }

    private function construirFiltrosCitas(array $filtros): array
    {
        $condiciones = ['t.fecha_hora_inicio>=CAST(:fechaDesde AS date)',"t.fecha_hora_inicio<CAST(:fechaHasta AS date)+INTERVAL '1 day'"];
        $parametros = ['fechaDesde' => $filtros['fechaDesde'],'fechaHasta' => $filtros['fechaHasta']];
        foreach (['idMedico' => 'm.id_medico','idEspecialidad' => 'e.id_especialidad','estado' => 'c.estado_cita'] as $campo => $columna) {
            if ($filtros[$campo] !== null && $filtros[$campo] !== '') {
                $condiciones[] = $columna . '=:' . $campo;
                $parametros[$campo] = $filtros[$campo];
            }
        }
        return [' WHERE ' . implode(' AND ', $condiciones),$parametros];
    }
}
