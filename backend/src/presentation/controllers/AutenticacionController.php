<?php
declare(strict_types=1);

namespace CitasMedicas\Presentation\Controllers;

use CitasMedicas\Business\Services\AutenticacionService;
use CitasMedicas\Shared\Utils\RespuestaJson;
use CitasMedicas\Shared\Utils\SolicitudHttp;

final class AutenticacionController
{
    public function __construct(private readonly AutenticacionService $autenticacionService) {}

    public function iniciarSesionUsuario(): never
    {
        $usuario = $this->autenticacionService->iniciarSesionUsuario(SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Sesion iniciada.', 'datos' => $usuario->obtenerDatosPublicosUsuario()]);
    }

    public function obtenerSesionUsuario(): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->autenticacionService->obtenerUsuarioAutenticado()->obtenerDatosPublicosUsuario()]);
    }

    public function cerrarSesionUsuario(): never
    {
        $this->autenticacionService->cerrarSesionUsuario();
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Sesion cerrada.']);
    }
}
