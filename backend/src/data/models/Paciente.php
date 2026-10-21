<?php
declare(strict_types=1);

namespace CitasMedicas\Data\Models;

final class Paciente
{
    public function __construct(private readonly array $registroPaciente) {}

    public function obtenerDatosPublicosPaciente(): array
    {
        return [
            'idPaciente' => (int) $this->registroPaciente['id_paciente'],
            'idUsuario' => (int) $this->registroPaciente['id_usuario'],
            'nombresUsuario' => $this->registroPaciente['nombres_usuario'],
            'apellidosUsuario' => $this->registroPaciente['apellidos_usuario'],
            'correoUsuario' => $this->registroPaciente['correo_usuario'],
            'cedulaIdentidad' => $this->registroPaciente['cedula_identidad'],
            'fechaNacimiento' => $this->registroPaciente['fecha_nacimiento'],
            'telefonoPaciente' => $this->registroPaciente['telefono_paciente'],
            'direccionPaciente' => $this->registroPaciente['direccion_paciente'],
            'nombreContactoEmergencia' => $this->registroPaciente['nombre_contacto_emergencia'],
            'telefonoContactoEmergencia' => $this->registroPaciente['telefono_contacto_emergencia'],
            'idHistoriaClinica' => (int) $this->registroPaciente['id_historia_clinica'],
        ];
    }
}
