<?php
declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

use CitasMedicas\Data\Models\Paciente;

final class PacienteRepository
{
    private const CONSULTA_PACIENTE = 'SELECT pacientes.*, usuarios.nombres_usuario,
        usuarios.apellidos_usuario, usuarios.correo_usuario, historias_clinicas.id_historia_clinica
        FROM pacientes INNER JOIN usuarios ON usuarios.id_usuario = pacientes.id_usuario
        INNER JOIN historias_clinicas ON historias_clinicas.id_paciente = pacientes.id_paciente';

    public function __construct(private readonly \PDO $conexionPostgresql) {}

    public function registrarPaciente(array $datosPaciente, int $idUsuario): int
    {
        $consulta = $this->conexionPostgresql->prepare(
            'INSERT INTO pacientes (id_usuario, cedula_identidad, fecha_nacimiento, telefono_paciente,
                direccion_paciente, nombre_contacto_emergencia, telefono_contacto_emergencia)
             VALUES (:idUsuario, :cedulaIdentidad, :fechaNacimiento, :telefonoPaciente,
                :direccionPaciente, :nombreContactoEmergencia, :telefonoContactoEmergencia)
             RETURNING id_paciente'
        );
        $consulta->execute([
            'idUsuario' => $idUsuario, 'cedulaIdentidad' => $datosPaciente['cedulaIdentidad'],
            'fechaNacimiento' => $datosPaciente['fechaNacimiento'], 'telefonoPaciente' => $datosPaciente['telefonoPaciente'],
            'direccionPaciente' => $datosPaciente['direccionPaciente'],
            'nombreContactoEmergencia' => $datosPaciente['nombreContactoEmergencia'],
            'telefonoContactoEmergencia' => $datosPaciente['telefonoContactoEmergencia'],
        ]);
        return (int) $consulta->fetchColumn();
    }

    public function obtenerPacientePorId(int $idPaciente): ?Paciente
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_PACIENTE . ' WHERE pacientes.id_paciente = :idPaciente');
        $consulta->execute(['idPaciente' => $idPaciente]);
        $registroPaciente = $consulta->fetch();
        return $registroPaciente ? new Paciente($registroPaciente) : null;
    }

    public function obtenerPacientePorIdUsuario(int $idUsuario): ?Paciente
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_PACIENTE . ' WHERE pacientes.id_usuario = :idUsuario');
        $consulta->execute(['idUsuario' => $idUsuario]);
        $registroPaciente = $consulta->fetch();
        return $registroPaciente ? new Paciente($registroPaciente) : null;
    }

    public function listarPacientesPorBusqueda(string $busqueda, int $pagina): array
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_PACIENTE .
            " WHERE concat_ws(' ', pacientes.cedula_identidad, usuarios.nombres_usuario, usuarios.apellidos_usuario) ILIKE :busqueda
              ORDER BY pacientes.id_paciente DESC LIMIT 21 OFFSET :desplazamiento");
        $consulta->bindValue(':busqueda', '%' . $busqueda . '%');
        $consulta->bindValue(':desplazamiento', ($pagina - 1) * 20, \PDO::PARAM_INT);
        $consulta->execute();
        return array_map(static fn(array $registroPaciente): Paciente => new Paciente($registroPaciente), $consulta->fetchAll());
    }

    public function actualizarContactoPaciente(int $idPaciente, array $datosPaciente): void
    {
        $consulta = $this->conexionPostgresql->prepare(
            'UPDATE pacientes SET telefono_paciente = :telefonoPaciente, direccion_paciente = :direccionPaciente,
             nombre_contacto_emergencia = :nombreContactoEmergencia, telefono_contacto_emergencia = :telefonoContactoEmergencia
             WHERE id_paciente = :idPaciente'
        );
        $consulta->execute(['idPaciente' => $idPaciente] + $datosPaciente);
    }
}
