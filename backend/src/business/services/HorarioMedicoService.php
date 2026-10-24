<?php
declare(strict_types=1);
namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\AgendaValidator;
use CitasMedicas\Business\Validators\ValidarCamposFormulario;
use CitasMedicas\Data\Models\HorarioMedico;
use CitasMedicas\Data\Models\Usuario;
use CitasMedicas\Data\Repositories\HorarioMedicoRepository;
use CitasMedicas\Data\Repositories\MedicoRepository;
use CitasMedicas\Data\Repositories\EspecialidadRepository;
use CitasMedicas\Shared\Constants\PermisosSistema;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class HorarioMedicoService
{
    private readonly HorarioMedicoRepository $horarioRepository;
    public function __construct(private readonly \PDO $conexionPostgresql, private readonly ?\DateTimeImmutable $momentoReferencia = null)
    {
        $this->horarioRepository = new HorarioMedicoRepository($conexionPostgresql);
    }
    private function obtenerAhora(): \DateTimeImmutable { return $this->momentoReferencia ?? new \DateTimeImmutable(); }

    public function listarMedicosDisponibles(Usuario $usuario): array
    {
        PermisosSistema::exigirPermiso($usuario, 'disponibilidad.consultar');
        return $this->horarioRepository->listarMedicosDisponibles();
    }

    public function listarTurnosDisponibles(Usuario $usuario, array $parametros): array
    {
        PermisosSistema::exigirPermiso($usuario, 'disponibilidad.consultar');
        $idMedico = AgendaValidator::validarIdentificador($parametros, 'idMedico');
        $fecha = AgendaValidator::validarFecha($parametros['fecha'] ?? null);
        return $this->horarioRepository->listarTurnosDisponibles($idMedico, $fecha, $this->obtenerAhora()->format('Y-m-d H:i:s'));
    }

    public function listarHorarios(Usuario $usuario, array $parametros): array
    {
        PermisosSistema::exigirPermiso($usuario, 'horarios.consultar');
        $filtros = ValidarCamposFormulario::validarParametrosBusqueda($parametros);
        $filtros['fecha'] = AgendaValidator::validarFecha($parametros['fecha'] ?? '', true);
        $filtros['idMedico'] = isset($parametros['idMedico']) && $parametros['idMedico'] !== ''
            ? AgendaValidator::validarIdentificador($parametros, 'idMedico') : null;
        $horarios = $this->horarioRepository->listarHorarios($usuario, $filtros);
        return ['elementos' => array_map(static fn (HorarioMedico $horario): array => $horario->obtenerDatosPublicosHorario(), array_slice($horarios, 0, 20)),
            'pagina' => $filtros['pagina'], 'hayPaginaSiguiente' => count($horarios) > 20];
    }

    public function registrarHorario(Usuario $usuario, array $datos): array
    {
        PermisosSistema::exigirPermiso($usuario, 'horarios.crear');
        $datos = AgendaValidator::validarHorario($datos, $this->obtenerAhora());
        $this->conexionPostgresql->beginTransaction();
        try {
            $this->conexionPostgresql->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            // Bloquear el medico serializa la creacion de sus bloques y sus reservas.
            $medico = (new MedicoRepository($this->conexionPostgresql))->obtenerMedicoPorId($datos['idMedico'], true)
                ?? throw new ErrorAplicacion('El medico no existe.', 404);
            $especialidad = (new EspecialidadRepository($this->conexionPostgresql))->obtenerEspecialidadPorId($medico->idEspecialidad, true);
            if (!$medico->medicoActivo || !$especialidad?->especialidadActiva) {
                throw new ErrorAplicacion('El medico y su especialidad deben estar activos.', 409);
            }
            if ($this->horarioRepository->existeCruceHorario($datos)) {
                throw new ErrorAplicacion('El medico ya tiene un bloque que se cruza con ese horario.', 409);
            }
            AgendaValidator::validarHorario($datos, $this->obtenerAhora());
            $resultado = $this->horarioRepository->registrarHorarioConTurnos($datos);
            $this->conexionPostgresql->commit();
            return $resultado;
        } catch (\Throwable $error) {
            if ($this->conexionPostgresql->inTransaction()) { $this->conexionPostgresql->rollBack(); }
            if ($error instanceof \PDOException && in_array((string) $error->getCode(), ['23505', '40P01'], true)) {
                throw new ErrorAplicacion('El horario cambio durante la operacion. Actualiza la lista e intenta otra vez.', 409);
            }
            throw $error;
        }
    }
    public function programarHorariosPorPeriodo(Usuario $usuario, array $datos): array
    {
        PermisosSistema::exigirPermiso($usuario, 'horarios.crear');
        $datos = AgendaValidator::validarProgramacionPeriodo($datos, $this->obtenerAhora());
        $resumen = ['fechasProcesadas' => 0, 'fechasCreadas' => 0, 'turnosCreados' => 0,
            'fechasExistentes' => 0, 'fechasPasadas' => 0, 'conflictosDetectados' => 0,
            'diasNoSeleccionados' => 0, 'detalle' => []];
        $this->conexionPostgresql->beginTransaction();
        try {
            $this->conexionPostgresql->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $medico = (new MedicoRepository($this->conexionPostgresql))->obtenerMedicoPorId($datos['idMedico'], true)
                ?? throw new ErrorAplicacion('El medico no existe.', 404);
            $especialidad = (new EspecialidadRepository($this->conexionPostgresql))->obtenerEspecialidadPorId($medico->idEspecialidad, true);
            if (!$medico->medicoActivo || !$especialidad?->especialidadActiva) {
                throw new ErrorAplicacion('El medico y su especialidad deben estar activos.', 409);
            }
            $bloque = array_intersect_key($datos, array_flip(['idMedico', 'horaInicio', 'horaFin', 'duracionCitaMinutos']));
            for ($fecha = new \DateTimeImmutable($datos['fechaInicial']);
                $fecha->format('Y-m-d') <= $datos['fechaFinal']; $fecha = $fecha->modify('+1 day')) {
                if (!in_array((int) $fecha->format('N'), $datos['diasSemana'], true)) { $resumen['diasNoSeleccionados']++; continue; }
                $resumen['fechasProcesadas']++;
                $bloque['fechaAtencion'] = $fecha->format('Y-m-d');
                $detalle = ['fecha' => $bloque['fechaAtencion'], 'turnosCreados' => 0];
                if (new \DateTimeImmutable($bloque['fechaAtencion'] . ' ' . $bloque['horaInicio']) <= $this->obtenerAhora()) {
                    $resumen['fechasPasadas']++;
                    $detalle += ['estado' => 'omitida', 'mensaje' => 'El inicio ya paso; no se genero el bloque.'];
                } elseif ($this->horarioRepository->existeHorarioEquivalente($bloque)) {
                    $resumen['fechasExistentes']++;
                    $detalle += ['estado' => 'existente', 'mensaje' => 'El bloque ya existe. Se conservaron sus turnos y citas.'];
                } elseif ($this->horarioRepository->existeCruceHorario($bloque)) {
                    $resumen['conflictosDetectados']++;
                    $detalle += ['estado' => 'conflicto', 'mensaje' => 'Se cruza con otro bloque; se conservo el horario anterior.'];
                } else {
                    $creado = $this->horarioRepository->registrarHorarioConTurnos($bloque);
                    $resumen['fechasCreadas']++;
                    $resumen['turnosCreados'] += $creado['cantidadTurnos'];
                    $detalle['turnosCreados'] = $creado['cantidadTurnos'];
                    $detalle += ['estado' => 'creada', 'mensaje' => 'Turnos consecutivos generados.', 'idHorarioMedico' => $creado['idHorarioMedico']];
                }
                $resumen['detalle'][] = $detalle;
            }
            $this->conexionPostgresql->commit();
            return $resumen;
        } catch (\Throwable $error) {
            if ($this->conexionPostgresql->inTransaction()) { $this->conexionPostgresql->rollBack(); }
            if ($error instanceof \PDOException && in_array((string) $error->getCode(), ['23505', '40P01', '40001'], true)) {
                throw new ErrorAplicacion('Otro proceso modifico la agenda. No se guardo este lote; vuelve a generarlo.', 409);
            }
            throw $error;
        }
    }

}
