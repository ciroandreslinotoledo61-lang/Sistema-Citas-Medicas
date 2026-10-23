<?php
declare(strict_types=1);

namespace CitasMedicas\Business\Validators;

use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class MedicoValidator
{
    public static function validarDatosActualizacionMedico(array $datos): array
    {
        $datosValidados = UsuarioValidator::validarDatosActualizacionUsuario($datos);
        $idEspecialidad = $datos['idEspecialidad'] ?? null;
        if ((!is_int($idEspecialidad) && !(is_string($idEspecialidad) && ctype_digit($idEspecialidad))) ||
            filter_var($idEspecialidad, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new ErrorAplicacion('Selecciona una especialidad valida.', 422, ['idEspecialidad' => 'Selecciona una especialidad.']);
        }
        $matriculaProfesional = strtoupper(ValidarCamposFormulario::validarTexto($datos, 'matriculaProfesional', 'La matricula profesional', 40));
        $matriculaProfesional = preg_replace('/\s+/', ' ', $matriculaProfesional);
        if (!preg_match('#^[A-Z0-9][A-Z0-9 ./-]{2,39}$#', $matriculaProfesional)) {
            throw new ErrorAplicacion('Revisa la matricula profesional.', 422, [
                'matriculaProfesional' => 'Usa de 3 a 40 letras, numeros, espacios, puntos, guiones o barras.',
            ]);
        }
        return $datosValidados + ['telefonoMedico' => ValidarCamposFormulario::validarTelefono($datos, 'telefonoMedico', true),
            'idEspecialidad' => (int) $idEspecialidad, 'matriculaProfesional' => $matriculaProfesional,
        ];
    }

    public static function validarDatosRegistroMedico(array $datos): array
    {
        $datosValidados = self::validarDatosActualizacionMedico($datos);
        $datosValidados['contrasenaUsuario'] = ValidarCamposFormulario::validarContrasenaRegistro($datos);
        return $datosValidados;
    }

    public static function validarEstadoMedico(array $datos): bool
    {
        if (!array_key_exists('medicoActivo', $datos) || !is_bool($datos['medicoActivo'])) {
            throw new ErrorAplicacion('El estado del medico debe ser true o false.', 422);
        }
        return $datos['medicoActivo'];
    }
}
