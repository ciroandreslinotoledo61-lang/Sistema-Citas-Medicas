<?php
declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

use CitasMedicas\Data\Models\Usuario;

final class UsuarioRepository
{
    private const CONSULTA_USUARIO = 'SELECT usuarios.*, roles.codigo_rol, roles.nombre_rol
        FROM usuarios INNER JOIN roles ON roles.id_rol = usuarios.id_rol';

    public function __construct(private readonly \PDO $conexionPostgresql) {}

    public function obtenerUsuarioPorId(int $idUsuario, bool $bloquearRegistro = false): ?Usuario
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_USUARIO .
            ' WHERE usuarios.id_usuario = :idUsuario' . ($bloquearRegistro ? ' FOR UPDATE OF usuarios' : ''));
        $consulta->execute(['idUsuario' => $idUsuario]);
        $registroUsuario = $consulta->fetch();
        return $registroUsuario ? Usuario::crearUsuarioDesdeRegistro($registroUsuario) : null;
    }

    public function obtenerUsuarioPorCorreo(string $correoUsuario): ?Usuario
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_USUARIO . ' WHERE usuarios.correo_usuario = :correoUsuario');
        $consulta->execute(['correoUsuario' => $correoUsuario]);
        $registroUsuario = $consulta->fetch();
        return $registroUsuario ? Usuario::crearUsuarioDesdeRegistro($registroUsuario) : null;
    }

    public function listarUsuariosPorBusqueda(string $busqueda, int $pagina): array
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_USUARIO .
            " WHERE concat_ws(' ', usuarios.nombres_usuario, usuarios.apellidos_usuario, usuarios.correo_usuario) ILIKE :busqueda
            ORDER BY usuarios.id_usuario DESC LIMIT 21 OFFSET :desplazamiento");
        $consulta->bindValue(':busqueda', '%' . $busqueda . '%');
        $consulta->bindValue(':desplazamiento', ($pagina - 1) * 20, \PDO::PARAM_INT);
        $consulta->execute();
        return array_map([Usuario::class, 'crearUsuarioDesdeRegistro'], $consulta->fetchAll());
    }

    public function registrarUsuario(array $datosUsuario, int $idRol, string $contrasenaHash): int
    {
        $consulta = $this->conexionPostgresql->prepare(
            'INSERT INTO usuarios (id_rol, nombres_usuario, apellidos_usuario, correo_usuario, contrasena_hash)
            VALUES (:idRol, :nombresUsuario, :apellidosUsuario, :correoUsuario, :contrasenaHash) RETURNING id_usuario'
        );
        $consulta->execute([
            'idRol' => $idRol, 'nombresUsuario' => $datosUsuario['nombresUsuario'],
            'apellidosUsuario' => $datosUsuario['apellidosUsuario'], 'correoUsuario' => $datosUsuario['correoUsuario'],
            'contrasenaHash' => $contrasenaHash,
        ]);
        return (int) $consulta->fetchColumn();
    }

    public function actualizarDatosUsuario(int $idUsuario, array $datosUsuario): void
    {
        $consulta = $this->conexionPostgresql->prepare(
            'UPDATE usuarios SET nombres_usuario = :nombresUsuario, apellidos_usuario = :apellidosUsuario,
            correo_usuario = :correoUsuario WHERE id_usuario = :idUsuario'
        );
        $consulta->execute([
            'idUsuario' => $idUsuario, 'nombresUsuario' => $datosUsuario['nombresUsuario'],
            'apellidosUsuario' => $datosUsuario['apellidosUsuario'], 'correoUsuario' => $datosUsuario['correoUsuario'],
        ]);
    }

    public function cambiarEstadoUsuario(int $idUsuario, bool $usuarioActivo): void
    {
        $consulta = $this->conexionPostgresql->prepare(
            'UPDATE usuarios SET usuario_activo = :usuarioActivo, version_credenciales = version_credenciales + 1
            WHERE id_usuario = :idUsuario'
        );
        $consulta->bindValue(':usuarioActivo', $usuarioActivo, \PDO::PARAM_BOOL);
        $consulta->bindValue(':idUsuario', $idUsuario, \PDO::PARAM_INT);
        $consulta->execute();
    }

    public function contarAdministradoresActivos(): int
    {
        $consulta = $this->conexionPostgresql->prepare(
            "SELECT count(*) FROM usuarios INNER JOIN roles USING (id_rol)
            WHERE roles.codigo_rol = 'administrador' AND usuarios.usuario_activo = TRUE"
        );
        $consulta->execute();
        return (int) $consulta->fetchColumn();
    }
}
