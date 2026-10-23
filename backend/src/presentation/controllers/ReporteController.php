<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Controllers;
use CitasMedicas\Business\Services\ReporteService;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Utils\RespuestaJson;
final class ReporteController
{
    public function __construct(private readonly ReporteService $reporteService) {}
    public function obtenerReporteAdministrativo(Usuario $usuario): never
    {
        RespuestaJson::enviarRespuestaJson(['datos'=>$this->reporteService->obtenerReporteAdministrativo($usuario,$_GET)]);
    }
}
