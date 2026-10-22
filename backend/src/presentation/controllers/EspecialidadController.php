<?php
declare(strict_types=1);

namespace CitasMedicas\Presentation\Controllers;

use CitasMedicas\Business\Services\EspecialidadService;
use CitasMedicas\Shared\Utils\RespuestaJson;
use CitasMedicas\Shared\Utils\SolicitudHttp;

final class EspecialidadController
{
    public function __construct(private readonly EspecialidadService $especialidadService) {}

    public function listarEspecialidades(): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->especialidadService->listarEspecialidades($_GET)]);
    }

    public function listarEspecialidadesActivas(): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->especialidadService->listarEspecialidadesActivas()]);
    }

    public function obtenerEspecialidadPorId(int $idEspecialidad): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->especialidadService->obtenerEspecialidadPorId($idEspecialidad)->obtenerDatosPublicosEspecialidad()]);
    }

    public function registrarEspecialidad(): never
    {
        $especialidad = $this->especialidadService->registrarEspecialidad(SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Especialidad registrada.', 'datos' => $especialidad->obtenerDatosPublicosEspecialidad()], 201);
    }

    public function actualizarDatosEspecialidad(int $idEspecialidad): never
    {
        $especialidad = $this->especialidadService->actualizarDatosEspecialidad($idEspecialidad, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Especialidad actualizada.', 'datos' => $especialidad->obtenerDatosPublicosEspecialidad()]);
    }

    public function cambiarEstadoEspecialidad(int $idEspecialidad): never
    {
        $especialidad = $this->especialidadService->cambiarEstadoEspecialidad($idEspecialidad, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Estado de la especialidad actualizado.', 'datos' => $especialidad->obtenerDatosPublicosEspecialidad()]);
    }
}
