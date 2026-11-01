<?php
declare(strict_types=1);

namespace CitasMedicas\Presentation\Controllers;

use CitasMedicas\Business\Services\CredencialService;
use CitasMedicas\Shared\Utils\RespuestaJson;
use CitasMedicas\Shared\Utils\SolicitudHttp;

final class CredencialController
{
    public function __construct(private readonly CredencialService $credenciales) {}

    public function cambiarContrasena(): never
    {
        $usuario = $this->credenciales->cambiarContrasena(SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson([
            'mensaje' => 'Contrasena actualizada. Se cerraron las demas sesiones de tu cuenta.',
            'datos' => $usuario->obtenerDatosPublicosUsuario(),
        ]);
    }

    public function restablecerContrasena(int $idUsuario): never
    {
        $this->credenciales->restablecerContrasena($idUsuario, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson([
            'mensaje' => 'Contrasena restablecida. Las sesiones anteriores se cerraron. La clave temporal permite un inicio de sesion durante 30 minutos; el usuario debera elegir su contrasena personal.',
        ]);
    }
}
