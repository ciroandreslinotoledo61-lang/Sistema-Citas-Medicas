<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Models;
final class EstadoSala
{
    public function __construct(private readonly array $registro) {}
    public function obtenerDatos(): array
    {
        $r=$this->registro;
        return ['idCita'=>(int)$r['id_cita'],'nombrePaciente'=>$r['nombre_paciente'],'nombreMedico'=>$r['nombre_medico'],
            'fechaHoraInicio'=>$r['fecha_hora_inicio'],'estadoCita'=>$r['estado_cita'],'estado'=>$r['estado'],
            'version'=>(int)$r['version_espera']];
    }
}
