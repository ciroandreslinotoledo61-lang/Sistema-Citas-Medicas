<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Services;
use CitasMedicas\Business\Validators\{RecetaValidator,ValidarCamposFormulario};
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\RecetaRepository;
use CitasMedicas\Shared\Config\CargarVariablesEntorno;
use CitasMedicas\Shared\Constants\{PermisosSistema,RolesSistema};
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class RecetaService
{
    private readonly RecetaRepository $recetas;
    private readonly Transaccion $transaccion;
    private readonly BitacoraService $bitacora;
    public function __construct(\PDO $conexion) { $this->recetas=new RecetaRepository($conexion);$this->transaccion=new Transaccion($conexion);$this->bitacora=new BitacoraService($conexion); }
    public function obtener(Usuario $u,int $id): array
    {
        PermisosSistema::exigirPermiso($u,'recetas.consultar');
        $r=$this->recetas->obtener($id,$u->idUsuario,$u->codigoRol===RolesSistema::PACIENTE)
            ??throw new ErrorAplicacion('Receta no disponible para tu cuenta.',404);
        $d=$r->obtenerDatos();$this->bitacora->registrarLecturaClinica($u,$d['idPaciente']);return $d;
    }
    public function listar(Usuario $u,array $p): array
    {
        PermisosSistema::exigirPermiso($u,'recetas.consultar');
        $f=ValidarCamposFormulario::validarParametrosBusqueda($p);
        $consulta=isset($p['idConsulta'])?ValidarCamposFormulario::validarIdentificador($p,'idConsulta'):null;
        $r=$this->recetas->listar($u->idUsuario,$u->codigoRol===RolesSistema::PACIENTE,$f,$consulta);
        // El listado no expone medicamentos; el detalle registra la lectura clínica.
        return ['elementos'=>array_map(static fn($r):array=>array_intersect_key($r->obtenerDatos(),array_flip(['idReceta','idConsulta','fechaEmision','estado'])),array_slice($r,0,20)),
            'pagina'=>$f['pagina'],'hayPaginaSiguiente'=>count($r)>20];
    }
    public function emitir(Usuario $u,array $p): array
    {
        PermisosSistema::exigirPermiso($u,'recetas.emitir');$d=RecetaValidator::validar($p);
        try { return $this->transaccion->ejecutar(function()use($u,$d):array{
            $this->bitacora->establecerUsuarioResponsable($u);
            $c=$this->recetas->obtenerContexto($d['idConsulta'],$u->idUsuario)??throw new ErrorAplicacion('Consulta no disponible para emitir recetas.',404);
            if($d['idRecetaAnterior']!==null){
                $anterior=$this->recetas->obtener($d['idRecetaAnterior'],$u->idUsuario,false,true)?->obtenerDatos();
                if(!$anterior||$anterior['idConsulta']!==$d['idConsulta']||$anterior['estado']!=='anulada')throw new ErrorAplicacion('Anula primero la receta anterior de esta consulta.',409);
            }
            $emision=['clinica'=>CargarVariablesEntorno::obtenerVariableEntorno('CLINICA_NOMBRE','Clínica Vida Salud'),
                'direccionClinica'=>CargarVariablesEntorno::obtenerVariableEntorno('CLINICA_DIRECCION'),
                'telefonoClinica'=>CargarVariablesEntorno::obtenerVariableEntorno('CLINICA_TELEFONO'),
                'paciente'=>$c['paciente'],'cedulaIdentidad'=>$c['cedula_identidad'],'medico'=>$c['medico'],'matricula'=>$c['matricula_profesional']];
            return $this->obtener($u,$this->recetas->registrar($d,$c,$emision));
        },Transaccion::LECTURA_CONFIRMADA); }
        catch(\PDOException $e){if(in_array((string)$e->getCode(),['23505','P2011','40P01'],true))throw new ErrorAplicacion('La receta cambió o la corrección ya fue emitida. Recarga la lista.',409);throw $e;}
    }
    public function anular(Usuario $u,int $id,array $p): array
    {
        PermisosSistema::exigirPermiso($u,'recetas.emitir');ValidarCamposFormulario::exigirCamposPermitidos($p,['motivo']);
        $motivo=ValidarCamposFormulario::validarTexto($p,'motivo','El motivo de anulación',500);
        return $this->transaccion->ejecutar(function()use($u,$id,$motivo):array{
            $r=$this->recetas->obtener($id,$u->idUsuario,false,true)?->obtenerDatos()??throw new ErrorAplicacion('Receta no disponible.',404);
            if($r['estado']!=='emitida')throw new ErrorAplicacion('La receta ya fue anulada.',409);
            $this->recetas->anular($id,$u->idUsuario,$motivo);return $this->obtener($u,$id);
        });
    }
}
