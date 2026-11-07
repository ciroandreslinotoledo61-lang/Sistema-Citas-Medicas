<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Services;
use CitasMedicas\Business\Validators\{SalaEsperaValidator,ValidarCamposFormulario};
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\{CitaRepository,SalaEsperaRepository};
use CitasMedicas\Shared\Constants\{PermisosSistema,RolesSistema};
use CitasMedicas\Shared\Errors\ErrorAplicacion;
final class SalaEsperaService
{
    private readonly SalaEsperaRepository $sala;private readonly CitaRepository $citas;private readonly Transaccion $transaccion;
    public function __construct(\PDO $conexion,private readonly ?\DateTimeImmutable $ahora=null){$this->sala=new SalaEsperaRepository($conexion);$this->citas=new CitaRepository($conexion);$this->transaccion=new Transaccion($conexion);}
    public function listar(Usuario $u,array $p): array
    {
        PermisosSistema::exigirPermiso($u,'sala.consultar');$f=ValidarCamposFormulario::validarParametrosBusqueda($p);
        $fecha=ValidarCamposFormulario::validarFecha($p['fecha']??($this->ahora??new \DateTimeImmutable())->format('Y-m-d'));
        $filas=$this->sala->listar($fecha,$f['pagina'],$u->codigoRol===RolesSistema::MEDICO?$u->idUsuario:null);
        return ['elementos'=>array_map(static fn($fila):array=>$fila->obtenerDatos(),array_slice($filas,0,20)),'pagina'=>$f['pagina'],'hayPaginaSiguiente'=>count($filas)>20];
    }
    public function historial(Usuario $u,int $id): array
    {
        PermisosSistema::exigirPermiso($u,'sala.consultar');$this->exigirCita($u,$id,false);return $this->sala->eventos($id);
    }
    private function exigirCita(Usuario $u,int $id,bool $bloquear): array
    {
        return $this->citas->obtenerCitaPorId($id,$bloquear,null,$u->codigoRol===RolesSistema::MEDICO?$u->idUsuario:null)?->obtenerDatosPublicosCita()
            ??throw new ErrorAplicacion('Cita no disponible para tu cuenta.',404);
    }
    public function cambiar(Usuario $u,int $id,array $p): array
    {
        $d=SalaEsperaValidator::validar($p);PermisosSistema::exigirPermiso($u,$d['accion']==='llegada'?'sala.llegada':'sala.atender');
        try{return $this->transaccion->ejecutar(function()use($u,$id,$d):array{
            $c=$this->exigirCita($u,$id,true);$s=$this->sala->obtenerEstado($id);
            if($c['estadoCita']!=='programada'||(int)($s['version_espera']??0)!==$d['version'])throw new ErrorAplicacion('La cita o la sala cambió. Actualiza antes de continuar.',409);
            $estado=$s['estado']??'pendiente_llegada';
            $origen=['llegada'=>'pendiente_llegada','iniciar'=>'en_espera','finalizar'=>'en_consulta'][$d['accion']];
            if($estado!==$origen)throw new ErrorAplicacion('Ese cambio de estado no está permitido.',409);
            if($d['accion']!=='llegada'&&new \DateTimeImmutable($c['fechaHoraInicio'])>($this->ahora??new \DateTimeImmutable()))throw new ErrorAplicacion('La atención comienza desde el inicio de la cita.',409);
            if($d['accion']==='llegada')$this->sala->llegada($id,$u->idUsuario);
            elseif($d['accion']==='iniciar')$this->sala->iniciar($id,$u->idUsuario);
            else $this->citas->registrarAsistencia($id,'asistio','atendida',$u->idUsuario);
            // El trigger sincroniza también la finalización desde consultas o citas.
            $actual=$this->sala->obtenerEstado($id);return ['idCita'=>$id,'estado'=>$actual['estado'],'version'=>(int)$actual['version_espera']];
        },Transaccion::LECTURA_CONFIRMADA);}catch(\PDOException $e){if(in_array((string)$e->getCode(),['23505','P2012','40P01'],true))throw new ErrorAplicacion('La sala cambió durante la operación. Recarga.',409);throw $e;}
    }
}
