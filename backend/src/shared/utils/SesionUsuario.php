<?php
declare(strict_types=1);

namespace CitasMedicas\Shared\Utils;

use CitasMedicas\Shared\Config\CargarVariablesEntorno;

final class SesionUsuario
{
    public static function iniciarSesionSegura(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name(CargarVariablesEntorno::obtenerVariableEntorno('SESSION_COOKIE_NAME', 'citas_medicas_sesion'));
        $cookieSegura = CargarVariablesEntorno::obtenerVariableEntorno('SESSION_SECURE', 'false') === 'true';
        if (CargarVariablesEntorno::obtenerVariableEntorno('APP_ENV', 'local') === 'production' && !$cookieSegura) {
            throw new \RuntimeException('En production, SESSION_SECURE debe ser true.');
        }
        session_set_cookie_params([
            'lifetime' => 0, 'path' => '/', 'secure' => $cookieSegura,
            'httponly' => true, 'samesite' => 'Lax',
        ]);
        if (!session_start()) {
            throw new \RuntimeException('No se pudo iniciar el almacenamiento de sesiones.');
        }
        $limiteInactividad = max(60, (int) CargarVariablesEntorno::obtenerVariableEntorno('SESSION_IDLE_SECONDS', '1800'));
        if (isset($_SESSION['ultimaActividad']) && time() - (int) $_SESSION['ultimaActividad'] > $limiteInactividad) {
            self::limpiarSesionUsuario();
        }
        $_SESSION['ultimaActividad'] = time();
    }

    public static function obtenerTokenCsrf(): string
    {
        $_SESSION['tokenCsrf'] ??= bin2hex(random_bytes(32));
        return $_SESSION['tokenCsrf'];
    }

    public static function establecerUsuarioAutenticado(int $idUsuario, int $versionCredenciales): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['idUsuario'] = $idUsuario;
        $_SESSION['versionCredenciales'] = $versionCredenciales;
        $_SESSION['ultimaActividad'] = time();
        self::obtenerTokenCsrf();
    }

    public static function limpiarSesionUsuario(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
