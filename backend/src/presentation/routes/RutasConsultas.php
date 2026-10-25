<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Routes;
use CitasMedicas\Presentation\Controllers\ConsultaController;
use CitasMedicas\Presentation\Http\RutaHttp;
final class RutasConsultas {public static function definir():array{return [
 new RutaHttp('GET','/api/consultas',ConsultaController::class,'listarConsultasMedico',['medico'],['usuario']),
 new RutaHttp('POST','/api/consultas',ConsultaController::class,'registrarConsulta',['medico'],['usuario']),
 new RutaHttp('GET','/api/consultas/{idConsulta}',ConsultaController::class,'obtenerConsulta',['medico','paciente'],['usuario','idConsulta']),
 new RutaHttp('PATCH','/api/consultas/{idConsulta}',ConsultaController::class,'actualizarConsulta',['medico'],['usuario','idConsulta']),
 new RutaHttp('GET','/api/historias-clinicas/mi-historial',ConsultaController::class,'obtenerMiHistorial',['paciente'],['usuario']),
 new RutaHttp('GET','/api/historias-clinicas/{idPaciente}',ConsultaController::class,'obtenerHistorialPaciente',['medico','paciente'],['usuario','idPaciente']),
 new RutaHttp('PATCH','/api/historias-clinicas/{idPaciente}',ConsultaController::class,'actualizarHistoria',['medico'],['usuario','idPaciente'])];}}
