<?php
declare(strict_types=1);

namespace CitasMedicas\Business\Validators;

use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class PacienteValidator
{
    public static function validarDatosActualizacionPaciente(array $datos): array
    {
        $datosValidados = UsuarioValidator::validarDatosActualizacionUsuario($datos);
        $cedulaIdentidad = strtoupper(ValidarCamposFormulario::validarTexto($datos, 'cedulaIdentidad', 'La cedula de identidad', 25));
        if (!preg_match('/^[A-Z0-9][A-Z0-9 -]{2,24}$/', $cedulaIdentidad)) {
            throw new ErrorAplicacion('Revisa la cedula de identidad.', 422, ['cedulaIdentidad' => 'Usa de 3 a 25 letras, numeros, espacios o guiones.']);
        }
        $fechaNacimiento = ValidarCamposFormulario::validarTexto($datos, 'fechaNacimiento', 'La fecha de nacimiento', 10);
        $fechaInterpretada = \DateTimeImmutable::createFromFormat('!Y-m-d', $fechaNacimiento);
        $fechaActual = new \DateTimeImmutable('today');
        if (!$fechaInterpretada || $fechaInterpretada->format('Y-m-d') !== $fechaNacimiento ||
            $fechaInterpretada > $fechaActual || $fechaInterpretada < $fechaActual->modify('-120 years')) {
            throw new ErrorAplicacion('Revisa la fecha de nacimiento.', 422, ['fechaNacimiento' => 'Selecciona una fecha real, no futura, de los ultimos 120 anos.']);
        }
        $sexo = $datos['sexoPaciente'] ?? 'no_especificado';
        if (!is_string($sexo) || !in_array($sexo, ['no_especificado','femenino','masculino','otro'], true)) { throw new ErrorAplicacion('Selecciona una opcion valida para sexo.', 422); }
        return $datosValidados + ['sexoPaciente' => $sexo,
            'cedulaIdentidad' => $cedulaIdentidad, 'fechaNacimiento' => $fechaNacimiento,
        ] + self::validarDatosContactoPaciente($datos);
    }

    public static function validarDatosContactoPaciente(array $datos): array
    {
        $nombreContacto = ValidarCamposFormulario::validarTexto($datos, 'nombreContactoEmergencia', 'El contacto de emergencia', 120, false);
        $telefonoContacto = ValidarCamposFormulario::validarTelefono($datos, 'telefonoContactoEmergencia', false);
        if (($nombreContacto === '') !== ($telefonoContacto === '')) {
            throw new ErrorAplicacion('Completa el nombre y el telefono del contacto de emergencia.', 422);
        }
        return [
            'telefonoPaciente' => ValidarCamposFormulario::validarTelefono($datos, 'telefonoPaciente', true),
            'direccionPaciente' => ValidarCamposFormulario::validarTexto($datos, 'direccionPaciente', 'La direccion', 200, false),
            'nombreContactoEmergencia' => $nombreContacto, 'telefonoContactoEmergencia' => $telefonoContacto,
        ];
    }

    public static function validarDatosRegistroPaciente(array $datos): array
    {
        return self::validarDatosActualizacionPaciente($datos) + ['contrasenaUsuario' => ValidarCamposFormulario::validarContrasenaRegistro($datos)];
    }
    public static function validarEstadoPaciente(array $datos): bool
    {
        if (!array_key_exists('pacienteActivo',$datos) || !is_bool($datos['pacienteActivo'])) { throw new ErrorAplicacion('El estado del paciente debe ser true o false.',422); }
        return $datos['pacienteActivo'];
    }

}
