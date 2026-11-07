<?php

declare(strict_types=1);

namespace CitasMedicas\Presentation\Routes;

use CitasMedicas\Presentation\Controllers\AutenticacionController;
use CitasMedicas\Presentation\Controllers\CredencialController;
use CitasMedicas\Presentation\Controllers\RecuperacionContrasenaController;
use CitasMedicas\Presentation\Controllers\PacienteController;
use CitasMedicas\Presentation\Http\RutaHttp;

final class RutasAutenticacion
{
    /** @return RutaHttp[] */
    public static function definir(): array
    {
        return [
            new RutaHttp('POST', '/api/autenticacion/recuperar-contrasena', RecuperacionContrasenaController::class, 'solicitarRecuperacion', null),
            new RutaHttp('POST', '/api/autenticacion/confirmar-recuperacion', RecuperacionContrasenaController::class, 'confirmarRecuperacion', null),
            new RutaHttp('POST', '/api/autenticacion/cambiar-contrasena', CredencialController::class, 'cambiarContrasena'),
            new RutaHttp(
                'POST',
                '/api/autenticacion/iniciar-sesion',
                AutenticacionController::class,
                'iniciarSesionUsuario',
                null,
                [],
            ),
            new RutaHttp(
                'POST',
                '/api/autenticacion/cerrar-sesion',
                AutenticacionController::class,
                'cerrarSesionUsuario',
                null,
                [],
            ),
            new RutaHttp(
                'GET',
                '/api/autenticacion/sesion',
                AutenticacionController::class,
                'obtenerSesionUsuario',
                [],
                [],
            ),
            new RutaHttp(
                'POST',
                '/api/autenticacion/registro-paciente',
                PacienteController::class,
                'registrarPacienteConHistoriaClinica',
                null,
                [],
            ),
        ];
    }
}
