<?php
declare(strict_types=1);

namespace CitasMedicas\Business\Validators;

use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class EspecialidadValidator
{
    public static function validarDatosEspecialidad(array $datos): array
    {
        $nombreEspecialidad = ValidarCamposFormulario::validarTexto($datos, 'nombreEspecialidad', 'El nombre de la especialidad', 100);
        $nombreEspecialidad = preg_replace('/\s+/u', ' ', $nombreEspecialidad);
        if (mb_strlen($nombreEspecialidad) < 3) {
            throw new ErrorAplicacion('El nombre de la especialidad debe tener al menos 3 caracteres.', 422, [
                'nombreEspecialidad' => 'Escribe al menos 3 caracteres.',
            ]);
        }
        return [
            'nombreEspecialidad' => $nombreEspecialidad,
            'descripcionEspecialidad' => ValidarCamposFormulario::validarTexto($datos, 'descripcionEspecialidad', 'La descripcion', 300, false),
        ];
    }

    public static function validarEstadoEspecialidad(array $datos): bool
    {
        if (!array_key_exists('especialidadActiva', $datos) || !is_bool($datos['especialidadActiva'])) {
            throw new ErrorAplicacion('El estado de la especialidad debe ser true o false.', 422);
        }
        return $datos['especialidadActiva'];
    }
}
