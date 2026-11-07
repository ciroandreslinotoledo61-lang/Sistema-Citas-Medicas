<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Services;
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Repositories\{CitasVencidasRepository,BitacoraRepository};
use CitasMedicas\Shared\Config\{ConfiguracionCitasVencidas,ConfiguracionAgenda};

/** Exclusivo de la tarea CLI. No se invoca desde páginas ni rutas HTTP. */
final class CitasVencidasService
{
    private readonly CitasVencidasRepository $citas;
    private readonly BitacoraRepository $bitacora;
    private readonly Transaccion $transaccion;

    public function __construct(\PDO $conexion,private readonly ?\DateTimeImmutable $momentoReferencia=null)
    {
        $this->citas=new CitasVencidasRepository($conexion);
        $this->bitacora=new BitacoraRepository($conexion);
        $this->transaccion=new Transaccion($conexion);
    }

    public function revisar(int $lote=200): array
    {
        if ($lote<1 || $lote>1000) throw new \InvalidArgumentException('El lote debe estar entre 1 y 1000.');
        $tolerancia=ConfiguracionCitasVencidas::obtenerToleranciaMinutos();
        $this->citas->exigirEsquemaDisponible();
        return $this->transaccion->ejecutar(function()use($lote,$tolerancia):array{
            $resultado=['noAsistieron'=>0,'requierenRevision'=>0,'ocupado'=>false,'loteCompleto'=>false,'toleranciaMinutos'=>$tolerancia];
            if (!$this->citas->intentarBloqueo()) { $resultado['ocupado']=true;return $resultado; }
            $zona=new \DateTimeZone(ConfiguracionAgenda::ZONA_HORARIA_BOLIVIA);
            $ahora=$this->momentoReferencia?->setTimezone($zona)??new \DateTimeImmutable($this->citas->obtenerHoraBolivia(),$zona);
            $limite=$ahora->modify('-'.$tolerancia.' minutes')->format('Y-m-d H:i:s.u');
            $this->bitacora->establecerUsuarioResponsable(null);
            $filas=$this->citas->seleccionarVencidas($limite,$lote);
            foreach($filas as $fila){
                $id=(int)$fila['id_cita'];
                if(in_array($fila['llegada_registrada'],[true,1,'1','t','true'],true)){
                    $this->bitacora->registrarEvento(null,'citas','revision_paciente_presente',$id,'exito','Sistema: paciente con llegada registrada y atención pendiente. Requiere revisión administrativa; no se atribuye ausencia al paciente.');
                    $resultado['requierenRevision']++;
                }elseif($this->citas->marcarAusenciaAutomatica($id,$limite)){
                    $this->bitacora->registrarEvento(null,'citas','inasistencia_automatica',$id,'exito','Sistema: cierre automático sin llegada ni consulta, tras finalizar el turno y '.$tolerancia.' minutos de tolerancia.');
                    $resultado['noAsistieron']++;
                }
            }
            $resultado['loteCompleto']=count($filas)===$lote;
            return $resultado;
        },Transaccion::LECTURA_CONFIRMADA);
    }
}
