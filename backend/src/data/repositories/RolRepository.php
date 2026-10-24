<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

use CitasMedicas\Data\Models\Rol;

final class RolRepository
{
    public function __construct(private readonly \PDO $conexionPostgresql)
    {
    }

    public function listarRolesSistema(): array
    {
        $consulta = $this->conexionPostgresql->prepare('SELECT id_rol, codigo_rol, nombre_rol FROM roles ORDER BY id_rol');
        $consulta->execute();
        return array_map([Rol::class, 'crearRolDesdeRegistro'], $consulta->fetchAll());
    }

    public function obtenerRolPorCodigo(string $codigoRol, bool $bloquearRegistro = false): ?Rol
    {
        $consulta = $this->conexionPostgresql->prepare(
            'SELECT id_rol, codigo_rol, nombre_rol FROM roles WHERE codigo_rol = :codigoRol' .
            ($bloquearRegistro ? ' FOR UPDATE' : '')
        );
        $consulta->execute(['codigoRol' => $codigoRol]);
        $registroRol = $consulta->fetch();
        return $registroRol ? Rol::crearRolDesdeRegistro($registroRol) : null;
    }
}
