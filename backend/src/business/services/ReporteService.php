<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Services;
use CitasMedicas\Business\Validators\ReporteValidator;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\ReporteRepository;
use CitasMedicas\Data\Repositories\CitaRepository;
use CitasMedicas\Shared\Constants\PermisosSistema;
final class ReporteService
{
    public function __construct(private readonly \PDO $conexionPostgresql) {}
    public function obtenerReporteAdministrativo(Usuario $usuario,array $parametros): array
    {
        PermisosSistema::exigirPermiso($usuario,'reportes.consultar');
        $filtros=ReporteValidator::validarFiltrosReporte($parametros);
        $repositorio=new ReporteRepository($this->conexionPostgresql);
        $this->conexionPostgresql->beginTransaction();
        try {
            $this->conexionPostgresql->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
            $citas=$repositorio->listarCitasReporte($filtros); $pacientes=$repositorio->listarPacientesRegistrados($filtros);
            $resultado=['filtros'=>$filtros,'totales'=>$repositorio->obtenerResumenCitas($filtros)+['pacientesRegistrados'=>$repositorio->contarPacientesRegistrados($filtros)],
                'porMedico'=>$repositorio->obtenerCitasAgrupadas($filtros,'medico'),'porEspecialidad'=>$repositorio->obtenerCitasAgrupadas($filtros,'especialidad'),'porEstado'=>$repositorio->obtenerCitasAgrupadas($filtros,'estado'),
                'citas'=>['elementos'=>array_slice($citas,0,20),'pagina'=>$filtros['paginaCitas'],'hayPaginaSiguiente'=>count($citas)>20],
                'pacientes'=>['elementos'=>array_slice($pacientes,0,20),'pagina'=>$filtros['paginaPacientes'],'hayPaginaSiguiente'=>count($pacientes)>20],
                'medicosDisponibles'=>(new CitaRepository($this->conexionPostgresql))->listarMedicosParaAgenda($usuario),
                'especialidadesDisponibles'=>$repositorio->listarEspecialidadesReporte()];
            $this->conexionPostgresql->commit(); return $resultado;
        } catch (\Throwable $error) { if ($this->conexionPostgresql->inTransaction()) { $this->conexionPostgresql->rollBack(); } throw $error; }
    }
}
