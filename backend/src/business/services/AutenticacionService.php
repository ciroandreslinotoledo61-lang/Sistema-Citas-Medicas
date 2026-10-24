<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\UsuarioValidator;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Errors\ErrorAplicacion;
use CitasMedicas\Shared\Utils\SesionUsuario;

final class AutenticacionService
{
    public function __construct(private readonly UsuarioRepository $usuarioRepository)
    {
    }

    public function iniciarSesionUsuario(array $datos): Usuario
    {
        $datosValidados = UsuarioValidator::validarDatosInicioSesion($datos);
        if (SesionUsuario::obtenerInicioVentanaIntentos() < time() - 300) {
            SesionUsuario::reiniciarIntentos(time());
        }
        if (SesionUsuario::obtenerIntentosFallidos() >= 5) {
            throw new ErrorAplicacion('Espera cinco minutos antes de volver a intentar.', 429);
        }
        $usuario = $this->usuarioRepository->obtenerUsuarioPorCorreo($datosValidados['correoUsuario']);
        // El hash fijo solo iguala el costo cuando el correo no existe. No es una cuenta.
        $contrasenaValida = $usuario
            ? $usuario->verificarContrasenaUsuario($datosValidados['contrasenaUsuario'])
            : password_verify($datosValidados['contrasenaUsuario'], '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if (!$usuario || !$contrasenaValida || !$usuario->usuarioActivo) {
            SesionUsuario::registrarIntentoFallido();
            throw new ErrorAplicacion('Correo o clave de acceso incorrectos.', 401);
        }
        SesionUsuario::establecerUsuarioAutenticado($usuario->idUsuario, $usuario->versionCredenciales);
        return $usuario;
    }

    public function obtenerUsuarioAutenticado(): Usuario
    {
        $idUsuario = SesionUsuario::obtenerIdUsuario();
        if ($idUsuario < 1) {
            throw new ErrorAplicacion('Inicia sesion para continuar.', 401);
        }
        $usuario = $this->usuarioRepository->obtenerUsuarioPorId($idUsuario);
        if (
            !$usuario || !$usuario->usuarioActivo ||
            $usuario->versionCredenciales !== SesionUsuario::obtenerVersionCredenciales()
        ) {
            SesionUsuario::limpiarSesionUsuario();
            throw new ErrorAplicacion('Tu sesion ya no esta disponible. Inicia sesion otra vez.', 401);
        }
        return $usuario;
    }

    public function cerrarSesionUsuario(): void
    {
        SesionUsuario::limpiarSesionUsuario();
    }
}
