<?php
declare(strict_types=1);

namespace CitasMedicas\Presentation\Controllers;

use CitasMedicas\Business\Services\RecuperacionContrasenaService;
use CitasMedicas\Shared\Utils\RespuestaJson;
use CitasMedicas\Shared\Utils\SolicitudHttp;

final class RecuperacionContrasenaController
{
    public function __construct(private readonly RecuperacionContrasenaService $recuperacion) {}

    public function solicitarRecuperacion(): never
    {
        $this->recuperacion->solicitarRecuperacion(SolicitudHttp::obtenerCuerpoJson(), SolicitudHttp::obtenerDireccionCliente());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Solicitud recibida. Si la cuenta está habilitada y el servicio de correo está disponible, recibirás un enlace de recuperación. Revisa también el correo no deseado.'], 202);
    }

    public function confirmarRecuperacion(): never
    {
        $this->recuperacion->confirmarRecuperacion(SolicitudHttp::obtenerCuerpoJson(), SolicitudHttp::obtenerDireccionCliente());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Contraseña actualizada. Se cerraron las sesiones anteriores. Ya puedes iniciar sesión con tu nueva contraseña.']);
    }
}
