<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Validators;

use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class AgendaValidator
{
    public static function validarIdentificador(array $datos, string $campo): int
    {
        $valor = $datos[$campo] ?? null;
        if ((!is_int($valor) && !is_string($valor)) ||
            !preg_match('/^[1-9][0-9]*$/', (string) $valor) ||
            filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new ErrorAplicacion('Selecciona un registro valido.', 422, [$campo => 'Selecciona una opcion valida.']);
        }
        return (int) $valor;
    }

    public static function validarFecha(mixed $valor, bool $opcional = false): string
    {
        if ($opcional && ($valor === '' || $valor === null)) { return ''; }
        if (!is_string($valor) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $valor)) {
            throw new ErrorAplicacion('La fecha no es valida. Usa AAAA-MM-DD.', 422);
        }
        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        if (!$fecha || str_starts_with($valor, '0000-') || $fecha->format('Y-m-d') !== $valor) {
            throw new ErrorAplicacion('La fecha no existe.', 422);
        }
        return $valor;
    }

    public static function validarHorario(array $datos, \DateTimeImmutable $ahora): array
    {
        $idMedico = self::validarIdentificador($datos, 'idMedico');
        $duracion = \CitasMedicas\Shared\Config\ConfiguracionAgenda::obtenerDuracionCitaMinutos();
        $fecha = self::validarFecha($datos['fechaAtencion'] ?? null);
        foreach (['horaInicio', 'horaFin'] as $campo) {
            if (!is_string($datos[$campo] ?? null) || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][05]$/', $datos[$campo])) {
                throw new ErrorAplicacion('Usa horas en intervalos de 5 minutos.', 422, [$campo => 'Ejemplo: 08:00, 08:05 o 08:30.']);
            }
        }
        $inicio = new \DateTimeImmutable($fecha . ' ' . $datos['horaInicio']);
        $fin = new \DateTimeImmutable($fecha . ' ' . $datos['horaFin']);
        if ($fin <= $inicio) { throw new ErrorAplicacion('La hora final debe ser posterior al inicio y del mismo dia.', 422); }
        if (($fin->getTimestamp() - $inicio->getTimestamp()) % ($duracion * 60) !== 0) { throw new ErrorAplicacion('El bloque debe contener turnos completos de ' . $duracion . ' minutos.', 422); }
        if ($inicio <= $ahora) { throw new ErrorAplicacion('El horario debe comenzar en el futuro.', 422); }
        if ($inicio > $ahora->modify('+1 year')) { throw new ErrorAplicacion('Configura horarios dentro de los proximos 12 meses.', 422); }
        return ['idMedico' => $idMedico, 'fechaAtencion' => $fecha,
            'horaInicio' => $datos['horaInicio'], 'horaFin' => $datos['horaFin'], 'duracionCitaMinutos' => $duracion];
    }

    public static function validarFiltrosCitas(array $parametros): array
    {
        $filtros = ValidarCamposFormulario::validarParametrosBusqueda($parametros);
        $filtros['fecha'] = self::validarFecha($parametros['fecha'] ?? '', true);
        $estado = $parametros['estado'] ?? '';
        if (!is_string($estado) || !in_array($estado, ['', 'pendiente', 'confirmada', 'cancelada', 'reprogramada', 'no_asistio', 'atendida'], true)) {
            throw new ErrorAplicacion('El estado de la cita no es valido.', 422);
        }
        $filtros['estado'] = $estado;
        foreach (['idMedico', 'idPaciente'] as $campo) { $filtros[$campo] = !isset($parametros[$campo]) || $parametros[$campo] === '' ? null : self::validarIdentificador($parametros, $campo); }
        return $filtros;
    }
}
