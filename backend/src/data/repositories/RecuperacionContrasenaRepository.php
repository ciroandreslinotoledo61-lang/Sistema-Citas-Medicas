<?php
declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

final class RecuperacionContrasenaRepository
{
    public function __construct(private readonly \PDO $conexionPostgresql) {}

    /** El servicio ya mantiene bloqueada la cuenta: siempre cuenta antes que token. */
    public function guardarToken(int $idUsuario, string $tokenHash, int $version, string $correoHash): void
    {
        $consulta = $this->conexionPostgresql->prepare(
            "INSERT INTO recuperaciones_contrasena
                (id_usuario, token_hash, correo_hash, version_credenciales, expira_en)
             VALUES (:id, :token, :correo, :version, clock_timestamp() + INTERVAL '30 minutes')
             ON CONFLICT (id_usuario) DO UPDATE SET token_hash = EXCLUDED.token_hash,
                correo_hash = EXCLUDED.correo_hash, version_credenciales = EXCLUDED.version_credenciales,
                creado_en = EXCLUDED.creado_en, expira_en = EXCLUDED.expira_en, usado_en = NULL"
        );
        $consulta->execute(['id' => $idUsuario, 'token' => $tokenHash, 'correo' => $correoHash, 'version' => $version]);
    }

    public function obtenerIdUsuarioPorToken(string $tokenHash): ?int
    {
        $consulta = $this->conexionPostgresql->prepare(
            'SELECT id_usuario FROM recuperaciones_contrasena WHERE token_hash = :token
             AND usado_en IS NULL AND expira_en > clock_timestamp()'
        );
        $consulta->execute(['token' => $tokenHash]);
        $id = $consulta->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    public function consumirToken(int $idUsuario, string $tokenHash, int $version, string $correoHash): bool
    {
        $consulta = $this->conexionPostgresql->prepare(
            'UPDATE recuperaciones_contrasena SET usado_en = clock_timestamp()
             WHERE id_usuario = :id AND token_hash = :token AND correo_hash = :correo
             AND version_credenciales = :version AND usado_en IS NULL AND expira_en > clock_timestamp()'
        );
        $consulta->execute(['id' => $idUsuario, 'token' => $tokenHash, 'correo' => $correoHash, 'version' => $version]);
        return $consulta->rowCount() === 1;
    }

    /** No revoca un enlace posterior si el proveedor respondio tarde. */
    public function invalidarToken(string $tokenHash): void
    {
        $consulta = $this->conexionPostgresql->prepare(
            'UPDATE recuperaciones_contrasena SET usado_en = clock_timestamp()
             WHERE token_hash = :token AND usado_en IS NULL'
        );
        $consulta->execute(['token' => $tokenHash]);
    }
}
