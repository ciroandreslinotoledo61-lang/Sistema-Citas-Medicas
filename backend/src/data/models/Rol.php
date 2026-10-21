<?php
declare(strict_types=1);

namespace CitasMedicas\Data\Models;

final class Rol
{
    public function __construct(
        public readonly int $idRol,
        public readonly string $codigoRol,
        public readonly string $nombreRol
    ) {}

    public static function crearRolDesdeRegistro(array $registroRol): self
    {
        return new self((int) $registroRol['id_rol'], $registroRol['codigo_rol'], $registroRol['nombre_rol']);
    }

    public function obtenerDatosPublicosRol(): array
    {
        return ['idRol' => $this->idRol, 'codigoRol' => $this->codigoRol, 'nombreRol' => $this->nombreRol];
    }
}
