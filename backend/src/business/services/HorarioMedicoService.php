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
}
