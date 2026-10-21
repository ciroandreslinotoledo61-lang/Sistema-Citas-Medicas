<?php
declare(strict_types=1);

final class ProteccionVista
{
    public static function exigirSesionParaVista(array $rolesPermitidos = []): array
    {
        $respuestaSesion = ClienteApiServidor::obtenerSesionDesdeBackend();
        if ($respuestaSesion['codigoHttp'] === 401 || !is_array($respuestaSesion['datos'])) {
            header('Location: /iniciar-sesion', true, 302);
            exit;
        }
        $usuario = $respuestaSesion['datos'];
        if ($rolesPermitidos !== [] && !in_array($usuario['codigoRol'], $rolesPermitidos, true)) {
            http_response_code(403);
            throw new DomainException('Tu cuenta no tiene acceso a esta pagina.');
        }
        return $usuario;
    }
}
