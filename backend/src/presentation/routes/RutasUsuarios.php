<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Presentation\Controllers\UsuarioController;
use CitasMedicas\Presentation\Http\RutaHttp;
use CitasMedicas\Shared\Constants\RolesSistema;

final class RutasUsuarios
{
    /** @return RutaHttp[] */
    public static function definir(): array
    {
        return [
            new RutaHttp(
                'GET',
                '/api/roles',
                UsuarioController::class,
                'listarRolesSistema',
                [RolesSistema::ADMINISTRADOR],
                [],
            ),
            new RutaHttp(
                'GET',
                '/api/usuarios',
                UsuarioController::class,
                'listarUsuarios',
                [RolesSistema::ADMINISTRADOR],
                [],
            ),
            new RutaHttp(
                'POST',
                '/api/usuarios',
                UsuarioController::class,
                'registrarUsuarioAdministrativo',
                [RolesSistema::ADMINISTRADOR],
                [],
            ),
            new RutaHttp(
                'GET',
                '/api/usuarios/{idUsuario}',
                UsuarioController::class,
                'obtenerUsuarioPorId',
                [RolesSistema::ADMINISTRADOR],
                ['idUsuario'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/usuarios/{idUsuario}',
                UsuarioController::class,
                'actualizarDatosUsuario',
                [RolesSistema::ADMINISTRADOR],
                ['idUsuario'],
            ),
            new RutaHttp(
                'PATCH',
                '/api/usuarios/{idUsuario}/estado',
                UsuarioController::class,
                'cambiarEstadoUsuario',
                [RolesSistema::ADMINISTRADOR],
                ['idUsuario', 'usuario'],
            ),
        ];
    }
}
