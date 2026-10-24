<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Config;

use CitasMedicas\Shared\Config\CargarVariablesEntorno;

final class ConexionPostgresql
{
    private static ?\PDO $conexionPostgresql = null;

    public static function obtenerConexionPostgresql(): \PDO
    {
        if (self::$conexionPostgresql !== null) {
            return self::$conexionPostgresql;
        }
        if (!extension_loaded('pdo_pgsql')) {
            throw new \RuntimeException('Activa la extension pdo_pgsql en php.ini.');
        }
        $host = CargarVariablesEntorno::obtenerVariableEntorno('DB_HOST', '127.0.0.1');
        $puerto = CargarVariablesEntorno::obtenerVariableEntorno('DB_PORT', '5432');
        $nombre = CargarVariablesEntorno::obtenerVariableEntorno('DB_NAME', 'citas_medicas');
        $modoSsl = CargarVariablesEntorno::obtenerVariableEntorno('DB_SSLMODE', 'prefer');
        foreach ([$host, $puerto, $nombre, $modoSsl] as $valorConexion) {
            if (strpbrk($valorConexion, ";\r\n") !== false) {
                throw new \RuntimeException('Configuracion de conexion invalida.');
            }
        }
        if (!in_array($modoSsl, ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'], true)) {
            throw new \RuntimeException('DB_SSLMODE no es valido.');
        }
        self::$conexionPostgresql = new \PDO(
            "pgsql:host={$host};port={$puerto};dbname={$nombre};sslmode={$modoSsl}",
            CargarVariablesEntorno::obtenerVariableEntorno('DB_USER', 'postgres'),
            CargarVariablesEntorno::obtenerVariableEntorno('DB_PASS'),
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
             \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
             \PDO::ATTR_EMULATE_PREPARES => false]
        );
        self::$conexionPostgresql->exec("SET TIME ZONE 'America/La_Paz'");
        return self::$conexionPostgresql;
    }
}
