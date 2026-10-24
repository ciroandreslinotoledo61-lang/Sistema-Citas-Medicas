<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Controllers;

use CitasMedicas\Business\Services\DisponibilidadService;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Utils\RespuestaJson;

final class DisponibilidadController
{
    public function __construct(private readonly DisponibilidadService $disponibilidadService)
    {
    }

    public function obtenerConfiguracionAgenda(Usuario $usuario): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->disponibilidadService->obtenerConfiguracionAgenda($usuario)]);
    }

    public function listarMedicosDisponibles(Usuario $usuario): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->disponibilidadService->listarMedicosDisponibles($usuario)]);
    }

    public function listarTurnosDisponibles(Usuario $usuario): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->disponibilidadService->listarTurnosDisponibles($usuario, $_GET)]);
    }
}
