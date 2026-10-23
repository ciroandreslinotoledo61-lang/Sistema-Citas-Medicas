<?php
declare(strict_types=1);
namespace CitasMedicas\Shared\Config;
final class ConfiguracionAgenda
{
    public const ZONA_HORARIA_BOLIVIA = 'America/La_Paz';
    public static function obtenerDuracionCitaMinutos(): int
    {
        $valor = CargarVariablesEntorno::obtenerVariableEntorno('CITA_DURACION_MINUTOS', '30');
        if (!ctype_digit($valor) || (int) $valor < 5 || (int) $valor > 120 || (int) $valor % 5 !== 0) {
            throw new \RuntimeException('CITA_DURACION_MINUTOS debe ser un multiplo de 5 entre 5 y 120.');
        }
        return (int) $valor;
    }
}
