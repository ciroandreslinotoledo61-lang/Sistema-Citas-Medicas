<?php
declare(strict_types=1);

namespace CitasMedicas\Data\Models;

final class Medico
{
    public function __construct(
        public readonly int $idMedico,
        public readonly int $idUsuario,
        public readonly int $idEspecialidad,
        public readonly string $nombresUsuario,
        public readonly string $apellidosUsuario,
        public readonly string $correoUsuario,
        public readonly string $matriculaProfesional,
        public readonly string $nombreEspecialidad,
        public readonly bool $especialidadActiva,
        public readonly bool $medicoActivo
    ) {}

    public static function crearMedicoDesdeRegistro(array $registroMedico): self
    {
        return new self(
            (int) $registroMedico['id_medico'], (int) $registroMedico['id_usuario'],
            (int) $registroMedico['id_especialidad'],
            $registroMedico['nombres_usuario'], $registroMedico['apellidos_usuario'],
            $registroMedico['correo_usuario'], $registroMedico['matricula_profesional'],
            $registroMedico['nombre_especialidad'],
            in_array($registroMedico['especialidad_activa'], [true, 1, '1', 't', 'true'], true),
            in_array($registroMedico['usuario_activo'], [true, 1, '1', 't', 'true'], true)
        );
    }

    public function obtenerDatosPublicosMedico(): array
    {
        return [
            'idMedico' => $this->idMedico, 'idUsuario' => $this->idUsuario,
            'idEspecialidad' => $this->idEspecialidad,
            'nombresUsuario' => $this->nombresUsuario, 'apellidosUsuario' => $this->apellidosUsuario,
            'correoUsuario' => $this->correoUsuario, 'matriculaProfesional' => $this->matriculaProfesional,
            'nombreEspecialidad' => $this->nombreEspecialidad,
            'especialidadActiva' => $this->especialidadActiva, 'medicoActivo' => $this->medicoActivo,
        ];
    }
}
