<?php
declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\MedicoValidator;
use CitasMedicas\Business\Validators\ValidarCamposFormulario;
use CitasMedicas\Data\Models\Medico;
use CitasMedicas\Data\Repositories\MedicoRepository;
use CitasMedicas\Data\Repositories\EspecialidadRepository;
use CitasMedicas\Data\Repositories\UsuarioRepository;
use CitasMedicas\Data\Repositories\RolRepository;
use CitasMedicas\Data\Repositories\CitaRepository;
use CitasMedicas\Shared\Constants\RolesSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class MedicoService
{
    private readonly MedicoRepository $medicoRepository;
    private readonly EspecialidadRepository $especialidadRepository;
    private readonly UsuarioRepository $usuarioRepository;

    public function __construct(private readonly \PDO $conexionPostgresql)
    {
        $this->medicoRepository = new MedicoRepository($conexionPostgresql);
        $this->especialidadRepository = new EspecialidadRepository($conexionPostgresql);
        $this->usuarioRepository = new UsuarioRepository($conexionPostgresql);
    }

    public function listarMedicos(array $parametros): array
    {
        $filtros = ValidarCamposFormulario::validarParametrosBusqueda($parametros);
        $medicos = $this->medicoRepository->listarMedicosPorBusqueda($filtros['busqueda'], $filtros['pagina']);
        return [
            'elementos' => array_map(static fn(Medico $medico): array => $medico->obtenerDatosPublicosMedico(), array_slice($medicos, 0, 20)),
            'pagina' => $filtros['pagina'], 'hayPaginaSiguiente' => count($medicos) > 20,
        ];
    }

    public function obtenerMedicoPorId(int $idMedico): Medico
    {
        return $this->medicoRepository->obtenerMedicoPorId($idMedico)
            ?? throw new ErrorAplicacion('El medico no existe.', 404);
    }

    public function registrarMedicoConUsuario(array $datos): Medico
    {
        $datosValidados = MedicoValidator::validarDatosRegistroMedico($datos);
        $contrasenaHash = password_hash($datosValidados['contrasenaUsuario'], PASSWORD_DEFAULT);
        $this->conexionPostgresql->beginTransaction();
        try {
            $this->exigirEspecialidadActiva($datosValidados['idEspecialidad']);
            $this->exigirDatosUnicosMedico($datosValidados);
            $rolMedico = (new RolRepository($this->conexionPostgresql))->obtenerRolPorCodigo(RolesSistema::MEDICO)
                ?? throw new ErrorAplicacion('El rol Medico no esta configurado.', 409);
            $idUsuario = $this->usuarioRepository->registrarUsuario($datosValidados, $rolMedico->idRol, $contrasenaHash);
            $idMedico = $this->medicoRepository->registrarPerfilMedico($idUsuario, $datosValidados);
            $medico = $this->obtenerMedicoPorId($idMedico);
            $this->conexionPostgresql->commit();
            return $medico;
        } catch (\Throwable $error) {
            if ($this->conexionPostgresql->inTransaction()) { $this->conexionPostgresql->rollBack(); }
            if ($error instanceof \PDOException && (string) $error->getCode() === '23505') {
                throw new ErrorAplicacion('El correo o la matricula profesional ya estan registrados.', 409);
            }
            throw $error;
        }
    }

    public function actualizarDatosMedico(int $idMedico, array $datos): Medico
    {
        $datosValidados = MedicoValidator::validarDatosActualizacionMedico($datos);
        $this->conexionPostgresql->beginTransaction();
        try {
            $medico = $this->medicoRepository->obtenerMedicoPorId($idMedico, true)
                ?? throw new ErrorAplicacion('El medico no existe.', 404);
            if ($medico->idEspecialidad !== $datosValidados['idEspecialidad']) {
                $this->exigirMedicoSinCitasVigentes($idMedico);
            }
            // Un medico inactivo puede conservar su especialidad inactiva al corregir sus datos.
            if ($medico->medicoActivo || $medico->idEspecialidad !== $datosValidados['idEspecialidad']) {
                $this->exigirEspecialidadActiva($datosValidados['idEspecialidad']);
            }
            $this->exigirDatosUnicosMedico($datosValidados, $medico->idMedico, $medico->idUsuario);
            $this->usuarioRepository->actualizarDatosUsuario($medico->idUsuario, $datosValidados);
            $this->medicoRepository->actualizarPerfilMedico($idMedico, $datosValidados);
            $medicoActualizado = $this->obtenerMedicoPorId($idMedico);
            $this->conexionPostgresql->commit();
            return $medicoActualizado;
        } catch (\Throwable $error) {
            if ($this->conexionPostgresql->inTransaction()) { $this->conexionPostgresql->rollBack(); }
            if ($error instanceof \PDOException && (string) $error->getCode() === '23505') {
                throw new ErrorAplicacion('El correo o la matricula profesional ya estan registrados.', 409);
            }
            throw $error;
        }
    }

    public function cambiarEstadoMedico(int $idMedico, array $datos): Medico
    {
        $medicoActivo = MedicoValidator::validarEstadoMedico($datos);
        $this->conexionPostgresql->beginTransaction();
        try {
            $medico = $this->medicoRepository->obtenerMedicoPorId($idMedico, true)
                ?? throw new ErrorAplicacion('El medico no existe.', 404);
            if (!$medicoActivo && $medico->medicoActivo) {
                $this->exigirMedicoSinCitasVigentes($idMedico);
            }
            if ($medicoActivo) {
                $this->exigirEspecialidadActiva($medico->idEspecialidad);
            }
            if ($medico->medicoActivo !== $medicoActivo) {
                // La cuenta es la unica fuente del estado. Tambien invalida sesiones anteriores.
                $this->usuarioRepository->cambiarEstadoUsuario($medico->idUsuario, $medicoActivo);
            }
            $medicoActualizado = $this->obtenerMedicoPorId($idMedico);
            $this->conexionPostgresql->commit();
            return $medicoActualizado;
        } catch (\Throwable $error) {
            if ($this->conexionPostgresql->inTransaction()) { $this->conexionPostgresql->rollBack(); }
            throw $error;
        }
    }

    private function exigirMedicoSinCitasVigentes(int $idMedico): void
    {
        if ((new CitaRepository($this->conexionPostgresql))->medicoTieneCitasVigentes($idMedico, date('Y-m-d H:i:s'))) {
            throw new ErrorAplicacion('Primero anula o reprograma las citas vigentes del medico antes de desactivarlo o cambiar su especialidad.', 409);
        }
    }

    private function exigirEspecialidadActiva(int $idEspecialidad): void
    {
        $especialidad = $this->especialidadRepository->obtenerEspecialidadPorId($idEspecialidad, true)
            ?? throw new ErrorAplicacion('La especialidad seleccionada no existe.', 404);
        if (!$especialidad->especialidadActiva) {
            throw new ErrorAplicacion('Selecciona una especialidad activa. Puedes reactivarla desde Especialidades.', 409);
        }
    }

    private function exigirDatosUnicosMedico(array $datosMedico, int $idMedicoExcluido = 0, int $idUsuarioExcluido = 0): void
    {
        if ($this->medicoRepository->existeMedicoConMatricula($datosMedico['matriculaProfesional'], $idMedicoExcluido)) {
            throw new ErrorAplicacion('La matricula profesional ya esta registrada.', 409, [
                'matriculaProfesional' => 'Esa matricula pertenece a otro medico.',
            ]);
        }
        $usuarioConCorreo = $this->usuarioRepository->obtenerUsuarioPorCorreo($datosMedico['correoUsuario']);
        if ($usuarioConCorreo !== null && $usuarioConCorreo->idUsuario !== $idUsuarioExcluido) {
            throw new ErrorAplicacion('El correo ya esta registrado.', 409, ['correoUsuario' => 'Usa un correo diferente.']);
        }
    }
}
