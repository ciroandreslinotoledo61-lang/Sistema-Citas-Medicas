<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\AgendaValidator;
use CitasMedicas\Business\Validators\ValidarCamposFormulario;
use CitasMedicas\Data\Models\Cita;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\CitaRepository;
use CitasMedicas\Data\Repositories\HorarioMedicoRepository;
use CitasMedicas\Data\Repositories\MedicoRepository;
use CitasMedicas\Data\Repositories\EspecialidadRepository;
use CitasMedicas\Shared\Constants\PermisosSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class CitaService
{
    private readonly CitaRepository $citaRepository;
    private readonly HorarioMedicoRepository $horarioRepository;
    public function __construct(private readonly \PDO $conexionPostgresql, private readonly ?\DateTimeImmutable $momentoReferencia = null)
    {
        $this->citaRepository = new CitaRepository($conexionPostgresql);
        $this->horarioRepository = new HorarioMedicoRepository($conexionPostgresql);
    }
    private function obtenerAhora(): \DateTimeImmutable { return $this->momentoReferencia ?? new \DateTimeImmutable(); }

    public function listarCitas(Usuario $usuario, array $parametros): array
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.consultar');
        $filtros = AgendaValidator::validarFiltrosCitas($parametros);
        $citas = $this->citaRepository->listarCitas($usuario, $filtros, $this->obtenerAhora()->format('Y-m-d H:i:s'));
        return ['elementos' => array_map(fn (Cita $cita): array => $this->prepararDatosCita($cita, $usuario), array_slice($citas, 0, 20)),
            'pagina' => $filtros['pagina'], 'hayPaginaSiguiente' => count($citas) > 20];
    }

    public function obtenerCita(Usuario $usuario, int $idCita): array
    {
        return $this->prepararDatosCita($this->exigirCitaAutorizada($usuario, $idCita), $usuario);
    }

    public function reservarCita(Usuario $usuario, array $datos): array
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.reservar');
        $idTurno = AgendaValidator::validarIdentificador($datos, 'idTurno');
        $motivo = ValidarCamposFormulario::validarTexto($datos, 'motivoCita', 'El motivo', 500, false);
        if ($usuario->codigoRol === 'paciente') {
            $idPaciente = $this->citaRepository->obtenerIdPacientePorUsuario($usuario->idUsuario)
                ?? throw new ErrorAplicacion('Tu perfil de paciente no existe.', 404);
            if (array_key_exists('idPaciente', $datos) && AgendaValidator::validarIdentificador($datos, 'idPaciente') !== $idPaciente) {
                throw new ErrorAplicacion('Solo puedes reservar citas para tu propia cuenta.', 403);
            }
        } else {
            $idPaciente = AgendaValidator::validarIdentificador($datos, 'idPaciente');
        }
        return $this->ejecutarTransaccion(function () use ($usuario, $idPaciente, $idTurno, $motivo): array {
            $turno = $this->prepararReserva($idPaciente, $idTurno);
            $idCita = $this->citaRepository->registrarCita($idPaciente, $idTurno, $motivo, $usuario->idUsuario, null, $turno['idEspecialidad']);
            return $this->obtenerCita($usuario, $idCita);
        });
    }

    public function cancelarCita(Usuario $usuario, int $idCita, array $datos): array
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.cancelar');
        $motivo = ValidarCamposFormulario::validarTexto($datos, 'motivoCancelacion', 'El motivo de anulacion', 300, false);
        return $this->ejecutarTransaccion(function () use ($usuario, $idCita, $motivo): array {
            $cita = $this->exigirCitaAutorizada($usuario, $idCita, true);
            $this->exigirCitaModificable($cita);
            $this->citaRepository->actualizarEstadoCita($idCita, 'cancelada', $usuario->idUsuario, $motivo);
            return $this->obtenerCita($usuario, $idCita);
        });
    }

    public function reprogramarCita(Usuario $usuario, int $idCita, array $datos): array
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.reprogramar');
        $idTurno = AgendaValidator::validarIdentificador($datos, 'idTurno');
        return $this->ejecutarTransaccion(function () use ($usuario, $idCita, $idTurno): array {
            $cita = $this->exigirCitaAutorizada($usuario, $idCita, true);
            $this->exigirCitaModificable($cita);
            $datosCita = $cita->obtenerDatosPublicosCita();
            if ($datosCita['idTurno'] === $idTurno) { throw new ErrorAplicacion('Selecciona un turno diferente.', 422); }
            $turno = $this->prepararReserva($datosCita['idPaciente'], $idTurno, $idCita);
            // Los bloqueos del nuevo turno pueden haber esperado hasta despues del inicio anterior.
            $this->exigirCitaModificable($cita);
            $this->citaRepository->actualizarEstadoCita($idCita, 'reprogramada', $usuario->idUsuario);
            $idNuevaCita = $this->citaRepository->registrarCita($datosCita['idPaciente'], $idTurno,
                $datosCita['motivoCita'], $usuario->idUsuario, $idCita, $turno['idEspecialidad']);
            return $this->obtenerCita($usuario, $idNuevaCita);
        });
    }

    public function registrarAsistencia(Usuario $usuario, int $idCita, array $datos): array
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.asistencia');
        $asistencia = $datos['asistenciaRegistrada'] ?? '';
        if (!in_array($asistencia, ['asistio', 'no_asistio'], true)) { throw new ErrorAplicacion('Selecciona Asistio o No asistio.', 422); }
        return $this->ejecutarTransaccion(function () use ($usuario, $idCita, $asistencia): array {
            $cita = $this->exigirCitaAutorizada($usuario, $idCita, true)->obtenerDatosPublicosCita();
            if (!in_array($cita['estadoCita'], ['pendiente', 'confirmada'], true) || $cita['asistenciaRegistrada'] !== 'pendiente') {
                throw new ErrorAplicacion('La asistencia ya fue registrada o la cita ya no esta vigente.', 409);
            }
            $limite = new \DateTimeImmutable($asistencia === 'asistio' ? $cita['fechaHoraInicio'] : $cita['fechaHoraFin']);
            if ($this->obtenerAhora() < $limite) {
                throw new ErrorAplicacion($asistencia === 'asistio' ? 'Registra la asistencia desde el inicio del turno.' : 'Registra la inasistencia cuando termine el turno.', 409);
            }
            $estado = $asistencia === 'no_asistio' ? 'no_asistio' : $cita['estadoCita'];
            $this->citaRepository->registrarAsistencia($idCita, $asistencia, $estado, $usuario->idUsuario);
            return $this->obtenerCita($usuario, $idCita);
        });
    }

    private function exigirCitaAutorizada(Usuario $usuario, int $idCita, bool $bloquear = false): Cita
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.consultar');
        $cita = $this->citaRepository->obtenerCitaPorId($idCita, $bloquear)
            ?? throw new ErrorAplicacion('La cita no existe o no esta disponible para tu cuenta.', 404);
        if (($usuario->codigoRol === 'paciente' && !$cita->perteneceAlPaciente($usuario->idUsuario)) ||
            ($usuario->codigoRol === 'medico' && !$cita->perteneceAlMedico($usuario->idUsuario))) {
            throw new ErrorAplicacion('La cita no existe o no esta disponible para tu cuenta.', 404);
        }
        return $cita;
    }

    private function exigirCitaModificable(Cita $cita): void
    {
        $datos = $cita->obtenerDatosPublicosCita();
        if (!in_array($datos['estadoCita'], ['pendiente', 'confirmada'], true) ||
            $datos['asistenciaRegistrada'] !== 'pendiente' || new \DateTimeImmutable($datos['fechaHoraInicio']) <= $this->obtenerAhora()) {
            throw new ErrorAplicacion('Solo se pueden anular o reprogramar citas vigentes antes del inicio del turno.', 409);
        }
    }

    private function prepararReserva(int $idPaciente, int $idTurno, int $idCitaExcluida = 0): array
    {
        $turnoInicial = $this->horarioRepository->obtenerTurnoPorId($idTurno)
            ?? throw new ErrorAplicacion('El turno no existe.', 404);
        $medico = (new MedicoRepository($this->conexionPostgresql))->obtenerMedicoPorId((int) $turnoInicial['id_medico'], true)
            ?? throw new ErrorAplicacion('El medico no existe.', 404);
        $especialidad = (new EspecialidadRepository($this->conexionPostgresql))->obtenerEspecialidadPorId($medico->idEspecialidad, true);
        if (!$medico->medicoActivo || !$especialidad?->especialidadActiva) { throw new ErrorAplicacion('El medico o su especialidad no estan disponibles.', 409); }
        // Una misma fila de paciente protege tambien reservas simultaneas con distintos medicos.
        $paciente = $this->citaRepository->bloquearPaciente($idPaciente)
            ?? throw new ErrorAplicacion('El paciente no existe.', 404);
        if (!in_array($paciente['usuario_activo'], [true, 1, '1', 't', 'true'], true)) { throw new ErrorAplicacion('La cuenta del paciente esta inactiva.', 409); }
        $turno = $this->horarioRepository->obtenerTurnoPorId($idTurno, true)
            ?? throw new ErrorAplicacion('El turno no existe.', 404);
        $inicio = new \DateTimeImmutable($turno['fecha_hora_inicio']);
        $fin = new \DateTimeImmutable($turno['fecha_hora_fin']);
        $inicioBloque = new \DateTimeImmutable($turno['fecha_atencion'] . ' ' . $turno['hora_inicio']);
        $finBloque = new \DateTimeImmutable($turno['fecha_atencion'] . ' ' . $turno['hora_fin']);
        if (!in_array($turno['turno_habilitado'], [true, 1, '1', 't', 'true'], true) || $inicio <= $this->obtenerAhora() ||
            $inicio < $inicioBloque || $fin > $finBloque || $fin->getTimestamp() - $inicio->getTimestamp() !== 900) {
            throw new ErrorAplicacion('Ese turno ya no esta disponible. Elige otro horario.', 409);
        }
        if ($this->citaRepository->turnoEstaOcupado($idTurno)) { throw new ErrorAplicacion('Otra persona ya reservo ese turno. Elige otro horario.', 409); }
        if ($this->citaRepository->pacienteTieneCruce($idPaciente, $turno['fecha_hora_inicio'], $turno['fecha_hora_fin'], $idCitaExcluida)) {
            throw new ErrorAplicacion('El paciente ya tiene una cita que se cruza con ese horario.', 409);
        }
        return ['idEspecialidad' => $medico->idEspecialidad];
    }

    private function prepararDatosCita(Cita $cita, Usuario $usuario): array
    {
        $datos = $cita->obtenerDatosPublicosCita();
        $vigente = in_array($datos['estadoCita'], ['pendiente', 'confirmada'], true) && $datos['asistenciaRegistrada'] === 'pendiente';
        $inicio = new \DateTimeImmutable($datos['fechaHoraInicio']);
        $fin = new \DateTimeImmutable($datos['fechaHoraFin']);
        $ahora = $this->obtenerAhora();
        $datos['puedeCancelar'] = $vigente && $inicio > $ahora && PermisosSistema::tienePermiso($usuario->codigoRol, 'citas.cancelar');
        $datos['puedeReprogramar'] = $vigente && $inicio > $ahora && PermisosSistema::tienePermiso($usuario->codigoRol, 'citas.reprogramar');
        $datos['puedeRegistrarAsistencia'] = $vigente && $inicio <= $ahora && PermisosSistema::tienePermiso($usuario->codigoRol, 'citas.asistencia');
        $datos['puedeRegistrarInasistencia'] = $vigente && $fin <= $ahora && PermisosSistema::tienePermiso($usuario->codigoRol, 'citas.asistencia');
        return $datos;
    }

    private function ejecutarTransaccion(callable $operacion): array
    {
        $this->conexionPostgresql->beginTransaction();
        try {
            $resultado = $operacion();
            $this->conexionPostgresql->commit();
            return $resultado;
        } catch (\Throwable $error) {
            if ($this->conexionPostgresql->inTransaction()) { $this->conexionPostgresql->rollBack(); }
            if ($error instanceof \PDOException && in_array((string) $error->getCode(), ['23505', '40P01', '40001'], true)) {
                throw new ErrorAplicacion('La disponibilidad cambio durante la operacion. Actualiza los turnos e intenta otra vez.', 409);
            }
            throw $error;
        }
    }
}
