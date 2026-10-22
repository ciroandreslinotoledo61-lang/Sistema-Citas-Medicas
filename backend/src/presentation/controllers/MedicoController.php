<?php
declare(strict_types=1);

namespace CitasMedicas\Presentation\Controllers;

use CitasMedicas\Business\Services\MedicoService;
use CitasMedicas\Shared\Utils\RespuestaJson;
use CitasMedicas\Shared\Utils\SolicitudHttp;

final class MedicoController
{
    public function __construct(private readonly MedicoService $medicoService) {}

    public function listarMedicos(): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->medicoService->listarMedicos($_GET)]);
    }

    public function obtenerMedicoPorId(int $idMedico): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->medicoService->obtenerMedicoPorId($idMedico)->obtenerDatosPublicosMedico()]);
    }

    public function registrarMedicoConUsuario(): never
    {
        $medico = $this->medicoService->registrarMedicoConUsuario(SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Medico registrado. Su cuenta ya puede iniciar sesion.', 'datos' => $medico->obtenerDatosPublicosMedico()], 201);
    }

    public function actualizarDatosMedico(int $idMedico): never
    {
        $medico = $this->medicoService->actualizarDatosMedico($idMedico, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Datos del medico actualizados.', 'datos' => $medico->obtenerDatosPublicosMedico()]);
    }

    public function cambiarEstadoMedico(int $idMedico): never
    {
        $medico = $this->medicoService->cambiarEstadoMedico($idMedico, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Estado del medico actualizado.', 'datos' => $medico->obtenerDatosPublicosMedico()]);
    }
}
