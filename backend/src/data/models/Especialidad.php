<?php
declare(strict_types=1);

namespace CitasMedicas\Data\Models;

final class Especialidad
{
    public function __construct(
        public readonly int $idEspecialidad,
        public readonly string $nombreEspecialidad,
        public readonly string $descripcionEspecialidad,
        public readonly bool $especialidadActiva,
        public readonly int $cantidadMedicosActivos
    ) {}

    public static function crearEspecialidadDesdeRegistro(array $registroEspecialidad): self
    {
        return new self(
            (int) $registroEspecialidad['id_especialidad'],
            $registroEspecialidad['nombre_especialidad'],
            $registroEspecialidad['descripcion_especialidad'],
            in_array($registroEspecialidad['especialidad_activa'], [true, 1, '1', 't', 'true'], true),
            (int) $registroEspecialidad['cantidad_medicos_activos']
        );
    }

    public function obtenerDatosPublicosEspecialidad(): array
    {
        return [
            'idEspecialidad' => $this->idEspecialidad,
            'nombreEspecialidad' => $this->nombreEspecialidad,
            'descripcionEspecialidad' => $this->descripcionEspecialidad,
            'especialidadActiva' => $this->especialidadActiva,
            'cantidadMedicosActivos' => $this->cantidadMedicosActivos,
        ];
    }
}
