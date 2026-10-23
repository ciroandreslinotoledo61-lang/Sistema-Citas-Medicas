<?php
declare(strict_types=1);
namespace CitasMedicas\Presentation\Controllers;
use CitasMedicas\Business\Services\CitaService;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Utils\RespuestaJson;
use CitasMedicas\Shared\Utils\SolicitudHttp;

final class CitaController
{
    public function __construct(private readonly CitaService $citaService) {}
    public function listarCitas(Usuario $usuario): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->citaService->listarCitas($usuario, $_GET)]);
    }
    public function obtenerCita(Usuario $usuario, int $idCita): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->citaService->obtenerCita($usuario, $idCita)]);
    }
    public function reservarCita(Usuario $usuario): never
    {
        $resultado = $this->citaService->reservarCita($usuario, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Cita reservada.', 'datos' => $resultado], 201);
    }
    public function cancelarCita(Usuario $usuario, int $idCita): never
    {
        $resultado = $this->citaService->cancelarCita($usuario, $idCita, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Cita anulada. El turno vuelve a estar disponible.', 'datos' => $resultado]);
    }
    public function reprogramarCita(Usuario $usuario, int $idCita): never
    {
        $resultado = $this->citaService->reprogramarCita($usuario, $idCita, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Cita reprogramada. Se conservo el registro anterior.', 'datos' => $resultado], 201);
    }
    public function registrarAsistencia(Usuario $usuario, int $idCita): never
    {
        $resultado = $this->citaService->registrarAsistencia($usuario, $idCita, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Asistencia registrada.', 'datos' => $resultado]);
    }
}
