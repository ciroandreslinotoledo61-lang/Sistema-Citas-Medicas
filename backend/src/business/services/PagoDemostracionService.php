<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Services;
use CitasMedicas\Business\Validators\ValidarCamposFormulario;
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\{CitaRepository,PagoDemostracionRepository};
use CitasMedicas\Shared\Config\ConfiguracionPagosDemo;
use CitasMedicas\Shared\Constants\{PermisosSistema,RolesSistema};
use CitasMedicas\Shared\Errors\ErrorAplicacion;
final class PagoDemostracionService
{
    private readonly PagoDemostracionRepository $pagos;private readonly CitaRepository $citas;private readonly Transaccion $transaccion;
    public function __construct(\PDO $conexion){$this->pagos=new PagoDemostracionRepository($conexion);$this->citas=new CitaRepository($conexion);$this->transaccion=new Transaccion($conexion);}
    public function configuracion(Usuario $u): array
    {
        PermisosSistema::exigirPermiso($u,'pagos.consultar');$centavos=ConfiguracionPagosDemo::obtenerCentavos();
        return ['habilitado'=>$centavos!==null,'montoCentavos'=>$centavos,'moneda'=>'BOB','esSimulado'=>true];
    }
    public function listar(Usuario $u,array $p): array
    {
        PermisosSistema::exigirPermiso($u,'pagos.consultar');$f=ValidarCamposFormulario::validarParametrosBusqueda($p);
        $r=$this->pagos->listar($u->codigoRol===RolesSistema::PACIENTE?$u->idUsuario:null,$f['pagina']);
        return ['elementos'=>array_map(static fn($p):array=>$p->obtenerDatos(),array_slice($r,0,20)),'pagina'=>$f['pagina'],'hayPaginaSiguiente'=>count($r)>20];
    }
    public function obtener(Usuario $u,int $id): array
    {
        PermisosSistema::exigirPermiso($u,'pagos.consultar');return $this->pagos->obtener($id,$u->codigoRol===RolesSistema::PACIENTE?$u->idUsuario:null)?->obtenerDatos()??throw new ErrorAplicacion('Pago no disponible.',404);
    }
    private function bloquearCitaPropia(Usuario $u,int $id): array
    {
        return $this->citas->obtenerCitaPorId($id,true,$u->idUsuario,null)?->obtenerDatosPublicosCita()??throw new ErrorAplicacion('Cita no disponible para tu cuenta.',404);
    }
    public function crear(Usuario $u,array $p): array
    {
        PermisosSistema::exigirPermiso($u,'pagos.simular');ValidarCamposFormulario::exigirCamposPermitidos($p,['idCita','metodo']);
        $id=ValidarCamposFormulario::validarIdentificador($p,'idCita');$metodo=$p['metodo']??null;
        if(!is_string($metodo)||!in_array($metodo,['qr_demo','paypal_demo'],true))throw new ErrorAplicacion('Selecciona QR o PayPal de demostración.',422);
        $monto=ConfiguracionPagosDemo::obtenerCentavos()??throw new ErrorAplicacion('No hay tarifa de demostración configurada. No se realizará ningún cobro.',409);
        return $this->transaccion->ejecutar(function()use($u,$id,$metodo,$monto):array{
            $c=$this->bloquearCitaPropia($u,$id);if(!in_array($c['estadoCita'],['programada','atendida'],true))throw new ErrorAplicacion('La cita no admite pagos simulados.',409);
            $activo=$this->pagos->activo($id)?->obtenerDatos();
            if($activo){if($activo['estado']==='pendiente')return $activo;throw new ErrorAplicacion('Esta cita ya tiene un pago simulado confirmado.',409);}
            return $this->obtener($u,$this->pagos->crear($id,$u->idUsuario,$monto,$metodo,'DEMO-'.bin2hex(random_bytes(16))));
        },Transaccion::LECTURA_CONFIRMADA);
    }
    public function finalizar(Usuario $u,int $id,array $p): array
    {
        PermisosSistema::exigirPermiso($u,'pagos.simular');ValidarCamposFormulario::exigirCamposPermitidos($p,['accion','confirmarSimulacion']);
        $accion=$p['accion']??null;
        if(!in_array($accion,['confirmar','cancelar'],true)||($p['confirmarSimulacion']??null)!==true)throw new ErrorAplicacion('Confirma que esta operación es exclusivamente simulada.',422);
        return $this->transaccion->ejecutar(function()use($u,$id,$accion):array{
            $inicial=$this->pagos->obtener($id,$u->idUsuario)?->obtenerDatos()??throw new ErrorAplicacion('Pago no disponible.',404);
            $c=$this->bloquearCitaPropia($u,$inicial['idCita']);
            $actual=$this->pagos->obtener($id,$u->idUsuario,true)?->obtenerDatos();
            if($actual['estado']!=='pendiente')throw new ErrorAplicacion('El pago ya fue finalizado; no puede confirmarse dos veces.',409);
            if($accion==='confirmar'&&!in_array($c['estadoCita'],['programada','atendida'],true))throw new ErrorAplicacion('La cita ya no permite confirmar el pago simulado.',409);
            $this->pagos->finalizar($id,$accion==='confirmar');return $this->obtener($u,$id);
        },Transaccion::LECTURA_CONFIRMADA);
    }
    public function reporte(Usuario $u,array $p): array
    {
        PermisosSistema::exigirPermiso($u,'pagos.reportar');
        $desde=ValidarCamposFormulario::validarFecha($p['fechaDesde']??date('Y-m-01'));$hasta=ValidarCamposFormulario::validarFecha($p['fechaHasta']??date('Y-m-d'));
        if($desde>$hasta)throw new ErrorAplicacion('El rango de fechas está invertido.',422);
        return ['esSimulado'=>true,'moneda'=>'BOB','fechaDesde'=>$desde,'fechaHasta'=>$hasta,'elementos'=>$this->pagos->reporte($desde,$hasta)];
    }
}
