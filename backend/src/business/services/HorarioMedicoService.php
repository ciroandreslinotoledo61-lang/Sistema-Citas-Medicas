<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\HorarioMedicoValidator;
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Models\HorarioMedico;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\EspecialidadRepository;
use CitasMedicas\Data\Repositories\HorarioMedicoRepository;
use CitasMedicas\Data\Repositories\MedicoRepository;
use CitasMedicas\Data\Repositories\TurnoRepository;
use CitasMedicas\Shared\Constants\PermisosSistema;
use CitasMedicas\Shared\Constants\RolesSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class HorarioMedicoService
{
    private readonly HorarioMedicoRepository $horarioRepository;
    private readonly MedicoRepository $medicoRepository;
    private readonly EspecialidadRepository $especialidadRepository;
    private readonly TurnoRepository $turnoRepository;
    private readonly Transaccion $transaccion;
    private readonly DisponibilidadService $disponibilidadService;

    public function __construct(\PDO $conexionPostgresql, private readonly ?\DateTimeImmutable $momentoReferencia = null)
    {
        $this->horarioRepository = new HorarioMedicoRepository($conexionPostgresql);
        $this->medicoRepository = new MedicoRepository($conexionPostgresql);
        $this->especialidadRepository = new EspecialidadRepository($conexionPostgresql);
        $this->turnoRepository = new TurnoRepository($conexionPostgresql);
        $this->transaccion = new Transaccion($conexionPostgresql);
        $this->disponibilidadService = new DisponibilidadService($conexionPostgresql, $momentoReferencia);
    }

    public function listarHorarios(Usuario $usuario, array $parametros): array
    {
        PermisosSistema::exigirPermiso($usuario, 'horarios.consultar');
        $filtros = HorarioMedicoValidator::validarFiltrosHorarios($parametros);
        $idUsuarioMedico = $usuario->codigoRol === RolesSistema::MEDICO ? $usuario->idUsuario : null;
        $horarios = $this->horarioRepository->listarHorarios($filtros, $idUsuarioMedico);
        return [
            'elementos' => array_map(static fn (HorarioMedico $horario): array => $horario->obtenerDatosPublicosHorario(), array_slice($horarios, 0, 20)),
            'pagina' => $filtros['pagina'],
            'hayPaginaSiguiente' => count($horarios) > 20,
        ];
    }

    public function registrarHorario(Usuario $usuario, array $datos): array
    {
        PermisosSistema::exigirPermiso($usuario, 'horarios.crear');
        $datos = HorarioMedicoValidator::validarHorario($datos, $this->obtenerAhora());
        try {
            return $this->transaccion->ejecutar(function () use ($datos): array {
                $this->bloquearMedicoDisponible($datos['idMedico']);
                if ($this->horarioRepository->existeCruceHorario($datos)) {
                    throw new ErrorAplicacion('El medico ya tiene un bloque que se cruza con ese horario.', 409);
                }
                // El bloqueo puede haber esperado: se comprueba de nuevo la hora actual.
                HorarioMedicoValidator::validarHorario($datos, $this->obtenerAhora());
                return $this->crearHorarioConTurnos($datos);
            }, Transaccion::LECTURA_CONFIRMADA);
        } catch (\PDOException $error) {
            if (in_array((string) $error->getCode(), ['23505', '40P01'], true)) {
                throw new ErrorAplicacion('El horario cambio durante la operacion. Actualiza la lista e intenta otra vez.', 409);
            }
            throw $error;
        }
    }

    public function programarHorariosPorPeriodo(Usuario $usuario, array $datos): array
    {
        PermisosSistema::exigirPermiso($usuario, 'horarios.crear');
        $datos = HorarioMedicoValidator::validarProgramacionPeriodo($datos, $this->obtenerAhora());
        try {
            return $this->transaccion->ejecutar(function () use ($datos): array {
                $this->bloquearMedicoDisponible($datos['idMedico']);
                return $this->procesarPeriodo($datos);
            }, Transaccion::LECTURA_CONFIRMADA);
        } catch (\PDOException $error) {
            if (in_array((string) $error->getCode(), ['23505', '40P01', '40001'], true)) {
                throw new ErrorAplicacion('Otro proceso modifico la agenda. No se guardo este lote; vuelve a generarlo.', 409);
            }
            throw $error;
        }
    }

    /** Conserva la API PHP anterior; las rutas usan DisponibilidadService directamente. */
    public function listarMedicosDisponibles(Usuario $usuario): array
    {
        return $this->disponibilidadService->listarMedicosDisponibles($usuario);
    }

    public function listarTurnosDisponibles(Usuario $usuario, array $parametros): array
    {
        return $this->disponibilidadService->listarTurnosDisponibles($usuario, $parametros);
    }

    private function obtenerAhora(): \DateTimeImmutable
    {
        return $this->momentoReferencia ?? new \DateTimeImmutable();
    }

    private function bloquearMedicoDisponible(int $idMedico): void
    {
        // El mismo bloqueo serializa programacion, reservas y cambios del medico.
        $medico = $this->medicoRepository->obtenerMedicoPorId($idMedico, true)
            ?? throw new ErrorAplicacion('El medico no existe.', 404);
        $especialidad = $this->especialidadRepository->obtenerEspecialidadPorId($medico->idEspecialidad, true);
        if (!$medico->medicoActivo || !$especialidad?->especialidadActiva) {
            throw new ErrorAplicacion('El medico y su especialidad deben estar activos.', 409);
        }
    }

    private function crearHorarioConTurnos(array $datos): array
    {
        $idHorario = $this->horarioRepository->registrarHorario($datos);
        $turnos = $this->generarIntervalosTurnos($datos);
        $this->turnoRepository->registrarTurnos($idHorario, $turnos);
        return ['idHorarioMedico' => $idHorario, 'cantidadTurnos' => count($turnos)] + $datos;
    }

    private function generarIntervalosTurnos(array $datos): array
    {
        $inicio = new \DateTimeImmutable($datos['fechaAtencion'] . ' ' . $datos['horaInicio']);
        $fin = new \DateTimeImmutable($datos['fechaAtencion'] . ' ' . $datos['horaFin']);
        $duracion = $datos['duracionCitaMinutos'];
        $turnos = [];
        for ($momento = $inicio; $momento < $fin; $momento = $momento->modify('+' . $duracion . ' minutes')) {
            $turnos[] = [
                'duracion' => $duracion,
                'inicio' => $momento->format('Y-m-d H:i:s'),
                'fin' => $momento->modify('+' . $duracion . ' minutes')->format('Y-m-d H:i:s'),
            ];
        }
        return $turnos;
    }

    private function procesarPeriodo(array $datos): array
    {
        $resumen = [
            'fechasProcesadas' => 0, 'fechasCreadas' => 0, 'turnosCreados' => 0,
            'fechasExistentes' => 0, 'fechasPasadas' => 0, 'conflictosDetectados' => 0,
            'diasNoSeleccionados' => 0, 'detalle' => [],
        ];
        $bloque = array_intersect_key($datos, array_flip(['idMedico', 'horaInicio', 'horaFin', 'duracionCitaMinutos']));
        for (
            $fecha = new \DateTimeImmutable($datos['fechaInicial']);
            $fecha->format('Y-m-d') <= $datos['fechaFinal']; $fecha = $fecha->modify('+1 day')
        ) {
            if (!in_array((int) $fecha->format('N'), $datos['diasSemana'], true)) {
                $resumen['diasNoSeleccionados']++;
                continue;
            }
            $resumen['fechasProcesadas']++;
            $bloque['fechaAtencion'] = $fecha->format('Y-m-d');
            $detalle = $this->procesarFecha($bloque);
            $contador = match ($detalle['estado']) {
                'omitida' => 'fechasPasadas',
                'existente' => 'fechasExistentes',
                'conflicto' => 'conflictosDetectados',
                'creada' => 'fechasCreadas',
            };
            $resumen[$contador]++;
            $resumen['turnosCreados'] += $detalle['turnosCreados'];
            $resumen['detalle'][] = $detalle;
        }
        return $resumen;
    }

    private function procesarFecha(array $bloque): array
    {
        $detalle = ['fecha' => $bloque['fechaAtencion'], 'turnosCreados' => 0];
        if (new \DateTimeImmutable($bloque['fechaAtencion'] . ' ' . $bloque['horaInicio']) <= $this->obtenerAhora()) {
            return $detalle + ['estado' => 'omitida', 'mensaje' => 'El inicio ya paso; no se genero el bloque.'];
        }
        if ($this->horarioRepository->existeHorarioEquivalente($bloque)) {
            return $detalle + ['estado' => 'existente', 'mensaje' => 'El bloque ya existe. Se conservaron sus turnos y citas.'];
        }
        if ($this->horarioRepository->existeCruceHorario($bloque)) {
            return $detalle + ['estado' => 'conflicto', 'mensaje' => 'Se cruza con otro bloque; se conservo el horario anterior.'];
        }
        $creado = $this->crearHorarioConTurnos($bloque);
        $detalle['turnosCreados'] = $creado['cantidadTurnos'];
        return $detalle + [
            'estado' => 'creada', 'mensaje' => 'Turnos consecutivos generados.',
            'idHorarioMedico' => $creado['idHorarioMedico'],
        ];
    }
}
