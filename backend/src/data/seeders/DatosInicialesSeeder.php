<?php
declare(strict_types=1);

namespace CitasMedicas\Data\Seeders;

use CitasMedicas\Business\Validators\ValidarCamposFormulario;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Data\Repositories\PacienteRepository;
use CitasMedicas\Data\Repositories\HistoriaClinicaRepository;

final class DatosInicialesSeeder
{
    public static function cargarDatosIniciales(\PDO $conexionPostgresql, string $contrasenaPrueba): void
    {
        ValidarCamposFormulario::validarContrasenaRegistro([
            'contrasenaUsuario' => $contrasenaPrueba, 'confirmacionContrasena' => $contrasenaPrueba,
        ]);
        $conexionPostgresql->beginTransaction();
        try {
            $conexionPostgresql->query('SELECT pg_advisory_xact_lock(2026102101)');
            if ((int) $conexionPostgresql->query('SELECT count(*) FROM usuarios')->fetchColumn() !== 0) {
                throw new \RuntimeException('Ya existen usuarios. El seeder no reemplaza cuentas ni claves.');
            }
            $insertarRol = $conexionPostgresql->prepare(
                'INSERT INTO roles (id_rol, codigo_rol, nombre_rol) VALUES (:idRol, :codigoRol, :nombreRol)'
            );
            foreach ([[1, 'administrador', 'Administrador'], [2, 'recepcionista', 'Recepcionista'],
                    [3, 'medico', 'Medico'], [4, 'paciente', 'Paciente']] as [$idRol, $codigoRol, $nombreRol]) {
                $insertarRol->execute(['idRol' => $idRol, 'codigoRol' => $codigoRol, 'nombreRol' => $nombreRol]);
            }
            $conexionPostgresql->query("SELECT setval(pg_get_serial_sequence('roles', 'id_rol'), 4, true)");
            $insertarEspecialidad = $conexionPostgresql->prepare(
                'INSERT INTO especialidades (nombre_especialidad) VALUES (:nombreEspecialidad) RETURNING id_especialidad'
            );
            $idMedicinaGeneral = 0;
            foreach (['Medicina general', 'Pediatria', 'Cardiologia'] as $nombreEspecialidad) {
                $insertarEspecialidad->execute(['nombreEspecialidad' => $nombreEspecialidad]);
                $idEspecialidad = (int) $insertarEspecialidad->fetchColumn();
                if ($nombreEspecialidad === 'Medicina general') { $idMedicinaGeneral = $idEspecialidad; }
            }
            $usuarioRepository = new UsuarioRepository($conexionPostgresql);
            foreach ([
                [1, 'Admin', 'Prueba', 'administrador@vidasalud.test'],
                [2, 'Recepcion', 'Prueba', 'recepcionista@vidasalud.test'],
                [3, 'Medico', 'Prueba', 'medico@vidasalud.test'],
                [4, 'Paciente', 'Prueba', 'paciente@vidasalud.test'],
            ] as [$idRol, $nombresUsuario, $apellidosUsuario, $correoUsuario]) {
                $datosUsuario = compact('nombresUsuario', 'apellidosUsuario', 'correoUsuario');
                $idUsuario = $usuarioRepository->registrarUsuario(
                    $datosUsuario, $idRol, password_hash($contrasenaPrueba, PASSWORD_DEFAULT)
                );
                if ($idRol === 3) {
                    $insertarMedico = $conexionPostgresql->prepare(
                        'INSERT INTO medicos (id_usuario, id_especialidad, matricula_profesional)
                        VALUES (:idUsuario, :idEspecialidad, :matriculaProfesional)'
                    );
                    $insertarMedico->execute(['idUsuario' => $idUsuario, 'idEspecialidad' => $idMedicinaGeneral, 'matriculaProfesional' => 'DEMO-M001']);
                }
                if ($idRol === 4) {
                    $pacienteRepository = new PacienteRepository($conexionPostgresql);
                    $idPaciente = $pacienteRepository->registrarPaciente([
                        'cedulaIdentidad' => 'DEMO-P001', 'fechaNacimiento' => '2000-01-15',
                        'telefonoPaciente' => '70000000', 'direccionPaciente' => 'Direccion de prueba',
                        'nombreContactoEmergencia' => '', 'telefonoContactoEmergencia' => '',
                    ], $idUsuario);
                    (new HistoriaClinicaRepository($conexionPostgresql))->crearHistoriaClinicaParaPaciente($idPaciente);
                }
            }
            $conexionPostgresql->commit();
        } catch (\Throwable $error) {
            if ($conexionPostgresql->inTransaction()) { $conexionPostgresql->rollBack(); }
            throw $error;
        }
    }
}
