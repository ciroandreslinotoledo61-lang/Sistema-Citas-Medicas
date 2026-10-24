<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Models;

final class Cita
{
    public function __construct(private readonly array $registro)
    {
    }

    public function perteneceAlPaciente(int $idUsuario): bool
    {
        return (int) $this->registro['id_usuario_paciente'] === $idUsuario;
    }

    public function perteneceAlMedico(int $idUsuario): bool
    {
        return (int) $this->registro['id_usuario_medico'] === $idUsuario;
    }

    public function obtenerDatosPublicosCita(): array
    {
        $registro = $this->registro;
        return [
            'idCita' => (int) $registro['id_cita'], 'idPaciente' => (int) $registro['id_paciente'],
            'idTurno' => (int) $registro['id_turno'], 'idMedico' => (int) $registro['id_medico'],
            'nombrePaciente' => $registro['nombre_paciente'], 'cedulaIdentidad' => $registro['cedula_identidad'],
            'fechaNacimiento' => $registro['fecha_nacimiento'], 'nombreMedico' => $registro['nombre_medico'],
            'nombreEspecialidad' => $registro['nombre_especialidad'],
            'fechaHoraInicio' => $registro['fecha_hora_inicio'], 'fechaHoraFin' => $registro['fecha_hora_fin'],
            'estadoCita' => $registro['estado_cita'], 'motivoCita' => $registro['motivo_cita'],
            'asistenciaRegistrada' => $registro['asistencia_registrada'],
            'motivoCancelacion' => $registro['motivo_cancelacion'],
            'historialCambios' => is_array($registro['historial_cambios'] ?? null) ? $registro['historial_cambios'] : json_decode($registro['historial_cambios'] ?? '[]', true, 512, JSON_THROW_ON_ERROR),
            'idCitaOrigen' => $registro['id_cita_origen'] === null ? null : (int) $registro['id_cita_origen'],
        ];
    }
}
