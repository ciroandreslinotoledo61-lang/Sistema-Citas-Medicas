<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Models;

final class Usuario
{
    public function __construct(
        public readonly int $idUsuario,
        public readonly string $nombresUsuario,
        public readonly string $apellidosUsuario,
        public readonly string $correoUsuario,
        private readonly string $contrasenaHash,
        public readonly bool $usuarioActivo,
        public readonly int $versionCredenciales,
        public readonly int $idRol,
        public readonly string $codigoRol,
        public readonly string $nombreRol
    ) {
    }

    public static function crearUsuarioDesdeRegistro(array $registroUsuario): self
    {
        return new self(
            (int) $registroUsuario['id_usuario'],
            $registroUsuario['nombres_usuario'],
            $registroUsuario['apellidos_usuario'],
            $registroUsuario['correo_usuario'],
            $registroUsuario['contrasena_hash'],
            in_array($registroUsuario['usuario_activo'], [true, 1, '1', 't', 'true'], true),
            (int) $registroUsuario['version_credenciales'],
            (int) $registroUsuario['id_rol'],
            $registroUsuario['codigo_rol'],
            $registroUsuario['nombre_rol']
        );
    }

    public function verificarContrasenaUsuario(string $contrasena): bool
    {
        return password_verify($contrasena, $this->contrasenaHash);
    }

    public function obtenerDatosPublicosUsuario(): array
    {
        return [
            'idUsuario' => $this->idUsuario,
            'nombresUsuario' => $this->nombresUsuario,
            'apellidosUsuario' => $this->apellidosUsuario,
            'correoUsuario' => $this->correoUsuario,
            'usuarioActivo' => $this->usuarioActivo,
            'idRol' => $this->idRol, 'codigoRol' => $this->codigoRol, 'nombreRol' => $this->nombreRol,
        ];
    }
}
