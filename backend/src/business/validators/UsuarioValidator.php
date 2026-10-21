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
        if (!is_string($contrasenaUsuario) || $contrasenaUsuario === '' ||
            strlen($contrasenaUsuario) > 72 || str_contains($contrasenaUsuario, "\0")) {
            throw new ErrorAplicacion('Correo o clave de acceso incorrectos.', 401);
        }
        return ['correoUsuario' => $correoUsuario, 'contrasenaUsuario' => $contrasenaUsuario];
    }

    public static function validarEstadoUsuario(array $datos): bool
    {
        if (!isset($datos['usuarioActivo']) || !is_bool($datos['usuarioActivo'])) {
            throw new ErrorAplicacion('El estado del usuario debe ser true o false.', 422);
        }
        return $datos['usuarioActivo'];
    }
}
