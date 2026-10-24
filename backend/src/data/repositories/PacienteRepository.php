<?php

declare(strict_types=1);

namespace CitasMedicas\Data\Repositories;

use CitasMedicas\Data\Models\Paciente;

final class PacienteRepository
{
    private const CONSULTA_PACIENTE = 'SELECT pacientes.*, usuarios.usuario_activo, usuarios.nombres_usuario,
        usuarios.apellidos_usuario, usuarios.correo_usuario, historias_clinicas.id_historia_clinica
        FROM pacientes INNER JOIN usuarios ON usuarios.id_usuario = pacientes.id_usuario
        INNER JOIN historias_clinicas ON historias_clinicas.id_paciente = pacientes.id_paciente';

    public function __construct(private readonly \PDO $conexionPostgresql)
    {
    }

    public function registrarPaciente(array $datosPaciente, int $idUsuario): int
    {
        $consulta = $this->conexionPostgresql->prepare(
            'INSERT INTO pacientes (id_usuario, sexo_paciente, cedula_identidad, fecha_nacimiento, telefono_paciente,
                direccion_paciente, nombre_contacto_emergencia, telefono_contacto_emergencia)
            VALUES (:idUsuario, :sexoPaciente, :cedulaIdentidad, :fechaNacimiento, :telefonoPaciente,
                :direccionPaciente, :nombreContactoEmergencia, :telefonoContactoEmergencia)
            RETURNING id_paciente'
        );
        $consulta->execute([
            'sexoPaciente' => $datosPaciente['sexoPaciente'] ?? 'no_especificado', 'idUsuario' => $idUsuario, 'cedulaIdentidad' => $datosPaciente['cedulaIdentidad'],
            'fechaNacimiento' => $datosPaciente['fechaNacimiento'], 'telefonoPaciente' => $datosPaciente['telefonoPaciente'],
            'direccionPaciente' => $datosPaciente['direccionPaciente'],
            'nombreContactoEmergencia' => $datosPaciente['nombreContactoEmergencia'],
            'telefonoContactoEmergencia' => $datosPaciente['telefonoContactoEmergencia'],
        ]);
        return (int) $consulta->fetchColumn();
    }

    public function obtenerPacientePorId(int $idPaciente, bool $bloquear = false): ?Paciente
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_PACIENTE . ' WHERE pacientes.id_paciente = :idPaciente' . ($bloquear ? ' FOR UPDATE OF pacientes, usuarios' : ''));
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

    public function listarPacientesPorBusqueda(string $busqueda, int $pagina, string $estado = ''): array
    {
        $consulta = $this->conexionPostgresql->prepare(self::CONSULTA_PACIENTE .
            " WHERE concat_ws(' ', pacientes.cedula_identidad, usuarios.nombres_usuario, usuarios.apellidos_usuario) ILIKE :busqueda
            " . ($estado === '' ? '' : ($estado === 'activo' ? ' AND usuarios.usuario_activo' : ' AND NOT usuarios.usuario_activo')) . " ORDER BY pacientes.id_paciente DESC LIMIT 21 OFFSET :desplazamiento");
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

    public function actualizarDatosPaciente(int $idPaciente, array $datos): void
    {
        $campos = ['cedulaIdentidad','fechaNacimiento','sexoPaciente','telefonoPaciente','direccionPaciente','nombreContactoEmergencia','telefonoContactoEmergencia'];
        $consulta = $this->conexionPostgresql->prepare('UPDATE pacientes SET cedula_identidad=:cedulaIdentidad,fecha_nacimiento=:fechaNacimiento,sexo_paciente=:sexoPaciente,
            telefono_paciente=:telefonoPaciente,direccion_paciente=:direccionPaciente,nombre_contacto_emergencia=:nombreContactoEmergencia,
            telefono_contacto_emergencia=:telefonoContactoEmergencia WHERE id_paciente=:idPaciente');
        $consulta->execute(['idPaciente' => $idPaciente] + array_intersect_key($datos, array_flip($campos)));
    }

    public function bloquearPaciente(int $idPaciente): ?array
    {
        $consulta = $this->conexionPostgresql->prepare(
            'SELECT pacientes.id_paciente, pacientes.id_usuario, usuarios.usuario_activo
             FROM pacientes JOIN usuarios ON usuarios.id_usuario = pacientes.id_usuario
             WHERE pacientes.id_paciente = :idPaciente FOR UPDATE OF pacientes, usuarios'
        );
        $consulta->execute(['idPaciente' => $idPaciente]);
        return $consulta->fetch() ?: null;
    }

    public function obtenerIdPacientePorUsuario(int $idUsuario): ?int
    {
        $consulta = $this->conexionPostgresql->prepare('SELECT id_paciente FROM pacientes WHERE id_usuario = :idUsuario');
        $consulta->execute(['idUsuario' => $idUsuario]);
        $resultado = $consulta->fetchColumn();
        return $resultado === false ? null : (int) $resultado;
    }
}
