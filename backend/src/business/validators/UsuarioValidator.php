<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Validators;

use CitasMedicas\Shared\Constants\RolesSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class UsuarioValidator
{
    public static function validarDatosActualizacionUsuario(array $datos): array
    {
        return [
            'nombresUsuario' => ValidarCamposFormulario::validarTexto($datos, 'nombresUsuario', 'El nombre', 80),
            'apellidosUsuario' => ValidarCamposFormulario::validarTexto($datos, 'apellidosUsuario', 'El apellido', 80),
            'correoUsuario' => ValidarCamposFormulario::validarCorreoUsuario($datos),
        ];
    }

    public static function validarDatosRegistroUsuario(array $datos): array
    {
        $datosValidados = self::validarDatosActualizacionUsuario($datos);
        $datosValidados['contrasenaUsuario'] = ValidarCamposFormulario::validarContrasenaRegistro($datos);
        $codigoRol = ValidarCamposFormulario::validarTexto($datos, 'codigoRol', 'El rol', 30);
        if (!in_array($codigoRol, RolesSistema::CUENTAS_ADMINISTRATIVAS, true)) {
            throw new ErrorAplicacion('Selecciona Administrador o Recepcionista. Los pacientes se registran en Pacientes.', 422);
        }
        $datosValidados['codigoRol'] = $codigoRol;
        return $datosValidados;
    }

    public static function validarDatosInicioSesion(array $datos): array
    {
        $correoUsuario = ValidarCamposFormulario::validarCorreoUsuario($datos);
        $contrasenaUsuario = $datos['contrasenaUsuario'] ?? '';
        if (
            !is_string($contrasenaUsuario) || $contrasenaUsuario === '' ||
            strlen($contrasenaUsuario) > 72 || str_contains($contrasenaUsuario, "\0")
        ) {
            throw new ErrorAplicacion('Correo o clave de acceso incorrectos.', 401);
        }
        return ['correoUsuario' => $correoUsuario, 'contrasenaUsuario' => $contrasenaUsuario];
    }

    public static function validarCambioContrasena(array $datos): array
    {
        self::exigirCamposPermitidos($datos, ['contrasenaActual', 'nuevaContrasena', 'confirmacionContrasena']);
        $actual = $datos['contrasenaActual'] ?? null;
        if (!is_string($actual) || $actual === '' || strlen($actual) > 72 || str_contains($actual, "\0")) {
            throw new ErrorAplicacion('Revisa la contraseña actual.', 422, [
                'contrasenaActual' => 'Escribe tu contraseña actual.',
            ]);
        }
        $nueva = self::validarNuevaContrasena($datos);
        return ['contrasenaActual' => $actual, 'nuevaContrasena' => $nueva,
            'confirmacionContrasena' => $datos['confirmacionContrasena']];
    }

    public static function validarSolicitudRecuperacion(array $datos): string
    {
        self::exigirCamposPermitidos($datos, ['correoUsuario']);
        return ValidarCamposFormulario::validarCorreoUsuario($datos);
    }

    public static function validarRecuperacionContrasena(array $datos): array
    {
        self::exigirCamposPermitidos($datos, ['token', 'nuevaContrasena', 'confirmacionContrasena']);
        $token = $datos['token'] ?? null;
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
            throw new ErrorAplicacion('El enlace es inválido o ha vencido. Solicita uno nuevo.', 422);
        }
        return ['token' => $token, 'nuevaContrasena' => self::validarNuevaContrasena($datos)];
    }

    private static function validarNuevaContrasena(array $datos): string
    {
        // Reutiliza los limites compatibles con password_hash sin recortar espacios.
        try {
            $nueva = ValidarCamposFormulario::validarContrasenaRegistro([
                'contrasenaUsuario' => $datos['nuevaContrasena'] ?? null,
                'confirmacionContrasena' => $datos['confirmacionContrasena'] ?? null,
            ]);
        } catch (ErrorAplicacion $error) {
            $campos = $error->erroresCampos;
            if (isset($campos['contrasenaUsuario'])) {
                $campos['nuevaContrasena'] = $campos['contrasenaUsuario'];
                unset($campos['contrasenaUsuario']);
            }
            throw new ErrorAplicacion($error->getMessage(), $error->codigoHttp, $campos);
        }
        if (!preg_match('/\p{L}/u', $nueva) || !preg_match('/[0-9]/', $nueva) || preg_match('/^(.{1,6})\1+$/usD', $nueva)) {
            throw new ErrorAplicacion('La nueva contraseña es demasiado predecible.', 422, [
                'nuevaContrasena' => 'Usa letras y números, sin patrones repetidos; entre 12 y 72 bytes.',
            ]);
        }
        return $nueva;
    }

    public static function validarRestablecimientoContrasena(array $datos): string
    {
        self::exigirCamposPermitidos($datos, ['contrasenaTemporal', 'confirmarRestablecimiento']);
        if (($datos['confirmarRestablecimiento'] ?? null) !== true) {
            throw new ErrorAplicacion('Confirma el restablecimiento de esta cuenta.', 422, [
                'confirmarRestablecimiento' => 'Debes confirmar el restablecimiento.',
            ]);
        }
        $temporal = $datos['contrasenaTemporal'] ?? null;
        if (!is_string($temporal) || !preg_match('/^[a-f0-9]{24}$/D', $temporal)
            || count(array_unique(str_split($temporal))) < 6 || preg_match('/^(.{1,12})\1+$/D', $temporal)) {
            throw new ErrorAplicacion('Genera una nueva contraseña temporal.', 422, [
                'contrasenaTemporal' => 'Usa el generador seguro del formulario.',
            ]);
        }
        return $temporal;
    }

    private static function exigirCamposPermitidos(array $datos, array $campos): void
    {
        if (array_diff(array_keys($datos), $campos) !== []) {
            throw new ErrorAplicacion('La solicitud contiene campos no permitidos.', 422);
        }
    }

    public static function validarEstadoUsuario(array $datos): bool
    {
        if (!isset($datos['usuarioActivo']) || !is_bool($datos['usuarioActivo'])) {
            throw new ErrorAplicacion('El estado del usuario debe ser true o false.', 422);
        }
        return $datos['usuarioActivo'];
    }
}
