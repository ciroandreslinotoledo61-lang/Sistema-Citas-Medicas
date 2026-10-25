<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Services;
use CitasMedicas\Business\Validators\BitacoraValidator;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\BitacoraRepository;
use CitasMedicas\Shared\Constants\PermisosSistema;
final class BitacoraService {
 private readonly BitacoraRepository $bitacoraRepository;
 public function __construct(\PDO $conexionPostgresql){$this->bitacoraRepository=new BitacoraRepository($conexionPostgresql);}
 public function establecerUsuarioResponsable(?Usuario $usuario):void{$this->bitacoraRepository->establecerUsuarioResponsable($usuario?->idUsuario);}
 public function registrarEventoAutenticacion(?Usuario $usuario,string $accion,string $resultado):void{$this->bitacoraRepository->registrarEvento($usuario?->idUsuario,'autenticacion',$accion,$usuario?->idUsuario,$resultado,'Operacion de autenticacion.');}
 public function registrarLecturaClinica(Usuario $usuario,int $idPaciente):void{$this->bitacoraRepository->registrarEvento($usuario->idUsuario,'historias_clinicas','consultar',$idPaciente,'exito','Acceso autorizado al historial clinico.');}
 public function listarBitacora(Usuario $usuario,array $datos):array {
  PermisosSistema::exigirPermiso($usuario,'bitacora.consultar');$f=BitacoraValidator::validarFiltrosBitacora($datos);$r=$this->bitacoraRepository->listarEventos($f);
  return ['elementos'=>array_map(static fn($e):array=>$e->obtenerDatosEvento(),array_slice($r,0,20)),'pagina'=>$f['pagina'],'hayPaginaSiguiente'=>count($r)>20];
 }
 public function obtenerFiltrosBitacora(Usuario $usuario):array {PermisosSistema::exigirPermiso($usuario,'bitacora.consultar');return $this->bitacoraRepository->obtenerCatalogosFiltros();}
}
