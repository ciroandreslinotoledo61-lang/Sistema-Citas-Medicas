<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Models;

final class HorarioMedico
{
    public function __construct(private readonly array $registro)
    {
    }

    public function obtenerDatosPublicosHorario(): array
    {
        return [
            'idHorarioMedico' => (int) $this->registro['id_horario_medico'],
            'idMedico' => (int) $this->registro['id_medico'],
            'nombreMedico' => $this->registro['nombre_medico'],
            'fechaAtencion' => $this->registro['fecha_atencion'],
            'horaInicio' => substr($this->registro['hora_inicio'], 0, 5),
            'horaFin' => substr($this->registro['hora_fin'], 0, 5),
            'duracionCitaMinutos' => (int) $this->registro['duracion_cita_minutos'],
            'cantidadTurnos' => (int) $this->registro['cantidad_turnos'],
        ];
    }
}
