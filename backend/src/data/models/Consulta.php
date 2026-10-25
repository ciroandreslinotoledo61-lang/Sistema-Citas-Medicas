<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Models;
final class Consulta {
 public function __construct(private readonly array $registro){}
 public function obtenerDatosConsulta():array {
  $r=$this->registro;return ['idConsulta'=>(int)$r['id_consulta'],'idCita'=>(int)$r['id_cita'],'idPaciente'=>(int)$r['id_paciente'],
   'idUsuarioMedico'=>(int)$r['id_usuario_medico'],'nombrePaciente'=>$r['nombre_paciente'],'nombreMedico'=>$r['nombre_medico'],
   'fechaAtencion'=>$r['fecha_hora_inicio'],'fechaRegistro'=>$r['fecha_registro_consulta'],'motivoConsulta'=>$r['motivo_consulta'],
   'diagnostico'=>$r['diagnostico_consulta'],'observaciones'=>$r['observaciones_consulta'],'versionConsulta'=>(int)$r['version_consulta'],
   'fechaActualizacion'=>$r['fecha_actualizacion_consulta'],'versionesAnteriores'=>is_array($r['versiones_anteriores'])?$r['versiones_anteriores']:json_decode($r['versiones_anteriores'],true,512,JSON_THROW_ON_ERROR)];
 }
}
