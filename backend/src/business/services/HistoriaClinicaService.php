<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Services;
use CitasMedicas\Business\Validators\ConsultaValidator;
use CitasMedicas\Data\Models\{Usuario,HistoriaClinica};
use CitasMedicas\Data\Repositories\{HistoriaClinicaRepository,PacienteRepository};
use CitasMedicas\Shared\Constants\{PermisosSistema,RolesSistema};
use CitasMedicas\Shared\Errors\ErrorAplicacion;
final class HistoriaClinicaService {
 private readonly HistoriaClinicaRepository $historias;private readonly PacienteRepository $pacientes;private readonly BitacoraService $bitacora;
 public function __construct(\PDO $conexionPostgresql){$this->historias=new HistoriaClinicaRepository($conexionPostgresql);$this->pacientes=new PacienteRepository($conexionPostgresql);$this->bitacora=new BitacoraService($conexionPostgresql);}
 public function obtenerIdPacientePropio(Usuario $usuario):int {
  if($usuario->codigoRol!==RolesSistema::PACIENTE)throw new ErrorAplicacion('Acceso restringido.',403);
  return $this->pacientes->obtenerIdPacientePorUsuario($usuario->idUsuario)??throw new ErrorAplicacion('Perfil no disponible.',404);
 }
 public function exigirHistoriaAutorizada(Usuario $usuario,int $idPaciente):HistoriaClinica {
  PermisosSistema::exigirPermiso($usuario,'historial.consultar');
  $ok=$usuario->codigoRol===RolesSistema::PACIENTE?$this->obtenerIdPacientePropio($usuario)===$idPaciente:$this->historias->medicoTienePacienteAsignado($usuario->idUsuario,$idPaciente);
  if(!$ok)throw new ErrorAplicacion('Historia no disponible para tu cuenta.',404);
  return $this->historias->obtenerHistoriaPorPaciente($idPaciente)??throw new ErrorAplicacion('Historia no disponible.',404);
 }
 public function actualizarHistoria(Usuario $usuario,int $idPaciente,array $datos):array {
  PermisosSistema::exigirPermiso($usuario,'consultas.escribir');$this->exigirHistoriaAutorizada($usuario,$idPaciente);$d=ConsultaValidator::validarHistoria($datos);$this->bitacora->establecerUsuarioResponsable($usuario);
  if(!$this->historias->actualizarAntecedentes($idPaciente,$d))throw new ErrorAplicacion('La historia cambio. Recarga antes de guardar.',409);
  return $this->exigirHistoriaAutorizada($usuario,$idPaciente)->obtenerDatosHistoria();
 }
}
