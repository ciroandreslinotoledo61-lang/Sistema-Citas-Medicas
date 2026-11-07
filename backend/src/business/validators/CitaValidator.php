<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Validators;

use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class CitaValidator
{
    public static function validarFiltrosCitas(array $parametros): array
    {
        $filtros = ValidarCamposFormulario::validarParametrosBusqueda($parametros);
        $filtros['fecha'] = ValidarCamposFormulario::validarFecha($parametros['fecha'] ?? '', true);
        $estado = $parametros['estado'] ?? '';
        if (!is_string($estado) || !in_array($estado, ['', 'programada', 'cancelada', 'reprogramada', 'no_asistio', 'atendida'], true)) {
            throw new ErrorAplicacion('El estado de la cita no es válido.', 422);
        }
        $filtros['estado'] = $estado;
        $vista = $parametros['vista'] ?? '';
        if (!is_string($vista) || !in_array($vista, ['', 'proximas', 'historial'], true)) {
            throw new ErrorAplicacion('Selecciona Próximas citas o Historial.', 422);
        }
        $filtros['vista'] = $vista;
        foreach (['idMedico', 'idPaciente'] as $campo) {
            $filtros[$campo] = !isset($parametros[$campo]) || $parametros[$campo] === '' ? null : ValidarCamposFormulario::validarIdentificador($parametros, $campo);
        }
        return $filtros;
    }

    public static function validarAsistencia(array $datos): string
    {
        $asistencia = $datos['asistenciaRegistrada'] ?? '';
        if (!in_array($asistencia, ['asistio', 'no_asistio'], true)) {
            throw new ErrorAplicacion('Selecciona Asistió o No asistió.', 422);
        }
        return $asistencia;
    }
}
