<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Validators;

/** Compatibilidad con pruebas y consumidores anteriores. Las reglas estan separadas por modulo. */

final class AgendaValidator
{
    public static function validarIdentificador(array $datos, string $campo): int
    {
        return ValidarCamposFormulario::validarIdentificador($datos, $campo);
    }

    public static function validarFecha(mixed $valor, bool $opcional = false): string
    {
        return ValidarCamposFormulario::validarFecha($valor, $opcional);
    }

    public static function validarHorario(array $datos, \DateTimeImmutable $ahora): array
    {
        return HorarioMedicoValidator::validarHorario($datos, $ahora);
    }

    public static function validarFiltrosCitas(array $parametros): array
    {
        return CitaValidator::validarFiltrosCitas($parametros);
    }

    public static function validarProgramacionPeriodo(array $datos, \DateTimeImmutable $ahora): array
    {
        return HorarioMedicoValidator::validarProgramacionPeriodo($datos, $ahora);
    }
}
