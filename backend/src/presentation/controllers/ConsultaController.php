<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Controllers;
use CitasMedicas\Business\Services\{ConsultaService,HistoriaClinicaService};
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Utils\{RespuestaJson,SolicitudHttp};
final class ConsultaController {
 public function __construct(private readonly ConsultaService $consultas,private readonly HistoriaClinicaService $historias){}
 public function listarConsultasMedico(Usuario $usuario):never{RespuestaJson::enviarRespuestaJson(['datos'=>$this->consultas->listarConsultasMedico($usuario,$_GET)]);}
 public function registrarConsulta(Usuario $usuario):never{RespuestaJson::enviarRespuestaJson(['mensaje'=>'Consulta registrada. La cita esta atendida.','datos'=>$this->consultas->registrarConsulta($usuario,SolicitudHttp::obtenerCuerpoJson())],201);}
 public function obtenerConsulta(Usuario $usuario,int $idConsulta):never{RespuestaJson::enviarRespuestaJson(['datos'=>$this->consultas->obtenerConsulta($usuario,$idConsulta)]);}
 public function actualizarConsulta(Usuario $usuario,int $idConsulta):never{RespuestaJson::enviarRespuestaJson(['mensaje'=>'Consulta actualizada; version anterior conservada.','datos'=>$this->consultas->actualizarConsulta($usuario,$idConsulta,SolicitudHttp::obtenerCuerpoJson())]);}
 public function obtenerMiHistorial(Usuario $usuario):never{RespuestaJson::enviarRespuestaJson(['datos'=>$this->consultas->obtenerHistorial($usuario,null,$_GET)]);}
 public function obtenerHistorialPaciente(Usuario $usuario,int $idPaciente):never{RespuestaJson::enviarRespuestaJson(['datos'=>$this->consultas->obtenerHistorial($usuario,$idPaciente,$_GET)]);}
 public function actualizarHistoria(Usuario $usuario,int $idPaciente):never{RespuestaJson::enviarRespuestaJson(['mensaje'=>'Antecedentes actualizados.','datos'=>$this->historias->actualizarHistoria($usuario,$idPaciente,SolicitudHttp::obtenerCuerpoJson())]);}
}
