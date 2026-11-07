<?php

declare(strict_types=1);

namespace CitasMedicas\Shared\Config;

final class ConfiguracionAgenda
{
    public const ZONA_HORARIA_BOLIVIA = 'America/La_Paz';
    public static function obtenerDuracionCitaMinutos(): int
    {
        // Regla del dominio: ninguna variable externa puede cambiar nuevas citas.
        return 30;
    }

    public static function obtenerHorarioClinica(): array
    {
        $apertura = CargarVariablesEntorno::obtenerVariableEntorno('CLINICA_HORA_APERTURA', '08:00');
        $cierre = CargarVariablesEntorno::obtenerVariableEntorno('CLINICA_HORA_CIERRE', '17:00');
        foreach ([$apertura, $cierre] as $hora) {
            if (!preg_match('/^(?:[01][0-9]|2[0-3]):(?:00|30)$/', $hora)) {
                throw new \RuntimeException('El horario de la clínica debe usar HH:00 o HH:30.');
            }
        }
        if ($apertura >= $cierre) {
            throw new \RuntimeException('La apertura debe ser anterior al cierre.');
        }
        $horas = [];
        for (
            $hora = new \DateTimeImmutable('2000-01-01 ' . $apertura);
            $hora <= new \DateTimeImmutable('2000-01-01 ' . $cierre); $hora = $hora->modify('+30 minutes')
        ) {
            $horas[] = $hora->format('H:i');
        }
        return ['horaApertura' => $apertura, 'horaCierre' => $cierre, 'horasPermitidas' => $horas];
    }

    public static function obtenerConfiguracionPublica(): array
    {
        return ['duracionCitaMinutos' => self::obtenerDuracionCitaMinutos()] + self::obtenerHorarioClinica();
    }
}
