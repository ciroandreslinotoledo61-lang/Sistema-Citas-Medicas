<?php
declare(strict_types=1);

/** Procesos locales con argumentos estructurados y salida en archivos, sin shell. */
final class SoporteProcesosPrueba
{
    public static function puertoLibre(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $codigo, $error);
        if ($socket === false) { throw new RuntimeException('No hay puerto local disponible.'); }
        $direccion = stream_socket_get_name($socket, false);
        fclose($socket);
        return (int) substr($direccion, strrpos($direccion, ':') + 1);
    }

    public static function iniciar(array $argumentos, string $directorio, array $entorno, string $log)
    {
        $proceso = proc_open($argumentos, [0 => ['pipe', 'r'], 1 => ['file', $log, 'ab'], 2 => ['redirect', 1]],
            $tuberias, $directorio, $entorno, ['bypass_shell' => true, 'create_new_console' => false]);
        if (!is_resource($proceso)) { throw new RuntimeException('No se pudo iniciar un proceso local de pruebas.'); }
        fclose($tuberias[0]);
        return $proceso;
    }

    public static function ejecutar(array $argumentos, string $directorio, array $entorno, string $log, int $segundos = 600): string
    {
        clearstatcache(true, $log);
        $inicioArchivo = is_file($log) ? filesize($log) : 0;
        $proceso = self::iniciar($argumentos, $directorio, $entorno, $log);
        $limite = microtime(true) + $segundos;
        try {
            do {
                $estado = proc_get_status($proceso);
                if (!$estado['running']) { break; }
                if (microtime(true) >= $limite) { throw new RuntimeException('Un proceso local excedio el tiempo disponible.'); }
                usleep(50000);
            } while (true);
            if ($estado['exitcode'] !== 0) {
                throw new RuntimeException('Fallo ' . basename($argumentos[0]) . '; revisar ' . basename($log) . '.');
            }
            return (string) file_get_contents($log, false, null, $inicioArchivo);
        } finally { self::detener($proceso); }
    }

    public static function esperarServidor($proceso, int $puerto): void
    {
        for ($i = 0; $i < 100; $i++) {
            if (!proc_get_status($proceso)['running']) { throw new RuntimeException('El servidor PHP termino antes de iniciar.'); }
            $socket = @stream_socket_client("tcp://127.0.0.1:$puerto", $codigo, $error, 0.2);
            if ($socket !== false) { fclose($socket); return; }
            usleep(100000);
        }
        throw new RuntimeException('El servidor PHP no quedo disponible.');
    }

    public static function detener($proceso): void
    {
        if (!is_resource($proceso)) { return; }
        if (proc_get_status($proceso)['running']) { proc_terminate($proceso); }
        $limite = microtime(true) + 5;
        while (proc_get_status($proceso)['running'] && microtime(true) < $limite) { usleep(50000); }
        if (proc_get_status($proceso)['running']) { proc_terminate($proceso, 9); }
        proc_close($proceso);
    }
}
