<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

/** Contadores compartidos por procesos y sesiones; nunca guarda correos ni credenciales. */
final class IntentoAutenticacionRepository
{
    public function __construct(private readonly \PDO $conexionPostgresql)
    {
    }

    public function consumirIntento(string $clave, int $limite, int $ventanaSegundos): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $clave) || $limite < 1 || $ventanaSegundos < 1) {
            throw new \InvalidArgumentException('Configuración de límite no válida.');
        }
        // El UPSERT bloquea la clave y decide el contador en una sola sentencia.
        $consulta = $this->conexionPostgresql->prepare(
            "WITH momento AS (
                SELECT clock_timestamp() AS ahora,
                make_interval(secs => CAST(:ventana AS integer)) AS ventana
            )
            INSERT INTO intentos_autenticacion (clave, intentos, inicio_ventana)
            SELECT :clave, 1, ahora FROM momento WHERE TRUE
            ON CONFLICT (clave) DO UPDATE SET
                intentos = CASE
                    WHEN intentos_autenticacion.inicio_ventana <= (SELECT ahora - ventana FROM momento)
                    THEN 1 ELSE LEAST(intentos_autenticacion.intentos, 2147483646) + 1 END,
                inicio_ventana = CASE
                    WHEN intentos_autenticacion.inicio_ventana <= (SELECT ahora - ventana FROM momento)
                    THEN (SELECT ahora FROM momento) ELSE intentos_autenticacion.inicio_ventana END
            RETURNING intentos"
        );
        $consulta->execute(['ventana' => $ventanaSegundos, 'clave' => $clave]);
        return (int) $consulta->fetchColumn() <= $limite;
    }

    public function limpiarExpirados(): void
    {
        $this->conexionPostgresql->exec(
            "DELETE FROM intentos_autenticacion WHERE inicio_ventana < clock_timestamp() - INTERVAL '24 hours'"
        );
    }
}
