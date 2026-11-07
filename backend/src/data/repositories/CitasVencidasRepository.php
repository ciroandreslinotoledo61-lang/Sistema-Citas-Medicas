<?php
declare(strict_types=1);
namespace CitasMedicas\Data\Repositories;

final class CitasVencidasRepository
{
    public function __construct(private readonly \PDO $conexion) {}

    public function exigirEsquemaDisponible(): void
    {
        $r = $this->conexion->query("SELECT to_regclass('public.sala_espera') IS NOT NULL
            AND to_regclass('public.eventos_sala_espera') IS NOT NULL
            AND to_regclass('public.consultas') IS NOT NULL AND to_regclass('public.bitacora_sistema') IS NOT NULL")->fetchColumn();
        if (!in_array($r,[true,1,'1','t','true'],true)) {
            throw new \RuntimeException('Revisión detenida: faltan tablas de asistencia/auditoría. Verifica el esquema previamente aprobado; esta tarea no ejecuta migraciones.');
        }
        $n = (int)$this->conexion->query("SELECT count(*) FROM pg_trigger WHERE tgrelid='public.citas'::regclass
            AND tgname IN ('tr_citas_politica_y_trazabilidad','tr_citas_auditoria','tr_citas_sala','tr_citas_sala_coherencia')
            AND tgenabled IN ('O','A') AND NOT tgisinternal")->fetchColumn();
        if ($n !== 4) throw new \RuntimeException('Revisión detenida: verifica los triggers de citas, historial y sala. No se modificó el esquema.');
    }

    public function intentarBloqueo(): bool
    {
        return in_array($this->conexion->query('SELECT pg_try_advisory_xact_lock(2026110901)')->fetchColumn(),[true,1,'1','t','true'],true);
    }

    public function obtenerHoraBolivia(): string
    {
        return (string)$this->conexion->query("SELECT (clock_timestamp() AT TIME ZONE 'America/La_Paz')::text")->fetchColumn();
    }

    public function seleccionarVencidas(string $limite,int $lote): array
    {
        $q = $this->conexion->prepare("SELECT c.id_cita,
            (s.id_cita IS NOT NULL OR EXISTS(SELECT 1 FROM eventos_sala_espera e WHERE e.id_cita=c.id_cita)) AS llegada_registrada
            FROM citas c JOIN turnos t USING(id_turno) LEFT JOIN sala_espera s USING(id_cita)
            WHERE c.estado_cita='programada' AND c.asistencia_registrada='pendiente' AND t.fecha_hora_fin<=:limite
            AND NOT EXISTS(SELECT 1 FROM consultas co WHERE co.id_cita=c.id_cita)
            AND (s.estado IS NULL OR s.estado='en_espera')
            AND ((s.id_cita IS NULL AND NOT EXISTS(SELECT 1 FROM eventos_sala_espera e WHERE e.id_cita=c.id_cita))
                OR NOT EXISTS(SELECT 1 FROM bitacora_sistema b WHERE b.modulo='citas' AND b.accion='revision_paciente_presente' AND b.id_registro=c.id_cita))
            ORDER BY t.fecha_hora_fin,c.id_cita LIMIT :lote FOR UPDATE OF c SKIP LOCKED");
        $q->bindValue(':limite',$limite);$q->bindValue(':lote',$lote,\PDO::PARAM_INT);$q->execute();return $q->fetchAll();
    }

    public function marcarAusenciaAutomatica(int $id,string $limite): bool
    {
        // La selección ya bloqueó la cita. Revalidar antes de escribir, sin saltar triggers.
        $q = $this->conexion->prepare("UPDATE citas c SET estado_cita='no_asistio',asistencia_registrada='no_asistio',
            id_usuario_actualizacion=NULL,fecha_actualizacion_cita=clock_timestamp()
            WHERE c.id_cita=:id AND c.estado_cita='programada' AND c.asistencia_registrada='pendiente'
            AND EXISTS(SELECT 1 FROM turnos t WHERE t.id_turno=c.id_turno AND t.fecha_hora_fin<=:limite)
            AND NOT EXISTS(SELECT 1 FROM sala_espera s WHERE s.id_cita=c.id_cita)
            AND NOT EXISTS(SELECT 1 FROM eventos_sala_espera e WHERE e.id_cita=c.id_cita)
            AND NOT EXISTS(SELECT 1 FROM consultas co WHERE co.id_cita=c.id_cita)");
        $q->execute(compact('id','limite'));return $q->rowCount()===1;
    }
}
