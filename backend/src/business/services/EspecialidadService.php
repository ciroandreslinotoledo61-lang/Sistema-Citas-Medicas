<?php

declare(strict_types=1);

namespace CitasMedicas\Business\Services;

use CitasMedicas\Business\Validators\EspecialidadValidator;
use CitasMedicas\Business\Validators\ValidarCamposFormulario;
use CitasMedicas\Data\Config\Transaccion;
use CitasMedicas\Data\Models\Especialidad;
use CitasMedicas\Data\Repositories\EspecialidadRepository;
use CitasMedicas\Shared\Errors\ErrorAplicacion;

final class EspecialidadService
{
    private readonly EspecialidadRepository $especialidadRepository;

    private readonly Transaccion $transaccion;

    public function __construct(\PDO $conexionPostgresql)
    {
        $this->transaccion = new Transaccion($conexionPostgresql);
        $this->especialidadRepository = new EspecialidadRepository($conexionPostgresql);
    }

    public function listarEspecialidades(array $parametros): array
    {
        $filtros = ValidarCamposFormulario::validarParametrosBusqueda($parametros);
        $especialidades = $this->especialidadRepository->listarEspecialidadesPorBusqueda($filtros['busqueda'], $filtros['pagina']);
        return [
            'elementos' => array_map(static fn(Especialidad $especialidad): array => $especialidad->obtenerDatosPublicosEspecialidad(), array_slice($especialidades, 0, 20)),
            'pagina' => $filtros['pagina'], 'hayPaginaSiguiente' => count($especialidades) > 20,
        ];
    }

    public function listarEspecialidadesActivas(): array
    {
        return array_map(
            static fn(Especialidad $especialidad): array => $especialidad->obtenerDatosPublicosEspecialidad(),
            $this->especialidadRepository->listarEspecialidadesActivas()
        );
    }

    public function obtenerEspecialidadPorId(int $idEspecialidad): Especialidad
    {
        return $this->especialidadRepository->obtenerEspecialidadPorId($idEspecialidad)
            ?? throw new ErrorAplicacion('La especialidad no existe.', 404);
    }

    public function registrarEspecialidad(array $datos): Especialidad
    {
        $datosValidados = EspecialidadValidator::validarDatosEspecialidad($datos);
        $this->exigirNombreEspecialidadDisponible($datosValidados['nombreEspecialidad']);
        try {
            $idEspecialidad = $this->especialidadRepository->registrarEspecialidad($datosValidados);
            return $this->obtenerEspecialidadPorId($idEspecialidad);
        } catch (\PDOException $error) {
            if ((string) $error->getCode() === '23505') {
                throw new ErrorAplicacion('Ya existe una especialidad con ese nombre.', 409);
            }
            throw $error;
        }
    }

    public function actualizarDatosEspecialidad(int $idEspecialidad, array $datos): Especialidad
    {
        $this->obtenerEspecialidadPorId($idEspecialidad);
        $datosValidados = EspecialidadValidator::validarDatosEspecialidad($datos);
        $this->exigirNombreEspecialidadDisponible($datosValidados['nombreEspecialidad'], $idEspecialidad);
        try {
            $this->especialidadRepository->actualizarDatosEspecialidad($idEspecialidad, $datosValidados);
            return $this->obtenerEspecialidadPorId($idEspecialidad);
        } catch (\PDOException $error) {
            if ((string) $error->getCode() === '23505') {
                throw new ErrorAplicacion('Ya existe una especialidad con ese nombre.', 409);
            }
            throw $error;
        }
    }

    public function cambiarEstadoEspecialidad(int $idEspecialidad, array $datos): Especialidad
    {
        $especialidadActiva = EspecialidadValidator::validarEstadoEspecialidad($datos);
        return $this->transaccion->ejecutar(function () use ($idEspecialidad, $especialidadActiva): Especialidad {
        // Crear, reasignar o activar medicos bloquea esta misma especialidad.
            $especialidad = $this->especialidadRepository->obtenerEspecialidadPorId($idEspecialidad, true)
            ?? throw new ErrorAplicacion('La especialidad no existe.', 404);
            if (!$especialidadActiva && $this->especialidadRepository->contarMedicosActivosPorEspecialidad($idEspecialidad) > 0) {
                throw new ErrorAplicacion('Primero desactiva o reasigna los medicos activos de esta especialidad.', 409);
            }
            if ($especialidad->especialidadActiva !== $especialidadActiva) {
                $this->especialidadRepository->cambiarEstadoEspecialidad($idEspecialidad, $especialidadActiva);
            }
            $especialidadActualizada = $this->obtenerEspecialidadPorId($idEspecialidad);

            return $especialidadActualizada;
        });
    }

    private function exigirNombreEspecialidadDisponible(string $nombreEspecialidad, int $idEspecialidadExcluida = 0): void
    {
        if ($this->especialidadRepository->existeEspecialidadConNombre($nombreEspecialidad, $idEspecialidadExcluida)) {
            throw new ErrorAplicacion('Ya existe una especialidad con ese nombre.', 409, [
                'nombreEspecialidad' => 'El nombre ya esta registrado. Revisa tambien las especialidades inactivas.',
            ]);
        }
    }
}
