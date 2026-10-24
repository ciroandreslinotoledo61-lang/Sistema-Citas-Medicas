<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\ReporteValidator;
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\MedicoRepository;
use CitasMedicas\Data\Repositories\ReporteRepository;
use CitasMedicas\Shared\Constants\PermisosSistema;

final class ReporteService
{
    private readonly ReporteRepository $reporteRepository;
    private readonly MedicoRepository $medicoRepository;
    private readonly Transaccion $transaccion;

    public function __construct(\PDO $conexionPostgresql)
    {
        $this->reporteRepository = new ReporteRepository($conexionPostgresql);
        $this->medicoRepository = new MedicoRepository($conexionPostgresql);
        $this->transaccion = new Transaccion($conexionPostgresql);
    }

    public function obtenerReporteAdministrativo(Usuario $usuario, array $parametros): array
    {
        PermisosSistema::exigirPermiso($usuario, 'reportes.consultar');
        $filtros = ReporteValidator::validarFiltrosReporte($parametros);
        return $this->transaccion->ejecutar(function () use ($filtros): array {
            $citas = $this->reporteRepository->listarCitasReporte($filtros);
            $pacientes = $this->reporteRepository->listarPacientesRegistrados($filtros);
            return [
                'filtros' => $filtros,
                'totales' => $this->reporteRepository->obtenerResumenCitas($filtros) + [
                    'pacientesRegistrados' => $this->reporteRepository->contarPacientesRegistrados($filtros),
                ],
                'porMedico' => $this->reporteRepository->obtenerCitasAgrupadas($filtros, 'medico'),
                'porEspecialidad' => $this->reporteRepository->obtenerCitasAgrupadas($filtros, 'especialidad'),
                'porEstado' => $this->reporteRepository->obtenerCitasAgrupadas($filtros, 'estado'),
                'citas' => [
                    'elementos' => array_slice($citas, 0, 20),
                    'pagina' => $filtros['paginaCitas'],
                    'hayPaginaSiguiente' => count($citas) > 20,
                ],
                'pacientes' => [
                    'elementos' => array_slice($pacientes, 0, 20),
                    'pagina' => $filtros['paginaPacientes'],
                    'hayPaginaSiguiente' => count($pacientes) > 20,
                ],
                'medicosDisponibles' => $this->medicoRepository->listarMedicosParaAgenda(),
                'especialidadesDisponibles' => $this->reporteRepository->listarEspecialidadesReporte(),
            ];
        }, Transaccion::REPORTE_CONSISTENTE);
    }
}
