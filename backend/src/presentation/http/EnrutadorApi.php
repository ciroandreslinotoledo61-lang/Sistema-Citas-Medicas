<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Http;

use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Presentation\Middlewares\VerificarRolUsuario;
use CitasMedicas\Presentation\Middlewares\VerificarSesionUsuario;
use CitasMedicas\Presentation\Routes\RutasApi;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SolicitudHttp;

final class EnrutadorApi
{
    public function __construct(private readonly FabricaControladores $controladores)
    {
    }

    public function atenderSolicitud(): never
    {
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $ruta = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
        $rutaConocida = false;
        foreach (RutasApi::definir() as $definicion) {
            $parametros = $definicion->coincidir($ruta);
            if ($parametros === null) {
                continue;
            }
            $rutaConocida = true;
            if ($definicion->metodo !== $metodo) {
                continue;
            }
            $usuario = $this->autorizarRuta($definicion);
            $argumentos = $this->resolverArgumentos($definicion, $parametros, $usuario);
            $controlador = $this->controladores->obtener($definicion->controlador);
            $controlador->{$definicion->accion}(...$argumentos);
        }
        if (!$rutaConocida) {
            throw new ErrorAplicacion('La ruta solicitada no existe.', 404);
        }
        // Se conserva el contrato 404 de la version original para metodos no disponibles.
        throw new ErrorAplicacion('La ruta o el metodo solicitado no esta disponible.', 404);
    }

    private function autorizarRuta(RutaHttp $ruta): ?Usuario
    {
        if ($ruta->roles === null) {
            return null;
        }
        $usuario = VerificarSesionUsuario::exigirUsuarioAutenticado($this->controladores->obtenerAutenticacionService());
        if ($ruta->roles !== []) {
            VerificarRolUsuario::exigirRolPermitido($usuario, $ruta->roles);
        }
        return $usuario;
    }

    private function resolverArgumentos(RutaHttp $ruta, array $parametros, ?Usuario $usuario): array
    {
        return array_map(static fn (string $nombre): mixed => match ($nombre) {
            'usuario' => $usuario,
            'idUsuarioAutenticado' => $usuario->idUsuario,
            default => SolicitudHttp::obtenerIdPositivo($parametros[$nombre]),
        }, $ruta->argumentos);
    }
}
