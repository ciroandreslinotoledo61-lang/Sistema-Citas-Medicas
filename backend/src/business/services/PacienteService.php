<?php
declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\PacienteValidator;
use CitasMedicas\Business\Validators\ValidarCamposFormulario;
use CitasMedicas\Data\Models\Paciente;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Shared\Constants\PermisosSistema;
use CitasMedicas\Data\Repositories\PacienteRepository;
use CitasMedicas\Data\Repositories\HistoriaClinicaRepository;
use CitasMedicas\Data\Repositories\RolRepository;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Shared\Constants\RolesSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class PacienteService
{
    private readonly PacienteRepository $pacienteRepository;

    public function __construct(private readonly \PDO $conexionPostgresql)
    {
        $this->pacienteRepository = new PacienteRepository($conexionPostgresql);
    }

    public function registrarPacienteConHistoriaClinica(array $datos): Paciente
    {
        $datosValidados = PacienteValidator::validarDatosRegistroPaciente($datos);
        $contrasenaHash = password_hash($datosValidados['contrasenaUsuario'], PASSWORD_DEFAULT);
        $this->conexionPostgresql->beginTransaction();
        try {
            $rolRepository = new RolRepository($this->conexionPostgresql);
            $rolPaciente = $rolRepository->obtenerRolPorCodigo(RolesSistema::PACIENTE)
                ?? throw new ErrorAplicacion('El rol paciente no esta configurado.', 409);
            $usuarioRepository = new UsuarioRepository($this->conexionPostgresql);
            $idUsuario = $usuarioRepository->registrarUsuario($datosValidados, $rolPaciente->idRol, $contrasenaHash);
            $idPaciente = $this->pacienteRepository->registrarPaciente($datosValidados, $idUsuario);
            $historiaRepository = new HistoriaClinicaRepository($this->conexionPostgresql);
            $historiaRepository->crearHistoriaClinicaParaPaciente($idPaciente);
            $paciente = $this->obtenerPacientePorId($idPaciente);
            $this->conexionPostgresql->commit();
            return $paciente;
        } catch (\Throwable $error) {
            if ($this->conexionPostgresql->inTransaction()) {
                $this->conexionPostgresql->rollBack();
            }
            throw $error;
        }
    }

    public function listarPacientes(array $parametros): array
    {
        $filtros = ValidarCamposFormulario::validarParametrosBusqueda($parametros);
        $estado = $parametros['estado'] ?? '';
        if (!is_string($estado) || !in_array($estado,['','activo','inactivo'],true)) { throw new ErrorAplicacion('El estado no es valido.',422); }
        $pacientes = $this->pacienteRepository->listarPacientesPorBusqueda($filtros['busqueda'], $filtros['pagina'], $estado);
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
        PermisosSistema::exigirPermiso($usuario,'pacientes.gestionar');
        $datos=PacienteValidator::validarDatosActualizacionPaciente($datos);
        $this->conexionPostgresql->beginTransaction();
        try {
            $paciente=$this->pacienteRepository->obtenerPacientePorId($idPaciente,true) ?? throw new ErrorAplicacion('El paciente no existe.',404);
            (new UsuarioRepository($this->conexionPostgresql))->actualizarDatosUsuario($paciente->obtenerDatosPublicosPaciente()['idUsuario'],$datos);
            $this->pacienteRepository->actualizarDatosPaciente($idPaciente,$datos);
            $resultado=$this->obtenerPacientePorId($idPaciente); $this->conexionPostgresql->commit(); return $resultado;
        } catch (\Throwable $error) {
            if ($this->conexionPostgresql->inTransaction()) { $this->conexionPostgresql->rollBack(); }
            if ($error instanceof \PDOException && (string)$error->getCode()==='23505') { throw new ErrorAplicacion('El correo o la cedula ya estan registrados.',409); }
            throw $error;
        }
    }
    public function cambiarEstadoPaciente(Usuario $usuario, int $idPaciente, array $datos): Paciente
    {
        PermisosSistema::exigirPermiso($usuario,'pacientes.gestionar');
        $activo=PacienteValidator::validarEstadoPaciente($datos);
        $this->conexionPostgresql->beginTransaction();
        try {
            $paciente=$this->pacienteRepository->obtenerPacientePorId($idPaciente,true) ?? throw new ErrorAplicacion('El paciente no existe.',404);
            $registro=$paciente->obtenerDatosPublicosPaciente();
            if ($registro['pacienteActivo']!==$activo) { (new UsuarioRepository($this->conexionPostgresql))->cambiarEstadoUsuario($registro['idUsuario'],$activo); }
            $resultado=$this->obtenerPacientePorId($idPaciente); $this->conexionPostgresql->commit(); return $resultado;
        } catch (\Throwable $error) {
            if ($this->conexionPostgresql->inTransaction()) { $this->conexionPostgresql->rollBack(); } throw $error;
        }
    }

}
