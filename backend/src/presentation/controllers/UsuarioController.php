<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Controllers;

use CitasMedicas\Business\Services\UsuarioService;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Utils\RespuestaJson;
use CitasMedicas\Shared\Utils\SolicitudHttp;

final class UsuarioController
{
    public function __construct(private readonly UsuarioService $usuarioService)
    {
    }

    public function listarUsuarios(): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->usuarioService->listarUsuarios($_GET)]);
    }

    public function obtenerUsuarioPorId(int $idUsuario): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->usuarioService->obtenerUsuarioPorId($idUsuario)->obtenerDatosPublicosUsuario()]);
    }

    public function registrarUsuarioAdministrativo(): never
    {
        $usuario = $this->usuarioService->registrarUsuarioAdministrativo(SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Usuario registrado.', 'datos' => $usuario->obtenerDatosPublicosUsuario()], 201);
    }

    public function actualizarDatosUsuario(int $idUsuario): never
    {
        $usuario = $this->usuarioService->actualizarDatosUsuario($idUsuario, SolicitudHttp::obtenerCuerpoJson());
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Usuario actualizado.', 'datos' => $usuario->obtenerDatosPublicosUsuario()]);
    }

    public function cambiarEstadoUsuario(int $idUsuario, Usuario $usuarioAutenticado): never
    {
        $usuario = $this->usuarioService->cambiarEstadoUsuario($idUsuario, SolicitudHttp::obtenerCuerpoJson(), $usuarioAutenticado);
        RespuestaJson::enviarRespuestaJson(['mensaje' => 'Estado actualizado.', 'datos' => $usuario->obtenerDatosPublicosUsuario()]);
    }

    public function listarRolesSistema(): never
    {
        RespuestaJson::enviarRespuestaJson(['datos' => $this->usuarioService->listarRolesSistema()]);
    }
}
