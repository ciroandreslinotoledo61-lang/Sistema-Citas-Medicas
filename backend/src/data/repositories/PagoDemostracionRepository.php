<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Repositories;
use CitasMedicas\Data\Models\PagoDemostracion;
final class PagoDemostracionRepository
{
    private const SELECCION='SELECT pd.* FROM pagos_demostracion pd JOIN citas c USING(id_cita) JOIN pacientes p USING(id_paciente)';
    public function __construct(private readonly \PDO $conexion) {}
    public function obtener(int $id,?int $usuario,bool $bloquear=false): ?PagoDemostracion
    {
        $q=$this->conexion->prepare(self::SELECCION.' WHERE pd.id_pago=:id'.($usuario!==null?' AND p.id_usuario=:usuario':'').($bloquear?' FOR UPDATE OF pd':''));
        $p=['id'=>$id];if($usuario!==null)$p['usuario']=$usuario;$q->execute($p);$r=$q->fetch();return $r?new PagoDemostracion($r):null;
    }
    public function listar(?int $usuario,int $pagina): array
    {
        $q=$this->conexion->prepare(self::SELECCION.($usuario!==null?' WHERE p.id_usuario=:usuario':'').' ORDER BY pd.id_pago DESC LIMIT 21 OFFSET :offset');
        if($usuario!==null)$q->bindValue(':usuario',$usuario,\PDO::PARAM_INT);$q->bindValue(':offset',($pagina-1)*20,\PDO::PARAM_INT);$q->execute();
        return array_map(static fn(array $r):PagoDemostracion=>new PagoDemostracion($r),$q->fetchAll());
    }
    public function activo(int $cita): ?PagoDemostracion
    {
        $q=$this->conexion->prepare("SELECT * FROM pagos_demostracion WHERE id_cita=:cita AND estado IN ('pendiente','pagado') FOR UPDATE");$q->execute(compact('cita'));$r=$q->fetch();return $r?new PagoDemostracion($r):null;
    }
    public function crear(int $cita,int $usuario,int $centavos,string $metodo,string $referencia): int
    {
        $q=$this->conexion->prepare('INSERT INTO pagos_demostracion(id_cita,creado_por,monto_centavos,metodo,referencia) VALUES(:cita,:usuario,:centavos,:metodo,:referencia) RETURNING id_pago');$q->execute(compact('cita','usuario','centavos','metodo','referencia'));return (int)$q->fetchColumn();
    }
    public function finalizar(int $id,bool $confirmar): void
    {
        $q=$this->conexion->prepare('UPDATE pagos_demostracion SET estado=:estado,'.($confirmar?'fecha_confirmacion':'fecha_cancelacion')."=clock_timestamp() WHERE id_pago=:id AND estado='pendiente'");$q->execute(['id'=>$id,'estado'=>$confirmar?'pagado':'cancelado']);
    }
    public function reporte(string $desde,string $hasta): array
    {
        $q=$this->conexion->prepare("SELECT metodo,count(*) AS cantidad,coalesce(sum(monto_centavos),0)::text AS total_centavos FROM pagos_demostracion
            WHERE estado='pagado' AND es_simulado AND fecha_confirmacion>=CAST(:desde AS date) AND fecha_confirmacion<CAST(:hasta AS date)+INTERVAL '1 day' GROUP BY metodo ORDER BY metodo");$q->execute(compact('desde','hasta'));return $q->fetchAll();
    }
}
