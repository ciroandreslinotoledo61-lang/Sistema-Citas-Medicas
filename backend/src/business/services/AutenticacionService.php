<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\UsuarioValidator;
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SesionUsuario;

final class AutenticacionService
{
    private readonly IntentosAutenticacionService $intentos;
    private readonly Transaccion $transaccion;

    public function __construct(private readonly UsuarioRepository $usuarioRepository,private readonly BitacoraService $bitacoraService, \PDO $conexion)
    {
        $this->intentos = new IntentosAutenticacionService($conexion);
        $this->transaccion = new Transaccion($conexion);
    }

    public function iniciarSesionUsuario(array $datos, string $direccionCliente = 'local'): Usuario
    {
        // No confiar en X-Forwarded-For proporcionado por el cliente.
        $this->intentos->exigirIntentoDisponible('iniciar-sesion-ip', $direccionCliente, 50);
        $datosValidados = UsuarioValidator::validarDatosInicioSesion($datos);
        $this->intentos->exigirIntentoDisponible('iniciar-sesion-cuenta', $datosValidados['correoUsuario']);
        $usuario = $this->usuarioRepository->obtenerUsuarioPorCorreo($datosValidados['correoUsuario']);
        // El hash fijo solo iguala el costo cuando el correo no existe. No es una cuenta.
        $contrasenaValida = $usuario
            ? $usuario->verificarContrasenaUsuario($datosValidados['contrasenaUsuario'])
            : password_verify($datosValidados['contrasenaUsuario'], '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if (!$usuario || !$contrasenaValida || !$usuario->usuarioActivo) {
            $this->bitacoraService->registrarEventoAutenticacion(null,'iniciar_sesion','rechazado');
            throw new ErrorAplicacion('Correo o clave de acceso incorrectos.', 401);
        }
        $usuario = $this->transaccion->ejecutar(function () use ($usuario): ?Usuario {
            $actual = $this->usuarioRepository->obtenerUsuarioPorId($usuario->idUsuario, true);
            if (!$actual || !$actual->usuarioActivo || $actual->versionCredenciales !== $usuario->versionCredenciales) {
                return null;
            }
            $this->bitacoraService->establecerUsuarioResponsable($actual);
            if ($actual->requiereCambioContrasena) {
                if (!$this->usuarioRepository->consumirContrasenaTemporal($actual->idUsuario, $actual->versionCredenciales)) {
                    return null;
                }
                $actual = $this->usuarioRepository->obtenerUsuarioPorId($actual->idUsuario);
            }
            $this->bitacoraService->registrarEventoAutenticacion($actual, 'iniciar_sesion', 'exito');
            return $actual;
        });
        if (!$usuario) {
            $this->bitacoraService->registrarEventoAutenticacion(null, 'iniciar_sesion', 'rechazado');
            throw new ErrorAplicacion('Correo o clave de acceso incorrectos.', 401);
        }
        SesionUsuario::establecerUsuarioAutenticado($usuario->idUsuario, $usuario->versionCredenciales);
        return $usuario;
    }

    public function obtenerUsuarioAutenticado(): Usuario
    {
        $idUsuario = SesionUsuario::obtenerIdUsuario();
        if ($idUsuario < 1) {
            throw new ErrorAplicacion('Inicia sesión para continuar.', 401);
        }
        $usuario = $this->usuarioRepository->obtenerUsuarioPorId($idUsuario);
        if (
            !$usuario || !$usuario->usuarioActivo ||
            $usuario->versionCredenciales !== SesionUsuario::obtenerVersionCredenciales() ||
            ($usuario->requiereCambioContrasena && (!$usuario->contrasenaTemporalExpira ||
                strtotime($usuario->contrasenaTemporalExpira) <= time()))
        ) {
            SesionUsuario::limpiarSesionUsuario();
            throw new ErrorAplicacion('Tu sesión ya no está disponible. Inicia sesión otra vez.', 401);
        }
        return $usuario;
    }

    public function cerrarSesionUsuario(): void
    {
        try {
            if (SesionUsuario::obtenerIdUsuario() > 0) {
                try {
                    $usuario = $this->obtenerUsuarioAutenticado();
                    $this->bitacoraService->establecerUsuarioResponsable($usuario);
                    $this->bitacoraService->registrarEventoAutenticacion($usuario, 'cerrar_sesion', 'exito');
                } catch (ErrorAplicacion $error) {
                    if ($error->codigoHttp !== 401) {
                        throw $error;
                    }
                }
            }
        } finally {
            // Cerrar tambien una sesion expirada o revocada, incluso si falla la auditoria.
            SesionUsuario::limpiarSesionUsuario();
        }
    }
}
