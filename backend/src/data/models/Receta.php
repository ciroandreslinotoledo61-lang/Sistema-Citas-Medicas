<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Models;
final class Receta
{
    public function __construct(private readonly array $registro) {}
    public function obtenerDatos(): array
    {
        $r=$this->registro;
        return ['idReceta'=>(int)$r['id_receta'],'idConsulta'=>(int)$r['id_consulta'],
            'idPaciente'=>(int)$r['id_paciente'],'idMedico'=>(int)$r['id_medico'],
            'idRecetaAnterior'=>$r['id_receta_anterior']===null?null:(int)$r['id_receta_anterior'],
            'datosEmision'=>json_decode($r['datos_emision'],true,512,JSON_THROW_ON_ERROR),
            'medicamentos'=>json_decode($r['medicamentos'],true,512,JSON_THROW_ON_ERROR),
            'fechaEmision'=>$r['fecha_emision'],'estado'=>$r['estado'],'motivoAnulacion'=>$r['motivo_anulacion'],
            'fechaAnulacion'=>$r['fecha_anulacion']];
    }
}
