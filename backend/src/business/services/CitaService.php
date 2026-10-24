<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\CitaValidator;
use CitasMedicas\Business\Validators\ValidarCamposFormulario;
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Models\Cita;
use CitasMedicas\Data\Models\Medico;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\CitaRepository;
use CitasMedicas\Data\Repositories\EspecialidadRepository;
use CitasMedicas\Data\Repositories\MedicoRepository;
use CitasMedicas\Data\Repositories\PacienteRepository;
use CitasMedicas\Data\Repositories\TurnoRepository;
use CitasMedicas\Shared\Constants\PermisosSistema;
use CitasMedicas\Shared\Constants\RolesSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class CitaService
{
    public const MENSAJE_CITA_ACTIVA = 'Ya tienes una cita activa. Para reservar una nueva cita, primero debes completar o cancelar tu cita actual.';
    private readonly CitaRepository $citaRepository;
    private readonly TurnoRepository $turnoRepository;
    private readonly PacienteRepository $pacienteRepository;
    private readonly MedicoRepository $medicoRepository;
    private readonly EspecialidadRepository $especialidadRepository;
    private readonly Transaccion $transaccion;

    public function __construct(\PDO $conexionPostgresql, private readonly ?\DateTimeImmutable $momentoReferencia = null)
    {
        $this->citaRepository = new CitaRepository($conexionPostgresql);
        $this->turnoRepository = new TurnoRepository($conexionPostgresql);
        $this->pacienteRepository = new PacienteRepository($conexionPostgresql);
        $this->medicoRepository = new MedicoRepository($conexionPostgresql);
        $this->especialidadRepository = new EspecialidadRepository($conexionPostgresql);
        $this->transaccion = new Transaccion($conexionPostgresql);
    }

    public function listarCitas(Usuario $usuario, array $parametros): array
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.consultar');
        $filtros = CitaValidator::validarFiltrosCitas($parametros);
        $idUsuarioPaciente = $usuario->codigoRol === RolesSistema::PACIENTE ? $usuario->idUsuario : null;
        $idUsuarioMedico = $usuario->codigoRol === RolesSistema::MEDICO ? $usuario->idUsuario : null;
        $citas = $this->citaRepository->listarCitas(
            $filtros,
            $this->obtenerAhora()->format('Y-m-d H:i:s'),
            $idUsuarioPaciente,
            $idUsuarioMedico
        );
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
        $idTurno = ValidarCamposFormulario::validarIdentificador($datos, 'idTurno');
        $motivo = ValidarCamposFormulario::validarTexto($datos, 'motivoCita', 'El motivo', 500, false);
        if ($usuario->codigoRol === RolesSistema::PACIENTE) {
            $idPaciente = $this->pacienteRepository->obtenerIdPacientePorUsuario($usuario->idUsuario)
                ?? throw new ErrorAplicacion('Tu perfil de paciente no existe.', 404);
            if (array_key_exists('idPaciente', $datos) && ValidarCamposFormulario::validarIdentificador($datos, 'idPaciente') !== $idPaciente) {
                throw new ErrorAplicacion('Solo puedes reservar citas para tu propia cuenta.', 403);
            }
        } else {
            $idPaciente = ValidarCamposFormulario::validarIdentificador($datos, 'idPaciente');
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
        $motivo = ValidarCamposFormulario::validarTexto($datos, 'motivoCancelacion', 'El motivo de cancelacion', 300, $usuario->codigoRol === RolesSistema::MEDICO);
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
        $idTurno = ValidarCamposFormulario::validarIdentificador($datos, 'idTurno');
        return $this->ejecutarTransaccion(function () use ($usuario, $idCita, $idTurno): array {
            $cita = $this->exigirCitaAutorizada($usuario, $idCita, true);
            $this->exigirCitaModificable($cita);
            $datosCita = $cita->obtenerDatosPublicosCita();
            if ($datosCita['idTurno'] === $idTurno) {
                throw new ErrorAplicacion('Selecciona un turno diferente.', 422);
            }
            $turno = $this->prepararReserva($datosCita['idPaciente'], $idTurno, $idCita);
            // Los bloqueos del nuevo turno pueden haber esperado hasta despues del inicio anterior.
            $this->exigirCitaModificable($cita);
            $this->citaRepository->actualizarEstadoCita($idCita, 'reprogramada', $usuario->idUsuario);
            $idNuevaCita = $this->citaRepository->registrarCita(
                $datosCita['idPaciente'],
                $idTurno,
                $datosCita['motivoCita'],
                $usuario->idUsuario,
                $idCita,
                $turno['idEspecialidad']
            );
            return $this->obtenerCita($usuario, $idNuevaCita);
        });
    }

    public function registrarAsistencia(Usuario $usuario, int $idCita, array $datos): array
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.asistencia');
        $asistencia = CitaValidator::validarAsistencia($datos);
        return $this->guardarAsistenciaCita($usuario, $idCita, $asistencia);
    }

    public function marcarCitaAtendida(Usuario $usuario, int $idCita): array
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.atender');
        return $this->guardarAsistenciaCita($usuario, $idCita, 'asistio');
    }

    public function listarMedicosParaAgenda(Usuario $usuario): array
    {
        PermisosSistema::exigirPermiso($usuario, 'agenda.filtrar');
        $idUsuarioMedico = $usuario->codigoRol === RolesSistema::MEDICO ? $usuario->idUsuario : null;
        return $this->medicoRepository->listarMedicosParaAgenda($idUsuarioMedico);
    }

    public function marcarCitaInasistencia(Usuario $usuario, int $idCita): array
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.inasistencia');
        return $this->guardarAsistenciaCita($usuario, $idCita, 'no_asistio');
    }

    public function confirmarCita(Usuario $usuario, int $idCita): array
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.confirmar');
        return $this->ejecutarTransaccion(function () use ($usuario, $idCita): array {
            $cita = $this->exigirCitaAutorizada($usuario, $idCita, true);
            $this->exigirCitaModificable($cita);
            if ($cita->obtenerDatosPublicosCita()['estadoCita'] !== 'pendiente') {
                throw new ErrorAplicacion('Solo se puede confirmar una cita programada.', 409);
            }
            $this->citaRepository->actualizarEstadoCita($idCita, 'confirmada', $usuario->idUsuario);
            return $this->obtenerCita($usuario, $idCita);
        });
    }

    public function listarCitasActivasPaciente(Usuario $usuario, array $parametros): array
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.reservar');
        $idPaciente = $usuario->codigoRol === RolesSistema::PACIENTE
            ? ($this->pacienteRepository->obtenerIdPacientePorUsuario($usuario->idUsuario) ?? throw new ErrorAplicacion('Tu perfil no existe.', 404))
            : ValidarCamposFormulario::validarIdentificador($parametros, 'idPaciente');
        if ($usuario->codigoRol === RolesSistema::PACIENTE && isset($parametros['idPaciente']) && ValidarCamposFormulario::validarIdentificador($parametros, 'idPaciente') !== $idPaciente) {
            throw new ErrorAplicacion('Solo puedes consultar tus propias citas activas.', 403);
        }
        return array_map(fn (Cita $cita): array => $this->prepararDatosCita($cita, $usuario), $this->citaRepository->listarCitasActivasPaciente($idPaciente));
    }

    private function obtenerAhora(): \DateTimeImmutable
    {
        return $this->momentoReferencia ?? new \DateTimeImmutable();
    }

    private function guardarAsistenciaCita(Usuario $usuario, int $idCita, string $asistencia): array
    {
        return $this->ejecutarTransaccion(function () use ($usuario, $idCita, $asistencia): array {
            $cita = $this->exigirCitaAutorizada($usuario, $idCita, true)->obtenerDatosPublicosCita();
            if (!in_array($cita['estadoCita'], ['pendiente', 'confirmada'], true) || $cita['asistenciaRegistrada'] !== 'pendiente') {
                throw new ErrorAplicacion('La asistencia ya fue registrada o la cita ya no esta vigente.', 409);
            }
            $limite = new \DateTimeImmutable($asistencia === 'asistio' ? $cita['fechaHoraInicio'] : $cita['fechaHoraFin']);
            if ($this->obtenerAhora() < $limite) {
                throw new ErrorAplicacion($asistencia === 'asistio' ? 'Registra la asistencia desde el inicio del turno.' : 'Registra la inasistencia cuando termine el turno.', 409);
            }
            $estado = $asistencia === 'no_asistio' ? 'no_asistio' : 'atendida';
            $this->citaRepository->registrarAsistencia($idCita, $asistencia, $estado, $usuario->idUsuario);
            return $this->obtenerCita($usuario, $idCita);
        });
    }

    private function exigirCitaAutorizada(Usuario $usuario, int $idCita, bool $bloquear = false): Cita
    {
        PermisosSistema::exigirPermiso($usuario, 'citas.consultar');
        $cita = $this->citaRepository->obtenerCitaPorId($idCita, $bloquear)
            ?? throw new ErrorAplicacion('La cita no existe o no esta disponible para tu cuenta.', 404);
        if (
            ($usuario->codigoRol === RolesSistema::PACIENTE && !$cita->perteneceAlPaciente($usuario->idUsuario)) ||
            ($usuario->codigoRol === RolesSistema::MEDICO && !$cita->perteneceAlMedico($usuario->idUsuario))
        ) {
            throw new ErrorAplicacion('La cita no existe o no esta disponible para tu cuenta.', 404);
        }
        return $cita;
    }

    private function exigirCitaModificable(Cita $cita): void
    {
        $datos = $cita->obtenerDatosPublicosCita();
        if (
            !in_array($datos['estadoCita'], ['pendiente', 'confirmada'], true) ||
            $datos['asistenciaRegistrada'] !== 'pendiente' || new \DateTimeImmutable($datos['fechaHoraInicio']) <= $this->obtenerAhora()
        ) {
            throw new ErrorAplicacion('Solo se pueden cancelar o reprogramar citas vigentes antes del inicio del turno.', 409);
        }
    }

    private function prepararReserva(int $idPaciente, int $idTurno, int $idCitaExcluida = 0): array
    {
        $turnoInicial = $this->turnoRepository->obtenerTurnoPorId($idTurno)
            ?? throw new ErrorAplicacion('El turno no existe.', 404);
        $medico = $this->bloquearMedicoDisponible((int) $turnoInicial['id_medico']);
        $this->exigirPacienteDisponible($idPaciente, $idCitaExcluida);
        $turno = $this->turnoRepository->obtenerTurnoPorId($idTurno, true)
            ?? throw new ErrorAplicacion('El turno no existe.', 404);
        $this->exigirTurnoVigente($turno);
        $this->exigirReservaSinCruces($idPaciente, $medico->idMedico, $idTurno, $turno, $idCitaExcluida);
        return ['idEspecialidad' => $medico->idEspecialidad];
    }

    private function bloquearMedicoDisponible(int $idMedico): Medico
    {
        $medico = $this->medicoRepository->obtenerMedicoPorId($idMedico, true)
            ?? throw new ErrorAplicacion('El medico no existe.', 404);
        $especialidad = $this->especialidadRepository->obtenerEspecialidadPorId($medico->idEspecialidad, true);
        if (!$medico->medicoActivo || !$especialidad?->especialidadActiva) {
            throw new ErrorAplicacion('El medico o su especialidad no estan disponibles.', 409);
        }
        return $medico;
    }

    private function exigirPacienteDisponible(int $idPaciente, int $idCitaExcluida): void
    {
        // Una misma fila de paciente protege tambien reservas simultaneas con distintos medicos.
        $paciente = $this->pacienteRepository->bloquearPaciente($idPaciente)
            ?? throw new ErrorAplicacion('El paciente no existe.', 404);
        if (!in_array($paciente['usuario_activo'], [true, 1, '1', 't', 'true'], true)) {
            throw new ErrorAplicacion('La cuenta del paciente esta inactiva.', 409);
        }
        if ($this->citaRepository->pacienteTieneCitaActiva($idPaciente, $idCitaExcluida)) {
            throw new ErrorAplicacion(self::MENSAJE_CITA_ACTIVA, 409);
        }
    }

    private function exigirTurnoVigente(array $turno): void
    {
        $inicio = new \DateTimeImmutable($turno['fecha_hora_inicio']);
        $fin = new \DateTimeImmutable($turno['fecha_hora_fin']);
        $inicioBloque = new \DateTimeImmutable($turno['fecha_atencion'] . ' ' . $turno['hora_inicio']);
        $finBloque = new \DateTimeImmutable($turno['fecha_atencion'] . ' ' . $turno['hora_fin']);
        if (
            !in_array($turno['turno_habilitado'], [true, 1, '1', 't', 'true'], true) || $inicio <= $this->obtenerAhora() ||
            $inicio < $inicioBloque || $fin > $finBloque ||
            $fin->getTimestamp() - $inicio->getTimestamp() !== (int) $turno['duracion_turno_minutos'] * 60 ||
            (int) $turno['duracion_turno_minutos'] !== (int) $turno['duracion_cita_minutos']
        ) {
            throw new ErrorAplicacion('Ese turno ya no esta disponible. Elige otro horario.', 409);
        }
    }

    private function exigirReservaSinCruces(int $idPaciente, int $idMedico, int $idTurno, array $turno, int $idCitaExcluida): void
    {
        if ($this->citaRepository->medicoTieneCruce($idMedico, $turno['fecha_hora_inicio'], $turno['fecha_hora_fin'], $idCitaExcluida)) {
            throw new ErrorAplicacion('El medico ya tiene una cita que se cruza.', 409);
        }
        if ($this->citaRepository->turnoEstaOcupado($idTurno)) {
            throw new ErrorAplicacion('Otra persona ya reservo ese turno. Elige otro horario.', 409);
        }
        if ($this->citaRepository->pacienteTieneCruce($idPaciente, $turno['fecha_hora_inicio'], $turno['fecha_hora_fin'], $idCitaExcluida)) {
            throw new ErrorAplicacion('El paciente ya tiene una cita que se cruza con ese horario.', 409);
        }
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
        $datos['puedeMarcarAtendida'] = $vigente && $inicio <= $ahora && PermisosSistema::tienePermiso($usuario->codigoRol, 'citas.atender');
        $datos['puedeRegistrarAsistencia'] = $vigente && $inicio <= $ahora && PermisosSistema::tienePermiso($usuario->codigoRol, 'citas.asistencia');
        $datos['puedeRegistrarInasistencia'] = $vigente && $fin <= $ahora && PermisosSistema::tienePermiso($usuario->codigoRol, 'citas.inasistencia');
        $datos['puedeConfirmar'] = $vigente && $datos['estadoCita'] === 'pendiente' && $inicio > $ahora && PermisosSistema::tienePermiso($usuario->codigoRol, 'citas.confirmar');
        $datos['requiereMotivoCancelacion'] = $usuario->codigoRol === RolesSistema::MEDICO;
        $datos['explicacionAcciones'] = !$vigente ? 'Esta cita es final y conserva su historial.'
            : ($inicio > $ahora ? 'Puedes cancelar antes del inicio. La atencion se registra desde el inicio del turno y la inasistencia desde su final.'
                : ($fin > $ahora ? 'El turno ya comenzo. La inasistencia se habilita al terminar; ya no se puede cancelar.'
                    : 'El turno termino. Un usuario autorizado debe registrar el resultado; no se cambia automaticamente.'));
        return $datos;
    }

    private function ejecutarTransaccion(callable $operacion): array
    {
        try {
            return $this->transaccion->ejecutar($operacion, Transaccion::LECTURA_CONFIRMADA);
        } catch (\PDOException $error) {
            if ((string) $error->getCode() === 'P2001' || str_contains($error->getMessage(), 'uq_citas_paciente_activa')) {
                throw new ErrorAplicacion(self::MENSAJE_CITA_ACTIVA, 409);
            }
            if ((string) $error->getCode() === 'P2002') {
                throw new ErrorAplicacion('La transicion de estado no esta permitida.', 409);
            }
            if (in_array((string) $error->getCode(), ['23505', '23P01', '40P01', '40001'], true)) {
                throw new ErrorAplicacion('La disponibilidad cambio durante la operacion. Actualiza los turnos e intenta otra vez.', 409);
            }
            throw $error;
        }
    }
}
