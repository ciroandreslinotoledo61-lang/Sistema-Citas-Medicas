<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Services;
use CitasMedicas\Business\Validators\ConsultaValidator;
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\{CitaRepository,ConsultaRepository,TratamientoRepository};
use CitasMedicas\Shared\Constants\{PermisosSistema,RolesSistema};
use CitasMedicas\Shared\Errors\ErrorAplicacion;
final class ConsultaService {
 private readonly CitaRepository $citas;private readonly ConsultaRepository $consultas;private readonly TratamientoRepository $tratamientos;
 private readonly HistoriaClinicaService $historias;private readonly BitacoraService $bitacora;private readonly Transaccion $transaccion;
 public function __construct(\PDO $conexionPostgresql,private readonly ?\DateTimeImmutable $momentoReferencia=null){
  $this->citas=new CitaRepository($conexionPostgresql);$this->consultas=new ConsultaRepository($conexionPostgresql);$this->tratamientos=new TratamientoRepository($conexionPostgresql);
  $this->historias=new HistoriaClinicaService($conexionPostgresql);$this->bitacora=new BitacoraService($conexionPostgresql);$this->transaccion=new Transaccion($conexionPostgresql);
 }
 public function registrarConsulta(Usuario $usuario,array $datos):array {
  PermisosSistema::exigirPermiso($usuario,'consultas.escribir');$d=ConsultaValidator::validarConsulta($datos);
  return $this->ejecutarTransaccion(function()use($usuario,$d):array{
   $this->bitacora->establecerUsuarioResponsable($usuario);$cita=$this->citas->obtenerCitaPorId($d['idCita'],true);
   if(!$cita||!$cita->perteneceAlMedico($usuario->idUsuario))throw new ErrorAplicacion('Cita no disponible para tu cuenta.',404);$c=$cita->obtenerDatosPublicosCita();
   if(!in_array($c['estadoCita'],['programada','atendida'],true)||new \DateTimeImmutable($c['fechaHoraInicio'])>($this->momentoReferencia??new \DateTimeImmutable()))throw new ErrorAplicacion('Registra la consulta desde el inicio de una cita valida.',409);
   if($this->consultas->obtenerIdConsultaPorCita($d['idCita'])!==null)throw new ErrorAplicacion('La cita ya tiene consulta. Abre el registro existente.',409);
   $h=$this->historias->exigirHistoriaAutorizada($usuario,$c['idPaciente']);$id=$this->consultas->registrarConsulta($d['idCita'],$h->idHistoriaClinica,$d,$usuario->idUsuario);$this->tratamientos->guardarTratamientosConsulta($id,$d['tratamientos']);
   if($c['estadoCita']==='programada')$this->citas->registrarAsistencia($d['idCita'],'asistio','atendida',$usuario->idUsuario);
   return $this->obtenerConsulta($usuario,$id,false);
  });
 }
 public function actualizarConsulta(Usuario $usuario,int $idConsulta,array $datos):array {
  PermisosSistema::exigirPermiso($usuario,'consultas.escribir');$d=ConsultaValidator::validarConsulta($datos,true);
  return $this->ejecutarTransaccion(function()use($usuario,$idConsulta,$d):array{
   $this->bitacora->establecerUsuarioResponsable($usuario);$c=$this->consultas->obtenerConsultaPorId($idConsulta,true)?->obtenerDatosConsulta();
   if(!$c||$c['idUsuarioMedico']!==$usuario->idUsuario)throw new ErrorAplicacion('Consulta no disponible para edicion.',404);
   if(!$this->consultas->actualizarConsulta($idConsulta,$d,$usuario->idUsuario))throw new ErrorAplicacion('La consulta cambio. Recarga antes de guardar.',409);
   $this->tratamientos->guardarTratamientosConsulta($idConsulta,$d['tratamientos']);return $this->obtenerConsulta($usuario,$idConsulta,false);
  });
 }
 public function obtenerConsulta(Usuario $usuario,int $idConsulta,bool $registrarLectura=true):array {
  PermisosSistema::exigirPermiso($usuario,'historial.consultar');$d=$this->consultas->obtenerConsultaPorId($idConsulta)?->obtenerDatosConsulta()??throw new ErrorAplicacion('Consulta no disponible.',404);
  $this->historias->exigirHistoriaAutorizada($usuario,$d['idPaciente']);$d['tratamientos']=array_map(static fn($t):array=>$t->obtenerDatosTratamiento(),$this->tratamientos->listarTratamientosConsulta($idConsulta));
  $d['puedeEditar']=$usuario->codigoRol===RolesSistema::MEDICO&&$d['idUsuarioMedico']===$usuario->idUsuario;
  if($registrarLectura)$this->bitacora->registrarLecturaClinica($usuario,$d['idPaciente']);return $d;
 }
 public function listarConsultasMedico(Usuario $usuario,array $datos):array {
  PermisosSistema::exigirPermiso($usuario,'consultas.escribir');$f=ConsultaValidator::validarFiltrosConsultas($datos);return $this->prepararListado($this->consultas->listarConsultas($f,$usuario->idUsuario),$f['pagina']);
 }
 public function obtenerHistorial(Usuario $usuario,?int $idPaciente,array $datos):array {
  $idPaciente??=$this->historias->obtenerIdPacientePropio($usuario);$h=$this->historias->exigirHistoriaAutorizada($usuario,$idPaciente);$f=ConsultaValidator::validarFiltrosConsultas($datos);
  $filas=$this->consultas->listarConsultas($f,null,$idPaciente);$this->bitacora->registrarLecturaClinica($usuario,$idPaciente);
  return ['historia'=>$h->obtenerDatosHistoria(),'consultas'=>$this->prepararListado($filas,$f['pagina']),'puedeEditarHistoria'=>$usuario->codigoRol===RolesSistema::MEDICO];
 }
 private function prepararListado(array $filas,int $pagina):array {
  return ['elementos'=>array_map(static fn($c):array=>array_intersect_key($c->obtenerDatosConsulta(),array_flip(['idConsulta','idCita','idPaciente','nombrePaciente','nombreMedico','fechaAtencion','versionConsulta'])),array_slice($filas,0,20)),'pagina'=>$pagina,'hayPaginaSiguiente'=>count($filas)>20];
 }
 private function ejecutarTransaccion(callable $operacion):array {
  try{return $this->transaccion->ejecutar($operacion,Transaccion::LECTURA_CONFIRMADA);}catch(\PDOException $e){if(in_array((string)$e->getCode(),['23505','P2003','40P01','40001'],true))throw new ErrorAplicacion('El registro cambio o ya existe. Recarga e intenta nuevamente.',409);throw $e;}
 }
}
