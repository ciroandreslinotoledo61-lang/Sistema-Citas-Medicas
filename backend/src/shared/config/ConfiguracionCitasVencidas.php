<?php
declare(strict_types=1);
namespace CitasMedicas\Shared\Config;

final class ConfiguracionCitasVencidas
{
    public static function obtenerToleranciaMinutos(): int
    {
        $valor = CargarVariablesEntorno::obtenerVariableEntorno('CITAS_TOLERANCIA_MINUTOS','30');
        if (!ctype_digit($valor) || (int)$valor > 10080) {
            throw new \RuntimeException('CITAS_TOLERANCIA_MINUTOS debe ser un entero entre 0 y 10080 (7 días).');
        }
        return (int)$valor;
    }
}
