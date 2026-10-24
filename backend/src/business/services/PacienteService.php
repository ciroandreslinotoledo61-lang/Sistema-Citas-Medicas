<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\PacienteValidator;
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Models\Paciente;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\HistoriaClinicaRepository;
use CitasMedicas\Data\Repositories\PacienteRepository;
use CitasMedicas\Data\Repositories\RolRepository;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Constants\PermisosSistema;
use CitasMedicas\Shared\Constants\RolesSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class PacienteService
{
    private readonly PacienteRepository $pacienteRepository;

    private readonly Transaccion $transaccion;
    private readonly UsuarioRepository $usuarioRepository;
    private readonly RolRepository $rolRepository;
    private readonly HistoriaClinicaRepository $historiaRepository;

    public function __construct(\PDO $conexionPostgresql)
    {
        $this->transaccion = new Transaccion($conexionPostgresql);
        $this->pacienteRepository = new PacienteRepository($conexionPostgresql);
        $this->usuarioRepository = new UsuarioRepository($conexionPostgresql);
        $this->rolRepository = new RolRepository($conexionPostgresql);
        $this->historiaRepository = new HistoriaClinicaRepository($conexionPostgresql);
    }

    public function registrarPacienteConHistoriaClinica(array $datos): Paciente
    {
        $datosValidados = PacienteValidator::validarDatosRegistroPaciente($datos);
        $contrasenaHash = password_hash($datosValidados['contrasenaUsuario'], PASSWORD_DEFAULT);
        return $this->transaccion->ejecutar(function () use ($datosValidados, $contrasenaHash): Paciente {
            $rolPaciente = $this->rolRepository->obtenerRolPorCodigo(RolesSistema::PACIENTE)
            ?? throw new ErrorAplicacion('El rol paciente no esta configurado.', 409);
            $idUsuario = $this->usuarioRepository->registrarUsuario($datosValidados, $rolPaciente->idRol, $contrasenaHash);
            $idPaciente = $this->pacienteRepository->registrarPaciente($datosValidados, $idUsuario);
            $this->historiaRepository->crearHistoriaClinicaParaPaciente($idPaciente);
            $paciente = $this->obtenerPacientePorId($idPaciente);

            return $paciente;
        });
    }

    public function listarPacientes(array $parametros): array
    {
        $filtros = PacienteValidator::validarFiltrosPacientes($parametros);
        $pacientes = $this->pacienteRepository->listarPacientesPorBusqueda($filtros['busqueda'], $filtros['pagina'], $filtros['estado']);
        return [
            'elementos' => array_map(static fn(Paciente $paciente): array => $paciente->obtenerDatosPublicosPaciente(), array_slice($pacientes, 0, 20)),
            'pagina' => $filtros['pagina'], 'hayPaginaSiguiente' => count($pacientes) > 20,
        ];
    }

    public function obtenerPacientePorId(int $idPaciente): Paciente
    {
        return $this->pacienteRepository->obtenerPacientePorId($idPaciente)
            ?? throw new ErrorAplicacion('El paciente no existe.', 404);
    }

    public function obtenerPacientePorUsuario(int $idUsuario): Paciente
    {
        return $this->pacienteRepository->obtenerPacientePorIdUsuario($idUsuario)
            ?? throw new ErrorAplicacion('El perfil del paciente no existe.', 404);
    }

    public function actualizarContactoPaciente(int $idPaciente, array $datos): Paciente
    {
        $this->obtenerPacientePorId($idPaciente);
        $datosValidados = PacienteValidator::validarDatosContactoPaciente($datos);
        $this->pacienteRepository->actualizarContactoPaciente($idPaciente, $datosValidados);
        return $this->obtenerPacientePorId($idPaciente);
    }

    public function actualizarDatosPaciente(Usuario $usuario, int $idPaciente, array $datos): Paciente
    {
        PermisosSistema::exigirPermiso($usuario, 'pacientes.gestionar');
        $datos = PacienteValidator::validarDatosActualizacionPaciente($datos);
        try {
            return $this->transaccion->ejecutar(function () use ($idPaciente, $datos): Paciente {
                $paciente = $this->pacienteRepository->obtenerPacientePorId($idPaciente, true) ?? throw new ErrorAplicacion('El paciente no existe.', 404);
                $this->usuarioRepository->actualizarDatosUsuario($paciente->obtenerDatosPublicosPaciente()['idUsuario'], $datos);
                $this->pacienteRepository->actualizarDatosPaciente($idPaciente, $datos);
                $resultado = $this->obtenerPacientePorId($idPaciente);
                return $resultado;
            });
        } catch (\Throwable $error) {
            if ($error instanceof \PDOException && (string)$error->getCode() === '23505') {
                throw new ErrorAplicacion('El correo o la cedula ya estan registrados.', 409);
            }
            throw $error;
        }
    }

    public function cambiarEstadoPaciente(Usuario $usuario, int $idPaciente, array $datos): Paciente
    {
        PermisosSistema::exigirPermiso($usuario, 'pacientes.gestionar');
        $activo = PacienteValidator::validarEstadoPaciente($datos);
        return $this->transaccion->ejecutar(function () use ($idPaciente, $activo): Paciente {
            $paciente = $this->pacienteRepository->obtenerPacientePorId($idPaciente, true) ?? throw new ErrorAplicacion('El paciente no existe.', 404);
            $registro = $paciente->obtenerDatosPublicosPaciente();
            if ($registro['pacienteActivo'] !== $activo) {
                $this->usuarioRepository->cambiarEstadoUsuario($registro['idUsuario'], $activo);
            }
            $resultado = $this->obtenerPacientePorId($idPaciente);
            return $resultado;
        });
    }
}
