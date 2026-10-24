<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Validators;

use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class ReporteValidator
{
    public static function validarFiltrosReporte(array $parametros): array
    {
        $desde = ValidarCamposFormulario::validarFecha($parametros['fechaDesde'] ?? date('Y-m-01'));
        $hasta = ValidarCamposFormulario::validarFecha($parametros['fechaHasta'] ?? date('Y-m-d'));
        if ($desde > $hasta) {
            throw new ErrorAplicacion('La fecha inicial no puede ser posterior a la final.', 422);
        }
        $filtros = ['fechaDesde' => $desde,'fechaHasta' => $hasta];
        foreach (['idMedico','idEspecialidad'] as $campo) {
            $filtros[$campo] = !isset($parametros[$campo]) || $parametros[$campo] === '' ? null : ValidarCamposFormulario::validarIdentificador($parametros, $campo);
        }
        $filtros['estado'] = CitaValidator::validarFiltrosCitas(['estado' => $parametros['estado'] ?? ''])['estado'];
        foreach (['paginaCitas','paginaPacientes'] as $campo) {
            $filtros[$campo] = ValidarCamposFormulario::validarParametrosBusqueda(['pagina' => $parametros[$campo] ?? 1])['pagina'];
        }
        return $filtros;
    }
}
